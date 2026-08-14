<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Etherpad\EtherpadClient;
use App\Etherpad\EtherpadException;
use App\Services\EtherpadService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class EtherpadServiceTest extends TestCase
{
    private function service(string $apiUrl, string $apiKey): EtherpadService
    {
        return new EtherpadService(new MockHttpClient(), 'http://localhost:82', $apiUrl, $apiKey);
    }

    public function testHandsOutAClientWhenConfigured(): void
    {
        $service = $this->service('http://etherpad:9001', 'a-key');

        self::assertTrue($service->isConfigured());
        self::assertInstanceOf(EtherpadClient::class, $service->getClient());
    }

    public function testTheClientIsBuiltOnce(): void
    {
        $service = $this->service('http://etherpad:9001', 'a-key');

        self::assertSame($service->getClient(), $service->getClient());
    }

    /**
     * The previous version declared a client return type and handed back null
     * when key or url were missing, so the failure surfaced somewhere else
     * entirely. Saying so at the source is the point.
     */
    #[DataProvider('incompleteConfigurations')]
    public function testRefusesToHandOutAClientWhenNotConfigured(string $apiUrl, string $apiKey): void
    {
        $service = $this->service($apiUrl, $apiKey);

        self::assertFalse($service->isConfigured());

        $this->expectException(EtherpadException::class);
        $service->getClient();
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function incompleteConfigurations(): iterable
    {
        yield 'nothing set' => ['', ''];
        yield 'no key' => ['http://etherpad:9001', ''];
        yield 'no url' => ['', 'a-key'];
    }

    public function testExposesTheBaseUrlForTheIframe(): void
    {
        self::assertSame(
            'http://localhost:82',
            $this->service('http://etherpad:9001', 'a-key')->getBaseUrl()
        );
    }
}
