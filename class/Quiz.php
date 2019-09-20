<?php

/*****************************************************************************
Quiz aims to pose a question that is easy for humans and difficult for an
automated robot. The quiz is rendered as a <html><div> containing a question
in the form of a randomized collection of visual elements. Care is taken to
include clues that are apparent to humans and opaque to robots; and to include
distractors that are apparent to robots and opaque to humans.

*****************************************************************************/


class Quiz {
    // contants used as keys in various arrays
    const LABEL = 'label';
    const OPERATOR = 'operator';
    const STYLE = 'style';
    const OPERAND = 'operand';

    // the type of questions able to be posed
    const TYPES = array('+');    // '*','↗', '?');

    // each slot in the quiz grid can contain a digit or letter
    const DIGITS = '0123456789';
    const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    // html styles are uses as both cues and distractors
    const STYLES = array('color','background-color','border-color','border-radius'); //,'border-width');
    const SLOTS = '123456789';
    const ARROWS = '↑→↓←↖↗↘↙';
    const POINT = array('4↑'=>'1','5↑'=>'2','6↑'=>'3','7↑'=>'14','8↑'=>'25','9↑'=>'36','1→'=>'23','2→'=>'3','4→'=>'56','5→'=>'6','7→'=>'89','8→'=>'9','1↓'=>'47','2↓'=>'58','3↓'=>'69','4↓'=>'7','5↓'=>'8','6↓'=>'9','2←'=>'1','3←'=>'12','5←'=>'4','6←'=>'45','8←'=>'7','9←'=>'78','5↖'=>'1','6↖'=>'2','8↖'=>'4','9↖'=>'15','↗'=>'','4↗'=>'2','5↗'=>'3','7↗'=>'5','8↗'=>'6','1↘'=>'59','2↘'=>'6','4↘'=>'7','5↘'=>'9','2↙'=>'4','3↙'=>'5','5↙'=>'7','6↙'=>'8');

    // base html styles for the quiz grid
    const BASE_ITEM_STYLE = array('text-align'=>'center','border-style'=>'solid','border-width'=>'1px','padding'=>'5px','font-weight'=>'bold');
    const DIV_STYLE = "width:100%;display:grid;grid-template-columns:auto auto auto;grid-gap:5% 5%;";
    
    private $items = array();        // one item for each slot in the quiz grid
    private $solution = array();
    private $div = "<div></div>";    // <html><div> containing the quiz grid

    // elements are removed from these arrays as they are used to build the quiz
    private $digits;
    private $letters;
    private $styles;
    private $slots;
    private $arrows;

    private $base_item_style;

    /**************************************************************************
    /* The constructor initializes variables; then builds the randomized quiz
    /*************************************************************************/
    public function __construct(){
        // initialize variables
        $this->digits  = str_shuffle(self::DIGITS);
        $this->letters = str_shuffle(self::LETTERS);
        $this->slots   = str_shuffle(self::SLOTS);
        $this->arrows  = self::ARROWS;
        $this->styles  = self::STYLES;
        shuffle($this->styles);
        $this->base_item_style = self::BASE_ITEM_STYLE;
        $this->solution[self::OPERAND] = array();
        $this->solution[self::STYLE] = array();
        for($i=1; $i<10; $i++){
            $this->items[$i] = array();
        }

        // select quiz type at random
        $type = self::TYPES[rand(1,count(self::TYPES))-1];

        // create the slots critical to the quiz solution
        $quizSlots = array();
        switch ($type){
            case '+': $quizSlots = $this->quizAdd(); break;
            case '*': $quizSlots = $this->quizMult(); break;
            case '↗': $quizSlots = $this->quizPoint(); break;
            default: $quizSlots = $this->quizAdd(); break;
        }

        // create contrast between the critical slots and the others
        $contrast = $this->labels();
        while($contrast < 2){
            $contrast++;
            $this->contrast($quizSlots);
        }

        // transform the slots into a final quiz <div>
        $this->div = $this->quizDiv();
    }


    function quiz(){
        return $this->div;
    }


    function id(){
        return md5($this->div);
    }


    function answer(){
        return $this->solution['answer'];
    }


    private function quizDiv(){
        $items = $this->items;
        $style = self::DIV_STYLE;
        $div = "<div style='$style'>\n";
        foreach($this->items as $item){
            $div .= $this->itemDiv($item) . "\n";
        }
        $div .= "</div>";
        return $div;
    }


    private function itemDiv($item){
        $label = $item[self::LABEL];
        unset($item[self::LABEL]);
        $styles = $this->base_item_style + $item;
        $keys = array_keys($styles);
        shuffle($keys);
        $style = '';
        foreach($keys as $key){
            $val = $styles[$key];
            $style .= "$key:$val;";
        }
        return "<div style='$style'>$label</div>";
    }

 
    private function solution($key, $val=NULL){
        if(isset($val)){
            if ($key == self::OPERAND
            || $key == self::STYLE){
                $this->solution[$key][] = $val;
            } else {
                $this->solution[$key] = $val;
            }
        }
        return isset($this->solution[$key]) ? $this->solution[$key] : NULL; 
    }


    private function labels($contrast=NULL){
        $contrast = isset($contrast) ? $contrast : rand(0,2);
        $solutionType = $this->solution('type');
        $type = ($contrast==0 || $contrast==2) ? $solutionType : 'mix';
        foreach($this->items as $slot => $item){
            if($contrast==2){    // set font-color = background-color
                $bgColor = $this->randomStyleValue('background-color');
                $fgColor = isset($item[self::LABEL]) 
                    ? $this->farStyleValue('color', $bgColor) 
                    : $this->nearStyleValue('color', $bgColor);
                $item['background-color'] = $bgColor;
                $item['color'] = $fgColor;
                $this->items[$slot] = $item;
            }
            if(!isset($item[self::LABEL])){
                switch($type){
                    case 'mix'   : $item[self::LABEL] = $this->rndLabel(FALSE);  break;
                    case 'digit' : $item[self::LABEL] = $this->rndDigit(FALSE);  break;
                    case 'letter': $item[self::LABEL] = $this->rndLetter(FALSE); break;
                }
                $this->items[$slot] = $item;
            }
        }
        if($contrast==2){
            $this->removeStyle('color');
            $this->removeStyle('background-color');
        }
        return $contrast;
    }


    private function contrast($quizSlots){
        $style = $this->rndStyle();
        $val1 = $this->randomStyleValue($style);
        $val2 = $this->farStyleValue($style, $val1);

        foreach($this->items as $slot => $item){
            $val = in_array($slot, $quizSlots) ? $val1 : $val2;
            $item[$style] = $this->nearStyleValue($style,$val);
            $this->items[$slot] = $item;
        }
    }


    /**************************************************************************
    / Create a quiz regarding the sum of 2 or 3 digits.
    /*************************************************************************/
    private function quizAdd($operands=NULL){
        $slots = array();

        $operands = isset($operands) ? $operands : rand(2,3);
        $answer = 0;
        for($op=1; $op<=$operands; $op++){
            $digit = $this->rndDigit(FALSE);
            $answer += $digit;
            $slot = $this->rndSlot();
            $this->items[$slot] = array(self::LABEL=>$digit);
            $slots[] = $slot;
        }

        $slot = $this->rndSlot();
        $this->items[$slot] = array(self::LABEL=>'+');
        $slots[] = $slot;

        $this->solution('operator','+');
        $this->solution('type','digit');
        $this->solution('answer', $answer);

        return $slots;
    }


    /**************************************************************************
    / Create a quiz regarding the product of 2 digits.
    /*************************************************************************/
    private function quizMult(){
        $slots = array();
        $answer = 1;
        for($op=1; $op<=2; $op++){
            $digit = $this->rndDigit(FALSE);
            $answer = $answer * $digit;
            $slot = $this->rndSlot();
            $this->items[$slot] = array(self::LABEL=>$digit);
            $slots[] = $slot;
        }

        $slot = $this->rndSlot();
        $this->items[$slot] = array(self::LABEL=>'*');
        $slots[] = $slot;

        $this->solution('operator','*');
        $this->solution('type','digit');
        $this->solution('answer', $answer);

        return $slots;
    }


    /**************************************************************************
    / Create a quiz involving an arrow pointing to the answer.
    /*************************************************************************/
    private function quizPoint(){
        $slots = array();

        $slot = $this->rndSlot();
        $arrow = $this->rndArrow(TRUE);
        while (!isset(self::POINT["$slot$arrow"])) {
            $slot = $this->rndSlot();
        }
        $this->items[$slot] = array(self::LABEL=>$arrow);
        $slots[] = $slot;

        $targets = self::POINT["$slot$arrow"];
        str_shuffle($targets);
        $slot = substr($targets,0,1);
        $answer = $this->rndDigit(TRUE);
        $this->items[$slot] = array(self::LABEL=>$answer);
        $slots[] = $slot;

        $this->solution('operator','↗');
        $this->solution('type','digit');
        $this->solution('answer', $answer);

        return $slots;
    }


    /**************************************************************************
    / return an available slot selected at random and remove from availability
    / if $exclusive == TRUE
    /*************************************************************************/
    private function rndSlot($exclusive=TRUE){
        $slot = NULL;
        if ($exclusive){
            $slot = substr($this->slots,0,1);
            $this->slots = substr($this->slots,1);
        } else {
            $this->slots = str_shuffle($this->slots);
            $slot = substr($this->slots,0,1);
        }
        return $slot;
    }


    /**************************************************************************
    / return an available digit selected at random and remove from availability
    / if $exclusive == TRUE
    /*************************************************************************/
    private function rndDigit($exclusive=FALSE){
        if ($exclusive){
            $a = substr($this->digits,0,1);
            $this->digits = substr($this->digits,1);
            return $a;
        } else {
            $this->digits = str_shuffle($this->digits);
            return substr($this->digits,0,1);
        }
    }


    /**************************************************************************
    / return an available letter selected at random and remove from
    / availability if $exclusive == TRUE
    /*************************************************************************/
    private function rndLetter($exclusive=FALSE){
        if ($exclusive){
            $a = substr($this->letters,0,1);
            $this->letters = substr($this->letters,1);
            return $a;
        } else {
            $this->letters = str_shuffle($this->letters);
            return substr($this->letters,0,1);
        }
    }


    /**************************************************************************
    / return an available arrow selected at random and remove from
    / availability if $exclusive == TRUE
    /*************************************************************************/
    private function rndArrow($exclusive=TRUE){
        $arrow = NULL;
        if ($exclusive){
            $arrow = substr($this->arrows,0,1);
            $this->arrows = substr($this->arrows,1);
        } else {
            $this->arrows = str_shuffle($this->arrows);
            $arrow = substr($this->arrows,0,1);
        }
        return $arrow;
    }


    /**************************************************************************
    / return an available label selected at random and remove from
    / availability if $exclusive == TRUE
    /*************************************************************************/
    private function rndLabel($exclusive=FALSE){
        $labels = str_shuffle($this->digits . $this->letters);
        $label = substr($labels,0,1);
        if ($exclusive){
            unset($this->letters[$label]);
            unset($this->digits[$label]);
        }
        return $label;
    }


    /**************************************************************************
    / return an available style selected at random and remove from
    / availability if $exclusive == TRUE
    /*************************************************************************/
    private function rndStyle($exclusive=TRUE){
        $style = NULL;
        if ($exclusive){
            $style = array_shift($this->styles);
        } elseif(count($this->styles)>0) {
            $this->styles = shuffle($this->styles);
            $style = $this->style[0];
        }
        switch($style){
            case 'border-color': $this->base_item_style['border-width'] = '3px'; break;
        }
        return $style;
    }


    /**************************************************************************
    / remove style from availabily
    /*************************************************************************/
    private function removeStyle($style){
        foreach($this->styles as $key => $val){
            if($val == $style){
                unset($this->styles[$key]);
            }
        }
    }


    /**************************************************************************
    / return a value for the $style, selected at random.
    /*************************************************************************/
    private function randomStyleValue($style){
        switch($style){
            case 'color':
            case 'border-color':
            case 'background-color':
                $h = rand(0,360);
                $s = rand(50,100);
                $l = rand(10,90);
                return "hsl($h,$s%,$l%)";
                break;
            case 'border-width':
                $width = rand(0,10);
                return "$width"."px";
                break;
            case 'border-radius':
                $rad = rand(15,50);
                return "$rad%";
                break;
        }
    }


    /**************************************************************************
    / return a value for the $style, selected at random, but near to $value.
    /*************************************************************************/
    private function nearStyleValue($style, $value){
        switch($style){
            case 'color':
            case 'border-color':
            case 'background-color':
                $hsl = explode(',', substr($value,4,-1));
                $h = $this->near($hsl[0],10,0,360);
                $s = $this->near(substr($hsl[1],0,-1),6,0,100);
                $l = $this->near(substr($hsl[2],0,-1),1,0,100);
                return "hsl($h,$s%,$l%)";
                break;
            case 'border-width':
                $width = substr($value,0,-2);
                $w = $this->near($width,1,0,10);
                return "$w"."px";
            case 'border-radius':
                $radius = substr($value,0,-1);
                $r = $this->near($radius,2,0,50);
                return "$r%";
                break;
        }
    }


    /**************************************************************************
    / return a value for the $style, selected at random, but far from $value.
    /*************************************************************************/
    private function farStyleValue($style, $value){
        switch($style){
            case 'color':
            case 'border-color':
            case 'background-color':
                $hsl = explode(',', substr($value,4,-1));
                $h = $this->far(array($hsl[0]),50,0,360);
                $s = $this->far(array(substr($hsl[1],0,-1)),30,0,100);
                $l = $this->far(array(substr($hsl[2],0,-1)),10,0,100);
                return "hsl($h,$s%,$l%)";
                break;
            case 'border-width':
                $width = substr($value,0,-2);
                $w = $this->far(array($width),5,0,10);
                return "$w"."px";
            case 'border-radius':
                $radius = substr($value,0,-1);
                $r = $this->far(array($radius),12,0,50);
                return "$r%";
        }
    }




    private function near($value, $radius=10, $min=0, $max=255){
        $lower = ($value - $radius) < $min ? $min : $value - $radius;
        $upper = ($value + $radius) > $max ? $max : $value + $radius;
        return rand($lower, $upper);
    }


    private function far($avoid=array(), $radius=30, $min=0, $max=100){
        $far = NULL;
        $valid = array();
        for($i=$min; $i<$max; $i++){
            $valid[$i] = $i;
        }

        foreach($avoid as $val){
            $lower = $val-$radius;
            $upper = $val+$radius;
            for($i=$lower; $i<=$upper; $i++){
                unset($valid[$i]);
            }
        }

        if(count($valid) > 0){
            shuffle($valid);
            $far = $valid[0];
        }

        return $far;
    }



} ?>
