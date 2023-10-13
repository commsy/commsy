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

use App\Repository\TermsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Term.
 */
#[ORM\Entity(repositoryClass: TermsRepository::class)]
#[ORM\Table(name: 'terms')]
class Terms
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'content_de', type: Types::TEXT)]
    private ?string $contentDe = null;

    #[ORM\Column(name: 'content_en', type: Types::TEXT)]
    private ?string $contentEn = null;

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
     * @return Term
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
     * @return Term
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
     * Set contentDe.
     *
     * @param string $contentDe
     *
     * @return Term
     */
    public function setContentDe($contentDe)
    {
        $this->contentDe = $contentDe;

        return $this;
    }

    /**
     * Get contentDe.
     *
     * @return string
     */
    public function getContentDe()
    {
        return $this->contentDe;
    }

    /**
     * Set contentEn.
     *
     * @param string $contentEn
     *
     * @return Term
     */
    public function setContentEn($contentEn)
    {
        $this->contentEn = $contentEn;

        return $this;
    }

    /**
     * Get contentEn.
     *
     * @return string
     */
    public function getContentEn()
    {
        return $this->contentEn;
    }
}
