<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 15:19
 */

namespace App\Action\MarkRead;


interface MarkReadInterface
{
    /**
     * @param \cs_item $item
     */
    public function markRead(\cs_item $item): void;
}