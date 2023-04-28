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

class AssessmentService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_assessments_manager $assessmentManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
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

    public function removeRating($item)
    {
        $item_id = $this->assessmentManager->getItemIDForOwn($item->getItemId());

        return $this->assessmentManager->delete($item_id);
    }
}
