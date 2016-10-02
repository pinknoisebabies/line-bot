<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LINE\LINEBot;
//use Line\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;

class Callback extends Controller
{
    public function __invoke(Request $request)
    {
        $signature = $request->header('X_LINE_SIGNATURE');

        if (empty($signature)) {
            return response('Bad Request', 400);
        }

        // TODO ENV
        $httpClient = new CurlHTTPClient('');
        $bot = new LINEBot($httpClient, ['channelSecret' => '']);
        $events = $bot->parseEventRequest($request->getContent(), $signature);

        foreach ($events as $event) {
            if (!($event instanceof MessageEvent)) {
                continue;
            }

            if (!($event instanceof TextMessage)) {
                continue;
            }

            $replyText = $event->getText();
            $resp = $bot->replyText($event->getReplyToken(), $replyText);
            file_put_contents("php://stderr", $resp->getHTTPStatus() . ': ' . $resp->getRawBody());
        }

        return response('OK', 200);
    }
}
