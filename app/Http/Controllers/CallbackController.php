<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Service\CurlHTTPClient;

use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\Response;

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
        $signature = $request->header('X_LINE_SIGNATURE');

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

            $replyText = $event->getText();
            $resp = $this->replyText($event->getReplyToken(), $replyText);
            file_put_contents("php://stderr", $resp->getHTTPStatus() . ': ' . $resp->getRawBody());
        }

        return response('OK', 200);
    }

    /**
     * Replies text message(s) to destination which is associated with reply token.
     *
     * This method receives variable texts. It can send text(s) message as bulk.
     *
     * @param string $replyToken Identifier of destination.
     * @param string $text Text of message.
     * @param string[] $extraTexts Extra text of message.
     * @return Response
     */
    public function replyText($replyToken, $text, ...$extraTexts)
    {
        $textMessageBuilder = new TextMessageBuilder($text, ...$extraTexts);
        return $this->replyMessage($replyToken, $textMessageBuilder);
    }

    /**
     * Replies arbitrary message to destination which is associated with reply token.
     *
     * @param string $replyToken Identifier of destination.
     * @param MessageBuilder $messageBuilder Message builder to send.
     * @return Response
     */
    public function replyMessage($replyToken, MessageBuilder $messageBuilder)
    {
        return $this->httpClient->post('https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => $messageBuilder->buildMessage(),
        ]);
    }
}
