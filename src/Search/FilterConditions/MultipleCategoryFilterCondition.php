<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Term;

class MultipleCategoryFilterCondition implements FilterConditionInterface
{
    /**
     * @var string[] $categories
     */
    private $categories;

    /**
     * @param string[] $categories
     * @return MultipleCategoryFilterCondition
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

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

}
