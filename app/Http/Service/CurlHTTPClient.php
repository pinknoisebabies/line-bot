<?php
namespace App\Http\Service;

use LINE\LINEBot\Constant\Meta;
use LINE\LINEBot\Exception\CurlExecutionException;
use LINE\LINEBot\HTTPClient;
use LINE\LINEBot\HTTPClient\Curl;
use LINE\LINEBot\Response;

class CurlHTTPClient implements HTTPClient
{
    /** @var array */
    private $authHeaders;
    /** @var array */
    private $userAgentHeader = ['User-Agent: LINE-BotSDK-PHP/' . Meta::VERSION];

    /**
     * CurlHTTPClient constructor.
     *
     * @param string $channelToken Access token of your channel.
     */
    public function __construct($channelToken)
    {
        $this->authHeaders = [
            "Authorization: Bearer $channelToken",
        ];
    }

    /**
     * Sends GET request to LINE Messaging API.
     *
     * @param string $url Request URL.
     * @return Response Response of API request.
     */
    public function get($url)
    {
        return $this->sendRequest('GET', $url, [], []);
    }

    /**
     * Sends POST request to LINE Messaging API.
     *
     * @param string $url Request URL.
     * @param array $data Request body.
     * @return Response Response of API request.
     */
    public function post($url, array $data)
    {
        return $this->sendRequest('POST', $url, ['Content-Type: application/json; charset=utf-8'], $data);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $additionalHeader
     * @param array $reqBody
     * @return Response
     * @throws CurlExecutionException
     */
    private function sendRequest($method, $url, array $additionalHeader, array $reqBody)
    {
        $curl = new Curl($url);

        $headers = array_merge($this->authHeaders, $this->userAgentHeader, $additionalHeader);

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_BINARYTRANSFER => true,
//            CURLOPT_HTTPPROXYTUNNEL => 1,
            CURLOPT_PROXY => getenv('FIXIE_URL'),
//            CURLOPT_PROXYPORT => 80,
        ];

        if ($method === 'POST' && !empty($reqBody)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($reqBody);
        }

        $curl->setoptArray($options);

        $body = $curl->exec();

        $info = $curl->getinfo();
        $httpStatus = $info['http_code'];

        if ($curl->errno()) {
            throw new CurlExecutionException($curl->error());
        }

        return new Response($httpStatus, $body);
    }
}