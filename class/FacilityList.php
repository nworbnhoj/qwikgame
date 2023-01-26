<?php

require_once 'Card.php';

/*******************************************************************************
    Class FacilityList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class FacilityList extends Card {


    /*******************************************************************************
    Class FacilityList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html, $id=NULL){
        parent::__construct($html, $id);
    }   


    protected function loadUser($uid){
        return new Manager($uid);
    }


    public function replicate($html, $variables){
        $manager = $this->manager();
        if (is_null($manager)){ return '';}
        $venue = $manager->venue();

        // get the current Venue YYY-MM-DD and Ddd (for today & tomorrow) 
        $tod = $venue->dateTime('now');
        $todYmd = $tod->format('Y-m-d');
        $todD = $tod->format('D');
        $tom = $tod->add(new DateInterval("P1D"));
        $tomYmd = $tom->format('Y-m-d');
        $tomD = $tom->format('D');

        $group = '';
        $facilities = $venue->facility();
        foreach($facilities as $facility){
            $game = (string) $facility['game'];
            // I think that this is correct .....
            //     $today = $facility->xpath("//hrs[@day='$todYmd']");
            // but is seems to return what I would expect from .....
            //     $today = $facilities->xpath("//hrs[@day='$todYmd']");
            // And the folowing works perfectly - but I have no idea why ?????
            $today = $facility->xpath("//facility[@game='$game']/hrs[@day='$todYmd']");
            if (empty($today)){
               $today = $facility->xpath("//facility[@game='$game']/hrs[@day='$todD']");
               if (empty($today)){
                   $today = array(0);
               }
            }

            $tomorrow = $facility->xpath("//facility[@game='$game']/hrs[@day='$tomYmd']");
            if (empty($tomorrow)){
               $tomorrow = $facility->xpath("//facility[@game='$game']/hrs[@day='$tomD']");
               if (empty($tomorrow)){
                   $tomorrow = array(0);
               }
            }

            $days = array(
                'today'    => (int) $today[0],
                'tomorrow' => (int) $tomorrow[0],
            );

            $facilityVars = array(
                'id'        => (string) $facility['id'],
                'vid'       => $venue->id(),
                'game'      => $game,
                'gameName'  => self::gameName($game),
                'hourRows'  => self::hourRows($days),
                'weekSpan'  => self::weekSpan($facility)
            );
            $vars = $facilityVars + $variables;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
