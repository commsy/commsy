<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.01.18
 * Time: 16:03
 */

namespace CommsyBundle\Http;


use Symfony\Component\HttpFoundation\JsonResponse;

class JsonErrorResponse extends JsonResponse
{
    public function __construct($error, $status = 200, $headers = array())
    {
        $data = [
            'error' => $error,
        ];

        parent::__construct($data, $status, $headers, false);
    }
}