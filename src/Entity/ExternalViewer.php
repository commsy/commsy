<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExternalViewer
 *
 * @ORM\Table(name="external_viewer", indexes={@ORM\Index(name="item_id", columns={"item_id", "user_id"})})
 * @ORM\Entity
 */
class ExternalViewer
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $itemId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=32)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId;


}

