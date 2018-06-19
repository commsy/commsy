<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 23.04.18
 * Time: 16:34
 */

namespace CommsyBundle\Database;


class DatabaseProblem
{
    private $object;

    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }
}