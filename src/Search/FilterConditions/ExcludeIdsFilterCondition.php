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

use Elastica\Query\Ids;

final class ExcludeIdsFilterCondition implements FilterConditionInterface
{
    /**
     * @var int[] $ids
     */
    private array $ids;

    public function setIds(array $ids): static
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    public function getConditions(): array
    {
        return [new Ids($this->ids)];
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST_NOT;
    }
}
