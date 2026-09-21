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

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\EntryAction;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Pins the {@see \App\Twig\Components\NotificationPanel} live component: it groups
 * an item's events into one row, marks the whole list read in place, and scopes to
 * one room when a context id is given.
 */
final class NotificationPanelTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    private KernelBrowser $client;
    private Account $account;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();

        // The live actions are guarded, so the component has to be driven by a
        // browser that carries a signed-in session. loginUser() is not enough:
        // UserProvider::refreshUser() resolves the portal from the request, and
        // the component endpoint carries none — only a real login puts it in
        // the session.
        $password = 'panel-component-test';
        $source = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$source]]);
        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $source,
            'plainPassword' => $password,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
        $portalId = $portal->getId();
        $username = $account->getUsername();
        $accountId = $account->getId();

        $this->client->request('GET', "/login/{$portalId}");
        $this->client->submitForm('login_local', ['email' => $username, 'password' => $password]);
        $this->client->followRedirect();

        // Each request detaches the Foundry entities, so take a managed one.
        $this->account = self::getContainer()->get(EntityManagerInterface::class)
            ->find(Account::class, $accountId);
    }

    public function testRendersEntriesWithoutAPerRowReadControl(): void
    {
        $account = $this->account;
        $this->persist($account, sourceItemId: 100, title: 'First entry');
        $this->persist($account, sourceItemId: 200, title: 'Second entry');

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account], $this->client);

        $html = (string) $component->render();
        self::assertStringContainsString('First entry', $html);
        self::assertStringContainsString('Second entry', $html);
        self::assertSame(2, $this->repository()->count(['readAt' => null]));

        // A single entry is marked read by opening it, not from the panel.
        self::assertStringNotContainsString('markRead', $html);
        self::assertStringContainsString('markAllRead', $html, 'the list-wide action stays');
    }

    public function testGroupsEventsOfTheSameItemIntoOneRow(): void
    {
        $account = $this->account;
        $this->persist($account, sourceItemId: 500, title: 'My material', action: EntryAction::Created, createdAt: new \DateTimeImmutable('2026-06-01 10:00:00'));
        $this->persist($account, sourceItemId: 500, title: 'My material', action: EntryAction::Edited, createdAt: new \DateTimeImmutable('2026-06-02 10:00:00'));
        $this->persist($account, sourceItemId: 600, title: 'Other entry', createdAt: new \DateTimeImmutable('2026-05-01 10:00:00'));

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account], $this->client);

        $groups = $component->component()->getGroups();
        self::assertCount(2, $groups, 'the two events for item 500 collapse into one row');
        self::assertCount(2, $groups[0]->events(), 'the active item (500) is first and holds both of its events');

        self::assertSame(3, $this->repository()->count([]), 'nothing is deleted by hand');
    }

    public function testMarkAllReadClearsTheUnreadState(): void
    {
        $account = $this->account;
        $this->persist($account, sourceItemId: 1, title: 'One');
        $this->persist($account, sourceItemId: 2, title: 'Two');

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account], $this->client);
        $component->call('markAllRead');

        self::assertSame(2, $this->repository()->count([]), 'the entries stay listed');
        self::assertSame(0, $this->repository()->count(['readAt' => null]));
    }

    public function testMarkAllReadLeavesAnUndecidedTaskAlone(): void
    {
        $account = $this->account;
        $this->persist($account, sourceItemId: 1, title: 'An entry');
        $task = $this->persist($account, sourceItemId: 77, title: 'Someone wants in', type: NotificationType::RoomJoinRequest);

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account], $this->client);
        $component->call('markAllRead');

        // The panel clears its own list; the bell keeps counting what still needs deciding.
        self::assertNull($this->repository()->find($task->getId())->getReadAt());
        self::assertSame(1, $this->repository()->count(['readAt' => null]));
    }

    public function testScopesToOneRoomWhenContextGiven(): void
    {
        $account = $this->account;
        $this->persist($account, sourceItemId: 1, title: 'In room ten', contextId: 10);
        $this->persist($account, sourceItemId: 2, title: 'In room twenty', contextId: 20);

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account, 'contextId' => 10], $this->client);

        $html = (string) $component->render();
        self::assertStringContainsString('In room ten', $html);
        self::assertStringNotContainsString('In room twenty', $html);
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }

    private function persist(
        Account $account,
        int $sourceItemId,
        string $title,
        int $contextId = 5,
        EntryAction $action = EntryAction::Created,
        ?\DateTimeImmutable $createdAt = null,
        NotificationType $type = NotificationType::Entry,
    ): Notification {
        $notification = new Notification(
            $account,
            $type,
            $contextId,
            $title,
            'Room',
            $createdAt ?? new \DateTimeImmutable(),
            $sourceItemId,
            'material',
            'Actor',
            // Only an entry carries an action; the entity refuses anything else.
            $type === NotificationType::Entry ? $action : null,
        );
        $this->repository()->save($notification);

        return $notification;
    }
}
