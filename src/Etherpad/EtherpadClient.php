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

namespace App\Etherpad;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The Etherpad HTTP API, limited to the calls this application makes.
 *
 * Etherpad reports failures with HTTP 200 and a non-zero `code` in the body.
 * Every method here turns that into an {@see EtherpadException} and returns
 * the payload directly, so a caller cannot mistake a refused call for an
 * empty result — which is how a failed read used to end up stored as an
 * empty description.
 *
 * The API version is pinned deliberately. Etherpad keeps serving old versions
 * across major releases, so staying on a known one is what makes the server
 * side upgradable without touching this class.
 */
#[Exclude]
class EtherpadClient
{
    public const API_VERSION = '1.2.13';

    /**
     * Etherpad composes a group pad's id from the group and the pad name,
     * which is what lets callers address a pad without storing its id.
     */
    public const GROUP_PAD_SEPARATOR = '$';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiUrl,
        private readonly string $apiKey,
    ) {
    }

    public static function padId(string $groupId, string $padName): string
    {
        return $groupId.self::GROUP_PAD_SEPARATOR.$padName;
    }

    public function createAuthorIfNotExistsFor(string $authorMapper, string $name): string
    {
        return (string) $this->call('createAuthorIfNotExistsFor', [
            'authorMapper' => $authorMapper,
            'name' => $name,
        ])['authorID'];
    }

    public function createGroupIfNotExistsFor(string $groupMapper): string
    {
        return (string) $this->call('createGroupIfNotExistsFor', [
            'groupMapper' => $groupMapper,
        ])['groupID'];
    }

    /**
     * @return list<string>
     */
    public function listPads(string $groupId): array
    {
        return array_values((array) $this->call('listPads', ['groupID' => $groupId])['padIDs']);
    }

    /**
     * @throws EtherpadException when the pad already exists; callers that use
     *                           creation as a find-or-create should check
     *                           {@see EtherpadException::meansPadAlreadyExists()}
     */
    public function createGroupPad(string $groupId, string $padName, string $text = ''): string
    {
        return (string) $this->call('createGroupPad', [
            'groupID' => $groupId,
            'padName' => $padName,
            'text' => $text,
        ])['padID'];
    }

    public function createSession(string $groupId, string $authorId, int $validUntil): string
    {
        return (string) $this->call('createSession', [
            'groupID' => $groupId,
            'authorID' => $authorId,
            'validUntil' => (string) $validUntil,
        ])['sessionID'];
    }

    public function getHtml(string $padId): string
    {
        return (string) $this->call('getHTML', ['padID' => $padId])['html'];
    }

    public function setHtml(string $padId, string $html): void
    {
        $this->call('setHTML', ['padID' => $padId, 'html' => $html]);
    }

    public function deletePad(string $padId): void
    {
        $this->call('deletePad', ['padID' => $padId]);
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array<string, mixed> the `data` payload, never null
     */
    private function call(string $method, array $parameters = []): array
    {
        $url = sprintf('%s/api/%s/%s', rtrim($this->apiUrl, '/'), self::API_VERSION, $method);

        try {
            $payload = $this->httpClient
                ->request('GET', $url, ['query' => ['apikey' => $this->apiKey] + $parameters])
                ->toArray(false);
        } catch (HttpExceptionInterface|\JsonException $e) {
            throw EtherpadException::transportFailed($method, $e);
        }

        $code = (int) ($payload['code'] ?? EtherpadException::CODE_INTERNAL_ERROR);
        if ($code !== EtherpadException::CODE_OK) {
            throw EtherpadException::fromApiResponse($method, $code, $payload['message'] ?? null);
        }

        // Calls that only act return `data: null`; callers of those ignore it.
        return (array) ($payload['data'] ?? []);
    }
}
