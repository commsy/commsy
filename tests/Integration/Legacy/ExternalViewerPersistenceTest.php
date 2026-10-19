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

namespace Tests\Integration\Legacy;

use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\LabelService;
use cs_environment;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The external viewers of an entry change only when a save names them: the
 * edit form sets or clears the list, every other save (tagging from the list,
 * attaching a file, activating, ...) leaves them as they are.
 */
#[WithStory(AccountStory::class)]
final class ExternalViewerPersistenceTest extends KernelTestCase
{
    private Connection $connection;
    private cs_environment $legacyEnvironment;
    private Room $room;
    private User $roomUser;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
        $this->legacyEnvironment = $container->get(LegacyEnvironment::class)->getEnvironment();

        $account = AccountStory::get('account');
        $portal = $account->getPortal();
        $this->room = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $this->roomUser = RoomUserFactory::createOne(['account' => $account, 'room' => $this->room, 'status' => 2]);

        $this->legacyEnvironment->setCurrentPortalID((int) $portal?->getId());
        $this->legacyEnvironment->setCurrentContextID($this->room->getItemId());
        $container->get(RequestStack::class)->push(new Request());
    }

    public function testTaggingFromTheListKeepsTheExternalViewers(): void
    {
        $itemId = $this->createMaterial(['gast', 'besuch']);
        $categoryId = (int) self::getContainer()->get(CategoryService::class)->addTag('Kategorie', $this->room->getItemId())->getItemID();

        self::getContainer()->get(LabelService::class)->addCategoriesById([$categoryId], [$itemId], $this->room->getItemId());

        self::assertSame(['besuch', 'gast'], $this->externalViewersOf($itemId));
    }

    public function testASaveThatDoesNotNameThemKeepsTheExternalViewers(): void
    {
        $itemId = $this->createMaterial(['gast']);

        $item = self::getContainer()->get(ItemService::class)->getTypedItem($itemId);
        $item->setTitle('Neuer Titel');
        $item->save();

        self::assertSame(['gast'], $this->externalViewersOf($itemId));
    }

    public function testASaveThatNamesThemReplacesTheExternalViewers(): void
    {
        $itemId = $this->createMaterial(['gast', 'besuch']);

        $item = self::getContainer()->get(ItemService::class)->getTypedItem($itemId);
        $item->setExternalViewerAccounts(['besuch', 'neu']);
        $item->save();

        self::assertSame(['besuch', 'neu'], $this->externalViewersOf($itemId));
    }

    public function testASaveThatClearsThemRemovesTheExternalViewers(): void
    {
        $itemId = $this->createMaterial(['gast']);

        $item = self::getContainer()->get(ItemService::class)->getTypedItem($itemId);
        $item->unsetExternalViewerAccounts();
        $item->save();

        self::assertSame([], $this->externalViewersOf($itemId));
    }

    /**
     * @param string[] $externalViewers
     */
    private function createMaterial(array $externalViewers): int
    {
        $itemId = (int) MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'externalViewers' => $externalViewers,
        ])->getItemId();
        self::assertCount(count($externalViewers), $this->externalViewersOf($itemId), 'the factory stores the external viewers');
        $this->actAsRoomUser();

        return $itemId;
    }

    /**
     * @return list<string>
     */
    private function externalViewersOf(int $itemId): array
    {
        return $this->connection->fetchFirstColumn('SELECT user_id FROM external_viewer WHERE item_id = ? ORDER BY user_id', [$itemId]);
    }

    private function actAsRoomUser(): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($this->room->getItemId());
        $userManager->setUserIDLimit($this->roomUser->getUserId());
        $userManager->select();
        /** @var cs_user_item $userItem */
        $userItem = $userManager->get()->getFirst();
        $this->legacyEnvironment->setCurrentUserItem($userItem);
    }
}
