<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Etherpad;

use App\Etherpad\EtherpadClient;
use App\Etherpad\EtherpadException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Pins the contract that the previous client got wrong: Etherpad reports
 * failures with HTTP 200 and a non-zero `code` in the body, so a transport
 * check says nothing about the outcome. Every such answer has to reach the
 * caller as an exception — the old library returned null, which was then
 * written into a material as an empty description.
 */
final class EtherpadClientTest extends TestCase
{
    private const KEY = 'test-key';

    /** @var list<string> */
    private array $requestedUrls = [];

    /**
     * @param array<string, mixed> ...$payloads
     */
    private function clientReturning(array ...$payloads): EtherpadClient
    {
        $responses = array_map(
            fn (array $payload) => new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]),
            $payloads
        );

        $mock = new MockHttpClient(function (string $method, string $url) use (&$responses) {
            $this->requestedUrls[] = $url;

            return array_shift($responses) ?? new MockResponse('{"code":0,"message":"ok","data":null}');
        });

        return new EtherpadClient($mock, 'http://etherpad:9001', self::KEY);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function ok(array $data): array
    {
        return ['code' => 0, 'message' => 'ok', 'data' => $data];
    }

    // ---- the defect this class exists to prevent

    public function testAFailedCallThrowsInsteadOfYieldingNothing(): void
    {
        $client = $this->clientReturning([
            'code' => 1,
            'message' => 'padID does not exist',
            'data' => null,
        ]);

        try {
            $client->getHtml('g.abc$1');
            self::fail('a refused call must not return quietly');
        } catch (EtherpadException $e) {
            self::assertSame(1, $e->apiCode);
            self::assertSame('padID does not exist', $e->apiMessage);
            self::assertStringContainsString('getHTML', $e->getMessage());
        }
    }

    #[DataProvider('errorCodes')]
    public function testEveryNonZeroCodeIsAnError(int $code, string $message): void
    {
        $client = $this->clientReturning(['code' => $code, 'message' => $message, 'data' => null]);

        $this->expectException(EtherpadException::class);
        $client->listPads('g.abc');
    }

    /**
     * @return iterable<string, array{0: int, 1: string}>
     */
    public static function errorCodes(): iterable
    {
        yield 'wrong parameters' => [1, 'padID does not exist'];
        yield 'internal error' => [2, 'internal error'];
        yield 'no such function' => [3, 'no such function'];
        yield 'wrong api key' => [4, 'no or wrong API Key'];
    }

    public function testAMissingCodeCountsAsFailureRatherThanSuccess(): void
    {
        // A body that is not the documented shape must not be read as "fine".
        $client = $this->clientReturning(['unexpected' => true]);

        $this->expectException(EtherpadException::class);
        $client->listPads('g.abc');
    }

    public function testAnUnreachableServerThrows(): void
    {
        $mock = new MockHttpClient(static function (): MockResponse {
            return new MockResponse('', ['error' => 'connection refused']);
        });

        $this->expectException(EtherpadException::class);
        (new EtherpadClient($mock, 'http://etherpad:9001', self::KEY))->listPads('g.abc');
    }

    // ---- successful calls hand back the payload

    public function testReturnsTheRequestedValues(): void
    {
        $client = $this->clientReturning(
            self::ok(['authorID' => 'a.7']),
            self::ok(['groupID' => 'g.abc']),
            self::ok(['padIDs' => ['g.abc$1', 'g.abc$2']]),
            self::ok(['padID' => 'g.abc$3']),
            self::ok(['sessionID' => 's.9']),
            self::ok(['html' => '<p>text</p>']),
        );

        self::assertSame('a.7', $client->createAuthorIfNotExistsFor('42', 'A Person'));
        self::assertSame('g.abc', $client->createGroupIfNotExistsFor('105'));
        self::assertSame(['g.abc$1', 'g.abc$2'], $client->listPads('g.abc'));
        self::assertSame('g.abc$3', $client->createGroupPad('g.abc', '3'));
        self::assertSame('s.9', $client->createSession('g.abc', 'a.7', 1000));
        self::assertSame('<p>text</p>', $client->getHtml('g.abc$1'));
    }

    public function testActionsWithoutAPayloadSucceedQuietly(): void
    {
        $client = $this->clientReturning(
            ['code' => 0, 'message' => 'ok', 'data' => null],
            ['code' => 0, 'message' => 'ok', 'data' => null],
        );

        $client->setHtml('g.abc$1', '<p>x</p>');
        $client->deletePad('g.abc$1');

        self::assertCount(2, $this->requestedUrls);
    }

    // ---- request shape

    public function testCallsThePinnedApiVersionAndSendsTheKey(): void
    {
        $client = $this->clientReturning(self::ok(['padIDs' => []]));
        $client->listPads('g.abc');

        self::assertStringContainsString('/api/'.EtherpadClient::API_VERSION.'/listPads', $this->requestedUrls[0]);
        self::assertStringContainsString('apikey='.self::KEY, $this->requestedUrls[0]);
        self::assertStringContainsString('groupID=g.abc', $this->requestedUrls[0]);
    }

    public function testTrailingSlashInTheApiUrlDoesNotDoubleUp(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url) {
            $this->requestedUrls[] = $url;

            return new MockResponse('{"code":0,"message":"ok","data":{"padIDs":[]}}');
        });

        (new EtherpadClient($mock, 'http://etherpad:9001/', self::KEY))->listPads('g.abc');

        self::assertStringNotContainsString('9001//api', $this->requestedUrls[0]);
    }

    // ---- the derived pad id

    public function testPadIdFollowsFromGroupAndName(): void
    {
        self::assertSame('g.abc$149', EtherpadClient::padId('g.abc', '149'));
    }

    /**
     * Creating a pad doubles as finding one, because the name is derived from
     * the item. The caller has to be able to tell that refusal apart from a
     * real fault, otherwise opening an existing pad looks like an error.
     */
    public function testRefusalForAnExistingPadIsRecognisable(): void
    {
        $client = $this->clientReturning([
            'code' => 1,
            'message' => 'padName does already exist',
            'data' => null,
        ]);

        try {
            $client->createGroupPad('g.abc', '149');
            self::fail('expected the refusal to surface');
        } catch (EtherpadException $e) {
            self::assertTrue($e->meansPadAlreadyExists());
        }
    }

    public function testOtherFailuresAreNotMistakenForAnExistingPad(): void
    {
        $client = $this->clientReturning([
            'code' => 4,
            'message' => 'no or wrong API Key',
            'data' => null,
        ]);

        try {
            $client->createGroupPad('g.abc', '149');
            self::fail('expected the refusal to surface');
        } catch (EtherpadException $e) {
            self::assertFalse($e->meansPadAlreadyExists());
        }
    }
}
