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

use App\Entity\Terms;
use App\Repository\TermsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * TermsRepository defines no custom query methods — only the inherited
 * ServiceEntityRepository surface. This smoke test confirms wiring +
 * basic findAll() against the Terms entity. The deeper Doctrine
 * assertions are owned by the bundle itself.
 */
final class TermsRepositoryTest extends KernelTestCase
{
    public function testRepositoryIsWiredAndFindAllExecutes(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(TermsRepository::class);

        $results = $repository->findAll();

        self::assertContainsOnlyInstancesOf(Terms::class, $results);
    }
}
