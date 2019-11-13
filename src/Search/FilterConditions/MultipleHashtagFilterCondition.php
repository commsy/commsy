<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Term;

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
     * @return Term[]
     */
    public function getConditions(): array
    {
        $terms = [];
        foreach ($this->hashtags as $hashtag) {
            $hashtagTerm = new Term();
            $hashtagTerm->setTerm('hashtags', $hashtag);
            $terms[] = $hashtagTerm;
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
