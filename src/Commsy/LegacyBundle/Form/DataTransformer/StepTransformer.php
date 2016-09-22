<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class StepTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_step_item object to an array
     *
     * @param cs_step_item $stepItem
     * @return array
     */
    public function transform($stepItem)
    {
        $stepData = array();

        if ($stepItem) {
            $stepData['title'] = $stepItem->getTitle();
            $stepData['description'] = $stepItem->getDescription();
            $stepData['permission'] = $stepItem->isPrivateEditing();
        }

        return $stepData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $stepObject
     * @param array $materialData
     * @return cs_step_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($stepObject, $stepData)
    {
        $stepObject->setTitle($stepData['title']);
        $stepObject->setDescription($stepData['description']);

        if ($stepData['permission']) {
            $stepObject->setPrivateEditing('0');
        } else {
            $stepObject->setPrivateEditing('1');
        }

        return $stepObject;
    }
}