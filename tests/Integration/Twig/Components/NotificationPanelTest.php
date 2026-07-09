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
use App\Enum\NotificationAction;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\AccountFactory;

/**
 * Pins the {@see \App\Twig\Components\NotificationPanel} live component: it groups
 * an item's events into one row, dismisses a whole entry / all entries in place,
 * and scopes to one room when a context id is given.
 */
final class NotificationPanelTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testRendersEntriesAndDismissesOneInPlace(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();
        $this->persist($account, sourceItemId: 100, title: 'First entry');
        $this->persist($account, sourceItemId: 200, title: 'Second entry');

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account]);

        $html = (string) $component->render();
        self::assertStringContainsString('First entry', $html);
        self::assertStringContainsString('Second entry', $html);

        // Dismiss is keyed by the source item id (the whole grouped entry).
        $component->call('dismiss', ['id' => 100]);

        $afterDismiss = (string) $component->render();
        self::assertStringNotContainsString('First entry', $afterDismiss);
        self::assertStringContainsString('Second entry', $afterDismiss);
    }

    public function testGroupsEventsOfTheSameItemIntoOneRowAndDismissClearsAll(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();
        $this->persist($account, sourceItemId: 500, title: 'My material', action: NotificationAction::Created, createdAt: new \DateTimeImmutable('2026-06-01 10:00:00'));
        $this->persist($account, sourceItemId: 500, title: 'My material', action: NotificationAction::Edited, createdAt: new \DateTimeImmutable('2026-06-02 10:00:00'));
        $this->persist($account, sourceItemId: 600, title: 'Other entry', createdAt: new \DateTimeImmutable('2026-05-01 10:00:00'));

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account]);

        $groups = $component->component()->getGroups();
        self::assertCount(2, $groups, 'the two events for item 500 collapse into one row');
        self::assertCount(2, $groups[0]->events(), 'the active item (500) is first and holds both of its events');

        // Dismissing the grouped entry clears all of its events.
        $component->call('dismiss', ['id' => 500]);

        self::assertSame(1, $this->repository()->count([]), 'both events of item 500 are gone, item 600 remains');
        self::assertStringNotContainsString('My material', (string) $component->render());
    }

    public function testDismissAllClearsThePanel(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();
        $this->persist($account, sourceItemId: 1, title: 'One');
        $this->persist($account, sourceItemId: 2, title: 'Two');

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account]);
        $component->call('dismissAll');

        self::assertSame(0, $this->repository()->count([]));
        $html = (string) $component->render();
        self::assertStringNotContainsString('One', $html);
        self::assertStringNotContainsString('Two', $html);
    }

    public function testScopesToOneRoomWhenContextGiven(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();
        $this->persist($account, sourceItemId: 1, title: 'In room ten', contextId: 10);
        $this->persist($account, sourceItemId: 2, title: 'In room twenty', contextId: 20);

        $component = $this->createLiveComponent('NotificationPanel', ['account' => $account, 'contextId' => 10]);

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
        NotificationAction $action = NotificationAction::Created,
        ?\DateTimeImmutable $createdAt = null,
    ): Notification {
        $notification = new Notification(
            $account,
            NotificationType::NewEntry,
            $contextId,
            $title,
            'Room',
            $createdAt ?? new \DateTimeImmutable(),
            $sourceItemId,
            'material',
            'Actor',
            $action,
        );
        $this->repository()->save($notification);

        return $notification;
    }
}
