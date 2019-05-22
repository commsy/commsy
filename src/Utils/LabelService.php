<?php

namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;

class LabelService
{
    private $legacyEnvironment;

    private $labelManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->labelManager = $this->legacyEnvironment->getEnvironment()->getLabelManager();
    }

    public function getLabel($itemId)
    {
        $label = $this->labelManager->getItem($itemId);
        return $label;
    }
}