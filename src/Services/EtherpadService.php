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

namespace App\Services;

use App\Etherpad\EtherpadClient;
use App\Etherpad\EtherpadException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EtherpadService
{
    private ?EtherpadClient $client = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%commsy.etherpad.base_url%')]
        private readonly string $baseUrl,
        #[Autowire('%commsy.etherpad.api_url%')]
        private readonly string $apiUrl,
        #[Autowire('%commsy.etherpad.api_key%')]
        private readonly string $apiKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== $this->apiKey && '' !== $this->apiUrl;
    }

    /**
     * @throws EtherpadException when key or url are missing — the previous
     *                           version declared a client return type and
     *                           handed back null instead, which surfaced far
     *                           away from the cause
     */
    public function getClient(): EtherpadClient
    {
        if (!$this->isConfigured()) {
            throw EtherpadException::notConfigured('getClient');
        }

        return $this->client ??= new EtherpadClient($this->httpClient, $this->apiUrl, $this->apiKey);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
