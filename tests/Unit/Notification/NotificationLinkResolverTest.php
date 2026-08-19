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

namespace Tests\Unit\Notification;

use App\Entity\Account;
use App\Entity\Notification;
use App\Enum\NotificationType;
use App\Notification\NotificationLinkResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationLinkResolverTest extends TestCase
{
    public function testResolvesEachKnownRubricToItsDetailRoute(): void
    {
        $map = [
            'announcement' => 'app_announcement_detail',
            'material' => 'app_material_detail',
            'date' => 'app_date_detail',
            'discussion' => 'app_discussion_detail',
            'todo' => 'app_todo_detail',
            'group' => 'app_group_detail',
            'topic' => 'app_topic_detail',
        ];

        foreach ($map as $type => $route) {
            $generator = $this->createMock(UrlGeneratorInterface::class);
            $generator->expects($this->once())
                ->method('generate')
                ->with($route, ['roomId' => 5, 'itemId' => 99])
                ->willReturn("/room/5/{$type}/99");

            $resolver = new NotificationLinkResolver($generator);

            self::assertSame("/room/5/{$type}/99", $resolver->resolve($this->notification($type, 5, 99)));
        }
    }

    public function testUnknownTypeResolvesToNull(): void
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator->expects($this->never())->method('generate');

        $resolver = new NotificationLinkResolver($generator);

        self::assertNull($resolver->resolve($this->notification('section', 1, 2)));
    }

    public function testMissingSourceItemResolvesToNull(): void
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator->expects($this->never())->method('generate');

        $resolver = new NotificationLinkResolver($generator);

        self::assertNull($resolver->resolve($this->notification('material', 1, null)));
    }

    private function notification(string $type, int $contextId, ?int $sourceItemId): Notification
    {
        return new Notification(
            $this->createMock(Account::class),
            NotificationType::Entry,
            $contextId,
            'Title',
            'Room',
            new \DateTimeImmutable(),
            $sourceItemId,
            $type,
            null,
        );
    }
}
