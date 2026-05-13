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

namespace Tests\Integration\Repository;

use App\Repository\TasksRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Repository contract for {@see TasksRepository::getTasksByContextId()}.
 *
 * The method is, charitably, in disrepair:
 *  - Zero callers in src/. Effectively dead code.
 *  - Returns a {@see QueryBuilder} (not executed results) — likely a
 *    forgotten `->getQuery()->getResult()` somewhere along the way.
 *  - The Tasks entity declares a `modifier` ManyToOne mapping
 *    `tasks.modifier_id`, but that column does not exist in the
 *    schema; SELECT against the entity therefore fails at the SQL
 *    level. Pre-existing on the branch baseline (see Tasks.php at
 *    cefc6f6d5) — not introduced by the EntityUsersTrait rollout.
 *
 * Given those layers, an "execute the query and inspect rows" test
 * isn't useful. Instead we exercise the DQL path to the point where
 * Doctrine parses it: that is enough to catch a future field-rename
 * (e.g. r.deleter → r.foo) which is the same regression class that
 * burned us during the trait rollout.
 *
 * If/when Tasks' schema mismatch is fixed (or the dead method is
 * deleted), this test should be revisited.
 */
final class TasksRepositoryTest extends KernelTestCase
{
    public function testGetTasksByContextIdProducesParseableDql(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(TasksRepository::class);

        $qb = $repository->getTasksByContextId(0);
        self::assertInstanceOf(QueryBuilder::class, $qb);

        // Force DQL → SQL compilation. This is the seam that broke
        // when r.deleterId stopped existing. It does NOT execute, so
        // the missing tasks.modifier_id column at runtime is not hit.
        $sql = $qb->getQuery()->getSQL();
        self::assertIsString($sql);
        self::assertStringContainsString('FROM tasks', $sql);
    }
}
