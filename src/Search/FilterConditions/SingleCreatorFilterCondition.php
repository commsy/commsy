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

namespace App\Search\FilterConditions;

use Elastica\Query\Terms;

class SingleCreatorFilterCondition implements FilterConditionInterface
{
    private ?string $creator = null;

    public function setCreator(string $creator): SingleCreatorFilterCondition
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        if ('all' === $this->creator) {
            return [];
        }

        $creatorTerm = new Terms('creator.fullName.raw');
        $creatorTerm->setTerms([$this->creator]);

        return [$creatorTerm];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
