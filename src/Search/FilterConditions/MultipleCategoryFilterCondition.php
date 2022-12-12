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

use Elastica\Query\Term;

class MultipleCategoryFilterCondition implements FilterConditionInterface
{
    /**
     * @var string[]
     */
    private array $categories;

    /**
     * @param string[] $categories
     */
    public function setCategories(array $categories): MultipleCategoryFilterCondition
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return Term[]
     */
    public function getConditions(): array
    {
        $terms = [];
        foreach ($this->categories as $category) {
            $categoryTerm = new Term();
            $categoryTerm->setTerm('tags', $category);
            $terms[] = $categoryTerm;
        }

        return $terms;
    }

    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }
}
