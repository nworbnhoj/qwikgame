<?php


require_once 'Logging.php';


class Qwik {
    const SUBDOMAIN  = 'www';
    const QWIK_URL   = 'https://' . self::SUBDOMAIN . '.qwikgame.org';

    const PATH_VENUE  = 'venue';
    const PATH_PLAYER = 'player';
    const PATH_PDF    = 'pdf';
    const PATH_LANG   = 'lang';
    const PATH_HTML   = 'html';
    const PATH_JSON   = 'json';
    const PATH_UPLOAD = 'uploads';
    const PATH_LOG    = '/var/log/'.self::SUBDOMAIN.'.qwikgame.org.log';
    
    const XML = '.xml';

    const TIDY_CONFIG = array(
        'indent' => TRUE,
        'input-xml' => TRUE,
        'output-xml' => TRUE,
        'wrap' => 200,
        'quote-ampersand' => TRUE
    );

    static private $games = array(
        'backgammon'  => '{Backgammon}',
        'badminton'   => '{Badminton}',
        'boules'      => '{Boules}',
        'billards'    => '{Billiards}',
        'checkers'    => '{Checkers}',
        'chess'       => '{Chess}',
        'cycle'       => '{Cycle}',
        'darts'       => '{Darts}',
        'dirt'        => '{Dirt Biking}',
        'fly'         => '{Fly Fishing}',
        'go'          => '{Go}',
        'golf'        => '{Golf}',
        'lawn'        => '{Lawn Bowls}',
        'mtnbike'     => '{Mountain_Biking}',
        'pool'        => '{Pool}',
        'racquetball' => '{Racquetball}',
        'run'         => '{Run}',
        'snooker'     => '{Snooker}',
        'squash'      => '{Squash}',
        'table'       => '{Table_Tennis}',
        'tennis'      => '{Tennis}',
        'tenpin'      => '{Tenpin}',
        'walk'        => '{Walk}'
    );


    static private $countries = array(
        'AF' => "Afghanistan",
        'AX' => "Åland Islands",
        'AL' => "Albania",
        'DZ' => "Algeria",
        'AS' => "American Samoa",
        'AD' => "Andorra",
        'AO' => "Angola",
        'AI' => "Anguilla",
        'AQ' => "Antarctica",
        'AG' => "Antigua and Barbuda",
        'AR' => "Argentina",
        'AM' => "Armenia",
        'AW' => "Aruba",
        'AU' => "Australia",
        'AT' => "Austria",
        'AZ' => "Azerbaijan",
        'BS' => "Bahamas",
        'BH' => "Bahrain",
        'BD' => "Bangladesh",
        'BB' => "Barbados",
        'BY' => "Belarus",
        'BE' => "Belgium",
        'BZ' => "Belize",
        'BJ' => "Benin",
        'BM' => "Bermuda",
        'BT' => "Bhutan",
        'BO' => "Bolivia, Plurinational State of",
        'BQ' => "Bonaire, Sint Eustatius and Saba",
        'BA' => "Bosnia and Herzegovina",
        'BW' => "Botswana",
        'BV' => "Bouvet Island",
        'BR' => "Brazil",
        'IO' => "British Indian Ocean Territory",
        'BN' => "Brunei Darussalam",
        'BG' => "Bulgaria",
        'BF' => "Burkina Faso",
        'BI' => "Burundi",
        'KH' => "Cambodia",
        'CM' => "Cameroon",
        'CA' => "Canada",
        'CV' => "Cape Verde",
        'KY' => "Cayman Islands",
        'CF' => "Central African Republic",
        'TD' => "Chad",
        'CL' => "Chile",
        'CN' => "China",
        'CX' => "Christmas Island",
        'CC' => "Cocos (Keeling) Islands",
        'CO' => "Colombia",
        'KM' => "Comoros",
        'CG' => "Congo",
        'CD' => "Congo, the Democratic Republic of the",
        'CK' => "Cook Islands",
        'CR' => "Costa Rica",
        'CI' => "Côte d'Ivoire",
        'HR' => "Croatia",
        'CU' => "Cuba",
        'CW' => "Curaçao",
        'CY' => "Cyprus",
        'CZ' => "Czech Republic",
        'DK' => "Denmark",
        'DJ' => "Djibouti",
        'DM' => "Dominica",
        'DO' => "Dominican Republic",
        'EC' => "Ecuador",
        'EG' => "Egypt",
        'SV' => "El Salvador",
        'GQ' => "Equatorial Guinea",
        'ER' => "Eritrea",
        'EE' => "Estonia",
        'ET' => "Ethiopia",
        'FK' => "Falkland Islands (Malvinas)",
        'FO' => "Faroe Islands",
        'FJ' => "Fiji",
        'FI' => "Finland",
        'FR' => "France",
        'GF' => "French Guiana",
        'PF' => "French Polynesia",
        'TF' => "French Southern Territories",
        'GA' => "Gabon",
        'GM' => "Gambia",
        'GE' => "Georgia",
        'DE' => "Germany",
        'GH' => "Ghana",
        'GI' => "Gibraltar",
        'GR' => "Greece",
        'GL' => "Greenland",
        'GD' => "Grenada",
        'GP' => "Guadeloupe",
        'GU' => "Guam",
        'GT' => "Guatemala",
        'GG' => "Guernsey",
        'GN' => "Guinea",
        'GW' => "Guinea-Bissau",
        'GY' => "Guyana",
        'HT' => "Haiti",
        'HM' => "Heard Island and McDonald Islands",
        'VA' => "Holy See (Vatican City State)",
        'HN' => "Honduras",
        'HK' => "Hong Kong",
        'HU' => "Hungary",
        'IS' => "Iceland",
        'IN' => "India",
        'ID' => "Indonesia",
        'IR' => "Iran, Islamic Republic of",
        'IQ' => "Iraq",
        'IE' => "Ireland",
        'IM' => "Isle of Man",
        'IL' => "Israel",
        'IT' => "Italy",
        'JM' => "Jamaica",
        'JP' => "Japan",
        'JE' => "Jersey",
        'JO' => "Jordan",
        'KZ' => "Kazakhstan",
        'KE' => "Kenya",
        'KI' => "Kiribati",
        'KP' => "Korea, Democratic People's Republic of",
        'KR' => "Korea, Republic of",
        'KW' => "Kuwait",
        'KG' => "Kyrgyzstan",
        'LA' => "Lao People's Democratic Republic",
        'LV' => "Latvia",
        'LB' => "Lebanon",
        'LS' => "Lesotho",
        'LR' => "Liberia",
        'LY' => "Libya",
        'LI' => "Liechtenstein",
        'LT' => "Lithuania",
        'LU' => "Luxembourg",
        'MO' => "Macao",
        'MK' => "Macedonia, the former Yugoslav Republic of",
        'MG' => "Madagascar",
        'MW' => "Malawi",
        'MY' => "Malaysia",
        'ML' => "Mali",
        'MT' => "Malta",
        'MH' => "Marshall Islands",
        'MQ' => "Martinique",
        'MR' => "Mauritania",
        'MU' => "Mauritius",
        'YT' => "Mayotte",
        'MX' => "Mexico",
        'FM' => "Micronesia, Federated States of",
        'MD' => "Moldova, Republic of",
        'MC' => "Monaco",
        'MN' => "Mongolia",
        'ME' => "Montenegro",
        'MS' => "Montserrat",
        'MA' => "Morocco",
        'MZ' => "Mozambique",
        'MM' => "Myanmar",
        'NA' => "Namibia",
        'NR' => "Nauru",
        'NP' => "Nepal",
        'NL' => "Netherlands",
        'NC' => "New Caledonia",
        'NZ' => "New Zealand",
        'NI' => "Nicaragua",
        'NE' => "Niger",
        'NG' => "Nigeria",
        'NU' => "Niue",
        'NF' => "Norfolk Island",
        'MP' => "Northern Mariana Islands",
        'NO' => "Norway",
        'OM' => "Oman",
        'PK' => "Pakistan",
        'PW' => "Palau",
        'PS' => "Palestinian Territory, Occupied",
        'PA' => "Panama",
        'PG' => "Papua New Guinea",
        'PY' => "Paraguay",
        'PE' => "Peru",
        'PH' => "Philippines",
        'PN' => "Pitcairn",
        'PL' => "Poland",
        'PT' => "Portugal",
        'PR' => "Puerto Rico",
        'QA' => "Qatar",
        'RE' => "Réunion",
        'RO' => "Romania",
        'RU' => "Russian Federation",
        'RW' => "Rwanda",
        'BL' => "Saint Barthélemy",
        'SH' => "Saint Helena, Ascension and Tristan da Cunha",
        'KN' => "Saint Kitts and Nevis",
        'LC' => "Saint Lucia",
        'MF' => "Saint Martin (French part)",
        'PM' => "Saint Pierre and Miquelon",
        'VC' => "Saint Vincent and the Grenadines",
        'WS' => "Samoa",
        'SM' => "San Marino",
        'ST' => "Sao Tome and Principe",
        'SA' => "Saudi Arabia",
        'SN' => "Senegal",
        'RS' => "Serbia",
        'SC' => "Seychelles",
        'SL' => "Sierra Leone",
        'SG' => "Singapore",
        'SX' => "Sint Maarten (Dutch part)",
        'SK' => "Slovakia",
        'SI' => "Slovenia",
        'SB' => "Solomon Islands",
        'SO' => "Somalia",
        'ZA' => "South Africa",
        'GS' => "South Georgia and the South Sandwich Islands",
        'SS' => "South Sudan",
        'ES' => "Spain",
        'LK' => "Sri Lanka",
        'SD' => "Sudan",
        'SR' => "Suriname",
        'SJ' => "Svalbard and Jan Mayen",
        'SZ' => "Swaziland",
        'SE' => "Sweden",
        'CH' => "Switzerland",
        'SY' => "Syrian Arab Republic",
        'TW' => "Taiwan, Province of China",
        'TJ' => "Tajikistan",
        'TZ' => "Tanzania, United Republic of",
        'TH' => "Thailand",
        'TL' => "Timor-Leste",
        'TG' => "Togo",
        'TK' => "Tokelau",
        'TO' => "Tonga",
        'TT' => "Trinidad and Tobago",
        'TN' => "Tunisia",
        'TR' => "Turkey",
        'TM' => "Turkmenistan",
        'TC' => "Turks and Caicos Islands",
        'TV' => "Tuvalu",
        'UG' => "Uganda",
        'UA' => "Ukraine",
        'AE' => "United Arab Emirates",
        'GB' => "United Kingdom",
        'US' => "United States",
        'UM' => "United States Minor Outlying Islands",
        'UY' => "Uruguay",
        'UZ' => "Uzbekistan",
        'VU' => "Vanuatu",
        'VE' => "Venezuela, Bolivarian Republic of",
        'VN' => "Viet Nam",
        'VG' => "Virgin Islands, British",
        'VI' => "Virgin Islands, U.S.",
        'WF' => "Wallis and Futuna",
        'EH' => "Western Sahara",
        'YE' => "Yemen",
        'ZM' => "Zambia",
        'ZW' => "Zimbabwe"
    );
    
    static $log;
    
    public function __construct(){}
    
    
    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        self::$log = new Logging();
        self::$log->lfile(self::PATH_LOG);
        set_error_handler(array('Qwik','exception_error_handler'), E_ALL);
        set_exception_handler(array('Qwik','exception_handler'));
    }
    


    static public function qwikGames(){
        return self::$games;
    }
    
    

    static public function countries(){
        return self::$countries;
    }
    
    
    
    
    /*****************************************************************
        Error handing
    *****************************************************************/
    
    // see: https://www.php.net/manual/en/class.errorexception.php
    static public function exception_error_handler($number, $string, $file, $line, $context)
    {
        // Determine if this error is one of the enabled ones in php config (php.ini, .htaccess, etc)
        $error_is_enabled = (bool)($number & ini_get('error_reporting') );
       
        // throw an Error Exception for Fatal Errors and simply log any other enabled errors.
        if( in_array($number, array(E_USER_ERROR, E_RECOVERABLE_ERROR)) && $error_is_enabled ) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        } else if( $error_is_enabled ) {
            Qwik::logMsg($string);
            return false;
        }
    }


    static public function exception_handler($uncaught){
        self::logThrown($uncaught);
        header('Location: ' . self::QWIK_URL, true, 303);
        die();
    }



    
    /*****************************************************************
        Logging Service functions
    *****************************************************************/
    
    

    static public function log(){
        return self::$log;
    }
    
    
    static public function logMsg($msg){
        self::$log->lwrite($msg);
        self::$log->lclose();
    }
    
    
    static public function logEmail($type, $pid, $game='', $vid='', $time=''){
        $p = substr($pid, 0, 4);
        $msg = "email $type pid=$p $game $vid $time";
        self::log()->lwrite($msg);
        self::log()->lclose();
    }


    static public function logThrown(Throwable $t){
        return self::logMsg((string)$t);
    }


    static public function snip($str){
        return substr($str, 0, 4);
    }

    
    
    /*****************************************************************
        File System Helper functions
    *****************************************************************/
    
    /**
    * Checks that a file is writable and attempts to unlink file.
    * @return True if file is unlinked, and false otherwise.
    * @throws RuntimeException if file is not unlinked
    */
    static public function deleteFile($file){
        if (!is_writable($file) && !is_link($file)){
            throw new RuntimeException("unable to write to $file");
            return FALSE;           
        }
        if (!unlink($file)){
            throw new RuntimeException("failed to unlink $file");
            return FALSE;           
        }
        return TRUE;
    }

    
    
    // https://stackoverflow.com/questions/720751/how-to-read-a-list-of-files-from-a-folder-using-php
    static public function fileList($dir){
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


    static public function venues($game=NULL){
        $venues = array();
        $path = SELF::PATH_VENUE;
        $path .= $game ? "/$game" : '';
        $fileList = self::fileList($path);
        foreach($fileList as $file){
            if (substr_count($file, '.xml') > 0){
                $venues[] = str_replace('.xml', '', $file);
            }
        }
        return $venues;
    }


    static public function pids($game){
        $pids = array();
        $fileList = self::fileList(SELF::PATH_PLAYER);
        foreach($fileList as $file){
            if (substr_count($file, '.xml') > 0){
                $pids[] = str_replace('.xml', '', $file);
            }
       }
        return $pids;
    }
    
    
    /*****************************************************************
        XML Helper functions
    *****************************************************************/
    
    /**
    * Calls SimpleXML->saveXML() to write $xml to $path/$fileName
    * @throws RuntimeException if there is a problem writing the xml.
    * @return True if the xml is written to file successfully, and false otherwise.
    */
    static public function writeXML($xml, $path, $filename){
        $cwd = getcwd();
        if(!chdir($path)){
            throw new RuntimeException("failed to change working directory to $path");
            return FALSE;
        }
        if(!$xml->saveXML($filename)){
            throw new RuntimeException("failed to save xml to $path/$fileName");
            return FALSE;
        }
        if(!chdir($cwd)){
            throw new RuntimeException("failed to return working directory to $cwd");
            return FALSE;
        }
        return TRUE;
    }


    /**
    * Calls simplexml_load_file() to read $xml from $path/$fileName
    * @throws RuntimeException if there is a problem reading the xml.
    * @return True if the xml is read from file successfully, and false otherwise.
    */
    static public function readXML($path, $fileName){
        $cwd = getcwd();
        if (!file_exists("$path/$fileName")) {
            throw new RuntimeException("failed to read xml $path/$fileName");
            return FALSE;
        }

        if(!chdir($path)){
            throw new RuntimeException("failed to change working directory to $path");
            return FALSE;
        }

        try{
            $xml = simplexml_load_file($fileName);
        } catch (Exception $e){
            self::logThrown($e);
            throw new RuntimeException("failed to read xml from $path/$fileName");
        } finally {
            if(!chdir($cwd)){
                throw new RuntimeException("failed to return working directory to $cwd");
                return FALSE;
            }
        }
        return $xml;
    }
    
    

    // https://secure.php.net/manual/en/class.simplexmlelement.php
    // Must be tested with ===, as in if(isXML($xml) === true){}
    // Returns the error message on improper XML
    static public function isXML($xml){
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
    
    
    
    static public function lockXML($xml, $token){
        $nekot = hash('sha256', $token);
        if (isset($token)){
            if (isset($xml['lock'])){
                $xml['lock'] = $token;
            } else {
                $xml->addAttribute('lock', $token);
            }
        }
    }


    static public function unlockXML($xml, $token){
        $nekot = hash('sha256', $token);
        $locked = $xml->xpath("//*[@lock='$token']");
        foreach($locked as $open){
            removeAtt($open, 'lock');
        }
    }


    static public function isLocked($xml){
    //    return ! empty($xml['lock']);
        return isset($xml['lock']) && strlen($xml['lock']) > 0;
    }


    static public function removeElement($xml){
        $dom=dom_import_simpleXML($xml);
        $dom->parentNode->removeChild($dom);
    }

    static public function removeAtt($xml, $att){
        $dom=dom_import_simpleXML($xml);
        $dom->removeAttribute($att);
    }




    /*****************************************************************
        Time Helper functions
    *****************************************************************/



    /********************************************************************************
    Returns a new DateTime object for the time string and time-zone requested

    $str    String    time & date
    $tz        String    time-zone
    ********************************************************************************/
    static public function tzDateTime($str='now', $tz){
        if(empty($tz)){
            return new DateTime($str);
        }
        return new DateTime($str, timezone_open($tz));
    }



    static public function day($tz, $dateStr){
        $date = self::tzDateTime($dateStr, $tz);
        $today = self::tzDateTime('today', $tz);
        $interval = $today->diff($date);
        switch ($interval->days) {
            case 0: return 'today'; break;
            case 1: return $interval->invert ? 'yesterday' : 'tomorrow'; break;
            default:
                return $date->format('jS M');
        }
    }


    static public function hr($hr){
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



    static public function clock($hr){
        global $clock24hr;
        return (($hr > 12) && !$clock24hr) ? $hr-12 : $hr;
    }


    /*****************************************************************
        Time Helper functions
    *****************************************************************/

    const DIGCHR = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    // https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425 
    static private function generateRandomString($length = 10) {
        $str_repeat = str_repeat($x=self::DIGCHR, ceil($length/strlen($x)));
        $str_shuffle = str_shuffle($str_repeat);
        return substr($str_shuffle,1,$length);
    }


    static public function newID($len = 6){
        return self::generateRandomString($len);
    }


    static public function newToken($len = 10){
        return self::generateRandomString($len);
    }


}


Qwik::initStatic();

?>
