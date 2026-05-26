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

namespace Tests\Integration\Twig\Components;

use App\Entity\Portal;
use App\Form\Model\MailText;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Integration\Concerns\PrimesSession;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see \App\Twig\Components\MailTextComponent}: portal admin
 * inline editor for translatable email templates. Mount with a Portal
 * + MailText model and verify the form renders. The LiveActions
 * (select / resetContent / save) pull in the legacy translator and
 * need their own fixtures — covered later through a controller-level
 * scenario.
 */
#[WithStory(AccountStory::class)]
final class MailTextComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use PrimesSession;

    private Portal $portal;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->primeSession();

        $portal = AccountStory::get('account')->getPortal();
        self::assertNotNull($portal, 'AccountStory must provision a portal');
        $this->portal = $portal;
    }

    public function testMountsWithPortalAndFreshMailTextModel(): void
    {
        $component = $this->createLiveComponent(
            name: 'mail_text',
            data: [
                'portal' => $this->portal,
                'mailText' => new MailText(),
            ],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
        self::assertSame($this->portal, $component->component()->portal);
        self::assertInstanceOf(MailText::class, $component->component()->mailText);
    }
}
