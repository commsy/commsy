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
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\AccountFactory;

/**
 * Pins the {@see \App\Twig\Components\NotificationIndicator} live component: it
 * exposes the account-wide unread count and links to the dashboard (no popup).
 */
final class NotificationIndicatorTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testExposesUnreadCountAndLinksToDashboard(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();
        $this->repository()->save(new Notification(
            $account,
            NotificationType::Entry,
            105,
            'Fresh entry',
            'Project room',
            new \DateTimeImmutable(),
            1,
            'material',
            'Actor',
        ));

        $component = $this->createLiveComponent('NotificationIndicator', [
            'account' => $account,
            'uikit3' => true,
            'roomId' => 112,
        ]);

        self::assertSame(1, $component->component()->getUnreadCount());
        self::assertStringContainsString('/dashboard/112', (string) $component->render());
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }
}
