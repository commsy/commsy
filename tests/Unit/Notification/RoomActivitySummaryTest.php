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

use App\Notification\RoomActivitySummary;
use PHPUnit\Framework\TestCase;

class RoomActivitySummaryTest extends TestCase
{
    public function testIndicatorIsNewAsSoonAsOneEntryWasCreated(): void
    {
        self::assertSame('new', $this->summary(created: 1, edited: 0, annotated: 0)->indicatorStatus());
        self::assertSame('new', $this->summary(created: 1, edited: 7, annotated: 3)->indicatorStatus());
    }

    public function testIndicatorIsChangedWithoutCreations(): void
    {
        self::assertSame('changed', $this->summary(created: 0, edited: 2, annotated: 0)->indicatorStatus());
        self::assertSame('changed', $this->summary(created: 0, edited: 0, annotated: 1)->indicatorStatus());
    }

    private function summary(int $created, int $edited, int $annotated): RoomActivitySummary
    {
        return new RoomActivitySummary(99, 'Projektraum', $created, $edited, $annotated, new \DateTimeImmutable('2026-06-01 10:00:00'));
    }
}
