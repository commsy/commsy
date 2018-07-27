<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.04.18
 * Time: 19:12
 */

namespace CommsyBundle\Action;


use Symfony\Component\HttpFoundation\Response;

interface ActionInterface
{
    public function execute(\cs_room_item $roomItem, array $items): Response;
}