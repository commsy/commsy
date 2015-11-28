<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use \DateTime;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class ItemTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms fields of a cs_item object to an array
     *
     * @param cs_material_item $materialItem
     * @return array
     */
    public function transform($item)
    {
        $itemData = array();

        if ($item) {
            $itemData['workflowTrafficLight'] = $item->getWorkflowTrafficLight();
            if ($item->getWorkflowResubmission() == '1') {
                $itemData['workflowResubmission'] = true;
            } else {
                $itemData['workflowResubmission'] = false;
            }
            $itemData['workflowResubmissionDate'] = new DateTime($item->getWorkflowResubmissionDate());
            $itemData['workflowResubmissionWho'] = $item->getWorkflowResubmissionWho();
            $itemData['workflowResubmissionWhoAdditional'] = $item->getWorkflowResubmissionWhoAdditional();
            $itemData['workflowResubmissionTrafficLight'] = $item->getWorkflowResubmissionTrafficLight();
            if ($item->getWorkflowValidity() == '1') {
                $itemData['workflowValidity'] = true;
            } else {
                $itemData['workflowValidity'] = false;
            }
            $itemData['workflowValidityDate'] = new DateTime($item->getWorkflowValidityDate());
            $itemData['workflowValidityWho'] = $item->getWorkflowValidityWho();
            $itemData['workflowValidityWhoAdditional'] = $item->getWorkflowValidityWhoAdditional();
            $itemData['workflowValidityTrafficLight'] = $item->getWorkflowValidityTrafficLight();
        }

        return $itemData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $materialObject
     * @param array $materialData
     * @return cs_material_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($item, $itemData)
    {
        $item->setWorkflowTrafficLight($itemData['workflowTrafficLight']);
        if ($itemData['workflowResubmission']) {
            $item->setWorkflowResubmission('1');
        } else {
            $item->setWorkflowResubmission('-1');
        }
        $item->setWorkflowResubmissionDate($itemData['workflowResubmissionDate']->format('Y-m-d H:i:s'));
        $item->setWorkflowResubmissionWho($itemData['workflowResubmissionWho']);
        $item->setWorkflowResubmissionWhoAdditional($itemData['workflowResubmissionWhoAdditional']);
        $item->setWorkflowResubmissionTrafficLight($itemData['workflowResubmissionTrafficLight']);
        if ($itemData['workflowValidity']) {
            $item->setWorkflowValidity('1');
        } else {
            $item->setWorkflowValidity('-1');
        }
        $item->setWorkflowValidityDate($itemData['workflowValidityDate']->format('Y-m-d H:i:s'));
        $item->setWorkflowValidityWho($itemData['workflowValidityWho']);
        $item->setWorkflowValidityWhoAdditional($itemData['workflowValidityWhoAdditional']);
        $item->setWorkflowValidityTrafficLight($itemData['workflowValidityTrafficLight']);
        return $item;
    }
}