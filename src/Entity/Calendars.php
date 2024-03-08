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

#[ORM\Entity]
#[ORM\Table(name: 'calendars')]
#[ORM\Index(columns: ['id'], name: 'id')]
class Calendars
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private ?int $context_id = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'color', type: Types::STRING, length: 255)]
    private ?string $color = null;

    #[ORM\Column(name: 'external_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $external_url = null;

    #[ORM\Column(name: 'default_calendar', type: Types::BOOLEAN)]
    private bool $default_calendar = false;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER, nullable: true)]
    private ?int $creator_id = null;

    #[ORM\Column(name: 'synctoken', type: Types::INTEGER, nullable: true)]
    private ?int $synctoken = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     */
    public function setContextId($contextId): Calendars
    {
        $this->context_id = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->context_id;
    }

    public function setTitle($title): Calendars
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set color.
     *
     * @param string $color
     */
    public function setColor($color): Calendars
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Set external_url.
     *
     * @param string $external_url
     */
    public function setExternalUrl($external_url): Calendars
    {
        $this->external_url = $external_url;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->external_url;
    }

    public function setDefaultCalendar(bool $default_calendar): Calendars
    {
        $this->default_calendar = $default_calendar;

        return $this;
    }

    public function getDefaultCalendar(): bool
    {
        return $this->default_calendar;
    }

    /**
     * Set creatorId.
     *
     * @param int $creatorId
     */
    public function setCreatorId($creatorId): Calendars
    {
        $this->creator_id = $creatorId;

        return $this;
    }

    public function getCreatorId(): ?int
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
     */
    public function setSynctoken($synctoken): Calendars
    {
        $this->synctoken = $synctoken;

        return $this;
    }

    public function getSynctoken(): ?int
    {
        return $this->synctoken;
    }
}
