<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzLinks
 *
 * @ORM\Table(name="zzz_links", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="link_type", columns={"link_type"}), @ORM\Index(name="from_item_id", columns={"from_item_id"}), @ORM\Index(name="from_version_id", columns={"from_version_id"}), @ORM\Index(name="to_item_id", columns={"to_item_id"}), @ORM\Index(name="to_version_id", columns={"to_version_id"})})
 * @ORM\Entity
 */
class ZzzLinks
{
    /**
     * @var integer
     *
     * @ORM\Column(name="from_item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $fromItemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="from_version_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $fromVersionId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="to_item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $toItemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="to_version_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $toVersionId = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="link_type", type="string", length=30)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $linkType;

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

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
     * @var integer
     *
     * @ORM\Column(name="x", type="integer", nullable=true)
     */
    private $x;

    /**
     * @var integer
     *
     * @ORM\Column(name="y", type="integer", nullable=true)
     */
    private $y;


}

