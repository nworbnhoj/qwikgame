<?php

require_once 'Node.php';
require_once 'Player.php';
require_once 'Qwik.php';


class Orb extends Qwik {

    private $game;
    private $nodes;

    public function __construct($game){
        $this->game = $game;
        $this->nodes = array();
    }


    public function empty(){
        return empty($this->nodes);
    }


    public function wipe($key=NULL){
        if(is_null($key)) {
            unset($this->nodes);
            $this->nodes = array();
        } else {
            unset($this->nodes[$key]);
        }
    }


    public function print($tabs="\t"){
        $game = $this->game;
        $nodes = $this->nodes;
        $count = count($nodes);
        $str = "Orb: $game ($count)";
        foreach($nodes as $node){
            $rid = $node->rid();
            $parity = $node->parity();
            $rely = $node->rely();
            $str .= "\n$tabs" . self::snip($rid) . " $parity $rely";
            $orb = $node->orb($this->game);
            if(!$orb->empty()){
                $str .= $orb->print("$tabs\t");
            }
        }
        return $str;
    }


    public function addNode($rid, $parity, $rely, $date){
        $this->nodes[] = new Node($rid, $parity, $rely, $date);
    }


    public function addNodes($orb){
        $this->nodes = array_merge($orb->nodes, $this->nodes);
    }


/********************************************************************************
Returns an estimate of the parity of a player to $rival

$orb    ArrayMap  The orb of the player, pre-pruned to contain paths only to the $rival
$rival  XML       player data of the rival

Computes the numeric Parity of the root of the $orb to the $rivalID.
Some examples:
    A>B & B<A implies A>B
    A>B & B=C implies A>C
    A>B & B=C & C=D implies A>D

This is a recursive function that computes the weighted average of each parity
path to the rival. There are two computations happening here; one of breadth
(multiple paths) and one of length (long parity chains).
- Depth. Each of several outcome are combined by weighted average, where the
weight is the reliability of the player (node)
- Length. A long chain of outcomes linking one player to another is combined
by adding the parities to account for stronger outcomes (+1), and much-weaker
(-2) outcomes for example. However shorter chains are given more weight than
longer chains by introducing a decay (=0.9) at each link.
********************************************************************************/
    public function parity($rivalID){
//echo "<br>PARITYORB rid=$rivalID<br>\n";
        $rivalID = Player::subID($rivalID);
        $relyChainDecay = 0.7;
        $parityTotal = 0.0;
        $relyTotal = 0.0;
        $n=0;
        foreach($this->nodes as $node){
            $parity = $node->parity();
            $subOrb = $node->orb($this->game);
            if (($node->rid() != $rivalID)
            && (isset($subOrb))){
//print_r("\n\t" . self::snip($node->rid()) . "\t" . self::snip($rivalID) . "\n");            
                $subParity = $subOrb->parity($rivalID);
                if (!is_null($subParity)){
                    $parity += $subParity * $relyChainDecay;
//print_r("\t$parity += $subParity * $relyChainDecay\t\tAAA\n");                    
                }             
            }
            $rely = $node->rely();
            $relyTotal += $rely;
            $parityTotal += $parity * $rely;      // note rely range [0,4]
//print_r("\t$parityTotal += $parity * $rely\t\tBBB\n");            
            $n++;
        }
        if ($n>0 && $relyTotal>0) {
            $relyAverage = $relyTotal / $n;
            $parityAverage = $parityTotal / ($n * $relyAverage);
//print_r("\t$parityAverage = $parityTotal / ($n * $relyAverage)\t\tCCC\n");            
        } else {
            $parityAverage = null;
        }
        return $parityAverage;
    }



/********************************************************************************
Retuns an Array mapping each rivalID to an array of 'inverted' nodes suitable to
be passed to function spiceOrb()

$orb    ArrayMap  the orb to be inverted
$pid    String    The unique PlayerID at the root of the $orb

An Orb contains a tree like structure of nodes. This function returns an Array
indexed by each of the rivalID's found in the Orb. Each rivalID is mapped to an
Array of Nodes found in the Tree and 'inverted' by swapping the ID's of Player
and Rival, and by negating the parity. These 'inverted' nodes are suitable for
passing to function spliceOrb() to be inserted into the corresponding rival orb.

********************************************************************************/
    public function inv($pid){
    //echo "function orbInv()";
        $inv = array();
        foreach($this->nodes as $node){
            $rid = $node->rid();
            if (!array_key_exists($rid, $inv)){
                $inv[$rid] = array();
            }
            $inv[$rid][] = new Node($pid, -1 * $node->parity(), $node->rely());

            // recursion
            $orb = $node->orb($this->game);
            if(!$orb->empty()){
                $subOrbInv = $orb->inv($node->rid());
                foreach ($subOrbInv as $rid => $subNode) {
                    if (!array_key_exists($rid, $inv)){
                        $inv[$rid] = array();
                    }
                    $inv[$rid] = array_merge($inv[$rid], $subNode);
                }
            }
        }
        return $inv;
    }



/********************************************************************************
Splices 2 orbs together by inserting 'inverted' Nodes from a Rivel Orb into $orb.

$orb    ArrayMap  the orb to be spliced
$pid    String    the unique PlayerID at the root of the $orb
$invOrb Arraymap  an Array mapping each rivalID to an array of nodes

This function traverses the $orb and inserts the Nodes from $invOrb into the
structure. The function orbInv() can prepar the nodes by swapping Player and Rival
and by negating Parity.

********************************************************************************/
    public function splice($pid, $invOrb){
    //echo "<br>SPLICEORB</br>";

        $pid = (string)$pid;
        if (array_key_exists($pid, $invOrb)){
            $invNodes = $invOrb[$pid];
            foreach ($invNodes as $invNode) {
                $this->nodes[] = $invNode;
            }
        }

        foreach($this->nodes as &$node){
            $rid = $node->rid();
            $node->orb($this->game)->splice($rid, $invOrb);
        }
        return $this;
    }



    /********************************************************************************
    Returns a player orb expanded out to include one addition set of relations from
    the edge.

    $crumbs  ArrayMap  node => a node closer to root

    ********************************************************************************/
    public function expand($crumbs){
        foreach($this->nodes as &$node){
            $rid = $node->rid();
            $nodeOrb = $node->orb($this->game);
            if(!$nodeOrb->empty()){
                $nodeOrbCrumbs = $nodeOrb->expand($crumbs);    //recursion
                $crumbs = array_merge($nodeOrbCrumbs, $crumbs);
            } elseif(!in_array($rid, $crumbs)){
                $rival = new Player($rid);
                if ($rival->exists()){
                    $nodeOrb->addNodes($rival->orb($this->game, $crumbs, FALSE));
                    $crumbs = array_merge($nodeOrb->crumbs($rid), $crumbs);
                    $rival->save();
                }
            }
        }
        return $crumbs;
    }



    /********************************************************************************
    Returns an ArrayMap of node => node next closest to orb root.

    $orbID   String    The player ID at the immediate base of the $orb
    $rootID  String    The player ID at the ultimate root of the $orb

    Each player's orb can be traversed outwards from one node to the next;
    but not in inwards direction (of course there are loops). This function
    constructs the shortest bread-crumb trails back to the root.
    ********************************************************************************/
    public function crumbs($orbID, $rootID){
        $crumbs = array();
        $orbID = Player::subID($orbID);
        foreach($this->nodes as $node){
            $rid = Player::subID($node->rid());
            if ($rid != $rootID){
                $nodeOrb = $node->orb($this->game);
                $nodeOrbCrumbs = $nodeOrb->crumbs($rid, $rootID);    // recursion
                $crumbs = array_merge($nodeOrbCrumbs, $crumbs);
                $crumbs[$rid] = $orbID;    // this is the shortest path
            }
        }
        return $crumbs;
    }




    private function depth($id, $crumbs){
        return array_key_exists($id, $crumbs)
            ? $this->depth($crumbs[$id], $crumbs) + 1
            : 0 ;
    }



    /********************************************************************************
    Returns the $orb with all nodes removed that are not in $keepers,
    and all denuded branches removed.


    $orb        ArrayMap    the orb to be pruned
    $keepers    Array        nodes to be retained

    ********************************************************************************/
    public function prune($keepers){
        //echo "PRUNEORB<br><br>\n";
        foreach($this->nodes as $key => &$node){
            $subOrb = $node->orb($this->game);
            if(!$subOrb->empty()){
                if($subOrb->prune($keepers)){
                    $subOrb->wipe();
                }
            } elseif(!in_array($node->rid(), $keepers)){
                $subOrb->wipe($key);
            }
        }
        return $this->size() == 0; //denuded branch
    }



    public function size(){
        return count($this->nodes);
    }


}

?>