<?php

namespace App\Metrics;

use App\Entity\Room;
use Doctrine\Persistence\ManagerRegistry;

class WorkspaceMetric extends AbstractMetric implements MetricInterface
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;

    public function __construct(
        ManagerRegistry $doctrine
    ) {
        $this->doctrine = $doctrine;
    }

    public function update(): void
    {
        $registry = PrometheusCollector::getCollectorRegistry();
        $workspaceNumberTotal = $registry->getOrRegisterGauge(
            $this->getCacheKey(),
            'workspace_total',
            'Number of workspaces',
            ['portal', 'type']
        );

        $roomRepository = $this->doctrine->getRepository(Room::class);
        $countByPortalAndType = $roomRepository->countByPortalAndType();

        foreach ($countByPortalAndType as $detail) {
            $workspaceNumberTotal->set($detail['count'], [$detail['portal'], $detail['type']]);
        }
    }
}
