<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzMaterials
 *
 * @ORM\Table(name="zzz_materials", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="modifier_id", columns={"modifier_id"})})
 * @ORM\Entity
 */
class ZzzMaterials
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
     * @ORM\Column(name="version_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $versionId = '0';

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
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=true)
     */
    private $modifierId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="activation_date", type="datetime")
     */
    private $activationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=200, nullable=true)
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="publishing_date", type="string", length=20, nullable=true)
     */
    private $publishingDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="world_public", type="smallint", nullable=false)
     */
    private $worldPublic = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=16777215, nullable=true)
     */
    private $extras;

    /**
     * @var boolean
     *
     * @ORM\Column(name="new_hack", type="boolean", nullable=false)
     */
    private $newHack = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="copy_of", type="integer", nullable=true)
     */
    private $copyOf;

    /**
     * @var string
     *
     * @ORM\Column(name="workflow_status", type="string", length=255, nullable=false)
     */
    private $workflowStatus = '3_none';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="workflow_resubmission_date", type="datetime", nullable=true)
     */
    private $workflowResubmissionDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="workflow_validity_date", type="datetime", nullable=true)
     */
    private $workflowValidityDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="locking_date", type="datetime", nullable=true)
     */
    private $lockingDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="locking_user_id", type="integer", nullable=true)
     */
    private $lockingUserId;


}

