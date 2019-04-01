<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.01.18
 * Time: 16:03
 */

namespace App\Http;


use Symfony\Component\HttpFoundation\JsonResponse;

class JsonRedirectResponse extends JsonResponse
{
    public function __construct($route, $status = 200, $headers = array())
    {
        $data = [
            'redirect' => [
                'route' => $route,
            ]
        ];

        parent::__construct($data, $status, $headers, false);
    }
}