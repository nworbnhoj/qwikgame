<?php

require_once 'up.php';
require_once UP.PATH_VENDOR.'autoload.php';

require_once 'Qwik.php';
require_once 'Service.php';

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/*******************************************************************************
    Class Push constructs a we push message by populating title and body
    templates with [variables] and {translations}.
*******************************************************************************/


class Push extends Qwik {

    static $vapid;

    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        $vapid = new Service('vapid');
        self::$vapid = [
            'VAPID' => [
                'subject' => Qwik::QWIK_URL,
                'publicKey' => $vapid->key('public'),
                'privateKey' => $vapid->key('private')
            ]
        ];
    }


    const DEFAULT_OPTIONS = [
        'TTL'       => 86400,         // 1 day
        'urgency'   => 'normal',
        'batchSize' => 10
    ];

    const INVITE_OPTIONS = [
        'TTL'       => 86400,         // 1 day
        'urgency'   => 'normal',
        'topic'     => 'invite',      // causes only most recent invite to show
        'batchSize' => 10
    ];

    const CONFIRM_OPTIONS = [
        'TTL'       => 86400,         // 1 day
        'urgency'   => 'normal',
        'topic'     => 'confirm',     // causes only most recent confirm to show
        'batchSize' => 10
    ];

    const MSG_OPTIONS = [
        'TTL'       => 86400,         // 1 day
        'urgency'   => 'normal',
        'topic'     => 'msg',         // causes only most recent msg to show
        'batchSize' => 10
    ];

    const CANCEL_OPTIONS = [
        'TTL'       => 86400,         // 1 day
        'urgency'   => 'high',
        'batchSize' => 10
    ];

    private $subscriptions = array();
    private $payload;
    private $options;
    private $deadEnds = array();


    /*******************************************************************************
    Class Push is constructed with an array containing relevent variables including
    Title & Body, and any additional fields necessary to customize as required.

    $subscriptions array  [["endpoint"=>'', "keys" => ['p256dh'=>'', 'auth'=>'']], ...]
    $title         string Notification title
    $body          string Notification message
    $options       array  Notification options
    *******************************************************************************/

    public function __construct($subscriptions, $title, $body, $options=self::DEFAULT_OPTIONS){
        parent::__construct();

        foreach($subscriptions as $sub){
            $this->subscriptions[] = Subscription::create($sub);
        }

        $this->payload = json_encode([
            "title" => $title,
            "body"   => $body
        ]);

        $this->options = $options;
    }


    public function send(){
        $webPush = new WebPush(self::$vapid);

        foreach ($this->subscriptions as $subscription){
            $webPush->sendNotification($subscription, $this->payload);
        }

        $allGood = TRUE;
        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $endpoint = $report->getEndpoint();
                self::logMsg("WebPush message failed for {$endpoint}: {$report->getReason()}");
                if ($report->isSubscriptionExpired()){
                    $this->deadEnds[] = $endpoint;
                }
                $allGood = FALSE;
            }
        }

        return $allGood;
    }


    public function deadEnds(){
        return $this->$deadEnds;
    }

}


Push::initStatic();

?>
