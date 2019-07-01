<?php


namespace App\Search\QueryConditions;


interface QueryConditionInterface
{
    public const BOOL_MUST = 'must';
    public const BOOL_SHOULD = 'should';

    /**
     * @return AbstractQuery[]
     */
    public function getConditions(): array;

    /**
     * @return string
     */
    public function getOperator(): string;
}
