<?php

require 'phpunit.phar';

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\TestCase as Assert;

require 'qwik.php';

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private $authURL;
	private $email;
	private $game;
	private $parity;
	private $pid;
	private $player;
	private $req = [];     // a post or get request
	private $time;
	private $token;
	private $venue;

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
		Assert::assertNotNull($this->player);
    }


    /**
     * @When I like to play :game at :venue
     */
    public function iLikeToPlaySquashAtMilawa($game, $venue)
    {
    	$this->req['game'] = $game;
    	$this->req['vid'] = $venue;
    	
    	$this->game = $game;
    	$this->venue = readVenueXML($this->req['vid']);
    }

    /**
     * @When I provide my email :address
     */
    public function iProvideMyEmailAddress($address)
    {
    	$this->req['email'] = $address;
    	
		if(!$this->player){
 		    $this->pid = anonID($address);
			$this->player = newPlayer($this->pid);
        }
        global $MONTH;
        $this->token = newPlayerToken($this->player, $MONTH);
	    $this->authURL = authURL($this->email, $this->token);
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
        $this->player = login($req);
        
        Assert::assertNotNull($this->player);
    }
    
    
    /**
     * @Then I will be available to play :game at :venue
     */
    public function iWillBeAvailableToPlaySquashAtMilawa($game, $venue)
    {
        global $HRS6amto8pm;
        
        // qwikAvailable is called when a player posts their availability
        qwikAvailable($this->player, $this->req, $this->venue);
        
        // create a dummy rival
        $rival = newPlayer(anonID("rival@qwikgame.org"));
        
        // check that this player is available when the rival is keen
        $date = venueDateTime('tomorrow', $venue);
        $availableHours = availableHours(
            $this->player,
            $rival,
            keenMatch($rival, $game, $venue, $date, $HRS6amto8pm)
        );
        
        Assert::assertGreaterThanOrEqual($HRS6amto8pm, $availableHours);       
        
        
        // Set this player as a familiar of the rival, with matching parity
        // qwikFamiliar($rival, array('game'=>$game, 'rival'=>$this->email, 'parity'=>0));  
    }


    /**
     * @Then I will be available to play :game at :venue on :day
     */
    public function iWillBeAvailableToPlaySquashAtMilawaOnSaturday($game, $venue, $day)
    {
        throw new PendingException();
    }


    /**
     * @When I like to play on :day
     */
    public function iLikeToPlayOnSaturday($day)
    {
        $this->req["$day"] = PHP_INT_MAX;
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
