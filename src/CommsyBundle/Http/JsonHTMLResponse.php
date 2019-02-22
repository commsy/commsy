<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.01.18
 * Time: 15:58
 */

namespace CommsyBundle\Http;


use Symfony\Component\HttpFoundation\JsonResponse;

class JsonHTMLResponse extends JsonResponse
{
    public function __construct($html, $status = 200, $headers = array())
    {
        $data = [
            'html' => $html,
        ];

        parent::__construct($data, $status, $headers, false);
    }
}