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

namespace Tests\Integration\Utils;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Utils\ItemService;
use cs_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 3.5 — characterization of ItemService::getEditorsForItem and the
 * getAdditionalEditorsForItem that wraps it. This is the only
 * getCurrentUserItem callsite in ItemService (line 100).
 *
 * Key observation: `$user = getCurrentUserItem()` at line 100 is fetched
 * but NEVER read in the method body — the result is built purely from the
 * legacy link_modifier_item table. Schritt 4's change here is a dead-line
 * removal; these tests pin the real method logic so that removal is
 * provably behaviour-neutral:
 *
 *   - getEditorsForItem returns the users recorded as modifiers
 *   - getAdditionalEditorsForItem returns those minus the item creator
 *
 * Reuse-priority suite: ItemService is a caller in all three locks.
 */
#[WithStory(AccountStory::class)]
final class ItemServiceCurrentUserCharacterizationTest extends KernelTestCase
{
    private ItemService $itemService;
    private Connection $connection;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->itemService = self::getContainer()->get(ItemService::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->account = AccountStory::get('account');
    }

    public function testEditorsListContainsEveryRecordedModifier(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($this->account, $room);
        $second = $this->member($this->secondAccount(), $room);
        $item = $this->material($room, $creator);

        $this->recordModifier($item->getItemID(), $creator->getItemId());
        $this->recordModifier($item->getItemID(), $second->getItemId());

        $editorIds = array_map(
            static fn ($u): int => $u->getItemID(),
            $this->itemService->getEditorsForItem($item),
        );

        self::assertContains($creator->getItemId(), $editorIds);
        self::assertContains($second->getItemId(), $editorIds);
    }

    public function testAdditionalEditorsExcludeTheItemCreator(): void
    {
        $room = $this->createRoom();
        $creator = $this->member($this->account, $room);
        $second = $this->member($this->secondAccount(), $room);
        $item = $this->material($room, $creator);

        $this->recordModifier($item->getItemID(), $creator->getItemId());
        $this->recordModifier($item->getItemID(), $second->getItemId());

        $additionalIds = array_map(
            static fn ($u): int => $u->getItemID(),
            $this->itemService->getAdditionalEditorsForItem($item),
        );

        self::assertNotContains(
            $item->getCreatorId(),
            $additionalIds,
            'the item creator must be filtered out of the additional editors',
        );
        self::assertContains($second->getItemId(), $additionalIds);
    }

    // ---- helpers

    private function recordModifier(int $itemId, int $modifierUserId): void
    {
        $this->connection->insert('link_modifier_item', [
            'item_id' => $itemId,
            'modifier_id' => $modifierUserId,
        ]);
    }

    private function material(Room $room, User $creator): cs_item
    {
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $creator,
        ]);

        $item = $this->itemService->getTypedItem($material->getItemId());
        self::assertInstanceOf(cs_item::class, $item);

        return $item;
    }

    private function createRoom(): Room
    {
        return RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }

    private function member(Account $account, Room $room): User
    {
        return RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => 2,
        ]);
    }

    private function secondAccount(): Account
    {
        $portal = $this->account->getPortal();

        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
    }
}
