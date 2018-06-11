<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 23.04.18
 * Time: 16:31
 */

namespace CommsyBundle\Database\Resolve;


use CommsyBundle\Database\DatabaseProblem;

interface ResolutionInterface
{
    /**
     * @param DatabaseProblem[] $problems
     * @return bool
     */
    public function resolve($problems);

    public function getKey();

    public function getDescription();
}