<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WorkflowRead
 *
 * @ORM\Table(name="workflow_read", indexes={@ORM\Index(name="item_id", columns={"item_id"}), @ORM\Index(name="user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class WorkflowRead
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
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId = '0';


}

