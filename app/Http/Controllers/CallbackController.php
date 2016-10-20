<?php

namespace App\Http\Controllers;

use Faker\Provider\cs_CZ\DateTime;
use Illuminate\Http\Request;

use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class CallbackController extends Controller
{
    private $httpClient;
    private $bot;

    public function __construct()
    {
        $this->httpClient = new CurlHTTPClient(getenv('LINE_Channel_Access_Token'));
        $this->bot = new LINEBot($this->httpClient, ['channelSecret' => getenv('LINE_Channel_Secret')]);
    }

    public function receive(Request $request)
    {
        $header = new HTTPHeader();
        $signature = $request->header($header::LINE_SIGNATURE);

        if (empty($signature)) {
            return response('Bad Request', 400);
        }

        $events = $this->bot->parseEventRequest($request->getContent(), $signature);

        foreach ($events as $event) {
            if (!($event instanceof MessageEvent)) {
                continue;
            }

            if (!($event instanceof TextMessage)) {
                continue;
            }

            file_put_contents("php://stderr", "受け取ったメッセージ: " . $event->getText() . " 判定: " . strpos($event->getText(), '打刻'));
            if (strpos($event->getText(), '打刻')) {
                $now = new \DateTime();
                $this->httpClient->get(getenv('Adit_URL') . "&year={$now->format('Y')}&month={$now->format('m')}&day={$now->format('d')}&hour={$now->format('H')}&minute={$now->format('i')}");
                $replyText = '打刻しました！';
            } else {
                $replyText = 'ん？';
            }


            $resp = $this->bot->replyText($event->getReplyToken(), $replyText);

            file_put_contents("php://stderr", $resp->getHTTPStatus() . ': ' . $resp->getRawBody());
        }

        return response('OK', 200);
    }
}
