<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

/**
 * Restrict access to the development environment.
 * - HTTP_CLIENT_IP will restrict access from share internet
 * - HTTP_X_FORWARDED_FOR catches proxy environments
 *
 * In addition access is allowed if:
 * - the remote address is localhost
 * - the remote address is listed in the server APP_DEV_ACCESS global
 * - we are using the php built-in webserver (cli-server)
 * - the server global APP_ENV has the value "development"
 */
$allowedRemoteAddresses = array_merge(['127.0.0.1', 'fe80::1', '::1'], explode(',', $_SERVER['APP_DEV_ACCESS']));
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(in_array(@$_SERVER['REMOTE_ADDR'], $allowedRemoteAddresses) || php_sapi_name() === 'cli-server' || @$_SERVER['APP_ENV'] === 'development')
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check ' . basename(__FILE__) . ' for more information.');
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';
Debug::enable();

$kernel = new AppKernel('dev', true);

$request = Request::createFromGlobals();

Request::setTrustedProxies(
    ['127.0.0.1', $request->server->get('REMOTE_ADDR')],
    Request::HEADER_X_FORWARDED_ALL
);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
