<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jsvrcek\ICS\Model\Calendar;
use Nette\Utils\Strings;
use Xmon\ColorPickerTypeBundle\Validator\Constraints as XmonAssertColor;

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
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=false)
     */
    private $context_id;

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
     * @XmonAssertColor\HexColor()
     */
    private $color;

    /**
     * @var string
     *
     * @ORM\Column(name="external_url", type="string", length=255, nullable=true)
     */
    private $external_url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_calendar", type="boolean", nullable=false)
     */
    private $default_calendar = '0';

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
        $this->context_id = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->context_id;
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

    /**
     * Set external_url
     *
     * @param string $external_url
     *
     * @return Calendars
     */
    public function setExternalUrl($external_url)
    {
        $this->external_url = $external_url;

        return $this;
    }

    /**
     * Get external_url
     *
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->external_url;
    }

    /**
     * Set default
     *
     * @param boolean $default
     *
     * @return Calendars
     */
    public function setDefaultCalendar($default_calendar)
    {
        $this->default_calendar = $default_calendar;

        return $this;
    }

    /**
     * Get default
     *
     * @return boolean
     */
    public function getDefaultCalendar()
    {
        return $this->default_calendar;
    }
}
