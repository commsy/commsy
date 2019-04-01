<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Items
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "annotation" = "Annotations",
 *     "announcement" = "Announcement",
 *     "assessments" = "Assessments",
 *     "auth_source" = "AuthSource",
 *     "community" = "Room",
 *     "date" = "Dates",
 *     "discarticle" = "Discussionarticles",
 *     "discussion" = "Discussions",
 *     "grouproom" = "Room",
 *     "label" = "Labels",
 *     "link_item" = "LinkItems",
 *     "material" = "Materials",
 *     "portal" = "Portal",
 *     "portfolio" = "Portfolio",
 *     "privateroom" = "RoomPrivat",
 *     "project" = "Room",
 *     "section" = "Section",
 *     "server" = "Server",
 *     "step" = "Step",
 *     "tag" = "Tag",
 *     "task" = "Tasks",
 *     "todo" = "Todos",
 *     "user" = "User"
 * })
 *
 * @ORM\Table(name="items", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="type", columns={"type"})
 * })
 * 
 */
abstract class Items
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
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;


}

