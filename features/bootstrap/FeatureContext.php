<?php


require 'qwik.php';

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

require 'phpunit.phar';
use PHPUnit\Framework\TestCase as Assert;


/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    
	private $Hrs6amto8pm =  2097088;
	private $Hrs24       = 33554431;

    private $authURL;
	private $email;
	private $game;
	private $parity;
	private $pid;
	private $player;
	private $req = [];     // a post or get request
	private $time;
	private $token;
	private $svid;
	private $venue;
	private $vid;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }
    
    
    /**
     * Converts a Short Venue ID $svid to a unique Venue ID $vid if possible
     */    
    private function locateVenue($svid, $game = '')
    {        
    	$vids = matchShortVenueID($svid, $game);
    	Assert::assertCount(1, $vids, "Please specify an existing Venue exactly");
    	return $vids[0];
    }
    
    

//     * @Given my email \b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b is not registered with
     
    /**
     * @Given my email :address is not registered with qwikgame
     */
    public function myEmailIsNotRegisteredWithQwikgame($address)
    {
    	$this->pid = anonID($address);
        removePlayer($this->pid);
    }


    /**
     * @Given my email :address is registered with qwikgame
     */
    public function myEmailIsRegisteredWithQwikgame($address)
    {
    	$this->pid = anonID($address);    	
        removePlayer($this->pid);
   	    $this->player = newPlayer($this->pid);
		$this->player->addChild('email', $address);
    	writePlayerXML($this->player);
    }


    /**
     * @When I like to play :game at :venue
     */
    public function iLikeToPlaySquashAtMilawa($game, $svid)
    {
    	$this->game = $game;
    	$this->svid = $svid;
    	$this->vid = $this->locateVenue($svid, $game);
    	$this->venue = readVenueXML($this->vid);
    	
    	$this->req['qwik'] = 'available';
    	$this->req['parity'] = 'all';
    	$this->req['game'] = $game;
    	$this->req['vid'] = $this->vid;
    }
    

    /**
     * @When I do not specify a time
     */
    public function iDoNotSpecifyATime()
    {   
    	$this->req['smtwtfs'] = "16777215";
    }
    
    
    /**
     * @When I like to play on :day at :hour
     */
    public function iLikeToPlayOnSaturdayAt3pm($day, $hour)
    {    
    	$day = date("D", strtotime($day));        // convert Saturday to Sat.    	
    	$hour = date("H", strtotime($hour));   // convert 12hr to 24hr format    	
    	
    	if (isset($this->req[$day])) {
    	    $this->req[$day] = $this->req[$day] | hour2bit($hour);
        } else {
        	$this->req[$day] = hour2bit($hour);
        }
    }
    

    /**
     * @When I provide my email :address
     */
    public function iProvideMyEmailAddress($address)
    {
    	$this->email = $address;
    	
		if(!$this->player){
 		    $this->pid = anonID($address);
			$this->player = newPlayer($this->pid);			
        }
        global $MONTH;
        $this->token = newPlayerToken($this->player, $MONTH);
	    $this->authURL = authURL($address, $this->token);
    }
    
    
    

    /**
     * @When I click Submit
     */
    public function iClickSubmit()
    {
        $qwik = $this->req['qwik'];
        switch ($qwik) {
            case "available":
                qwikAvailable($this->player, $this->req, $this->venue);
			    break;
    		case 'delete':
           	    qwikDelete($this->player, $this->req);
          		break;
            default:
                Assert::assertTrue(FALSE, "qwik $qwik is not implemented for this feature.");
	    }
    }
    

    /**
     * @When I click on the link in the confirmation email
     */
    public function iClickOnTheLinkInTheConfirmationEmail()
    {    	
        Assert::assertNotNull($this->authURL);
        
    	$query = parse_url($this->authURL, PHP_URL_QUERY);    	
    	Assert::assertNotNull($query);
    	
    	parse_str($query, $req);    	
    	Assert::assertNotNull($req);
    	
    	qwikAccount($this->player, $req);
        Assert::assertTrue(
            validPlayerToken($this->player, $req['token']),
            "The player token is invalid"
        );
    }
    
    
    

    /**
     * @Then my email :address will be registered with qwikgame
     */
    public function myEmailWillBeRegisteredWithQwikgame($address)
    {
    	Assert::assertEquals($this->email, $address);
        Assert::assertNotNull(readPlayerXML($this->pid));
    }
    
    
    /**
     * Confirms that $this->player is Available to play at ALL of the $hours
     *
     * @param Bitfield $hours The hours to chesk for availability
     * @param String $game The game to check availability for
     * @param XML $venue The Venue to check availability at
     * @param DateTime $date The date to check availability on
     * @param String $parity The parity of the rival
     *
     * @return Boolean TRUE if $this->player is available at ALL of the $hours
     */
    private function isAvailable(
         $hours,
         $game, 
         $venue, 
         $date, 
         $parity = 'any')
    {
	    // create a dummy rival
        $rival = newPlayer(anonID("rival@qwikgame.org"));
        
        // Set this player as a familiar of the rival, with matching parity
        // qwikFamiliar($rival, array('game'=>$game, 'rival'=>$this->email, 'parity'=>0)); 
        
        // The rival is keen for a match
        $match = keenMatch($rival, $game, $venue, $date, $this->Hrs24);
        
        // check when this player is available to play the keen rival
        $availableHours = availableHours($this->player, $rival, $match);
              
//printf("hours %1$32b = %1$2d\navail %2$32b = %2$2d\n\n", $hours, $availableHours);

        // check that $player is avaiable at ALL of the requested hours
        return $hours == ($hours & $availableHours);
    }
    
    
    
    /**
     * @Then I will be available to play :game at :venue
     */
    public function iWillBeAvailableToPlaySquashAtMilawa($game, $svid)
    {
        Assert::assertEquals($game, $this->game);
        Assert::assertEquals($svid, $this->svid);        
                
        $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        
        foreach ($days as $day) {
            Assert::assertTrue(
    	        $this->isAvailable(
    	            $this->Hrs6amto8pm, 
    	            $game, 
    	            $this->venue, 
    	            venueDateTime($day, $this->venue)
    	        )
    	    );   
        }
    }


    /**
     * @Then I will be available to play :game at :venue on :day at :hour
     */
    public function iWillBeAvailableToPlaySquashAtMilawaOnSaturdayAt3pm(
        $game, 
        $svid, 
        $day, 
        $hour)
    {
        Assert::assertEquals($game, $this->game);
        Assert::assertEquals($svid, $this->svid);   
    	$day = date("D", strtotime($day));        // convert Saturday to Sat.    	
    	$hour = date("H", strtotime($hour));   // convert 12hr to 24hr format        
        
        Assert::assertTrue(
            $this->isAvailable(
                hour2bit($hour), 
                $game, 
                $this->venue, 
                venueDateTime($day, $this->venue)
            )
        );
    }
    
    
    /**
     * @Then I will NOT be available to play :game at :venue on :day at :hour
     */
    public function iWillNotBeAvailableToPlaySquashAtOnSaturdayAt4pm(
        $game, 
        $svid, 
        $day, 
        $hour)
    {
        Assert::assertEquals($game, $this->game);
        Assert::assertEquals($svid, $this->svid);   
    	$day = date("D", strtotime($day));        // convert Saturday to Sat.    	
    	$hour = date("H", strtotime($hour));   // convert 12hr to 24hr format 
        
        Assert::assertFalse(
            $this->isAvailable(
                hour2bit($hour), 
                $game, 
                $this->venue, 
                venueDateTime($day, $this->venue)
            )
        );
    }


    

}
