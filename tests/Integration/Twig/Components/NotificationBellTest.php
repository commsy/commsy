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
 * Pins the {@see \App\Twig\Components\NotificationBell} live component: it
 * renders the recipient's latest notifications and unread count, and the
 * mark-all-read live action clears the unread count in place.
 */
final class NotificationBellTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testRendersUnreadAndMarksAllReadInPlace(): void
    {
        self::bootKernel();
        $account = AccountFactory::createOne();
        $this->repository()->save(new Notification(
            $account,
            NotificationType::NewEntry,
            10,
            'Fresh entry',
            'Project room',
            new \DateTimeImmutable(),
            1,
            'material',
            null,
        ));

        $component = $this->createLiveComponent(
            name: 'NotificationBell',
            data: ['account' => $account],
        );

        self::assertStringContainsString('Fresh entry', (string) $component->render());
        self::assertSame(1, $component->component()->getUnreadCount());

        $component->call('markAllRead');

        self::assertSame(0, $component->component()->getUnreadCount(), 'mark-all-read clears the badge in place');
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }
}
