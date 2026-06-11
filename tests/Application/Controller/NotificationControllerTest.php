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
use App\Entity\Portal;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Covers the notifications page and its actions: listing only the logged-in
 * account's notifications, the navbar bell badge, click-through (mark read +
 * redirect to the entry), mark-all-read, and access control against another
 * account's notifications.
 *
 * Note: ids/passwords are captured as scalars before logging in. The login and
 * each request detach the Foundry entities, so they must not be reused as
 * managed objects afterwards.
 */
class NotificationControllerTest extends AbstractApplicationTestCase
{
    public function testListShowsOwnNotificationsAndBellBadgeButNotOthers(): void
    {
        [$portal, $account] = $this->member();
        $portalId = $portal->getId();
        $password = $account->getPlainPassword();
        $stranger = $this->extraAccount($portal);

        $this->repository()->save($this->notification($account, 'A new material for me', 1));
        $this->repository()->save($this->notification($stranger, 'Not for me', 1));

        $this->loginAsUser($portalId, 'member.local', $password);
        $crawler = $this->client->request('GET', "/portal/{$portalId}/notifications");

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('A new material for me', $content);
        self::assertStringNotContainsString('Not for me', $content, 'must not leak another account\'s notifications');

        $indicator = $crawler->filter('#cs-nav-notification-indicator');
        self::assertGreaterThan(0, $indicator->count(), 'bell badge must be present');
        self::assertStringContainsString('1', $indicator->text(), 'bell badge shows the single unread');
    }

    public function testOpenMarksReadAndRedirectsToEntry(): void
    {
        [$portal, $account] = $this->member();
        $portalId = $portal->getId();
        $password = $account->getPlainPassword();

        $notification = $this->notification($account, 'Open me', 999);
        $this->repository()->save($notification);
        $id = $notification->getId();

        $this->loginAsUser($portalId, 'member.local', $password);
        $this->client->request('GET', "/portal/{$portalId}/notifications/{$id}");

        $this->assertResponseRedirects('/room/10/material/999');
        self::assertNotNull($this->repository()->find($id)->getReadAt(), 'click marks it read');
    }

    public function testOpenRejectsAnotherAccountsNotification(): void
    {
        [$portal, $account] = $this->member();
        $portalId = $portal->getId();
        $password = $account->getPlainPassword();

        $stranger = $this->extraAccount($portal);
        $strangerNotification = $this->notification($stranger, 'Secret', 999);
        $this->repository()->save($strangerNotification);
        $strangerId = $strangerNotification->getId();

        $this->loginAsUser($portalId, 'member.local', $password);
        $this->client->request('GET', "/portal/{$portalId}/notifications/{$strangerId}");

        $this->assertResponseStatusCodeSame(404);
    }

    public function testReadAllMarksEverythingRead(): void
    {
        [$portal, $account] = $this->member();
        $portalId = $portal->getId();
        $password = $account->getPlainPassword();
        $accountId = $account->getId();

        $this->repository()->save($this->notification($account, 'One', 1));
        $this->repository()->save($this->notification($account, 'Two', 2));

        $this->loginAsUser($portalId, 'member.local', $password);
        $crawler = $this->client->request('GET', "/portal/{$portalId}/notifications");
        self::assertSame(2, $this->unreadCount($accountId));

        $form = $crawler->filter('form[action$="/read-all"]')->first()->form();
        $this->client->submit($form);

        $this->assertResponseRedirects("/portal/{$portalId}/notifications");
        self::assertSame(0, $this->unreadCount($accountId));
    }

    /**
     * @return array{0: Portal, 1: Account}
     */
    private function member(): array
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

        return [$portal, $account];
    }

    private function extraAccount(Portal $portal): Account
    {
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
    }

    private function repository(): NotificationRepository
    {
        return static::getContainer()->get(NotificationRepository::class);
    }

    private function unreadCount(int $accountId): int
    {
        $account = static::getContainer()->get(EntityManagerInterface::class)->find(Account::class, $accountId);

        return $this->repository()->countUnreadForAccount($account);
    }

    private function notification(Account $recipient, string $title, int $sourceItemId): Notification
    {
        return new Notification(
            $recipient,
            NotificationType::NewEntry,
            10,
            $title,
            'Project room',
            new \DateTimeImmutable(),
            $sourceItemId,
            'material',
            'Alice',
        );
    }
}
