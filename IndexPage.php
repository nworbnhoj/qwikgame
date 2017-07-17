<?php

require_once 'Page.php';

class IndexPage extends Page {


    public function __construct(){
        Page::__construct('index');
    }


    public function serve($template){
        if (null !== $this->player()){
            $query = http_build_query($this->req());
		    header("Location: $qwikURL/player.php?$query");
            return;
	    }
	    Page::serve($template);
	}



    public function processRequest(){
		$qwik = $this->req('qwik');
		$email = $this->req('email');
		if ($qwik == 'available'
		&& null !== $this->req('venue')
		&& null !== $this->req('game')
		&& isset($email)){
			$pid = anonID($email);
			$anon = new Player($pid, $log, TRUE);
			if(isset($anon)){
				$token = $anon->token(2*Player::DAY);
				$anon->save();
				$this->req('pid', $pid);
				$this->req('token', $token);
				$this->req('repost', 'player.php#available');
				$this->emailStash($email, 'locate.php', $this->req(), $pid, $token);
			}
		}
	}


    public function variables(){
        $venue = $this->req('venue');
        $game = $this->req('game');
        
        $variables = Page::variables();

        $variables['playerCount']    = $this->countFiles('player');
        $variables['venueCount']     = $this->countFiles('venue');
        $variables['venuesLink']     = "<a href='venues.php?game=squash'><t>venues</t></a>";
        $variables['venue']          = isset($venue) ? $venue : '';
        $variables['gameOptions']    = $this->gameOptions($game, "\t\t");
        $variables['datalists']      = $this->datalists();
        
        return $variables;
    }



    private function emailStash($email, $page, $req, $id, $token){
        $subject = 'qwikgame.org confirm availability';
        $query =  http_build_query($req);
        $game = $req['game'];
        $venue = $req['venue'];

        $msg  = "<p>\n";
        $msg .= "\tPlease click this link to \n";
        $msg .= "\t<a href='".QWIK_URL."/$page?$query' target='_blank'>confirm</a>\n";
        $msg .= " that you are available to play <b>$game</b> at <b>$venue</b>.<br>\n";
        $msg .= "\t\t\t</p>\n";
        $msg .= "<p>\n";
        $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
        $msg .= "<p>\n";

        qwikEmail($email, $subject, $msg, $id, $token);
        $this->logEmail('login', $id);
    }



    private function countFiles($path){
        return iterator_count(new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS));
    }

}


?>
