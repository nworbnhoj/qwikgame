<?php

require_once 'Qwik.php';
require_once 'Email.php';
require_once 'Notify.php';


class User extends Qwik {
    
    const CSV = ".csv";
    const PATH = PATH_USER;

    const DEFAULT_USER_XML = 
   "<?xml version='1.0' encoding='UTF-8'?>
    <user lang='en' ok='true'>
      <notify/>
    </user>";
    
    /*******************************************************************************
    Returns the sha256 hash of the $email address provided

    $email    String    an email address

    The unique user ID is chosen by taking the sha256 hash of the email address. 
    This has a number of advantages:
    - The user ID will be unique because the email address will be unique
    - Qwikgame can accept and use a sha256 hash to store anonymous user data
    - A new email address can be linked to existing anonymous user data

    *******************************************************************************/
    static function anonID($email){
        return hash('sha256', $email);
    }


    static function exists($id){
        $XML = self::XML;
        $PATH = self::PATH;
        return file_exists("$PATH$id$XML");
    }


    private $id;
    private $xml;


    /**
    * @throws RuntimeException if construction fails.
    */
    public function __construct($pid, $forge=FALSE){
        parent::__construct();
        $this->id = $pid;
        if (!self::exists($pid) && $forge) {
            $this->xml = $this->newXML($pid);
            $this->save();
            self::logMsg("user new $pid");
        }
        if (self::exists($pid)){
            $this->xml = $this->retrieve($this->fileName());
        } else {
            $sid = self::snip($pid);
            throw new RuntimeException("user missing $sid");
        }
    }
    
    
    public function fileName(){
        return $this->id() . self::XML;
    }


    public function default_xml(){
        return DEFAULT_USER_XML;
    }


    private function newXML(){
        $xml = new SimpleXMLElement(self::default_xml());
        $xml->addAttribute('id', $this->id());
        $now = new DateTime('now');
        $xml->addAttribute('debut', $now->format('d-m-Y'));
        return $xml;
    }


    /**
    * Saves the User records to a file named id.xml 
    * @return TRUE if the User xml is saved successfully, and FALSE
    * otherwise.
    * @throws RuntimeException if the User is not saved cleanly.
    */
    public function save(){
        $PATH = self::PATH;
        $fileName = $this->fileName();
        if (!self::writeXML($this->xml, $PATH, $fileName)){
            self::logThrown($e);
            throw new RuntimeException("user save failed $fileName");
            return FALSE;
        }
        return TRUE;
    }


    /**
    * @throws RuntimeException if the xml cannot be read from file.
    */
    public function retrieve($fileName){
        try {
            $PATH = self::PATH;
            $fileName = $this->fileName();
            $xml = self::readXML($PATH, $fileName);
        } catch (RuntimeException $e){
            self::logThrown($e);
            $xml = new SimpleXMLElement("<user ok='false'/>");
            $sid = self::snip($this->id);
            throw new RuntimeException("user retrieve failed $sid");
        }
        return $xml;
    }


    public function ok($value=NULL){
      if(is_null($this->xml)){
        return false;
      }
      if(!isset($this->xml['ok'])){
        $this->xml->addAttribute('ok', (isset($value) ? $value : 'true'));
        $this->save();
      } elseif (isset($value)){
        $this->xml['ok'] = $value;
        $this->save();
      }
      return ((string) $this->xml['ok']) === 'true';
    }


    public function id(){
        return $this->id;
    }


    public function debut(){
        return $this->debut;
    }


    public function lang($lang=NULL){
        if (!is_null($lang)){
            $this->xml['lang'] = $lang;
        }
        return (string) $this->xml['lang'];
    }


    public function admin($admin=NULL){
        if (!is_null($admin)){
            $this->xml['admin'] = $admin;
        }
        return (string) $this->xml['admin'];
    }


    public function nick($nick=NULL){
        if (!is_null($nick)){
            if (isset($this->xml['nick'])){
                $this->xml['nick'] = $nick;
            } else {
                $this->xml->addAttribute('nick', $nick);
            }
        }
        return (string) $this->xml['nick'];
    }


    public function email($newEmail=NULL){
        if (!is_null($newEmail)){
            $xmlEmail = $this->xml->email;
            $newEmail = strtolower($newEmail);
            if(empty($xmlEmail)){
                $this->xml->addChild('email', htmlspecialchars($newEmail));
                $notify = new Notify($this);
                $notify->email($newEmail, Notify::MSG_ALL);
            } else {
                $oldEmail = $xmlEmail[0];
                if (strcmp($oldEmail, $newEmail) != 0) {
                    changeEmail($newEmail);
                }
            }
        }

        $xmlEmail = $this->xml->email;
        if (empty($xmlEmail)){
            return NULL;
        } else if (count($xmlEmail) == 1){
            return (string) $xmlEmail[0];
        } else {
            return (string) $xmlEmail[0];
        }
    }


    private function changeEmail($newEmail){
        $newID = User::anonID($newEmail);
        $oldID = $this->id();
        if (User::exists($newID)){
            self::logMsg("aborted change UserID from $oldID to $newID.");
            return FALSE;
        }

        self::removeElement($this->xml->email[0]);
        $this->xml->addChild('email', htmlspecialchars($newEmail));
        $this->id = $newID;
        $this->xml['id'] = $newID;

        try { // save User xml with new ID
            $this->save();
        } catch (RuntimeException $e){
            self::logThrown($e);
            // back out email and id changes
            self::removeElement($this->xml->email[0]);
            $this->xml->addChild('email', htmlspecialchars($oldEmail));
            $this->id = $oldID;
            $this->xml['id'] = $oldID;
            return FALSE;
        }
        try { // replace old user file with a symlink to the new file
            $PATH = self::PATH;
            self::deleteFile("$PATH$oldID.xml");
            symlink("$PATH$newID.xml", "$PATH$oldID.xml");
        } catch (RuntimeException $e){
            self::logThrown($e);
            throw new RuntimeException("failed to replace User $oldID.xml with a symlink.");
            return FALSE;
        }
        return TRUE;
    }



    public function token($term = User::SECOND){
        $token = self::newToken(10);
        $nekot = $this->xml->addChild('nekot', htmlspecialchars($this->nekot($token)));
        $nekot->addAttribute('exp', time() + $term);
        $this->save();
        return $token;
    }


    /*******************************************************************************
    Returns the sha256 hash of the $token provided

    $token    String    an token

    When it is necessary to send a token to a user (e.g. via email as a proof of 
    identity) then only the sha256 hash of the token is stored by qwikgame.
    This has a number of advantages:
    - the sha256 hash can be computed on presented tokens and validated against the 
    stored hash
    - if the system is compromised then the user held token remain secure.
    *******************************************************************************/
    function nekot($token){
        return hash('sha256', $token);
    }


    public function isValidToken($token){
        $nekot = $this->nekot($token);
        if($this->ok()){
            return count($this->xml->xpath("/*/nekot[text()='$nekot']"))>0;
        }
        return FALSE;
    }


    // https://stackoverflow.com/questions/262351/remove-a-child-with-a-specific-attribute-in-simplexml-for-php/16062633#16062633
    public function deleteData($id){
        $rubbish = $this->xml->xpath("//*[@id='$id']");
        foreach($rubbish as $junk){
            self::removeElement($junk);
        }
    }


    public function quit(){
        foreach($this->xml->xpath("email") as $xml){
            self::removeElement($xml);
        }
        foreach($this->xml->xpath("notify/path") as $xml){
            self::removeElement($xml);
        }

        self::removeAtt($this->xml, "nick");
        self::removeAtt($this->xml, "url");

        $this->save();
    }


    public function authURL($shelfLife, $target='account.php', $param=NULL){
        $query = is_array($param) ? $param : array();
        $query['pid'] = $this->id();
        $query['token'] = $this->token($shelfLife);
        if(!isset($query['qwik'])){
            $query['qwik'] = 'login';
        }
        return QWIK_URL."$target?" . http_build_query($query);
    }

    
    public function authLink($shelfLife, $target='account.php', $param=NULL){
        $authURL = $this->authURL($shelfLife, $target, $param);
        $authURL = htmlspecialchars($authURL, ENT_HTML5, 'UTF-8');
        return "<a id='login' href='$authURL'>{login}</a>";
    }


    public function notifyXML(){
        $xmlArray = $this->xml->xpath("notify");

        if (is_array($xmlArray) && isset($xmlArray[0])){
            $xml = $xmlArray[0];
        } else {
            $xml = $this->xml->addChild('notify', '');
        }

        if (isset($xmlArray[1])){  // integrity check
            $pid = self::snip($this->id());
            self::logMsg("user $pid has duplicate <notify> elements");
        }

        return $xml;
    }


    public function emailWelcome($email, $req, $target='account.php'){
        $param = array(
            "email"   => $email,
            "qwik"    => 'register',
            "game"    => $req['game'],
            "vid"     => $req['vid']
        );
        $authLink = $this->authLink(self::MONTH, $target, $param);
        $paras = array(
            "{Please activate}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailWelcomeSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('welcome', $this->id());
        return $email;
    }


    public function emailLogin($email){
        $paras = array(
            "{Click to login}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailLoginSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "authLink"   => $this->authLink(self::DAY)
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('login', $this->id());
    }


    private function emailChange($email){
        $paras = array(
            "{Click to change}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailChangeSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email(),
            "email"      => $email,
            "authLink"   => $this->authLink(self::DAY, 'account.php')
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('email', $this->id());
    }


    function emailQuit(){
        $paras = array(
            "{Sorry that you...}",
            "{Your info removed}",
            "{Anon feedback remains}",
            "{Backups remain}",
            "{Good luck}"
        );
        $vars = array(
            "subject"    => "{emailQuitSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email()
        );
        $email = new Email($vars, $this->lang());
        $email->send();
        self::logEmail('quit', $this->id());
    }


}


?>