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

use App\Entity\AccountSetting;
use App\Repository\AccountSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * AccountSettingsRepository ships only commented-out method stubs;
 * there is no custom query surface. Smoke test verifies wiring +
 * inherited findAll() against the AccountSetting entity.
 */
final class AccountSettingsRepositoryTest extends KernelTestCase
{
    public function testRepositoryIsWiredAndFindAllExecutes(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(AccountSettingsRepository::class);

        $results = $repository->findAll();

        self::assertContainsOnlyInstancesOf(AccountSetting::class, $results);
    }
}
