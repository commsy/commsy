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
            if ($item->getWorkflowResubmissionDate() != '0000-00-00 00:00:00') {
                $itemData['workflowResubmissionDate'] = new \DateTime($item->getWorkflowResubmissionDate());
            }
            
            $itemData['workflowResubmissionWho'] = $item->getWorkflowResubmissionWho();
            $itemData['workflowResubmissionWhoAdditional'] = $item->getWorkflowResubmissionWhoAdditional();
            $itemData['workflowResubmissionTrafficLight'] = $item->getWorkflowResubmissionTrafficLight();
            if ($item->getWorkflowValidity() == '1') {
                $itemData['workflowValidity'] = true;
            } else {
                $itemData['workflowValidity'] = false;
            }
            if ($item->getWorkflowValidityDate() != '0000-00-00 00:00:00') {
                $itemData['workflowValidityDate'] = new \DateTime($item->getWorkflowValidityDate());
            }
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
     * @return \cs_item
     */
    public function applyTransformation($item, $itemData)
    {
        // workflow resubmission
        $item->setWorkflowTrafficLight($itemData['workflowTrafficLight']);
        if ($itemData['workflowResubmission']) {
            $item->setWorkflowResubmission('1');

            if ($itemData['workflowResubmissionDate']) {
                $item->setWorkflowResubmissionDate($itemData['workflowResubmissionDate']->format('Y-m-d H:i:s'));
            } else {
                $item->setWorkflowResubmissionDate($itemData['workflowResubmissionDate']);
            }
            $item->setWorkflowResubmissionWho($itemData['workflowResubmissionWho']);
            $item->setWorkflowResubmissionWhoAdditional($itemData['workflowResubmissionWhoAdditional']);
            $item->setWorkflowResubmissionTrafficLight($itemData['workflowResubmissionTrafficLight']);
        } else {
            // reset data
            $item->setWorkflowResubmission('-1');
            $item->setWorkflowResubmissionDate(null);
            $item->setWorkflowResubmissionWho('');
            $item->setWorkflowResubmissionWhoAdditional(null);
            $item->setWorkflowResubmissionTrafficLight('3_none');
        }

        // workflow validity
        if ($itemData['workflowValidity']) {
            $item->setWorkflowValidity('1');
            
            if ($itemData['workflowValidityDate']) {
                $item->setWorkflowValidityDate($itemData['workflowValidityDate']->format('Y-m-d H:i:s'));
            } else {
                $item->setWorkflowValidityDate($itemData['workflowValidityDate']);
            }
            $item->setWorkflowValidityWho($itemData['workflowValidityWho']);
            $item->setWorkflowValidityWhoAdditional($itemData['workflowValidityWhoAdditional']);
            $item->setWorkflowValidityTrafficLight($itemData['workflowValidityTrafficLight']);
        } else {
            // reset data
            $item->setWorkflowValidity('-1');
            $item->setWorkflowValidityDate(null);
            $item->setWorkflowValidityWho('');
            $item->setWorkflowValidityWhoAdditional(null);
            $item->setWorkflowValidityTrafficLight('3_none');
        }
        
        return $item;
    }
}