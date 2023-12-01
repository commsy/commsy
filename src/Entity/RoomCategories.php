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
#[ORM\Table(name: 'room_categories')]
#[ORM\Index(columns: ['id'], name: 'id')]
class RoomCategories
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: false)]
    private int $context_id;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return RoomCategories
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
     * @return RoomCategories
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
}
