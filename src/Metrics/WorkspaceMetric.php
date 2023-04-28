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

use App\Entity\Room;
use Doctrine\Persistence\ManagerRegistry;

class WorkspaceMetric extends AbstractMetric implements MetricInterface
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public function update(): void
    {
        $workspaceNumberTotal = $this->getCollectorRegistry()->getOrRegisterGauge(
            $this->getNamespace(),
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
