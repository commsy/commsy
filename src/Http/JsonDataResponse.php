<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.01.18
 * Time: 16:03
 */

namespace App\Http;


use Symfony\Component\HttpFoundation\JsonResponse;

class JsonDataResponse extends JsonResponse
{
    public function __construct($data, $status = 200, $headers = array())
    {
        $data = [
            'payload' => $data,
        ];

        parent::__construct($data, $status, $headers, false);
    }
}