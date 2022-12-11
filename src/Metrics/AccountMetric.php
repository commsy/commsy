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

namespace App\Metrics;

use App\Entity\Account;
use App\Entity\Portal;
use Doctrine\Persistence\ManagerRegistry;

class AccountMetric extends AbstractMetric implements MetricInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function update(): void
    {
        $accountNumberTotal = $this->getCollectorRegistry()->getOrRegisterGauge(
            $this->getNamespace(),
            'account_total',
            'Number of accounts',
            ['portal']
        );

        $accountRepository = $this->doctrine->getRepository(Account::class);
        $countByPortal = $accountRepository->countByPortal();

        foreach ($countByPortal as $detail) {
            /** @var Portal $portal */
            $portal = $detail['portal'];
            $accountNumberTotal->set($detail['count'], [$portal->getTitle()]);
        }
    }
}
