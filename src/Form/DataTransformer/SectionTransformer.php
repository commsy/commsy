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

use cs_section_item;

class SectionTransformer extends AbstractTransformer
{
    protected $entity = 'section';

    /**
     * Transforms a cs_material_item object to an array.
     *
     * @param cs_section_item $sectionItem
     *
     * @return array
     */
    public function transform($sectionItem)
    {
        $sectionData = [];

        if ($sectionItem) {
            $sectionData['title'] = html_entity_decode($sectionItem->getTitle());
            $sectionData['description'] = $sectionItem->getDescription();
            $sectionData['permission'] = $sectionItem->isPrivateEditing();
        }

        return $sectionData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $sectionObject
     * @param array  $sectionData
     *
     * @return cs_section_item|null
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($sectionObject, $sectionData)
    {
        $sectionObject->setTitle($sectionData['title']);
        $sectionObject->setDescription($sectionData['description']);

        if ($sectionData['permission']) {
            $sectionObject->setPrivateEditing('0');
        } else {
            $sectionObject->setPrivateEditing('1');
        }

        return $sectionObject;
    }
}
