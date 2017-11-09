<?php

namespace CommsyMediawikiBundle\Services;

use Circle\RestClientBundle\Exceptions\CurlException;
use Circle\RestClientBundle\Services\RestClient;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Request;
use MediaWiki\OAuthClient\Token;

class MediawikiService
{
    private $restClient;
    private $legacyEnvironment;
    private $apiUrl;
    private $isLoggedIn = false;
    
    public function __construct(LegacyEnvironment $legacyEnvironment, $apiUrl)
    {
        $this->restClient = "";
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->apiUrl = $apiUrl;





//Verbrauchertoken
//    7ea156f1a7d3c4e267b3101778d3765a
//Verbrauchergeheimnis
//    5e1de0b6b3f1500a0e1b9df2962ee124044376a1
//Zugriffstoken
//    009264ff3572a056c4bac0667d5cb255
//Zugriffsgeheimnis
//    3616e4b27eb4ec849543b8b937087398c1d4a167
    }

    /**
     * Enables a wiki
     *
     * @param $roomId commsy room id
     * @return bool
     */
    public function enableWiki($roomId)
    {
        $this->isWikiEnabled($roomId);





//        if (!$this->isWikiEnabled($roomId)) {
//            $url = $this->apiUrl;
//            $url .= '?action=commsy';
//            $url .= '&function=enablewiki';
//            $url .= '&session-id=' . $this->legacyEnvironment->getSessionID();
//            $url .= '&context-id=' . $roomId;
//            $url .= '&format=json';
//            $url .= '&assert=user';
//
//            try {
//                $response = json_decode($this->restClient->get($url)->getContent());
//
//                if (!isset($response->commsy)) {
//                    return false;
//                }
//
//                if (!isset($response->commsy->error)) {
//                    return $response->commsy->result == 'wiki enabled';
//                }
//            } catch (CurlException $exception) {
//                return false;
//            }
//        }
//
//        return false;
    }

    /**
     * Disabled a wiki
     *
     * @param $roomId commsy room id
     * @return bool
     */
    public function disableWiki($roomId)
    {
        $this->isWikiEnabled($roomId);
//        if (!$this->isWikiEnabled($roomId)) {
//            $url = $this->apiUrl;
//            $url .= '?action=commsy';
//            $url .= '&function=disablewiki';
//            $url .= '&session-id=' . $this->legacyEnvironment->getSessionID();
//            $url .= '&context-id=' . $roomId;
//            $url .= '&format=json';
//            $url .= '&assert=user';
//
//            try {
//                $response = json_decode($this->restClient->get($url)->getContent());
//
//                if (!isset($response->commsy)) {
//                    return false;
//                }
//
//                if (!isset($response->commsy->error)) {
//                    return $response->commsy->result == 'wiki disabled' || $response->commsy->result == 'wiki removed';
//                }
//            } catch (CurlException $exception) {
//                return false;
//            }
//        }
//
//        return false;
    }

    /**
     * Checks if a wiki is already enabled
     *
     * @param $roomId commsy room id
     * @return bool
     */
    public function isWikiEnabled($roomId)
    {
        $consumer = new Consumer('7ea156f1a7d3c4e267b3101778d3765a', '5e1de0b6b3f1500a0e1b9df2962ee124044376a1');
        $accessToken = new Token('009264ff3572a056c4bac0667d5cb255', '3616e4b27eb4ec849543b8b937087398c1d4a167');

        $request = Request::fromConsumerAndToken($consumer, $accessToken, 'GET', $this->apiUrl);

        $header = $request->toHeader();

        return false;


//        $url = $this->apiUrl;
////        $url .= '?action=commsy';
////        $url .= '&function=iswikienabled';
////        $url .= '&session-id=' . $this->legacyEnvironment->getSessionID();
////        $url .= '&context-id=' . $roomId;
////        $url .= '&format=json';
//        $url .= '?action=query';
//        $url .= '&assert=bot&format=json';
//
//        try {
//            $url = $this->apiUrl . '?action=query&meta=tokens&type=login&format=json';
//
//            $this->authenticate();
//
//            $response = json_decode($this->restClient->get($url)->getContent());
//
//            if (!isset($response->commsy)) {
//                return false;
//            }
//
//            if (!isset($response->commsy->error)) {
//                return $response->commsy->result == 'wiki is enabled';
//            }
//        } catch (CurlException $exception) {
//            return false;
//        }
    }

//    private function authenticate()
//    {
//        $url = $this->apiUrl;
//        $url .= '?action=query';
//        $url .= '&meta=tokens';
//        $url .= '&type=login';
//        $url .= '&format=json';
//
//        try {
//            $response = json_decode($this->restClient->get($url, [
//                CURLOPT_COOKIEFILE => '',
//            ])->getContent());
//
//            $loginToken = null;
//            if (isset($response->query)) {
//                if (isset($response->query->tokens)) {
//                    if (isset($response->query->tokens->logintoken)) {
//                        $loginToken = $response->query->tokens->logintoken;
//                    }
//                }
//            }
//
//            if ($loginToken) {
//                $url = $this->apiUrl;
//                $url .= '?action=login';
//                $url .= '&format=json';
//
//                $payload = 'lgname=CommSy';
//                $payload .= '&lgpassword=40kjgmrl0n3ssqp7ghb5gjroihabbgjp';
//                $payload .= '&lgtoken=' . urlencode($loginToken);
//
//                $response = json_decode($this->restClient->post($url, $payload, [
//                    CURLOPT_COOKIEFILE => '',
//                ])->getContent());
//
//                $test = 5;
//            }
//        } catch (CurlException $exception) {
//            return false;
//        }
//    }
}