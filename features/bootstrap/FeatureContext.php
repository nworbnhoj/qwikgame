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
    private $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
	private $games = array(
    	'Backgammon',
    	'Badminton',
    	'Boules',
    	'Billards',
    	'Checkers',
    	'Chess',
    	'Cycle',
    	'Darts',
    	'Dirt',
    	'Fly',
    	'Go',
    	'Golf',
        'Lawn',
    	'Mtnbike',
    	'Pool',
    	'Racquetball',
    	'Run',
    	'Snooker',
    	'Squash',
    	'Table',
    	'Tennis',
	    'Tenpin',
	    'Walk'
	);

    private $authURL;
	private $email;
	private $game;
	private $id;
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
        global $log;
        $log = new Logging();
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
     * @Given I am not available to play
     */
    public function iAmNotAvailableToPlay()
    {    
        $available = $this->player->xpath('available');
	    foreach($available as $avail){
			removeElement($available);
	    }
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
    	
    	$this->req['parity'] = 'any';
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
     * @When I like to play a rival of :parity ability
     */
    public function iLikeToPlayARivalOfSimilarAbility($parity)
    {
        Assert::assertContainsOnly('string', ['matched', 'similar', 'any']);
    	$this->req['parity'] = $parity;
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
     * @When I Submit this favourite
     */
    public function iSubmitThisFavourite()
    {
      	$this->req['qwik'] = 'available';
        $this->id = qwikAvailable($this->player, $this->req, $this->venue);
    }      


    /**
     * @When I delete this favourite
     */
    public function iDeleteThisFavourite()
    {    
    	$this->req['qwik'] = 'delete';
    	$this->req['id'] = $this->id;
   	    $this->id = qwikDelete($this->player, $this->req);
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
     * 
     * @param Array $req A get/post query representing a Match
     * @param XML $venue The game Venue for the vid field in $req
     * @param boolean $willBe a toogle for (willBeAvailable | willNotBeAvailable)
     *
     * @return void
     *
     * @throws 
     */
    private function iWillBeAvailableToPlay($req, $venue, $rivalParity='any', $test='ALL')
    {        
        $game = $req['game'];
        $rival = newPlayer(anonID("rival@qwikgame.org"));  //dummy rival
        
        foreach ($this->days as $day) {
            if (isset($req[$day])){
                $date  = venueDateTime($day, $this->venue);            
                $hours = $req[$day];
                
                // Set this player as a familiar of the rival, with suitable parity
                qwikFamiliar(
                    $rival, 
                    array(
                        'game'=>$game, 
                        'rival'=>$this->email,
                        'parity'=>$rivalParity)
                    ); 
        
                // The rival is keen for a match
                $match = keenMatch($rival, $game, $venue, $date, $this->Hrs24);
        
                // check when this player is available to play the keen rival
                $availableHours = availableHours($this->player, $rival, $match);
                                     
//print_r("$game at $venue[id]\n");              
//printf("hours %1$25b = %1$2d\navail %2$25b = %2$2d\n\n", $hours, $availableHours);
                
                switch ($test) {
                    case 'ALL':
                        Assert::assertEquals(
                            $hours,
                            $hours & $availableHours,
                            "Player not Available when they should be: $game $day $hours"
                        );
                        break;                    
                    case 'NONE':                    
                        Assert::assertEquals(
                            0,
                            $hours & $availableHours,
                            "Player Available when they should not be: $game $day $hours"
                        );
    	                break;
                    default:
                        Assert::assertTrue(FALSE, "Invalid test: $test");
                
                }
            }        
        }
    }    
    
    
    
    /**
     * @Then I will be available to play my favourite game
     */
    public function iWillBeAvailableToPlayMyFavouriteGame()
    {       
        $this->iWillBeAvailableToPlay($this->req, $this->venue, 'any', 'ALL');
    }
  


    /**
     * @When I will not be available otherwise
     */
    public function iWillNotBeAvailableOtherwise()
    {
        // Check other times for this game and venue
    	$req = $this->req;
        foreach ($this->days as $day) {
            $req[$day] = $this->Hrs24 ^ (isset($req[$day]) ? $req[$day] : 0);
        }
    	$this->iWillBeAvailableToPlay($req, $this->venue, 'any', 'NONE');

    	// Check other abilities for this game and venue
    	 if ($this->req['parity'] == 'matched') {
            $this->iWillBeAvailableToPlay($req, $this->venue, 'much-stronger', 'NONE');
            $this->iWillBeAvailableToPlay($req, $this->venue, 'stronger', 'NONE');
            $this->iWillBeAvailableToPlay($req, $this->venue, 'weaker', 'NONE');
            $this->iWillBeAvailableToPlay($req, $this->venue, 'much-weaker', 'NONE');
    	} elseif ($this->req['parity'] == 'similar') {
            $this->iWillBeAvailableToPlay($req, $this->venue, 'much-stronger', 'NONE');
            $this->iWillBeAvailableToPlay($req, $this->venue, 'much-weaker', 'NONE');
    	} else {
    	    // no alternate to check for parity==all
    	}
        
        // Check other games for this venue
    	$req = $this->req;         
        foreach ($this->days as $day) {
            $req[$day] = $this->Hrs24;
        }
        foreach ($this->games as $game) {
            if ($game != $this->game) { 
                $req['game'] = $game;            
                $this->iWillBeAvailableToPlay($req, $this->venue, 'any', 'NONE');
            }
        }    
        
        // Check other venues  
    }



    /**
     * @Then I will not be available to play
     */
    public function iWillNotBeAvailableToPlay()
    {    
        foreach ($this->days as $day) {
            unset($this->req[$day]);
        }
        $this->iWillNotBeAvailableOtherwise();
    }


        private $rivals = array();

    /**
     * @Given a community of Players
     */
    public function aCommunityOfPlayers()
    {
        foreach (range('A', 'Z') as $char) {
            $email = $char . "@qwikgame.org";
            $id = anonID($email);
            $player = newPlayer($id);
            $player->addAttribute('nick', "player.$char");
            writePlayerXML($player);
            $this->rivals[$char] = $id;
		}
    }


    private $paritySymbol = array('<<'=>-2, '<'=>-1, '='=>0, '>'=>1, '>>'=>2);


    /**
     * @When /([A-Z]) reports ([A-Z])(<<|<|=|>|>>)([A-Z]) from match on day ([0-9]+)/
     */
    public function playerReportsParityFromMatchOnDay(
        $player,
        $samePlayer,
        $parity,
        $rival,
        $day)
    {
        Assert::assertEquals($player, $samePlayer,
            "Step should be of the form 'A reports A>B on day D'");

        $pidA = $this->rivals[$player];
        $pidB = $this->rivals[$rival];

        $playerA = readPlayerXML($pidA);
        $playerB = readPlayerXML($pidB);

        $matches = $playerA->xpath("match[@status='confirmed' and rival='$pidB']");

        if (count($matches) == 1) {
            $matchA = $matches[0];
        } else {    // set up a dummy match between A & B, ready for feedback
            $vid = $this->locateVenue("Milawa Squash Courts | Milawa");
            $venue = readVenueXML($vid);
            $date = date_add(
                venueDateTime("today", $venue),
                date_interval_create_from_date_string("$day days")
            );
            $hours = 1;

            $keenMatch = keenMatch($playerA, 'Squash', $venue, $date, $hours);
            $matchA = inviteMatch($playerB, $keenMatch, $playerA, $hours);
            $matchB = inviteMatch($playerA, $keenMatch, $playerB, $hours);
            $matchA['status'] = 'confirmed';
	        $matchB['status'] = 'confirmed';
		    removeElement($keenMatch);
		    writePlayerXML($playerA);
		    writePlayerXML($playerB);
		}

        qwikFeedback(
            $playerA, 
            array(
                'id'=>$matchA['id'],
                'rep'=>'0',
                'parity'=>$this->paritySymbol[$parity]
            )
        );
    }


    /**
     * @Then /([A-Z])(<<|<|=|>|>>)([A-Z]) on day ([0-9]+)/
     */
    public function playerParityOnDay($player, $parity, $rival, $day)
    {
        $pidA = $this->rivals[$player];
        $pidB = $this->rivals[$rival];

        $playerA = readPlayerXML($pidA);
        $playerB = readPlayerXML($pidB);

        $parityStr = parityStr(parity($playerA, $playerB, 'Squash'));

        switch ($parity) {
            case '<<':
                Assert::assertSame($parityStr, "<t>much_weaker</t>");
                break;
            case '<':
                Assert::assertSame($parityStr, "<t>weaker</t>");
                break;
            case '=':
                Assert::assertSame($parityStr, "<t>well_matched</t>");
                break;
            case '>':
                Assert::assertSame($parityStr, "<t>stronger</t>");
                break;
            case '>>':
                Assert::assertSame($parityStr, "<t>much_stronger</t>");
                break;
        }
    }   
    
    
}
