<?php 

const SUBDOMAIN = 'www';
const QWIK_URL = 'http://'.SUBDOMAIN.'.qwikgame.org';

include 'Player.php';
include 'Venue.php';


$languages = array(
    'zh'=>'中文',
    'es'=>'Español',
    'en'=>'English',
    // 'fr'=>'français',
    // 'hi'=>'हिन्दी भाषा',
    // 'ar'=>'اللغة العربية',
    // 'jp'=>'日本語'
);


// include language translations
foreach($languages as $code => $language){
    include "lang/$code.php";
}


const BACK_ICON      = 'fa fa-chevron-circle-left icon';
const COMMENT_ICON   = 'fa fa-comment-o comment';
const CROSS_ICON     = 'fa fa-times-circle cross';
const EMAIL_ICON     = 'fa fa-envelope-o icon';
const FACEBOOK_ICON  = 'fa fa-facebook icon';
const FEMALE_ICON    = 'fa fa-female person';
const HOME_ICON      = 'fa fa-home icon';
const INFO_ICON      = 'fa fa-question-circle icon';
const RELOAD_ICON    = 'fa fa-refresh icon';
const LANG_ICON      = 'fa fa-globe icon';
const LOGOUT_ICON    = 'fa fa-power-off icon';
const MALE_ICON      = 'fa fa-male person';
const MAP_ICON       = 'fa fa-map-marker';
const SEND_ICON      = 'fa fa-send';
const THUMB_DN_ICON  = 'fa fa-thumbs-o-down thumb red';
const THUMB_UP_ICON  = 'fa fa-thumbs-o-up thumb green';
const TICK_ICON      = 'fa fa-check-circle tick';
const TWITTER_ICON   = 'fa fa-twitter icon';

const FLYER_URL = QWIK_URL.'/pdf/qwikgame.org%20flyer.pdf';
const TERMS_URL = QWIK_URL.'/pdf/qwikgame.org%20terms%20and%20conditions.pdf';
const PRIVACY_URL = QWIK_URL.'/pdf/qwikgame.org%20privacy%20policy.pdf';
const FACEBOOK_URL = 'https://www.facebook.com/sharer/sharer.php?u='.QWIK_URL;
const TWITTER_URL = 'https://twitter.com/intent/tweet?text=<t>tagline</t>&url='.QWIK_URL;

const EMAIL_IMG = "<img src='img/email.png' alt='email' class='socialmedia'>";
const FACEBOOK_IMG = "<img src='img/facebook.png' alt='facebook' class='socialmedia'>";
const TWITTER_IMG = "<img src='img/twitter.png' alt='twitter' class='socialmedia'>";

const EMAIL_LNK = "<a href='mailto:?subject=".QWIK_URL."&body=".QWIK_URL."%20makes%20it%20easy%20to%20<t>tagline</t>&target=_blank'>".EMAIL_IMG."</a>";
const FACEBOOK_LNK = "<a href='".FACEBOOK_URL."' target='_blank'>".FACEBOOK_IMG."</a>";
const FLYER_LNK = "<a href='".FLYER_URL."' target='_blank'><t>flyer</t></a>";
const TWITTER_LNK = "<a href='".TWITTER_URL."' target='_blank'>".TWITTER_IMG."</a>";

const CC_ICON_LINK = "
    <a rel='license' href='http://creativecommons.org/licenses/by/4.0/'>
        <img alt='Creative Commons License' 
            style='border-width:0' 
            src='https://i.creativecommons.org/l/by/4.0/88x31.png' />
    </a>";
const CC_ATTR_LINK = "
    <a xmlns:cc='http://creativecommons.org/ns#' 
        href='qwikgame.org' 
        property='cc:attributionName' 
        rel='cc:attributionURL'>
        qwikgame.org
    </a>";
const CC_LICENCE_LINK = "
    <a rel='license' 
        href='http://creativecommons.org/licenses/by/4.0/'>
        <t>Creative Commons Attribution 4.0 International License</t>
    </a>";

const REVERT_CHAR = '⟲';



$geo;
$star = '★';
$stars = array("","$star","$star$star","$star$star$star","$star$star$star$star","$star$star$star$star$star");
$clock24hr = FALSE;
$tick = 'fa-check-circle';
$cross = 'fa-times-circle';
$help = 'fa-question-circle';
$home = 'fa-home';
$reload = 'fa-refresh';
$revert = '⟲';
$back = '⤺';
$bug = '☹';
$logout = 'fa-power-off';

$qwiks=array('accept','account','activate','available','cancel','deactivate','decline','delete','familiar','feedback','keen','login','logout','msg','recover','region','upload');

$parityExp=array();
$parityExp[-2]    = 'much weaker';
$parityExp[-1] = 'weaker';
$parityExp[0]    = 'well matched';
$parityExp[1]    = 'stronger';
$parityExp[2]    = 'much stronger';

$parityFilter = array('any','similar','matching', '-2', '-1', '0', '1', '2');



$games = array(
    'backgammon'  => '<t>Backgammon</t>',
    'badminton'   => '<t>Badminton</t>',
    'boules'      => '<t>Boules</t>',
    'billards'    => '<t>Billiards</t>',
    'checkers'    => '<t>Checkers</t>',
    'chess'       => '<t>Chess</t>',
    'cycle'       => '<t>Cycle</t>',
    'darts'       => '<t>Darts</t>',
    'dirt'        => '<t>Dirt Biking</t>',
    'fly'         => '<t>Fly Fishing</t>',
    'go'          => '<t>Go</t>',
    'golf'        => '<t>Golf</t>',
    'lawn'        => '<t>Lawn Bowls</t>',
    'mtnbike'     => '<t>Mountain_Biking</t>',
    'pool'        => '<t>Pool</t>',
    'racquetball' => '<t>Racquetball</t>',
    'run'         => '<t>Run</t>',
    'snooker'     => '<t>Snooker</t>',
    'squash'      => '<t>Squash</t>',
    'table'       => '<t>Table_Tennis</t>',
    'tennis'      => '<t>Tennis</t>',
    'tenpin'      => '<t>Tenpin</t>',
    'walk'        => '<t>Walk</t>'
);

$status = array(
    'keen'       => 1,
    'invitation' => 2,
    'accepted'   => 3,
    'confirmed'  => 4,
    'feedback'   => 5,
    'history'    => 6,
    'cancelled'  => 10
);




# SECURITY escape all parameters to prevent malicious code insertion
# http://au.php.net/manual/en/function.htmlentities.php
function SECURITYsanitizeHTML($data){
    if (is_array($data)){
        foreach($data as $key => $val){
            $data[$key] = SECURITYsanitizeHTML($val);
        }
    } else {
        $data = htmlentities(trim($data), ENT_QUOTES | ENT_HTML5);
    }
    return $data;
}


// https://stackoverflow.com/questions/5647461/how-do-i-send-a-post-request-with-php
function post($url, $data){
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    
    var_dump($result);
}




    

/********************************************************************************
Returns a new DateTime object for the time string and time-zone requested

$str    String    time & date
$tz        String    time-zone
********************************************************************************/
function tzDateTime($str='now', $tz){
//echo "<br>VENUEDATETIME $str</br>" . $venue['tz'];
    if(empty($tz)){
        return new DateTime($str);
    }
    return new DateTime($str, timezone_open($tz));
}



////////// VALIDATE ////////////////////////////////////////




/********************************************************************************
Post an explanation of a failed post&get request to error.php

$req    ArrayMap    url parameters from post&get
$msg    String        An explanatory message to display to the user at error.php
********************************************************************************/
function invalidRequest($post, $get, $msg){
    $str = '<b>POST</b><br>';
    foreach($post as $key => $val){
        $str .= "$key => $val<br>";
    }
    $str .= '<b>GET</b><br>';
    foreach($get as $key => $val){
        $str .= "$key => $val<br>";
    }
    header("Location: error.php?msg=<u>$msg</u><br>$str");
}



function subID($id){
    return (string)$id;
    return substr("$id",0, 10);
}




////////////////////// TIME //////////////////////////////

// handy bitfield constants
$Hrs24 = 33554431;
$Hrs6amto8pm = 16777215;


function hour2bit($hour){
    if ($hour < 0 || $hour >= 24) {
        return 0;
    }
    return 2 ** $hour;
}

function bit2hour(){
    $mask = 1;
    for ($hour = 0; $hour < 24; $hour++){
        if ($bits & $mask){
            return $hour;
        }
        $mask = $mask * 2;
    }    
}

/*******************************************************************************

*******************************************************************************/
function hours2bits($request, $day){
    $bitfield = 0;
    for ($hour = 0; $hour < 24; $hour++){
        $name = "$day$hour";
        if(isset($request[$name])){
            $bitfield += $request[$name];
        }
    } 
    return $bitfield;
}


// Accepts a bitfield representing the 24hrs in a day and returns and array of hours
function hours($bits){
    $hours = array();
    $mask = 1;
    for ($hour = 0; $hour < 24; $hour++){
        if ($bits & $mask){
            $hours[] = $hour ;
        }
        $mask = $mask * 2;
    }    
    return $hours;
}


function addHoursXML($element, $request, $day){
    $hourBits = hours2bits($request, $day);
    if ($hourBits > 0){
        $element->addChild($day, hours2bits($request,$day));
        return True;
    }
    return FALSE;
}


function day($tz, $dateStr){
    $date = tzDateTime($dateStr, $tz);
    $today = tzDateTime('today', $tz);
    $interval = $today->diff($date);
    switch ($interval->days) {
        case 0: return 'today'; break;
        case 1: return $interval->invert ? 'yesterday' : 'tomorrow'; break;
        default:
            return $date->format('jS M');
    }
}


function hr($hr){
    global $clock24hr;
    $apm = ':00';
    if (!$clock24hr){
        if ($hr < 12){
            $apm = 'am';
        } elseif ($hr > 12) {
            $hr = $hr - 12;
            $apm = 'pm';
        } else {
            $apm = 'pm';
        }
    }
    return "$hr$apm";
}


function snip($str){
    return substr($str, 0, 4);
}



// https://secure.php.net/manual/en/class.simplexmlelement.php
// Must be tested with ===, as in if(isXML($xml) === true){}
// Returns the error message on improper XML
function isXML($xml){
    libxml_use_internal_errors(true);

    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->loadXML($xml);

    $errors = libxml_get_errors();

    if(empty($errors)){
        return true;
    }

    $error = $errors[0];
    if($error->level < 3){
        return true;
    }

    $explodedxml = explode("r", $xml);
    $badxml = $explodedxml[($error->line)-1];

    $message = $error->message . ' at line ' . $error->line . '. Bad XML: ' . htmlentities($badxml);
    return $message;
}


function deleteFile($file){
//echo "<br>DELETEFILE $file<br>";
//    $fileName = realpath("$file");

    if (is_writable($file)){
//echo "<br>$file is writeable<br>";
        return unlink($file);
    }
    return false;
}


function lockXML($xml, $token){
    $nekot = hash('sha256', $token);
    if (isset($token)){
        if (isset($xml['lock'])){
            $xml['lock'] = $token;
        } else {
            $xml->addAttribute('lock', $token);
        }
    }
}


function unlockXML($xml, $token){
    $nekot = hash('sha256', $token);
    $locked = $xml->xpath("//*[@lock='$token']");
    foreach($locked as $open){
        removeAtt($open, 'lock');
    }
}


function isLocked($xml){
//    return ! empty($xml['lock']);
    return isset($xml['lock']) && strlen($xml['lock']) > 0;
}


// https://stackoverflow.com/questions/720751/how-to-read-a-list-of-files-from-a-folder-using-php
function fileList($dir){
    $fileList = array();
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $fileList[] = $file;
            }
            closedir($dh);
        }
    }
    return $fileList;
}


function venues($game){
    $venues = array();
    $path = "venue";
    $path .= $game ? "/$game" : '';
    $fileList = fileList($path);
    foreach($fileList as $file){
        if (substr_count($file, '.xml') > 0){
            $venues[] = str_replace('.xml', '', $file);
        }
    }
    return $venues;
}


function pids($game){
    $pids = array();
    $fileList = fileList("player");
    foreach($fileList as $file){
        if (substr_count($file, '.xml') > 0){
            $pids[] = str_replace('.xml', '', $file);
        }
    }
    return $pids;
}


function trim_value(&$value) 
{ 
    $value = trim($value); 
}



// https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425 
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}


function newID($len = 6){
    return generateRandomString($len);
}


function newToken($len = 10){
    return generateRandomString($len);
}


function removeElement($xml){
    $dom=dom_import_simpleXML($xml);
    $dom->parentNode->removeChild($dom);
}

function removeAtt($xml, $att){
    $dom=dom_import_simpleXML($xml);
    $dom->removeAttribute($att);
}



function daySpan($bits, $day=''){
    global $clock24hr;
    $hours = hours($bits);
    if (count($hours) > 0){
        $dayX = substr($day, 0, 3);
        $dayP = $clock24hr ? 0 : 12;

        if (count($hours) == 24){
            return "<span class='lolite'><b>$dayX</b></span>";
        } else {
            $str =  $clock24hr ? $dayX : '';
            $last = null;
            foreach($hours as $hr){
                $pm = $hr > 12;
                $consecutive = $hr == ($last + 1);
                $str .= $consecutive ? "&middot" : clock($last) . ' ';
    
                if ($pm && !$clock24hr) {
                    $str .= "<b>$dayX</b>";
                    $dayX = '';
                }

                $str .= $consecutive ? '' : " " . clock($hr);
                $last = $hr;
            }
            $str .= $consecutive ? clock($last) : '';
            return "<span class='lolite'>$str</span>";
        }
    }
    return "";
}


function clock($hr){
    global $clock24hr;
    return (($hr > 12) && !$clock24hr) ? $hr-12 : $hr;
}


function parityStr($parity){
//echo "<br>PARITYSTR $parity<br>";
    if(!is_numeric("$parity")){
        return '';
    }

    $pf = floatval($parity);
    if($pf <= -2){
        return "<t>much_weaker</t>";
    } elseif($pf <= -1){
        return "<t>weaker</t>";
    } elseif($pf < 1){
        return "<t>well_matched</t>";
    } elseif($pf < 2){
        return "<t>stronger</t>";
    } else {
        return "<t>much_stronger</t>";
    }
}


$countries = array();
$countries['AF'] = "Afghanistan";
$countries['AX'] = "Åland Islands";
$countries['AL'] = "Albania";
$countries['DZ'] = "Algeria";
$countries['AS'] = "American Samoa";
$countries['AD'] = "Andorra";
$countries['AO'] = "Angola";
$countries['AI'] = "Anguilla";
$countries['AQ'] = "Antarctica";
$countries['AG'] = "Antigua and Barbuda";
$countries['AR'] = "Argentina";
$countries['AM'] = "Armenia";
$countries['AW'] = "Aruba";
$countries['AU'] = "Australia";
$countries['AT'] = "Austria";
$countries['AZ'] = "Azerbaijan";
$countries['BS'] = "Bahamas";
$countries['BH'] = "Bahrain";
$countries['BD'] = "Bangladesh";
$countries['BB'] = "Barbados";
$countries['BY'] = "Belarus";
$countries['BE'] = "Belgium";
$countries['BZ'] = "Belize";
$countries['BJ'] = "Benin";
$countries['BM'] = "Bermuda";
$countries['BT'] = "Bhutan";
$countries['BO'] = "Bolivia, Plurinational State of";
$countries['BQ'] = "Bonaire, Sint Eustatius and Saba";
$countries['BA'] = "Bosnia and Herzegovina";
$countries['BW'] = "Botswana";
$countries['BV'] = "Bouvet Island";
$countries['BR'] = "Brazil";
$countries['IO'] = "British Indian Ocean Territory";
$countries['BN'] = "Brunei Darussalam";
$countries['BG'] = "Bulgaria";
$countries['BF'] = "Burkina Faso";
$countries['BI'] = "Burundi";
$countries['KH'] = "Cambodia";
$countries['CM'] = "Cameroon";
$countries['CA'] = "Canada";
$countries['CV'] = "Cape Verde";
$countries['KY'] = "Cayman Islands";
$countries['CF'] = "Central African Republic";
$countries['TD'] = "Chad";
$countries['CL'] = "Chile";
$countries['CN'] = "China";
$countries['CX'] = "Christmas Island";
$countries['CC'] = "Cocos (Keeling) Islands";
$countries['CO'] = "Colombia";
$countries['KM'] = "Comoros";
$countries['CG'] = "Congo";
$countries['CD'] = "Congo, the Democratic Republic of the";
$countries['CK'] = "Cook Islands";
$countries['CR'] = "Costa Rica";
$countries['CI'] = "Côte d'Ivoire";
$countries['HR'] = "Croatia";
$countries['CU'] = "Cuba";
$countries['CW'] = "Curaçao";
$countries['CY'] = "Cyprus";
$countries['CZ'] = "Czech Republic";
$countries['DK'] = "Denmark";
$countries['DJ'] = "Djibouti";
$countries['DM'] = "Dominica";
$countries['DO'] = "Dominican Republic";
$countries['EC'] = "Ecuador";
$countries['EG'] = "Egypt";
$countries['SV'] = "El Salvador";
$countries['GQ'] = "Equatorial Guinea";
$countries['ER'] = "Eritrea";
$countries['EE'] = "Estonia";
$countries['ET'] = "Ethiopia";
$countries['FK'] = "Falkland Islands (Malvinas)";
$countries['FO'] = "Faroe Islands";
$countries['FJ'] = "Fiji";
$countries['FI'] = "Finland";
$countries['FR'] = "France";
$countries['GF'] = "French Guiana";
$countries['PF'] = "French Polynesia";
$countries['TF'] = "French Southern Territories";
$countries['GA'] = "Gabon";
$countries['GM'] = "Gambia";
$countries['GE'] = "Georgia";
$countries['DE'] = "Germany";
$countries['GH'] = "Ghana";
$countries['GI'] = "Gibraltar";
$countries['GR'] = "Greece";
$countries['GL'] = "Greenland";
$countries['GD'] = "Grenada";
$countries['GP'] = "Guadeloupe";
$countries['GU'] = "Guam";
$countries['GT'] = "Guatemala";
$countries['GG'] = "Guernsey";
$countries['GN'] = "Guinea";
$countries['GW'] = "Guinea-Bissau";
$countries['GY'] = "Guyana";
$countries['HT'] = "Haiti";
$countries['HM'] = "Heard Island and McDonald Islands";
$countries['VA'] = "Holy See (Vatican City State)";
$countries['HN'] = "Honduras";
$countries['HK'] = "Hong Kong";
$countries['HU'] = "Hungary";
$countries['IS'] = "Iceland";
$countries['IN'] = "India";
$countries['ID'] = "Indonesia";
$countries['IR'] = "Iran, Islamic Republic of";
$countries['IQ'] = "Iraq";
$countries['IE'] = "Ireland";
$countries['IM'] = "Isle of Man";
$countries['IL'] = "Israel";
$countries['IT'] = "Italy";
$countries['JM'] = "Jamaica";
$countries['JP'] = "Japan";
$countries['JE'] = "Jersey";
$countries['JO'] = "Jordan";
$countries['KZ'] = "Kazakhstan";
$countries['KE'] = "Kenya";
$countries['KI'] = "Kiribati";
$countries['KP'] = "Korea, Democratic People's Republic of";
$countries['KR'] = "Korea, Republic of";
$countries['KW'] = "Kuwait";
$countries['KG'] = "Kyrgyzstan";
$countries['LA'] = "Lao People's Democratic Republic";
$countries['LV'] = "Latvia";
$countries['LB'] = "Lebanon";
$countries['LS'] = "Lesotho";
$countries['LR'] = "Liberia";
$countries['LY'] = "Libya";
$countries['LI'] = "Liechtenstein";
$countries['LT'] = "Lithuania";
$countries['LU'] = "Luxembourg";
$countries['MO'] = "Macao";
$countries['MK'] = "Macedonia, the former Yugoslav Republic of";
$countries['MG'] = "Madagascar";
$countries['MW'] = "Malawi";
$countries['MY'] = "Malaysia";
$countries['ML'] = "Mali";
$countries['MT'] = "Malta";
$countries['MH'] = "Marshall Islands";
$countries['MQ'] = "Martinique";
$countries['MR'] = "Mauritania";
$countries['MU'] = "Mauritius";
$countries['YT'] = "Mayotte";
$countries['MX'] = "Mexico";
$countries['FM'] = "Micronesia, Federated States of";
$countries['MD'] = "Moldova, Republic of";
$countries['MC'] = "Monaco";
$countries['MN'] = "Mongolia";
$countries['ME'] = "Montenegro";
$countries['MS'] = "Montserrat";
$countries['MA'] = "Morocco";
$countries['MZ'] = "Mozambique";
$countries['MM'] = "Myanmar";
$countries['NA'] = "Namibia";
$countries['NR'] = "Nauru";
$countries['NP'] = "Nepal";
$countries['NL'] = "Netherlands";
$countries['NC'] = "New Caledonia";
$countries['NZ'] = "New Zealand";
$countries['NI'] = "Nicaragua";
$countries['NE'] = "Niger";
$countries['NG'] = "Nigeria";
$countries['NU'] = "Niue";
$countries['NF'] = "Norfolk Island";
$countries['MP'] = "Northern Mariana Islands";
$countries['NO'] = "Norway";
$countries['OM'] = "Oman";
$countries['PK'] = "Pakistan";
$countries['PW'] = "Palau";
$countries['PS'] = "Palestinian Territory, Occupied";
$countries['PA'] = "Panama";
$countries['PG'] = "Papua New Guinea";
$countries['PY'] = "Paraguay";
$countries['PE'] = "Peru";
$countries['PH'] = "Philippines";
$countries['PN'] = "Pitcairn";
$countries['PL'] = "Poland";
$countries['PT'] = "Portugal";
$countries['PR'] = "Puerto Rico";
$countries['QA'] = "Qatar";
$countries['RE'] = "Réunion";
$countries['RO'] = "Romania";
$countries['RU'] = "Russian Federation";
$countries['RW'] = "Rwanda";
$countries['BL'] = "Saint Barthélemy";
$countries['SH'] = "Saint Helena, Ascension and Tristan da Cunha";
$countries['KN'] = "Saint Kitts and Nevis";
$countries['LC'] = "Saint Lucia";
$countries['MF'] = "Saint Martin (French part)";
$countries['PM'] = "Saint Pierre and Miquelon";
$countries['VC'] = "Saint Vincent and the Grenadines";
$countries['WS'] = "Samoa";
$countries['SM'] = "San Marino";
$countries['ST'] = "Sao Tome and Principe";
$countries['SA'] = "Saudi Arabia";
$countries['SN'] = "Senegal";
$countries['RS'] = "Serbia";
$countries['SC'] = "Seychelles";
$countries['SL'] = "Sierra Leone";
$countries['SG'] = "Singapore";
$countries['SX'] = "Sint Maarten (Dutch part)";
$countries['SK'] = "Slovakia";
$countries['SI'] = "Slovenia";
$countries['SB'] = "Solomon Islands";
$countries['SO'] = "Somalia";
$countries['ZA'] = "South Africa";
$countries['GS'] = "South Georgia and the South Sandwich Islands";
$countries['SS'] = "South Sudan";
$countries['ES'] = "Spain";
$countries['LK'] = "Sri Lanka";
$countries['SD'] = "Sudan";
$countries['SR'] = "Suriname";
$countries['SJ'] = "Svalbard and Jan Mayen";
$countries['SZ'] = "Swaziland";
$countries['SE'] = "Sweden";
$countries['CH'] = "Switzerland";
$countries['SY'] = "Syrian Arab Republic";
$countries['TW'] = "Taiwan, Province of China";
$countries['TJ'] = "Tajikistan";
$countries['TZ'] = "Tanzania, United Republic of";
$countries['TH'] = "Thailand";
$countries['TL'] = "Timor-Leste";
$countries['TG'] = "Togo";
$countries['TK'] = "Tokelau";
$countries['TO'] = "Tonga";
$countries['TT'] = "Trinidad and Tobago";
$countries['TN'] = "Tunisia";
$countries['TR'] = "Turkey";
$countries['TM'] = "Turkmenistan";
$countries['TC'] = "Turks and Caicos Islands";
$countries['TV'] = "Tuvalu";
$countries['UG'] = "Uganda";
$countries['UA'] = "Ukraine";
$countries['AE'] = "United Arab Emirates";
$countries['GB'] = "United Kingdom";
$countries['US'] = "United States";
$countries['UM'] = "United States Minor Outlying Islands";
$countries['UY'] = "Uruguay";
$countries['UZ'] = "Uzbekistan";
$countries['VU'] = "Vanuatu";
$countries['VE'] = "Venezuela, Bolivarian Republic of";
$countries['VN'] = "Viet Nam";
$countries['VG'] = "Virgin Islands, British";
$countries['VI'] = "Virgin Islands, U.S.";
$countries['WF'] = "Wallis and Futuna";
$countries['EH'] = "Western Sahara";
$countries['YE'] = "Yemen";
$countries['ZM'] = "Zambia";
$countries['ZW'] = "Zimbabwe";

                                                                                           
?>
