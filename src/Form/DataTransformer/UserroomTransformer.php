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

class UserroomTransformer extends AbstractTransformer
{
    protected $entity = 'userroom';

    /**
     * Transforms a cs_userroom_item object to an array.
     *
     * @param \cs_userroom_item $userroom
     *
     * @return array
     */
    public function transform($userroom)
    {
        $projectData = [];

        if ($userroom) {
            $projectData['title'] = html_entity_decode($userroom->getTitle());
            $projectData['description'] = $userroom->getDescription();
        }

        return $projectData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $userroomObject
     * @param array  $userroomData
     *
     * @return \cs_userroom_item|null
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($userroomObject, $userroomData)
    {
        $userroomObject->setTitle($userroomData['title']);
        $userroomObject->setDescription($userroomData['description']);

        return $userroomObject;
    }
}
