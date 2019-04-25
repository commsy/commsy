<?php


namespace App\Search\FilterConditions;


use Elastica\Query\Terms;

interface FilterConditionInterface
{
    public const BOOL_MUST = 'must';
    public const BOOL_SHOULD = 'should';

    /**
     * @return Terms[]
     */
    public function getConditions(): array;

    /**
     * @return string
     */
    public function getOperator(): string;
}