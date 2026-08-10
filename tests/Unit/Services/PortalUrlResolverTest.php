<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Entity\Portal;
use App\Proxy\PortalProxy;
use App\Services\PortalUrlResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PortalUrlResolverTest extends TestCase
{
    public function testConfiguredBaseUrlWins(): void
    {
        $portal = $this->createMock(Portal::class);
        $portal->method('getBaseUrl')->willReturn('https://www.unicommsy.uni-hamburg.de');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::never())->method('generate');

        $resolver = new PortalUrlResolver($urlGenerator);

        self::assertSame('https://www.unicommsy.uni-hamburg.de', $resolver->resolve($portal));
    }

    public function testFallsBackToRoutedPortalEntryUrl(): void
    {
        $portal = $this->createMock(Portal::class);
        $portal->method('getBaseUrl')->willReturn(null);
        $portal->method('getId')->willReturn(42);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'app_helper_portalenter',
                ['context' => 42],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://commsy.example/portal/42/enter');

        $resolver = new PortalUrlResolver($urlGenerator);

        self::assertSame('https://commsy.example/portal/42/enter', $resolver->resolve($portal));
    }

    public function testResolvesLegacyPortalProxyAsWell(): void
    {
        $portal = $this->createMock(PortalProxy::class);
        $portal->method('getBaseUrl')->willReturn(null);
        $portal->method('getId')->willReturn(7);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://commsy.example/portal/7/enter');

        $resolver = new PortalUrlResolver($urlGenerator);

        self::assertSame('https://commsy.example/portal/7/enter', $resolver->resolve($portal));
    }

    /**
     * The setter normalises blank input so an admin clearing the field restores the fallback
     * rather than putting an empty href into every mail.
     */
    public function testBlankBaseUrlIsStoredAsNull(): void
    {
        $portal = new Portal();

        $portal->setBaseUrl('   ');
        self::assertNull($portal->getBaseUrl());

        $portal->setBaseUrl('  https://www.unicommsy.uni-hamburg.de  ');
        self::assertSame('https://www.unicommsy.uni-hamburg.de', $portal->getBaseUrl());
    }
}
