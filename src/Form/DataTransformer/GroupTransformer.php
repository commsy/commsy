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

use cs_group_item;
use Symfony\Component\Form\Exception\TransformationFailedException;

class GroupTransformer extends AbstractTransformer
{
    protected $entity = 'group';

    /**
     * Transforms a cs_group_item object to an array.
     *
     * @param cs_group_item $groupItem
     *
     * @return array
     */
    public function transform($groupItem)
    {
        $groupData = [];

        if ($groupItem) {
            $groupData['title'] = html_entity_decode($groupItem->getTitle());
            $groupData['description'] = $groupItem->getDescription();
        }

        return $groupData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $groupObject
     * @param array  $groupData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($groupObject, $groupData): cs_group_item
    {
        $groupObject->setTitle($groupData['title']);
        $groupObject->setDescription($groupData['description']);

        return $groupObject;
    }
}
