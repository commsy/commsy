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

namespace Tests\Application;

use App\Entity\Account;
use App\Entity\Notification;
use App\Entity\Portal;
use App\Enum\EntryAction;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * The bell as a logged-in visitor meets it: rendered into the global navbar on
 * an ordinary page, counting one line per room and nothing that belongs to
 * somebody else. The dropdown's own behaviour is covered by the component test.
 *
 * Note: ids/passwords are captured as scalars before logging in. The login and
 * each request detach the Foundry entities, so they must not be reused as
 * managed objects afterwards.
 */
class NotificationBellNavbarTest extends AbstractApplicationTestCase
{
    public function testBadgeCountsOneLinePerRoomAndIgnoresOtherAccounts(): void
    {
        [$portal, $account] = $this->member();
        $portalId = $portal->getId();
        $password = $account->getPlainPassword();
        $stranger = $this->extraAccount($portal);

        // Two entries changed in the same room: one line, not two.
        $this->repository()->save($this->notification($account, 'A new material', 1));
        $this->repository()->save($this->notification($account, 'And another one', 2));
        $this->repository()->save($this->notification($stranger, 'Not for me', 3));

        $this->loginAsUser($portalId, 'member.local', $password);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', "/portal/{$portalId}/enter");

        $indicator = $crawler->filter('#cs-nav-notification-indicator');
        self::assertGreaterThan(0, $indicator->count(), 'bell badge must be present');
        self::assertSame('1', trim($indicator->text()), 'one room line, whatever happened inside it');
        self::assertStringNotContainsString('Not for me', $this->client->getResponse()->getContent());
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

    private function notification(Account $recipient, string $title, int $sourceItemId): Notification
    {
        return new Notification(
            $recipient,
            NotificationType::Entry,
            10,
            $title,
            'Project room',
            new \DateTimeImmutable(),
            $sourceItemId,
            'material',
            'Alice',
            EntryAction::Created,
        );
    }
}
