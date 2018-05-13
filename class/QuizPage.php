<?php

require_once 'Page.php';
require_once 'Quiz.php';

class QuizPage extends Page {

    private $todo = 1;
    private $tally = 0;
    private $quizID = NULL;
    private $quizDiv = "";
    private $repost;

    public function __construct($template='quiz'){
        parent::__construct($template);
        $this->repost = $this->req('repost');
        $this->tally = isset($_SESSION['quiz_tally']) ? $_SESSION['quiz_tally'] : 0 ;
        $this->todo = isset($_SESSION['quiz_todo']) ? $_SESSION['quiz_todo'] : 1 ;
    }


    public function serve(){
        $this->processRequest();
        parent::serve();
    }


    public function processRequest(){
        $qid = $this->req('qid');
        if(isset($qid)){
            $answer = $_SESSION[$qid];
            unset($_SESSION[$qid]);    // one shot answer
            $reply = $this->req('reply');
            if(isset($reply)){
                if($reply == $answer){
                     $this->tally++;
                     $_SESSION['quiz_tally'] = $this->tally;
                } else {
                     $this->todo++;
                     $_SESSION['quiz_todo'] = $this->todo;
                }
            }
        }

        if($this->tally < $this->todo){  // serve up another quiz
            $quiz = new Quiz();
            $_SESSION[$quiz->id()] = $quiz->answer();
            $this->quizID = $quiz->id();
            $this->quizDiv = $quiz->quiz();
        } else {    // quiz passed! repost to destination page
            $QWIK_URL = self::QWIK_URL;
            $query = http_build_query($this->req());
            $repost = $this->repost;
            $url = "$QWIK_URL/$repost?$query";
            header("Location: $url");
        }
    }


    public function variables(){
        $vars = parent::variables();
        $tally = $this->tally;
        $todo = $this->todo;
	$vars['repost']   = $this->repost;
        $vars['progress'] = "$tally/$todo";
        $vars['quiz']     = $this->quizDiv;
        $vars['qid']      = $this->quizID;
        return $vars;
    }

}

?>
