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

class StepTransformer extends AbstractTransformer
{
    protected $entity = 'step';

    /**
     * Transforms a cs_step_item object to an array.
     *
     * @param cs_step_item $stepItem
     *
     * @return array
     */
    public function transform($stepItem)
    {
        $stepData = [];

        if ($stepItem) {
            $stepData['description'] = $stepItem->getDescription();
        }

        return $stepData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $stepObject
     * @param array  $stepData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($stepObject, $stepData): cs_step_item
    {
        $stepObject->setDescription($stepData['description']);

        return $stepObject;
    }
}
