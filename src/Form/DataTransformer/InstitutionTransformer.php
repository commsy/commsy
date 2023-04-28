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

use cs_label_item;
use DateTime;
use Symfony\Component\Form\Exception\TransformationFailedException;

class InstitutionTransformer extends AbstractTransformer
{
    protected $entity = 'institution';

    /**
     * Transforms a cs_group_item object to an array.
     *
     * @param cs_label_item $labelItem
     *
     * @return array
     */
    public function transform($labelItem)
    {
        $labelData = [];

        if ($labelItem) {
            $labelData['title'] = html_entity_decode($labelItem->getTitle());
            $labelData['description'] = $labelItem->getDescription();
            $labelData['permission'] = $labelItem->isPrivateEditing();

            if ($labelItem->isNotActivated()) {
                $labelData['hidden'] = true;

                $activating_date = $labelItem->getActivatingDate();
                if (!stristr((string) $activating_date, '9999')) {
                    $datetime = new DateTime($activating_date);
                    $labelData['hiddendate']['date'] = $datetime;
                    $labelData['hiddendate']['time'] = $datetime;
                }
            }
        }

        return $labelData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param cs_label_item $labelObject
     * @param array          $labelData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($labelObject, $labelData): cs_label_item
    {
        $labelObject->setTitle($labelData['title']);
        $labelObject->setDescription($labelData['description']);

        if ($labelData['permission']) {
            $labelObject->setPrivateEditing('0');
        } else {
            $labelObject->setPrivateEditing('1');
        }

        if (isset($labelData['hidden'])) {
            if ($labelData['hidden']) {
                if ($labelData['hiddendate']['date']) {
                    // add validdate to validdate
                    $datetime = $labelData['hiddendate']['date'];
                    if ($labelData['hiddendate']['time']) {
                        $time = explode(':', (string) $labelData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $labelObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $labelObject->setModificationDate('9999-00-00 00:00:00');
                }
            } else {
                if ($labelObject->isNotActivated()) {
                    $labelObject->setModificationDate(getCurrentDateTimeInMySQL());
                }
            }
        } else {
            if ($labelObject->isNotActivated()) {
                $labelObject->setModificationDate(getCurrentDateTimeInMySQL());
            }
        }

        return $labelObject;
    }
}
