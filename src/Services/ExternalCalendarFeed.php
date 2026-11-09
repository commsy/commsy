<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fetches the feed of an external calendar. Its address is entered in a
 * room, so only a public http(s) address is fetched: no other scheme and,
 * also after a redirect, no host in a private or local network.
 */
final readonly class ExternalCalendarFeed
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = new NoPrivateNetworkHttpClient($client);
    }

    /**
     * @throws InvalidArgumentException for an address that is not fetched
     * @throws ExceptionInterface       when fetching fails
     */
    public function fetch(string $url): string
    {
        $url = (string) preg_replace(['~^webcals://~i', '~^webcal://~i'], ['https://', 'http://'], trim($url));

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || '' === (string) parse_url($url, PHP_URL_HOST)) {
            throw new InvalidArgumentException('Only an http or https address is fetched.');
        }

        return $this->client->request('GET', $url, ['timeout' => 30, 'max_redirects' => 5])->getContent();
    }
}
