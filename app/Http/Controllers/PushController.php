<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Response;

class PushController extends Controller
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

    public function send(Request $request)
    {
        $userId = $request->get('user_id');
        $text = $request->get('text');

        $textMessageBuilder = app('LINE\LINEBot\MessageBuilder\TextMessageBuilder', [$text]);

        /* @var Response $resp */
        $response = $this->bot->pushMessage($userId, $textMessageBuilder);

        echo $response->getHTTPStatus() . ' ' . $response->getRawBody();
    }
}
