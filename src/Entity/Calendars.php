<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Invitations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'calendars')]
#[ORM\Index(columns: ['id'], name: 'id')]
class Calendars
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = 0;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private ?int $context_id = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'color', type: Types::STRING, length: 255)]
    private ?string $color = null;

    #[ORM\Column(name: 'external_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $external_url = null;

    #[ORM\Column(name: 'default_calendar', type: Types::BOOLEAN)]
    private ?bool $default_calendar = false;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER, nullable: true)]
    private ?int $creator_id = null;

    #[ORM\Column(name: 'synctoken', type: Types::INTEGER, nullable: true)]
    private ?int $synctoken = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Calendars
     */
    public function setContextId($contextId)
    {
        $this->context_id = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->context_id;
    }

    /**
     * Set title.
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
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set color.
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
     * Get title.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set external_url.
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
     * Get external_url.
     *
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->external_url;
    }

    /**
     * Set default.
     *
     * @param bool $default_calendar
     *
     * @return Calendars
     */
    public function setDefaultCalendar($default_calendar)
    {
        $this->default_calendar = $default_calendar;

        return $this;
    }

    /**
     * Get default.
     *
     * @return bool
     */
    public function getDefaultCalendar()
    {
        return $this->default_calendar;
    }

    /**
     * Set creatorId.
     *
     * @param int $creatorId
     *
     * @return Calendars
     */
    public function setCreatorId($creatorId)
    {
        $this->creator_id = $creatorId;

        return $this;
    }

    /**
     * Get creatorId.
     *
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creator_id;
    }

    public function hasLightColor()
    {
        $hexColor = str_ireplace('#', '', $this->getColor());

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        $squared_contrast = (
            $r * $r * .299 +
            $g * $g * .587 +
            $b * $b * .114
        );

        if ($squared_contrast > 220 ** 2) { // 220 -> 75% max
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set synctoken.
     *
     * @param int $synctoken
     *
     * @return Calendars
     */
    public function setSynctoken($synctoken)
    {
        $this->synctoken = $synctoken;

        return $this;
    }

    /**
     * Get synctoken.
     *
     * @return int
     */
    public function getSynctoken()
    {
        return $this->synctoken;
    }
}
