<?php

require_once 'class/Qwik.php';
require_once 'class/Logging.php';
require_once 'class/PlayerPage.php';
require_once 'class/Player.php';
require_once 'class/Ranking.php';
require_once 'class/LocatePage.php';
require_once 'class/Hours.php';
require_once 'class/Page.php';


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
    private $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    private $authURL;
    private $email;
    private $game;
    private $id;
    private $parity;
    private $pid;
    private $player;
    private $rankingFileName;
    private $req = [];     // a post or get request
    private $time;
    private $token;
    private $svid;
    private $venue;
    private $vid;
    private $log;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->log = new Logging();
    }
    
    
    /**
     * Converts a Short Venue ID $svid to a unique Venue ID $vid if possible
     */    
    private function locateVenue($svid, $game = '')
    {        
        $vids = LocatePage::matchShortVenueID($svid, $game);
        Assert::assertCount(1, $vids, "Please specify an existing Venue exactly");
        return $vids[0];
    }
    
    

//     * @Given my email \b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b is not registered with
     
    /**
     * @Given my email :address is not registered with qwikgame
     */
    public function myEmailIsNotRegisteredWithQwikgame($address)
    {
        $this->pid = Player::anonID($address);
        Ranking::removePlayer($this->pid);
    }


    /**
     * @Given my email :address is registered with qwikgame
     */
    public function myEmailIsRegisteredWithQwikgame($address)
    {
        $this->email = $address;

        $this->pid = Player::anonID($address);
        Ranking::removePlayer($this->pid);
        $this->player = new Player($this->pid, TRUE);
        $this->player->email($address);
        $token = $this->player->token();
        $this->player->save(); 

        $this->req['pid'] = $this->player->id();
        $this->req['token'] = $token;
    }
    
     /**
     * @Given I am not available to play
     */
    public function iAmNotAvailableToPlay()
    {    
        $available = $this->player->available();
        foreach($available as $avail){
            removeElement($available);
        }
    }


    /**
     * @When I like to play :game at :venue
     */
    public function iLikeToPlaySquashAtQwikgame($game, $svid)
    {
        $this->game = $this->gameKey($game);
        $this->svid = $svid;
        $this->vid = $this->locateVenue($this->svid, $this->game);
        $this->venue = new Venue($this->vid);

        $this->req['parity'] = 'any';
        $this->req['game'] = $this->game;
        $this->req['vid'] = $this->vid;
    }


    // accepts and Game Name in english looks up the game $key inefficiently
    private function gameKey($gameName){
        foreach(Qwik::qwikGames() as $key => $value){
           $val = substr($value, 1, strlen($value)-2);
           if ($val === $gameName){
               return $key;
           }
        }
        return null;
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
        $hour = new Hours(date("H", strtotime($hour)));   // convert 12hr to 24hr format
        if (isset($this->req[$day])) {
            $hour->include(new Hour($this->req[$day]));
        }
        $this->req[$day] = $hour->bits();
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
        $this->pid = Player::anonID($address);
        $this->req['email'] = $address;
    }
    
    
    

    /**
     * @When I Submit this favourite
     */
    public function iSubmitThisFavourite()
    {
        $this->req['qwik'] = 'available';
        $_GET = $this->req;
        $page = new PlayerPage();
        $this->id = $page->processRequest();
        $this->player = new Player($this->pid, FALSE);
        $this->authURL = $this->player->authURL(2*Player::DAY, $this->req);
    }


    /**
     * @When I delete this favourite
     */
    public function iDeleteThisFavourite()
    {
        $this->req['qwik'] = 'delete';
        $this->req['id'] = $this->id;
        $_GET = $this->req;
        $page = new PlayerPage();
        $this->id = $page->processRequest();
        $this->player = new Player($this->pid, FALSE);
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

        $_GET = $req;
        $page = new PlayerPage();
        $player = $page->player();

        Assert::assertTrue(
            isset($player),
            "The player could not be logged in."
        );

        $page->processRequest();
    }
    

    /**
     * @Then my email :address will be registered with qwikgame
     */
    public function myEmailWillBeRegisteredWithQwikgame($address)
    {
        Assert::assertEquals($this->email, $address);
        Assert::assertNotNull(new Player($this->pid));
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
    private function iWillBeAvailableToPlay($game, $venue, $rivalParity='any', $test='ALL')
    {
        $rid = Player::anonID("rival@qwikgame.org");
        $rival = new Player($rid, TRUE);  //dummy rival
        // Set this player as a familiar of the rival, with suitable parity
        $_GET = array(
                'pid'=>$rid,
                'token'=>$rival->token(),
                'qwik'=>'familiar',
                'game'=>$game, 
                'rival'=>$this->email,
                'parity'=>$this->parityPhrase[$rivalParity]
            ); 
        $page = new PlayerPage();
        $page->processRequest();

        $parity = $this->player->parity($rival, $game);
        foreach ($this->days as $day) {
            if (isset($this->req[$day])){
                $date  = $venue->dateTime($day);
                $hours = new Hours($this->req[$day]);

                $availableHours = $this->player->availableHours($venue->id(), $game, $day, $parity);
//$vid = $venue->id();
//print_r("$game at $vid\n");
//printf("hours %1$25b = %1$2d\navail %2$25b = %2$2d\n\n", $hours->bits(), $availableHours->bits());
                
                switch ($test) {
                    case 'ALL':
                        Assert::assertTrue(
                            $availableHours->equals($hours),
                            "Player not Available when they should be: $game $day $hours"
                        );
                        break;                    
                    case 'NONE':
                        $availableHours->includeOnly($hours);
                        Assert::assertTrue(
                            $availableHours->empty(),
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
        $this->iWillBeAvailableToPlay($this->req['game'], $this->venue, 'any', 'ALL');
    }
  


    /**
     * @When I will not be available otherwise
     */
    public function iWillNotBeAvailableOtherwise()
    {
        $req = $this->req;
        foreach ($this->days as $day) {
            $this->req[$day] = Hours::HRS_24 ^ (isset($req[$day]) ? $req[$day] : -0);
        }
        $game = $this->req['game'];
        $this->iWillBeAvailableToPlay($game, $this->venue, 'any', 'NONE');

        // Check other abilities for this game and venue
         if ($this->req['parity'] == 'matched') {
            $this->iWillBeAvailableToPlay($game, $this->venue, 'much_stronger', 'NONE');
            $this->iWillBeAvailableToPlay($game, $this->venue, 'stronger', 'NONE');
            $this->iWillBeAvailableToPlay($game, $this->venue, 'weaker', 'NONE');
            $this->iWillBeAvailableToPlay($game, $this->venue, 'much_weaker', 'NONE');
        } elseif ($this->req['parity'] == 'similar') {
            $this->iWillBeAvailableToPlay($game, $this->venue, 'much_stronger', 'NONE');
            $this->iWillBeAvailableToPlay($game, $this->venue, 'much_weaker', 'NONE');
        } else {
            // no alternate to check for parity==all
        }
        
        // Check other games for this venue
        $req = $this->req;
        foreach ($this->days as $day) {
            $req[$day] = Hours::HRS_24;
        }
        $games = array_keys(Qwik::qwikGames());
        foreach ($games as $game) {
            if ($game !== $this->game) {
                $req['game'] = $game;            
                $this->iWillBeAvailableToPlay($game, $this->venue, 'any', 'NONE');
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
            $name = "player.$char";
            $email = "$name@qwikgame.org";
            $id = Player::anonID($email);
            Ranking::removePlayer($id);
            $player = new Player($id, TRUE);
            $player->nick($name);
            $player->email($email);
            $player->save();
            $this->rivals[$char] = $id;
        }
    }


    private $paritySymbol = array('<<'=>-2, '<'=>-1, '='=>0, '>'=>1, '>>'=>2);
    private $parityPhrase = array('much_weaker'=>-2, 'weaker'=>-1, 'well_matched'=>0, 'stronger'=>1, 'much_stronger'=>2, 'any'=>2);


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

        $playerA = new Player($pidA);
        $playerB = new Player($pidB);

        $matches = $playerA->matchQuery("match[@status='confirmed' and rival='$pidB']");

        if (count($matches) == 1) {
            $matchA = new Match($playerA, $matches[0]);
        } else {    // set up a dummy match between A & B, ready for feedback
            $vid = $this->locateVenue("Qwikgame Venue | Milawa");
            $venue = new Venue($vid);
            $date = date_add(
                $venue->dateTime("today"),
                date_interval_create_from_date_string("$day days")
            );
            $hours = new Hours(1);

            $keenMatch = $playerA->matchKeen('squash', $venue, $date, $hours);
            $matchB = $playerB->matchAdd($keenMatch);
            $matchA = $playerA->matchAdd($matchB);
            $matchA->status('confirmed');
            $matchB->status('confirmed');
            $keenMatch->cancel();
            $keenMatch->remove();
        }

        $_GET = array(
                'pid'=>$playerA->id(),
                'token'=>$playerA->token(),
                'qwik'=>'feedback',
                'id'=>$matchA->id(),
                'rep'=>'0',
                'parity'=>$this->paritySymbol[$parity]
            );
        $page = new PlayerPage();
        $page->processRequest();
    }


    /**
     * @Then /([A-Z])(<<|<|=|>|>>)([A-Z]) on day ([0-9]+)/
     */
    public function playerParityOnDay($player, $parity, $rival, $day)
    {
        $pidA = $this->rivals[$player];
        $pidB = $this->rivals[$rival];

        $playerA = new Player($pidA);
        $playerB = new Player($pidB);

        $parityEstimate = $playerA->parity($playerB, 'squash');
        $parityStr = Page::parityStr($parityEstimate);

//$nickA = $playerA['nick'];
//$nickB = $playerB['nick'];
//$relyA=$playerA->rely['val'];
//$relyB=$playerB->rely['val'];
//$rely = $playerA->rely;
//print_r("$nickA rely=$relyA\n");
//print_r("$nickB rely=$relyB\n");
//print_r("$parity\t$parityEstimate\t$parityStr\n");

        switch ($parity) {
            case '<<':
                Assert::assertSame($parityStr, "{much_weaker}");
                break;
            case '<':
                Assert::assertSame($parityStr, "{weaker}");
                break;
            case '=':
                Assert::assertSame($parityStr, "{well_matched}");
                break;
            case '>':
                Assert::assertSame($parityStr, "{stronger}");
                break;
            case '>>':
                Assert::assertSame($parityStr, "{much_stronger}");
                break;
        }
    }   
    
    
    
    /**
     * @Given a :game ranking file :fileName from :plyr
     */
    public function aRankingFile($game, $fileName, $plyr)
    {
        $pid = $this->rivals[$plyr];
        $path = Qwik::PATH_UPLOAD . "/" . $fileName . Ranking::CSV;
        $this->player = new Player($pid);
        $ranking = $this->player->importRanking($game, $path, $fileName);
        $this->rankingFileName = "$fileName.xml";
        $path = Qwik::PATH_UPLOAD . "/$fileName.xml";
        $file = fopen($path, "r");
    }



    /**
     * @When the ranking is Activated
     */
    public function theRankingIsActivated()
    {
        $fileName = $this->rankingFileName;
        $player = $this->player;
        $ranking = $player->uploadAdd($fileName);
        $player->rankingActivate($fileName);
    }



    /**
     * @When I am keen to play :game at :venue
     */
    public function iAmKeenToPlaySquashAtQwikgame($game, $svid)
    {
        $this->game = $this->gameKey($game);
        $this->svid = $svid;
        $this->vid = $this->locateVenue($this->svid, $this->game);
        $this->venue = new Venue($this->vid);

        $this->req['parity'] = 'any';
        $this->req['game'] = $this->game;
        $this->req['vid'] = $this->vid;
    }

    
    
}
