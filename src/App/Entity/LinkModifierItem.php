<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LinkModifierItem
 *
 * @ORM\Table(name="link_modifier_item")
 * @ORM\Entity
 */
class LinkModifierItem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $modifierId = '0';


}

