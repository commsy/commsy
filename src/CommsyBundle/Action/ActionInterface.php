<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.04.18
 * Time: 19:12
 */

namespace CommsyBundle\Action;


interface ActionInterface
{
    public function execute($items);
}