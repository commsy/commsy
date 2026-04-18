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

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_assessments_manager;
use cs_environment;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class AssessmentService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_assessments_manager $assessmentManager;

    private readonly Connection $connection;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->assessmentManager = $this->legacyEnvironment->getAssessmentManager();
        $this->assessmentManager->reset();

        $this->connection = $entityManager->getConnection();
    }

    public function getListAverageRatings($itemIds)
    {
        return $this->assessmentManager->getAssessmentForItemAverageByIDArray($itemIds);
    }

    public function getRatingDetail($item)
    {
        return $this->assessmentManager->getAssessmentForItemDetail($item);
    }

    public function getAverageRatingDetail($item)
    {
        return $this->assessmentManager->getAssessmentForItemAverage($item);
    }

    public function getOwnRatingDetail($item)
    {
        return $this->assessmentManager->getAssessmentForItemOwn($item);
    }

    public function rateItem($item, $vote)
    {
        return $this->assessmentManager->addAssessmentForItem($item, $vote);
    }

    /**
     * Soft-deletes the current user's rating for the given item.
     *
     * Replaces the legacy `cs_assessments_manager::delete()` cascade
     * (UPDATE `assessments` + parent UPDATE `items`) with an explicit
     * two-statement DBAL path so we can retire the legacy manager
     * `delete()` methods without a soft-delete gap.
     */
    public function removeRating($item): void
    {
        $itemId = (int) $this->assessmentManager->getItemIDForOwn($item->getItemId());
        if ($itemId === 0) {
            return;
        }

        $deleterId = (int) ($this->legacyEnvironment->getCurrentUserItem()?->getItemID() ?: 0);

        $this->connection->executeStatement(
            'UPDATE assessments SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deletion_date = NOW(), deleter_id = :deleterId WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );
    }
}
