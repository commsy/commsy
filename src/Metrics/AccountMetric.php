<?php

namespace App\Metrics;

use App\Entity\Account;
use App\Entity\Portal;
use Doctrine\Persistence\ManagerRegistry;

class AccountMetric extends AbstractMetric implements MetricInterface
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
