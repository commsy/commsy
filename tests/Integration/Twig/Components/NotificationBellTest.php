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
use App\Notification\NotificationPayload;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Pins the {@see \App\Twig\Components\NotificationBell} live component: it shows
 * personal/administrative notifications only, keeps content activity out, counts
 * open tasks, and resolves a task that someone else already decided.
 */
final class NotificationBellTest extends WebTestCase
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
        $password = 'bell-component-test';
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

    public function testSeparatesTasksFromInformationAndCondensesRoomActivity(): void
    {
        $account = $this->account;
        $this->persist($account, NotificationType::RoomJoinRequest, 'Ada Lovelace', sourceItemId: 42);
        $this->persist($account, NotificationType::Entry, 'Some material', sourceItemId: 99, action: EntryAction::Created);
        $this->persist($account, NotificationType::Entry, 'Some material', sourceItemId: 99, action: EntryAction::Edited);
        $this->persist($account, NotificationType::Entry, 'Another material', sourceItemId: 100, action: EntryAction::Edited);

        $component = $this->createLiveComponent('NotificationBell', ['account' => $account], $this->client);
        $bell = $component->component();

        self::assertCount(1, $bell->getTasks(), 'the join request is the only task');
        self::assertCount(0, $bell->getMessages(), 'entry activity is not listed one by one');

        $activity = $bell->getRoomActivity();
        self::assertCount(1, $activity, 'one line per room');
        self::assertSame(1, $activity[0]->created);
        self::assertSame(2, $activity[0]->edited, 'two distinct entries were edited');

        $html = (string) $component->render();
        self::assertStringContainsString('Ada Lovelace', $html);
        self::assertStringNotContainsString('Some material', $html, 'single entries stay in the panels');

        // The badge counts the task plus one room line, not the four events.
        self::assertSame(2, $bell->getUnreadCount());
    }

    public function testRoomLineDisappearsOnceTheActivityIsRead(): void
    {
        $account = $this->account;
        $this->persist($account, NotificationType::Entry, 'Some material', sourceItemId: 99);

        $component = $this->createLiveComponent('NotificationBell', ['account' => $account], $this->client);
        self::assertCount(1, $component->component()->getRoomActivity());

        $component->call('markAllRead');

        self::assertCount(0, $component->component()->getRoomActivity());
        self::assertSame(0, $this->repository()->count(['readAt' => null]), 'entry activity is marked read from the bell too');
    }

    public function testDecisionNotificationIsShown(): void
    {
        $account = $this->account;
        $this->persist(
            $account,
            NotificationType::RoomJoinDecision,
            'Projektraum',
            sourceItemId: 42,
            payload: new NotificationPayload(decision: 'accepted'),
        );

        $html = (string) $this->createLiveComponent('NotificationBell', ['account' => $account], $this->client)->render();

        self::assertStringContainsString('Projektraum', $html);
    }

    public function testDecidingAnAlreadyHandledRequestResolvesItQuietly(): void
    {
        $account = $this->account;
        $this->persist($account, NotificationType::RoomJoinRequest, 'Ada', sourceItemId: 42);

        $component = $this->createLiveComponent('NotificationBell', ['account' => $account], $this->client);

        // No such user exists, so the decider finds nothing to decide.
        $component->call('accept', ['id' => 42]);

        self::assertTrue($component->component()->alreadyDecided);
        self::assertSame(0, $this->repository()->count([]), 'the stale task is cleared');
    }

    public function testMarkAllReadLeavesOpenTasksCountable(): void
    {
        $account = $this->account;
        $this->persist($account, NotificationType::RoomJoinRequest, 'Ada', sourceItemId: 42);
        $this->persist($account, NotificationType::RoomJoinDecision, 'Projektraum', sourceItemId: 43);

        $component = $this->createLiveComponent('NotificationBell', ['account' => $account], $this->client);
        $component->call('markAllRead');

        self::assertSame(1, $this->repository()->count(['readAt' => null]), 'the task stays unread, the decision is read');
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }

    public function testAReadDecisionLeavesTheDropdown(): void
    {
        $account = $this->account;
        $this->persist($account, NotificationType::RoomJoinDecision, 'Projektraum', 180,
            new NotificationPayload(decision: 'accepted'));

        $component = $this->createLiveComponent('NotificationBell', ['account' => $account], $this->client);
        self::assertStringContainsString('angenommen', (string) $component->render());

        $component->call('markAllRead');

        // Unlike a task, an informational message has nothing left to come back to.
        self::assertStringNotContainsString('angenommen', (string) $component->render());
    }

    private function persist(
        Account $account,
        NotificationType $type,
        string $title,
        int $sourceItemId,
        ?NotificationPayload $payload = null,
        \App\Enum\EntryAction $action = \App\Enum\EntryAction::Created,
    ): void {
        $this->repository()->save(new Notification(
            $account,
            $type,
            105,
            $title,
            'Projektraum',
            new \DateTimeImmutable(),
            $sourceItemId,
            'user',
            $title,
            $type === NotificationType::Entry ? $action : null,
            $payload ?? new NotificationPayload(actorId: $sourceItemId),
        ));
    }
}
