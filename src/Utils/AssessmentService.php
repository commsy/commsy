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

use App\Assessment\AssessmentDeleter;
use App\Services\LegacyEnvironment;
use cs_assessments_manager;
use cs_environment;

class AssessmentService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_assessments_manager $assessmentManager;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly AssessmentDeleter $assessmentDeleter,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->assessmentManager = $this->legacyEnvironment->getAssessmentManager();
        $this->assessmentManager->reset();
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
     * Thin wrapper: the actual cascade (assessment row + shared `items`
     * twin) lives in {@see AssessmentDeleter::softDelete()}. This method
     * only resolves the own-rating item id and the current deleter id
     * from the legacy environment and hands off.
     */
    public function removeRating($item): void
    {
        $itemId = (int) $this->assessmentManager->getItemIDForOwn($item->getItemId());
        if ($itemId === 0) {
            return;
        }

        $deleterId = (int) ($this->legacyEnvironment->getCurrentUserItem()?->getItemID() ?: 0);

        $this->assessmentDeleter->softDelete($itemId, $deleterId);
    }
}
