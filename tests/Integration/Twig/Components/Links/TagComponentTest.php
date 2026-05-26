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

namespace Tests\Integration\Twig\Components\Links;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Integration\Concerns\PrimesSession;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Probe for the FormTrait-based live components: TagComponent uses
 * ComponentWithFormTrait + Symfony forms with CSRF, which requires a
 * Session on the RequestStack at mount time. Verifies the session
 * priming trait is enough to get such components through render and
 * a LiveAction toggle.
 */
#[WithStory(AccountStory::class)]
final class TagComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use PrimesSession;

    private Account $account;
    private Room $room;
    private User $roomUser;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->primeSession();

        $this->account = AccountStory::get('account');
        $this->room = RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
        $this->roomUser = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $this->room,
            'status' => 2,
        ]);
    }

    public function testMountsWithItemIdAndRendersInitialReadMode(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Links:TagComponent',
            data: ['itemId' => $itemId],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
        self::assertFalse(
            $component->component()->editMode,
            'default mount stays in read mode unless room flags mandate edit',
        );
    }

    public function testEnableEditModeActionFlipsEditMode(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Links:TagComponent',
            data: ['itemId' => $itemId],
        );

        self::assertFalse($component->component()->editMode);

        $component->call('enableEditMode');

        self::assertTrue(
            $component->component()->editMode,
            'enableEditMode LiveAction must flip editMode to true',
        );
    }

    private function createMaterialItemId(): int
    {
        return (int) MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ])->getItemId();
    }
}
