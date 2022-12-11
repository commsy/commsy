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

namespace App\Form\DataTransformer;

class ItemTransformer extends AbstractTransformer
{
    protected $entity = 'item';

    /**
     * Transforms fields of a cs_item object to an array.
     *
     * @param cs_material_item $materialItem
     *
     * @return array
     */
    public function transform($item)
    {
        $itemData = [];

        if ($item) {
            $itemData['workflowTrafficLight'] = $item->getWorkflowTrafficLight();
            if ('1' == $item->getWorkflowResubmission()) {
                $itemData['workflowResubmission'] = true;
            } else {
                $itemData['workflowResubmission'] = false;
            }
            if ('0000-00-00 00:00:00' != $item->getWorkflowResubmissionDate()) {
                $itemData['workflowResubmissionDate'] = new \DateTime($item->getWorkflowResubmissionDate());
            }

            $itemData['workflowResubmissionWho'] = $item->getWorkflowResubmissionWho();
            $itemData['workflowResubmissionWhoAdditional'] = $item->getWorkflowResubmissionWhoAdditional();
            $itemData['workflowResubmissionTrafficLight'] = $item->getWorkflowResubmissionTrafficLight();
            if ('1' == $item->getWorkflowValidity()) {
                $itemData['workflowValidity'] = true;
            } else {
                $itemData['workflowValidity'] = false;
            }
            if ('0000-00-00 00:00:00' != $item->getWorkflowValidityDate()) {
                $itemData['workflowValidityDate'] = new \DateTime($item->getWorkflowValidityDate());
            }
            $itemData['workflowValidityWho'] = $item->getWorkflowValidityWho();
            $itemData['workflowValidityWhoAdditional'] = $item->getWorkflowValidityWhoAdditional();
            $itemData['workflowValidityTrafficLight'] = $item->getWorkflowValidityTrafficLight();
        }

        return $itemData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $materialObject
     * @param array  $materialData
     *
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
