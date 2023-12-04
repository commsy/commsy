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

use cs_discussionarticle_item;

class DiscussionarticleTransformer extends AbstractTransformer
{
    protected $entity = 'discarticle';

    /**
     * Transforms a cs_discussion_item object to an array.
     *
     * @param cs_discussionarticle_item $discussionarticleItem
     */
    public function transform($discussionarticleItem): array
    {
        $discussionarticleData = [];

        if ($discussionarticleItem) {
            $discussionarticleData['description'] = $discussionarticleItem->getDescription();
        }

        return $discussionarticleData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param cs_discussionarticle_item $discussionArticle
     * @param array                      $data
     */
    public function applyTransformation($discussionArticle, $data): cs_discussionarticle_item
    {
        $discussionArticle->setDescription($data['description']);

        // editable only by creator
        $discussionArticle->setPrivateEditing('0');

        return $discussionArticle;
    }
}
