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

use cs_annotation_item;
use cs_item;

class AnnotationTransformer extends AbstractTransformer
{
    protected $entity = 'annotation';

    /**
     * Transforms a \cs_item object to an array.
     *
     * @param cs_item $annotationItem
     *
     * @return array
     */
    public function transform($annotationItem)
    {
        $annotationData = [];

        if ($annotationItem) {
            $annotationData['description'] = $annotationItem->getDescription();
        }

        return $annotationData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $materialObject
     * @param array  $annotationData
     */
    public function applyTransformation($annotationObject, $annotationData): cs_annotation_item
    {
        $annotationObject->setDescription($annotationData['description']);

        return $annotationObject;
    }
}
