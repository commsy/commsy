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

class SingleContextFilterCondition implements FilterConditionInterface
{
    /**
     * @var int|null context id
     */
    private ?int $contextId = null;

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setContextId(int $contextId): SingleContextFilterCondition
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $contextTerm = new Terms('contextId');
        $contextTerm->setTerms([$this->contextId]);

        $parentTerm = new Terms('parentId');
        $parentTerm->setTerms([$this->contextId]);

        return [$contextTerm, $parentTerm];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_SHOULD;
    }
}
