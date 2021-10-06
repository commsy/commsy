<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_environment;
use cs_labels_manager;

class LabelService
{
    /** @var cs_environment $legacyEnvironment */
    private cs_environment $legacyEnvironment;

    /** @var cs_labels_manager $labelManager */
    private cs_labels_manager $labelManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->labelManager = $this->legacyEnvironment->getLabelManager();
    }

    public function getLabel($itemId)
    {
        $label = $this->labelManager->getItem($itemId);
        return $label;
    }

    /**
     * Creates and returns a new hashtag with the given name and context.
     * @param string $hashtagName
     * @param int $contextId
     * @return \cs_label_item
     */
    public function getNewHashtag(string $hashtagName, int $contextId): \cs_label_item
    {
        $hashtag = $this->labelManager->getNewItem();

        $hashtag->setLabelType('buzzword');
        $hashtag->setContextID($contextId);
        $hashtag->setCreatorItem($this->legacyEnvironment->getCurrentUserItem());
        $hashtag->setName($hashtagName);

        $hashtag->save();

        return $hashtag;
    }
}