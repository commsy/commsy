<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzTag2tag
 *
 * @ORM\Table(name="zzz_tag2tag", indexes={@ORM\Index(name="from_item_id", columns={"from_item_id"}), @ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="deletion_date", columns={"deletion_date"}), @ORM\Index(name="deleter_id", columns={"deleter_id"})})
 * @ORM\Entity
 */
class ZzzTag2tag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="link_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $linkId;

    /**
     * @var integer
     *
     * @ORM\Column(name="from_item_id", type="integer", nullable=false)
     */
    private $fromItemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="to_item_id", type="integer", nullable=false)
     */
    private $toItemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=false)
     */
    private $contextId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=false)
     */
    private $modifierId = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     */
    private $modificationDate = '0000-00-00 00:00:00';

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

    /**
     * @var boolean
     *
     * @ORM\Column(name="sorting_place", type="boolean", nullable=true)
     */
    private $sortingPlace;


}

