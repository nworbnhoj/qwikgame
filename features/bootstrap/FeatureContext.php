<?php

require_once 'class/Qwik.php';
require_once 'class/Logging.php';
require_once 'class/Player.php';
require_once 'class/Ranking.php';
require_once 'class/Hours.php';
require_once 'class/Page.php';
require_once 'class/Email.php';
require_once 'class/MatchPage.php';
require_once 'class/FriendPage.php';
require_once 'class/FavoritePage.php';
require_once 'class/AccountPage.php';

require_once 'features/bootstrap/Post.php';
require_once 'features/bootstrap/Get.php';


use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use PHPUnit\Framework\TestCase as Assert;


/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    
    private $Hrs6amto8pm =  2097088;
    private $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    private $authURL;
    private $emailWelcome;
    private $email;
    private $game;
    private $id;
    private $parity;
    private $pid;
    private $rankingID;
    private $req = [];     // a post or get request
    private $time;
    private $token;
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
    

//     * @Given \b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b is not registered with
     
    /**
     * @Given :address is not registered with qwikgame
     */
    public function emailIsNotRegisteredWithQwikgame($address)
    {
        $pid = Player::anonID($address);
        Player::removePlayer($pid);
    }


    /**
     * @Given my email :address is registered with qwikgame
     */
    public function myEmailIsRegisteredWithQwikgame($address)
    {
        $this->email = $address;

        $this->pid = Player::anonID($address);
        Player::removePlayer($this->pid);
        $player = new Player($this->pid, TRUE);
        $player->email($address);
        $token = $player->token();
        $player->save();
    }
    
     /**
     * @Given I am not available to play
     */
    public function iAmNotAvailableToPlay()
    {       
        $player = new Player($this->pid);
        $available = $player->available();
        foreach($available as $avail){
            removeElement($available);
        }
    }


    /**
     * @When I like to play :game at :venue
     */
    public function iLikeToPlaySquashAtQwikgame($game, $vid)
    {
        $this->game = $this->gameKey($game);
        $this->vid = $vid;
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
            $hour->append(new Hour($this->req[$day]));
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
     * @When I Register this favorite
     */
    public function iRegisterThisFavourite()
    {
        $this->req['qwik'] = 'available';
        $req = $this->req;
        $email = $req['email'];
        $req['name'] = $email;  // side-step honeypot
        unset($req['email']);
        $this->id = Post::indexPage($req);
        $player = new Player($this->pid);
        $this->emailWelcome = $player->emailWelcome($email, $req);
    }
    

    /**
     * @When I Submit this favorite
     */
    public function iSubmitThisFavourite()
    {
        $player = new Player($this->pid);
        $this->req['qwik'] = 'available';
        $this->id = Post::favoritePage($this->pid, $this->req);
    }


    /**
     * @When I delete this favorite
     */
    public function iDeleteThisFavourite()
    {
        $req = array();
        $req['qwik'] = 'delete';
        $req['id'] = $this->id;
        $this->id = Post::favoritePage($this->pid, $req);
    }


    /**
     * @When I click on the link in the welcome email
     */
    public function iClickOnTheLinkInTheWelcomeEmail()
    {
        Assert::assertNotNull($this->emailWelcome);
        $body = $this->emailWelcome->body();
        $dom = DOMDocument::loadHTML($body);
        $link = $dom->getElementById('login');
        Assert::assertNotNull($link);
        $href = $link->getAttribute('href');
        Assert::assertNotNull($href);   
        $query = parse_url($href, PHP_URL_QUERY);
        Assert::assertNotNull($query);
        parse_str($query, $req);
        Assert::assertNotNull($req);

        $this->id = Get::favoritePage($req);

        Assert::assertTrue(
            isset($this->id),
            "The player registration failed."
        );
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
        new Player($rid, TRUE);  //dummy rival
        // Set this player as a friend of the rival, with suitable parity
        $req = array(
                'qwik'=>'friend',
                'game'=>$game, 
                'rival'=>$this->email,
                'parity'=>$this->parityPhrase[$rivalParity]
            );
        Post::friendPage($rid, $req);

        $player = new Player($this->pid);
        $rival = new Player($rid);
        $parity = $player->parity($rival, $game);
        foreach ($this->days as $day) {
            if (isset($this->req[$day])){
                $date  = $venue->dateTime($day);
                $hours = new Hours($this->req[$day]);

                $availableHours = $player->availableHours($venue->id(), $game, $day, $parity);
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
                            $availableHours->purge(),
                            "Player Available when they should not be: $game $day $hours"
                        );
                        break;
                    default:
                        Assert::assertTrue(FALSE, "Invalid test: $test");
                
                }
            }        
        }
        $rival->delete();
    }    
    
    
    
    /**
     * @Then I will be available to play my favorite game
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
            $email = strtolower("$name@qwikgame.org");
            $id = Player::anonID($email);
            Assert::assertTrue(Player::removePlayer($id));
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

//        $matches = $playerA->matchQuery("match[@status='confirmed' and rival='$pidB']");
        $matches = $playerA->matchQuery("match[rival='$pidB']");

        if (count($matches) == 1) {
          $match = new Match($playerA, $matches[0]);
          $mid = $match->id();
        } else {    // set up a dummy match between A & B, ready for feedback
          $vid = "Qwikgame Venue|South Pole|AU|AQ";
          $hour = '1024';
          $invite = array($playerB->email());
          $kid = Post::matchPageKeen($pidA, 'squash', $vid, '0', $hour, $invite);
          $mid = Post::matchPageAccept($pidB, $kid, $hour);
          Post::matchPageAccept($pidA, $mid, $hour);

//            $keenMatchA->cancel();
            
        }
        $ps = $this->paritySymbol[$parity];
        Post::matchPageFeedback($pidA, $mid, $ps);
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
        $msg = "$parityEstimate = $parityStr";

        switch ($parity) {
            case '<<':
                Assert::assertSame($parityStr, "{much_weaker}", $msg);
                break;
            case '<':
                Assert::assertSame($parityStr, "{weaker}", $msg);
                break;
            case '=':
                Assert::assertSame($parityStr, "{well_matched}", $msg);
                break;
            case '>':
                Assert::assertSame($parityStr, "{stronger}", $msg);
                break;
            case '>>':
                Assert::assertSame($parityStr, "{much_stronger}", $msg);
                break;
        }
    }   
    
    
    
    /**
     * @Given a :game ranking file :rid from :plyr
     */
    public function aRankingFile($game, $rid, $plyr)
    {
        $this->pid = $this->rivals[$plyr];
        $player = new Player($this->pid);
        $ranking = $player->importRanking($rid, $game);
        $this->rankingID = $ranking->id();
    }


    /**
     * @When the ranking is Activated
     */
    public function theRankingIsActivated()
    {
        $rid = $this->rankingID;
        $ranking = new Ranking($rid);
        $player = new Player($this->pid);
        $player->rankingActivate($rid);
    }


    /**
     * @When the ranking is Deactivated
     */
    public function theRankingIsDeactivated()
    {
        $rid = $this->rankingID;
        $ranking = new Ranking($rid);
        $player = new Player($this->pid);
        $player->rankingDeactivate($rid);
    }


    /**
     * @Then the ranking is active
     */
    public function theRankingIsActive()
    {
      $rid = $this->rankingID;
      $ranking = new Ranking($rid);        
      $rid = $ranking->id();
      $ranks = $ranking->ranks();
      foreach($ranks as $pid => $rank){
        $player = new Player($pid);
        if (isset($player) && $player->ok()){
            $r = $player->matchQuery("rank[@id='$rid']");
            Assert::assertTrue(count($r) > 0, "Rank id=$rid not found in Player id=$pid");
            Assert::assertEquals(count($r), 1, "Multiple rank id=$rid found in Player id=$pid");
        }
      }
    }


    /**
     * @Then the ranking is inactive
     */
    public function theRankingIsInactive()
    {
      $rid = $this->rankingID;
      $ranking = new Ranking($rid);        
      $rid = $ranking->id();
      $ranks = $ranking->ranks();
      foreach($ranks as $pid => $rank){
        $player = new Player($pid);
        if (isset($player) && $player->ok()){
            $r = $player->matchQuery("rank[@id='$rid']");
            Assert::assertEquals(count($r), 0, "Rank id=$rid found in Player id=$pid");
        }
      }
    }




    /**
     * @When I am keen to play :game at :venue
     */
    public function iAmKeenToPlaySquashAtQwikgame($game, $vid)
    {
        $this->game = $this->gameKey($game);
        $this->vid = $vid;
        $this->venue = new Venue($this->vid);

        $this->req['parity'] = 'any';
        $this->req['game'] = $this->game;
        $this->req['vid'] = $this->vid;
    }

    
    /**
     * @Given I add :email as a :parity :game player
     */
    public function iAddAsAPlayer($email, $parity, $game)
    {
        $options = array('much_stronger'=>'2', 'stronger'=>'1', 'well_matched'=>'0', 'weaker'=>'-1', 'much_weaker'=>'-2');
        $this->req['qwik'] = 'friend';
        $this->req['rival'] = $email;
        $this->req['parity'] = $options[$parity];
        $this->req['game'] = $game;
        $this->id = Post::friendPage($this->pid, $this->req);
    }

    /**
     * @Then :email is a :parity :game player
     */
    public function isAPlayer($email, $parityPhrase, $game)
    {
        $rid = Player::anonID($email);
        $rival = new Player($rid, FALSE);
        $checkParity = $this->parityPhrase[$parityPhrase];
        $player = new Player($this->pid);
        $calcParity = $player->parity($rival, $game);

        Assert::assertEquals($checkParity, $calcParity);
    }


    
}
