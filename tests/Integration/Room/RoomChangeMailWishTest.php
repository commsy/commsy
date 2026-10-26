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

namespace Tests\Integration\Room;

use App\Entity\Account;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use App\Room\RoomType;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The mails about a change of a room reach only the moderators who want them,
 * as chosen in their room profile.
 */
#[WithStory(AccountStory::class)]
final class RoomChangeMailWishTest extends KernelTestCase
{
    use MailerAssertionsTrait;

    private cs_environment $legacyEnvironment;
    private Account $account;
    private Portal $portal;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->legacyEnvironment = $container->get(LegacyEnvironment::class)->getEnvironment();
        $container->get(RequestStack::class)->push(new Request());

        $this->account = AccountStory::get('account');
        $portal = $this->account->getPortal();
        self::assertInstanceOf(Portal::class, $portal);
        $this->portal = $portal;

        $this->legacyEnvironment->setCurrentPortalID($portal->getId());
        $this->legacyEnvironment->setCurrentContextID($portal->getId());
        $this->legacyEnvironment->setCurrentUserItem($container->get(UserService::class)->getPortalUser($this->account));
    }

    public function testAProjectRoomTellsOnlyTheModeratorsWhoWantIt(): void
    {
        $this->assertOnlyTheWillingAreTold($this->room(RoomType::Project));
    }

    public function testACommunityRoomTellsOnlyTheModeratorsWhoWantIt(): void
    {
        $this->assertOnlyTheWillingAreTold($this->room(RoomType::Community));
    }

    public function testAGroupRoomTellsOnlyTheModeratorsWhoWantIt(): void
    {
        $project = $this->room(RoomType::Project);

        $this->assertOnlyTheWillingAreTold($this->room(RoomType::GroupRoom, ['PROJECT_ROOM_ITEM_ID' => $project->getItemId()]));
    }

    private function assertOnlyTheWillingAreTold(Room $room): void
    {
        $willing = $this->moderator($room, 'yes');
        $unwilling = $this->moderator($room, 'no');
        $silent = $this->moderator($room, null);
        self::getContainer()->get('mailer.message_logger_listener')->reset();

        $legacyRoom = $this->legacyEnvironment->getRoomManager()->getItem($room->getItemId());
        $legacyRoom->lock();
        $legacyRoom->save();

        self::assertNotSame([], $this->mailsTo($willing), 'a moderator who wants the mails');
        self::assertNotSame([], $this->mailsTo($silent), 'a moderator who never chose');
        self::assertSame([], $this->mailsTo($unwilling), 'a moderator who does not want them');
    }

    /**
     * @param array<string, mixed> $extras
     */
    private function room(RoomType $type, array $extras = []): Room
    {
        return RoomFactory::createOne(['type' => $type->value, 'contextId' => $this->portal->getId(), 'portal' => $this->portal, 'extras' => $extras]);
    }

    /**
     * @return string the moderator's address
     */
    private function moderator(Room $room, ?string $wantsMail): string
    {
        $account = AccountFactory::createOne(['portal' => $this->portal, 'authSource' => $this->portal->getAuthSources()->first()]);
        $user = RoomUserFactory::new()->asModerator()->create(['account' => $account, 'room' => $room]);
        if (null !== $wantsMail) {
            self::assertInstanceOf(User::class, $user);
            self::getContainer()->get(EntityManagerInterface::class)->getConnection()->update(
                'user',
                ['extras' => serialize(['ROOMWANTMAIL' => $wantsMail])],
                ['item_id' => $user->getItemId()],
            );
        }

        return (string) $account->getEmail();
    }

    /**
     * @return list<Email>
     */
    private function mailsTo(string $address): array
    {
        return array_values(array_filter(
            self::getMailerMessages(),
            static fn ($mail): bool => $mail instanceof Email && in_array($address, array_map(static fn (Address $to): string => $to->getAddress(), $mail->getTo()), true),
        ));
    }
}
