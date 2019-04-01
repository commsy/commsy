<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzLinkItems
 *
 * @ORM\Table(name="zzz_link_items", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="first_item_id", columns={"first_item_id"}), @ORM\Index(name="second_item_id", columns={"second_item_id"}), @ORM\Index(name="first_item_type", columns={"first_item_type"}), @ORM\Index(name="second_item_type", columns={"second_item_type"}), @ORM\Index(name="deletion_date", columns={"deletion_date"}), @ORM\Index(name="deleter_id", columns={"deleter_id"})})
 * @ORM\Entity
 */
class ZzzLinkItems
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="first_item_id", type="integer", nullable=false)
     */
    private $firstItemId = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="first_item_type", type="string", length=15, nullable=true)
     */
    private $firstItemType;

    /**
     * @var integer
     *
     * @ORM\Column(name="second_item_id", type="integer", nullable=false)
     */
    private $secondItemId = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="second_item_type", type="string", length=15, nullable=true)
     */
    private $secondItemType;

    /**
     * @var integer
     *
     * @ORM\Column(name="sorting_place", type="integer", nullable=true)
     */
    private $sortingPlace;

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=16777215, nullable=true)
     */
    private $extras;


}

