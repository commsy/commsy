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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Covers the notifications page: it lists the logged-in account's own
 * notifications, never another account's, and the navbar bell shows the
 * unread badge.
 */
class NotificationControllerTest extends AbstractApplicationTestCase
{
    public function testListShowsOwnNotificationsAndBellBadgeButNotOthers(): void
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'member.local',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
        $stranger = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $repository = static::getContainer()->get(NotificationRepository::class);
        $repository->save($this->notification($account, 'A new material for me'));
        $repository->save($this->notification($stranger, 'Not for me'));

        $portalId = $portal->getId();
        $this->loginAsUser($portalId, 'member.local', $account->getPlainPassword());

        $crawler = $this->client->request('GET', "/portal/{$portalId}/notifications");

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('A new material for me', $content);
        self::assertStringNotContainsString('Not for me', $content, 'must not leak another account\'s notifications');

        $indicator = $crawler->filter('#cs-nav-notification-indicator');
        self::assertGreaterThan(0, $indicator->count(), 'bell badge must be present');
        self::assertStringContainsString('1', $indicator->text(), 'bell badge shows the single unread');
    }

    private function notification(Account $recipient, string $title): Notification
    {
        return new Notification(
            $recipient,
            NotificationType::NewEntry,
            10,
            $title,
            'Project room',
            new \DateTimeImmutable(),
            999,
            'material',
            'Alice',
        );
    }
}
