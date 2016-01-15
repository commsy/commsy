<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class AssessmentService
{
    private $legacyEnvironment;

    private $assessmentManager;
    

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
}