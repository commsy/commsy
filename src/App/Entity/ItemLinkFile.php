<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemLinkFile
 *
 * @ORM\Table(name="item_link_file")
 * @ORM\Entity
 */
class ItemLinkFile
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $itemIid = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="item_vid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $itemVid = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="file_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $fileId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;


}

