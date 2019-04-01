<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzDiscussionarticles
 *
 * @ORM\Table(name="zzz_discussionarticles", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="discussion_id", columns={"discussion_id"}), @ORM\Index(name="creator_id", columns={"creator_id"})})
 * @ORM\Entity
 */
class ZzzDiscussionarticles
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
     * @ORM\Column(name="discussion_id", type="integer", nullable=false)
     */
    private $discussionId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=true)
     */
    private $modifierId;

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
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=false)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=255, nullable=false)
     */
    private $position = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=65535, nullable=true)
     */
    private $extras;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';


}

