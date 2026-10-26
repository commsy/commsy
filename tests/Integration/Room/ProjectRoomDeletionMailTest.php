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
use App\Room\ProjectRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Room\RoomType;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_project_item;
use Doctrine\DBAL\Connection;
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
 * Deleting a project room tells the moderation of its community rooms, where
 * the portal asks for that; the mails name the community rooms, and these no
 * longer list the project room.
 */
#[WithStory(AccountStory::class)]
final class ProjectRoomDeletionMailTest extends KernelTestCase
{
    use MailerAssertionsTrait;

    private Connection $connection;
    private cs_environment $legacyEnvironment;
    private Account $account;
    private Account $communityModerator;
    private Room $project;
    private Room $community;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();
        $this->legacyEnvironment = $container->get(LegacyEnvironment::class)->getEnvironment();
        $container->get(RequestStack::class)->push(new Request());

        $this->account = AccountStory::get('account');
        $portal = $this->account->getPortal();
        self::assertInstanceOf(Portal::class, $portal);
        $portal->setNotifyCommunityModForAllProjectRooms(true);
        $container->get(EntityManagerInterface::class)->flush();

        $this->project = $this->room(RoomType::Project, 'Projekt');
        $this->community = $this->room(RoomType::Community, 'Lindenhof');
        $this->communityModerator = AccountFactory::createOne(['portal' => $portal, 'authSource' => $portal->getAuthSources()->first()]);
        RoomUserFactory::new()->asModerator()->create(['account' => $this->account, 'room' => $this->project]);
        RoomUserFactory::new()->asModerator()->create(['account' => $this->communityModerator, 'room' => $this->community]);

        // the deletion must not rely on what was read before it
        foreach ([$this->legacyEnvironment->getLinkItemManager(), $this->legacyEnvironment->getProjectManager(), $this->legacyEnvironment->getCommunityManager(), $this->legacyEnvironment->getRoomManager()] as $manager) {
            $manager->setCacheOff();
        }

        $this->legacyEnvironment->setCurrentPortalID($portal->getId());
        $this->legacyEnvironment->setCurrentContextID($portal->getId());
        $this->legacyEnvironment->setCurrentUserItem($container->get(UserService::class)->getPortalUser($this->account));

        $project = $this->legacyEnvironment->getProjectManager()->getItem($this->project->getItemId());
        self::assertInstanceOf(cs_project_item::class, $project);
        $project->setCommunityListByID([$this->community->getItemId()]);
        $project->save();
        self::assertSame([$this->project->getItemId()], $this->projectsListedBy($this->community));
        self::getContainer()->get('mailer.message_logger_listener')->reset();
    }

    public function testTheModerationOfTheCommunityRoomsIsTold(): void
    {
        $this->delete();

        self::assertNotSame([], $this->mailsTo((string) $this->communityModerator->getEmail()));
    }

    public function testTheMailsNameTheCommunityRooms(): void
    {
        $this->delete();

        $mails = $this->mails();
        self::assertNotSame([], $mails);
        foreach ($mails as $mail) {
            self::assertMatchesRegularExpression('~<li>\s*Lindenhof\s*</li>~', (string) $mail->getHtmlBody());
        }
    }

    public function testTheCommunityRoomsNoLongerListTheProjectRoom(): void
    {
        $this->delete();

        self::assertSame([], $this->projectsListedBy($this->community));
    }

    private function delete(): void
    {
        self::getContainer()->get(ProjectRoomDeleter::class)->softDeleteRoom($this->project->getItemId(), (int) $this->account->getId(), RoomDeletionOptions::forUserAction());
    }

    private function room(RoomType $type, string $title): Room
    {
        $portal = $this->account->getPortal();

        return RoomFactory::createOne(['type' => $type->value, 'title' => $title, 'contextId' => $portal?->getId(), 'portal' => $portal]);
    }

    /**
     * @return list<int> the project rooms in the community room's own list
     */
    private function projectsListedBy(Room $community): array
    {
        $extras = unserialize((string) $this->connection->fetchOne('SELECT extras FROM room WHERE item_id = ?', [$community->getItemId()]), ['allowed_classes' => false]);

        return array_values(array_map('intval', is_array($extras) ? ($extras['PROJECT_ID_ARRAY'] ?? []) : []));
    }

    /**
     * @return list<Email>
     */
    private function mails(): array
    {
        return array_values(array_filter(self::getMailerMessages(), static fn ($message): bool => $message instanceof Email));
    }

    /**
     * @return list<Email>
     */
    private function mailsTo(string $address): array
    {
        return array_values(array_filter($this->mails(), static fn (Email $mail): bool => in_array($address, array_map(static fn (Address $to): string => $to->getAddress(), $mail->getTo()), true)));
    }
}
