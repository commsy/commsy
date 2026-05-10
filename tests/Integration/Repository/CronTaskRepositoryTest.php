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

use App\Entity\CronTask;
use App\Repository\CronTaskRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * CronTaskRepository has no custom query methods. Smoke test verifies
 * service wiring + the inherited findAll() against the entity.
 */
final class CronTaskRepositoryTest extends KernelTestCase
{
    public function testRepositoryIsWiredAndFindAllExecutes(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(CronTaskRepository::class);

        $results = $repository->findAll();

        self::assertContainsOnlyInstancesOf(CronTask::class, $results);
    }
}
