<?php

namespace CommsyMediawikiBundle\Services;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Request;
use MediaWiki\OAuthClient\SignatureMethod\HmacSha1;
use MediaWiki\OAuthClient\Token;

class MediawikiService
{
    private $legacyEnvironment;
    private $apiUrl;

    private $consumerKey;
    private $consumerSecret;
    private $accessToken;
    private $accessSecret;
    
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        $apiUrl,
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessSecret)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->apiUrl = $apiUrl;

        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->accessToken = $accessToken;
        $this->accessSecret = $accessSecret;
    }

    /**
     * Enables a wiki
     *
     * @param int $roomId commsy room id
     * @return bool
     */
    public function enableWiki($roomId)
    {
        if (!$this->isWikiEnabled($roomId)) {
            $request = $this->buildRequest('GET', [
                'action' => 'commsy',
                'function' => 'enablewiki',
                'session-id' => $this->legacyEnvironment->getSessionID(),
                'context-id' => $roomId,
                'format' => 'json',
            ]);

            try {
                $response = $request->send();

                return $response->body->commsy->result == 'wiki enabled';
            } catch (\Exception $exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Disabled a wiki
     *
     * @param int $roomId commsy room id
     * @return bool
     */
    public function disableWiki($roomId)
    {
        if ($this->isWikiEnabled($roomId)) {
            $request = $this->buildRequest('GET', [
                'action' => 'commsy',
                'function' => 'disablewiki',
                'session-id' => $this->legacyEnvironment->getSessionID(),
                'context-id' => $roomId,
                'format' => 'json',
            ]);

            try {
                $response = $request->send();

                return $response->body->commsy->result == 'wiki disabled' || $response->body->commsy->result == 'wiki removed';
            } catch (\Exception $exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Checks if a wiki is already enabled
     *
     * @param int $roomId commsy room id
     * @return bool
     */
    public function isWikiEnabled($roomId)
    {
        $request = $this->buildRequest('GET', [
            'action' => 'commsy',
            'function' => 'iswikienabled',
            'session-id' => $this->legacyEnvironment->getSessionID(),
            'context-id' => $roomId,
            'format' => 'json',
        ]);

        try {
            $response = $request->send();

            return $response->body->commsy->result == 'wiki is enabled';
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Builds the request with an appropriate OAuth-Header for communication with mediawiki
     *
     * @param string $method The HTTP method like GET, POST
     * @param array $parameters Parameters to send with the request
     *
     * @return \Httpful\Request An Httpful request object
     *
     * @throws \Exception
     */
    private function buildRequest($method, array $parameters)
    {
        $consumer = new Consumer($this->consumerKey, $this->consumerSecret);
        $accessToken = new Token($this->accessToken, $this->accessSecret);

        $request = Request::fromConsumerAndToken($consumer, $accessToken, $method, $this->apiUrl, $parameters);
        $request->signRequest(new HmacSha1(), $consumer, $accessToken);

        $authorizationHeader = $request->toHeader();
        $authorizationHeader = substr($authorizationHeader, 15);

        /** @noinspection PhpUndefinedMethodInspection */
        return \Httpful\Request::get($request->toUrl())
            ->expectsJson()
            ->withAuthorization($authorizationHeader);
    }
}