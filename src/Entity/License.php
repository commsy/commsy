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

use Doctrine\ORM\Mapping as ORM;

/**
 * License.
 */
#[ORM\Entity(repositoryClass: \App\Repository\LicenseRepository::class)]
#[ORM\Table(name: 'licenses')]
class License
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'context_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'title', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'content', type: \Doctrine\DBAL\Types\Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(name: 'position', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $position = null;

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
     * @param string $contextId
     *
     * @return License
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return string
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return License
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
     * Set content.
     *
     * @param string $content
     *
     * @return License
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return License
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
}
