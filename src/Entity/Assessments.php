<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Assessments
 *
 * @ORM\Table(name="assessments", indexes={@ORM\Index(name="item_link_id", columns={"item_link_id"}), @ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="deleter_id", columns={"deleter_id"})})
 * @ORM\Entity
 */
class Assessments
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
    private $creatorId;

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
     * @var integer
     *
     * @ORM\Column(name="item_link_id", type="integer", nullable=false)
     */
    private $itemLinkId;

    /**
     * @var integer
     *
     * @ORM\Column(name="assessment", type="integer", nullable=false)
     */
    private $assessment;


}

