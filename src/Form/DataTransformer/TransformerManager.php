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

class TransformerManager
{
    /**
     * TransformerManager constructor.
     */
    public function __construct(private iterable $transformers)
    {
    }

    public function getConverter($entity): ?DataTransformerInterface
    {
        /** @var DataTransformerInterface $transformer */
        foreach ($this->transformers as $transformer) {
            if ($transformer->supportsFormat($entity)) {
                return $transformer;
            }
        }

        return null;
    }
}
