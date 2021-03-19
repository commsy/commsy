<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class StepTransformer extends AbstractTransformer
{
    protected $entity = 'step';

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
            $stepData['description'] = $stepItem->getDescription();
        }

        return $stepData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $stepObject
     * @param array $stepData
     * @return cs_step_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($stepObject, $stepData)
    {
        $stepObject->setDescription($stepData['description']);

        return $stepObject;
    }
}