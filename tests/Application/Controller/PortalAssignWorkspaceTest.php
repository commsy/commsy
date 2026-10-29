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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Portal;
use App\Entity\Room;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;

/**
 * Assigning an account to a room in the account administration offers and
 * takes only live project rooms of the portal.
 */
final class PortalAssignWorkspaceTest extends AbstractApplicationTestCase
{
    private const PASSWORD = 'assign-workspace-pw';

    private Portal $portal;
    private Account $moderator;
    private Account $member;
    private int $memberUserId;
    private Room $project;
    private Room $privateRoom;
    private Room $deletedProject;
    private Room $foreignProject;

    public function setUp(): void
    {
        parent::setUp();

        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $this->portal = PortalFactory::createOne(['authSources' => [$localSource]]);
        $this->moderator = $this->createAccount($this->portal, $localSource, 'assign.mod');
        $this->member = $this->createAccount($this->portal, $localSource, 'assign.member');
        $this->memberUserId = $this->portalUserId($this->member);
        $this->connection()->executeStatement(
            'UPDATE user SET status = 3 WHERE account_id = ? AND context_id = ?',
            [$this->moderator->getId(), $this->portal->getId()]
        );

        $this->project = $this->room($this->portal, 'project', 'Lernraum Projekt');
        $this->privateRoom = $this->room($this->portal, 'privateroom', 'Lernraum Privat');
        $this->deletedProject = $this->room($this->portal, 'project', 'Lernraum Alt');
        $this->connection()->update('room', ['deletion_date' => '2026-01-01 10:00:00'], ['item_id' => $this->deletedProject->getItemId()]);

        $foreignSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $foreignPortal = PortalFactory::createOne(['authSources' => [$foreignSource]]);
        $this->foreignProject = $this->room($foreignPortal, 'project', 'Lernraum Fremd');
    }

    public function testTheSearchOffersOnlyLiveProjectRoomsOfThePortal(): void
    {
        self::assertSame([$this->project->getItemId()], $this->offeredFor('Lernraum'));
    }

    public function testTheSearchFindsAProjectRoomByItsId(): void
    {
        self::assertSame([$this->project->getItemId()], $this->offeredFor((string) $this->project->getItemId()));
        self::assertSame([], $this->offeredFor((string) $this->foreignProject->getItemId()));
    }

    public function testASearchWithoutMatchOffersNothing(): void
    {
        self::assertSame([], $this->offeredFor('Kein Treffer'));
    }

    public function testTheTermIsMatchedAsWritten(): void
    {
        self::assertSame([], $this->offeredFor('%'));
        self::assertSame([], $this->offeredFor('Lernraum_Projekt'));
    }

    public function testTheAccountIsAssignedToAProjectRoomOfThePortal(): void
    {
        $this->assign($this->project);

        self::assertSame(1, $this->profilesIn($this->project));
    }

    public function testNoOtherRoomIsTaken(): void
    {
        foreach ([$this->privateRoom, $this->deletedProject, $this->foreignProject] as $room) {
            $this->assign($room);

            self::assertSame(0, $this->profilesIn($room), $room->getTitle());
        }
    }

    /**
     * @return list<int> the rooms offered for the term
     */
    private function offeredFor(string $term): array
    {
        $this->loginAsUser($this->portal->getId(), $this->moderator->getUsername(), self::PASSWORD);
        $crawler = $this->client->request('GET', $this->path());
        $form = $crawler->filter('button[name$="[search]"]')->form();
        $form['account_index_detail_assign_workspace[searchForWorkspace]'] = $term;
        $result = $this->client->submit($form);

        return array_values(array_map('intval', array_filter(
            $result->filter('select[name$="[workspaceSelection]"] option')->each(static fn (Crawler $option): string => (string) $option->attr('value')),
            static fn (string $value): bool => '' !== $value && '0' !== $value,
        )));
    }

    private function assign(Room $room): void
    {
        $this->loginAsUser($this->portal->getId(), $this->moderator->getUsername(), self::PASSWORD);
        $crawler = $this->client->request('GET', $this->path());
        $form = $crawler->filter('button[name$="[save]"]')->form();
        $selection = $form['account_index_detail_assign_workspace[workspaceSelection]'];
        self::assertInstanceOf(ChoiceFormField::class, $selection);
        $selection->disableValidation()->setValue((string) $room->getItemId());
        $this->client->submit($form);
    }

    private function profilesIn(Room $room): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM user WHERE account_id = ? AND context_id = ? AND deletion_date IS NULL',
            [$this->member->getId(), $room->getItemId()]
        );
    }

    private function path(): string
    {
        return "/portal/{$this->portal->getId()}/settings/accountIndex/detail/{$this->memberUserId}/assignWorkspace";
    }

    private function room(Portal $portal, string $type, string $title): Room
    {
        return RoomFactory::createOne(['type' => $type, 'title' => $title, 'contextId' => $portal->getId(), 'portal' => $portal]);
    }

    private function createAccount(Portal $portal, mixed $authSource, string $username): Account
    {
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $authSource,
            'username' => $username,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
            'plainPassword' => self::PASSWORD,
        ]);
    }

    private function portalUserId(Account $account): int
    {
        $id = $this->connection()->fetchOne(
            'SELECT item_id FROM user WHERE account_id = ? AND context_id = ? AND deletion_date IS NULL',
            [$account->getId(), $account->getPortal()?->getId()]
        );
        self::assertNotFalse($id);

        return (int) $id;
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
