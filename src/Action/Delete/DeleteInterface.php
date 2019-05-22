<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 22:12
 */

namespace App\Action\Delete;


interface DeleteInterface
{
    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void;

    /**
     * @param \cs_item $item
     * @return string|null
     */
    public function getRedirectRoute(\cs_item $item);
}