<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

class MultipleHashtagFilterCondition implements FilterConditionInterface
{
    /**
     * @var string[] $hashtags
     */
    private $hashtags;

    /**
     * @param string[] $hashtags
     * @return MultipleHashtagFilterCondition
     */
    public function setHashtags(array $hashtags): MultipleHashtagFilterCondition
    {
        $this->hashtags = $hashtags;
        return $this;
    }

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $hashtagTerm = new Terms();
        $hashtagTerm->setTerms('hashtags', $this->hashtags);

        return [$hashtagTerm];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

}
