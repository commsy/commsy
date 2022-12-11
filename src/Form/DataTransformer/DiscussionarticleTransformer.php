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

class DiscussionarticleTransformer extends AbstractTransformer
{
    protected $entity = 'discarticle';

    /**
     * Transforms a cs_discussion_item object to an array.
     *
     * @param cs_discarticle_item $discussionarticleItem
     *
     * @return array
     */
    public function transform($discussionarticleItem)
    {
        $discussionarticleData = [];

        if ($discussionarticleItem) {
            $discussionarticleData['title'] = html_entity_decode($discussionarticleItem->getTitle());
            $discussionarticleData['permission'] = $discussionarticleItem->isPrivateEditing();
            $discussionarticleData['description'] = $discussionarticleItem->getDescription();
        }

        return $discussionarticleData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $discussionObject
     * @param array  $discussionData
     *
     * @return cs_discussion_item|null
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($discussionarticleObject, $discussionarticleData)
    {
        $discussionarticleObject->setTitle($discussionarticleData['title']);

        if ($discussionarticleData['permission']) {
            $discussionarticleObject->setPrivateEditing('0');
        } else {
            $discussionarticleObject->setPrivateEditing('1');
        }

        $discussionarticleObject->setDescription($discussionarticleData['description']);

        return $discussionarticleObject;
    }
}
