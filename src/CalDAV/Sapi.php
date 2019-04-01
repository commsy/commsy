<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 21.06.18
 * Time: 16:55
 */

namespace App\CalDAV;

use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi as BaseSapi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Sapi extends BaseSapi
{
    public static $symfonyResponse;

    static function sendResponse(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (is_resource($body)) {
            self::$symfonyResponse = new StreamedResponse();
            self::$symfonyResponse->setCallback(function () use ($body) {
                stream_copy_to_stream($body, fopen('php://output', 'wb'));
            });
        } else {
            self::$symfonyResponse = new Response();
            self::$symfonyResponse->setContent($body);
        }

        self::$symfonyResponse->setStatusCode($response->getStatus());
        self::$symfonyResponse->setProtocolVersion($response->getHttpVersion());

        foreach ($response->getHeaders() as $key => $value) {
            foreach ($value as $k => $v) {
                if ($k === 0) {
                    self::$symfonyResponse->headers->set($key, $v);
                } else {
                    self::$symfonyResponse->headers->set($key, $v, false);
                }
            }
        }
    }
}