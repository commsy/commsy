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

use cs_project_item;

class ProjectTransformer extends AbstractTransformer
{
    protected $entity = 'project';

    /**
     * Transforms a cs_project_item object to an array.
     *
     * @param cs_project_item $projectItem
     *
     * @return array
     */
    public function transform($projectItem)
    {
        $projectData = [];

        if ($projectItem) {
            $projectData['title'] = html_entity_decode($projectItem->getTitle());
            $projectData['description'] = $projectItem->getDescription();
        }

        return $projectData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $projectObject
     * @param array  $projectData
     *
     * @return cs_project_item|null
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($projectObject, $projectData)
    {
        $projectObject->setTitle($projectData['title']);
        $projectObject->setDescription($projectData['description']);

        return $projectObject;
    }
}
