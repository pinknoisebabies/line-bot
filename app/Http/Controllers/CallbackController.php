<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\Response;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

class CallbackController extends Controller
{
    /* @var CurlHTTPClient $httpClient */
    private $httpClient;

    /* @var LINEBot $httpClient */
    private $bot;

    public function __construct()
    {
        $this->httpClient = app('LINE\LINEBot\HTTPClient\CurlHTTPClient', [getenv('LINE_Channel_Access_Token')]);
        $this->bot = app('LINE\LINEBot', [$this->httpClient, ['channelSecret' => getenv('LINE_Channel_Secret')]]);
    }

    public function receive(Request $request, HTTPHeader $header)
    {
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

            file_put_contents("php://stderr", "UserID: " . $event->getUserId());

            $messageBuilder = new TextMessageBuilder('ん？');

            if (strpos($event->getText(), '打刻') !== false) {
                $now = new \DateTime();
                $this->httpClient->get(getenv('Adit_URL') . "&year={$now->format('Y')}&month={$now->format('m')}&day={$now->format('d')}&hour={$now->format('H')}&minute={$now->format('i')}");
                $messageBuilder = new TextMessageBuilder('打刻しました！');
            }

            if (strpos($event->getText(), '登録') !== false) {
                $templateBuilder = new ButtonTemplateBuilder(
                    "タイトルとは",
                    "友達追加ありがとうとか". PHP_EOL . "アカウント連携をしてねとか",
                    getenv('LINE_Thumbnail_Image_Url'),
                    [new UriTemplateActionBuilder('登録ボタン', 'https://google.com/')]);
                $messageBuilder = new TemplateMessageBuilder('thank you add friend', $templateBuilder);
            }

            /* @var Response $resp */
            $resp = $this->bot->replyMessage($event->getReplyToken(), $messageBuilder);

            file_put_contents("php://stderr", $resp->getHTTPStatus() . ': ' . $resp->getRawBody());
        }

        return response('OK', 200);
    }
}
