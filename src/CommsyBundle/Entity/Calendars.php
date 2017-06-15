<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jsvrcek\ICS\Model\Calendar;
use Nette\Utils\Strings;

/**
 * Invitations
 *
 * @ORM\Table(name="calendars", indexes={@ORM\Index(name="id", columns={"id"})})
 * @ORM\Entity
 */
class Calendars
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=false)
     */
    private $contextId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=255, nullable=false)
     */
    private $color;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return Calendars
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Calendars
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return Calendars
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }
}
