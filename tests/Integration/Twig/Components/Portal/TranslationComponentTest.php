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

namespace Tests\Integration\Twig\Components\Portal;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Integration\Concerns\PrimesSession;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see \App\Twig\Components\Portal\TranslationComponent}: portal
 * admin translation editor. The PostHydrate `checkAccess()` runs only
 * when `$currentTranslation` is set, so the initial mount path does
 * not trigger Security::getUser() — the auth scaffold lives elsewhere
 * (controller-level tests with a logged-in portal moderator).
 */
#[WithStory(AccountStory::class)]
final class TranslationComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use PrimesSession;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->primeSession();
    }

    public function testMountsWithPortalId(): void
    {
        $portalId = (int) AccountStory::get('account')->getPortal()?->getId();

        $component = $this->createLiveComponent(
            name: 'Portal:TranslationComponent',
            data: ['portalId' => $portalId],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
        self::assertSame($portalId, $component->component()->portalId);
        self::assertNull(
            $component->component()->currentTranslation,
            'initial mount keeps currentTranslation null — checkAccess stays bypassed',
        );
    }
}
