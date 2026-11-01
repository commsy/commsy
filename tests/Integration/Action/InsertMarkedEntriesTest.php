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

namespace Tests\Integration\Action;

use App\Action\Mark\InsertAction;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Inserting marked entries into a room copies those that can be copied and
 * skips the others (groups, topics, persons, rooms) instead of failing on
 * them; the message counts what was inserted.
 */
#[WithStory(AccountStory::class)]
final class InsertMarkedEntriesTest extends KernelTestCase
{
    private Account $account;
    private Room $sourceRoom;
    private Room $targetRoom;
    private User $targetUser;
    private int $groupId;
    private int $announcementId;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->account = AccountStory::get('account');
        $portal = $this->account->getPortal();
        $this->sourceRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $this->targetRoom = RoomFactory::new()->project()->create(['contextId' => $portal?->getId(), 'portal' => $portal]);
        $sourceUser = RoomUserFactory::createOne(['account' => $this->account, 'room' => $this->sourceRoom, 'status' => 2]);
        $this->targetUser = RoomUserFactory::createOne(['account' => $this->account, 'room' => $this->targetRoom, 'status' => 2]);

        $this->groupId = (int) LabelFactory::createOne(['room' => $this->sourceRoom, 'creator' => $sourceUser, 'type' => 'group', 'name' => 'Exkursionsgruppe'])->getItemId();
        $this->announcementId = (int) AnnouncementFactory::createOne(['room' => $this->sourceRoom, 'creator' => $sourceUser, 'title' => 'Exkursion'])->getItemId();
    }

    public function testAMarkedGroupIsSkippedAndTheOthersAreInserted(): void
    {
        $this->actInTargetRoom();
        $itemService = self::getContainer()->get(ItemService::class);
        $items = [$itemService->getTypedItem($this->groupId), $itemService->getTypedItem($this->announcementId)];
        $roomItem = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment()->getRoomManager()->getItem($this->targetRoom->getItemId());

        $response = self::getContainer()->get(InsertAction::class)->execute($roomItem, $items);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['Exkursion'], $this->titlesInTheTargetRoom());
        $inserted = self::getContainer()->get(TranslatorInterface::class)->trans('inserted %count% entries in this room', ['%count%' => 1]);
        self::assertStringContainsString($inserted, (string) json_decode((string) $response->getContent(), true)['payload']['message']);
    }

    /**
     * @return list<string>
     */
    private function titlesInTheTargetRoom(): array
    {
        return self::getContainer()->get(Connection::class)->fetchFirstColumn(
            'SELECT title FROM announcement WHERE context_id = ? AND deletion_date IS NULL',
            [$this->targetRoom->getItemId()],
        );
    }

    private function actInTargetRoom(): void
    {
        $container = self::getContainer();
        $container->get('security.token_storage')->setToken(new UsernamePasswordToken($this->account, 'main', $this->account->getRoles()));
        $request = new Request();
        $request->attributes->set('roomId', $this->targetRoom->getItemId());
        $container->get(RequestStack::class)->push($request);

        $environment = $container->get(LegacyEnvironment::class)->getEnvironment();
        $environment->setCurrentPortalID((int) $this->account->getPortal()?->getId());
        $environment->setCurrentContextID($this->targetRoom->getItemId());
        $userManager = $environment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($this->targetRoom->getItemId());
        $userManager->setUserIDLimit($this->targetUser->getUserId());
        $userManager->select();
        /** @var cs_user_item $userItem */
        $userItem = $userManager->get()->getFirst();
        $environment->setCurrentUserItem($userItem);
    }
}
