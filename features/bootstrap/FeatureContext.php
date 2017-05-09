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
    	$this->player=readPlayerXML($this->pid);
		Assert::assertFalse($this->player);
    }


    /**
     * @Given my email :address is registered with qwikgame
     */
    public function myEmailIsRegisteredWithQwikgame($address)
    {
    	$this->pid = anonID($address);
    	$this->player=readPlayerXML($this->pid);
    	if (!$this->player) {
    	    $this->player = newPlayer($this->pid);
    	writePlayerXML($this->player);
    	}
		Assert::assertNotNull($this->player);
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
    	
    	$this->req['game'] = $game;
    	$this->req['vid'] = $this->vid;
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
	     global $Hrs24;
        
        // create a dummy rival
        $rival = newPlayer(anonID("rival@qwikgame.org"));
        
        // Set this player as a familiar of the rival, with matching parity
        // qwikFamiliar($rival, array('game'=>$game, 'rival'=>$this->email, 'parity'=>0)); 
        
        // The rival is keen for a match
        $match = keenMatch($rival, $game, $venue, $date, $Hrs24);

        // check when this player is available to play the keen rival
        $availableHours = availableHours($this->player, $rival, $match);
        
        // check that $player is avaiable at ALL of the requested hours
        return $hours == ($hours & $availableHours);
    }
    
    
    
    /**
     * @Then I will be available to play :game at :venue
     */
    public function iWillBeAvailableToPlaySquashAtMilawa($game, $svid)
    {
        global $Hrs6amto8pm;
        Assert::assertEquals($game, $this->game);
        Assert::assertEquals($svid, $this->svid);
                
        // qwikAvailable is called when a player posts their availability
        qwikAvailable($this->player, $this->req, $this->venue);
                
        $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        
        foreach ($days as $day) {
            $date = venueDateTime($day, $this->venue);
	        Assert::assertTrue(
    	        $this->isAvailable($Hrs6amto8pm, $game, $this->venue, $date)
    	    );   
        }
    }


    /**
     * @Then I will be available to play :game at :venue on :day
     */
    public function iWillBeAvailableToPlaySquashAtMilawaOnSaturday($game, $svid, $day)
    {
        global $Hrs6amto8pm;
        Assert::assertEquals($game, $this->game);
        Assert::assertEquals($svid, $this->svid);
                
        // qwikAvailable is called when a player posts their availability
        qwikAvailable($this->player, $this->req, $this->venue);
        
        $date = venueDateTime($day, $this->venue);
        
        Assert::assertTrue(
            $this->isAvailable($Hrs6amto8pm, $game, $this->venue, $date)
        );
    }


    /**
     * @When I like to play on :day
     */
    public function iLikeToPlayOnSaturday($day)
    {
	    global $Hrs6amto8pm;
        $this->req["$day"] = $Hrs6amto8pm;
    }
    
    
    /**
     * @When I like to play on :day at :hour
     */
    public function iLikeToPlayOnSaturdayAt3pm($day, $hour)
    {
    	switch ($hour){
    	    case '12am': 
    	
    	}
        $this->req["$day"] = PHP_INT_MAX;
        throw new PendingException();
    }



}
