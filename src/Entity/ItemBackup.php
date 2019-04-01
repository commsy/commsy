<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemBackup
 *
 * @ORM\Table(name="item_backup")
 * @ORM\Entity
 */
class ItemBackup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="backup_date", type="datetime", nullable=false)
     */
    private $backupDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public;

    /**
     * @var string
     *
     * @ORM\Column(name="special", type="text", length=65535, nullable=false)
     */
    private $special;


}

