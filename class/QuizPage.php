<?php

require_once 'Page.php';
require_once 'Quiz.php';

class QuizPage extends Page {

    const TALLY_KEY  = 'quiz_tally';
    const TODO_KEY   = 'quiz_todo';
    const SCORE_KEY  = 'quiz_score';
    const DELAY      = 0.01;

    private $todo = 1;
    private $tally = 0;
    private $progress = '';
    private $quizID = NULL;
    private $quizDiv = "";
    private $repost;

    public function __construct($templateName='quiz'){
        parent::__construct(NULL, $templateName);

        $this->repost = $this->req('repost');
        $this->tally = isset($_SESSION[self::TALLY_KEY]) ? $_SESSION[self::TALLY_KEY] : 0 ;
        $this->todo = isset($_SESSION[self::TODO_KEY]) ? $_SESSION[self::TODO_KEY] : 1 ;
        if(!isset($_SESSION[self::SCORE_KEY])){
            $_SESSION[self::SCORE_KEY] = '';
        }
    }


    public function processRequest(){
        $qid = $this->req('qid');
        if(!empty($qid)){
            $answer = $_SESSION[$qid];
            unset($_SESSION[$qid]);    // one shot answer
            $reply = $this->req('reply');
            if(!empty($reply)){
                if($reply == $answer){
                     $this->tally++;
                     $_SESSION[self::TALLY_KEY] = $this->tally;
                     $_SESSION[self::SCORE_KEY] .= '☑';
                } else {
                     $this->todo++;
                     $_SESSION[self::TODO_KEY] = $this->todo;
                     $_SESSION[self::SCORE_KEY] .= '☒';
                }
            }
        }

        if($this->tally < $this->todo){  // serve up another quiz
            $this->rateLimit();
            $quiz = new Quiz();
            $_SESSION[$quiz->id()] = $quiz->answer();
            $this->quizID = $quiz->id();
            $this->quizDiv = $quiz->quiz();
        } else {    // quiz passed! repost to destination page
            unset($_SESSION[self::TALLY_KEY]);    // reset tally
            unset($_SESSION[self::TODO_KEY]);     // reset todo
            unset($_SESSION[self::SCORE_KEY]);    // reset progress
            $QWIK_URL = self::QWIK_URL;
            $query = http_build_query($this->req());
            $repost = $this->repost;
            $url = "$QWIK_URL/$repost?$query";
            header("Location: $url");
        }
    }


    public function variables(){
        $vars = parent::variables();
        $progress = "{quiz}";
        if($this->todo > 1){
            $progress = $_SESSION[self::SCORE_KEY];
            $remaining = $this->todo - $this->tally;
            $progress .= str_repeat('☐',$remaining);
        }
	$vars['repost']   = $this->repost;
        $vars['progress'] = $progress;
        $vars['quiz']     = $this->quizDiv;
        $vars['qid']      = $this->quizID;
        return $vars;
    }


    private function rateLimit(){
        $delay = self::DELAY;
        $todo = $this->todo;
        sleep($delay ^ $todo);
    }

}

?>
