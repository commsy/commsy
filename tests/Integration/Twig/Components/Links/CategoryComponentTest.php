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
 * Pins {@see \App\Twig\Components\Links\CategoryComponent}: sibling of
 * TagComponent for room categories. Same shape (editMode flip,
 * itemId-bound form on mount) so the test surface mirrors
 * TagComponentTest.
 */
#[WithStory(AccountStory::class)]
final class CategoryComponentTest extends KernelTestCase
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

    public function testMountsWithItemIdAndStartsInReadMode(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Links:CategoryComponent',
            data: ['itemId' => $itemId],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
        self::assertFalse($component->component()->editMode);
        self::assertFalse($component->component()->embedded);
    }

    public function testEditModeIsWritableViaLiveProp(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Links:CategoryComponent',
            data: ['itemId' => $itemId],
        );

        $component->set('editMode', true);
        self::assertTrue($component->component()->editMode);

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered, 're-render after editMode flip must succeed');
    }

    private function createMaterialItemId(): int
    {
        return (int) MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ])->getItemId();
    }
}
