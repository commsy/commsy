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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set contextId.
     *
     * @param string $contextId
     */
    public function setContextId($contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?string
    {
        return $this->contextId;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setContentDe(?string $contentDe): static
    {
        $this->contentDe = $contentDe;

        return $this;
    }

    public function getContentDe(): ?string
    {
        return $this->contentDe;
    }

    public function setContentEn(?string $contentEn): static
    {
        $this->contentEn = $contentEn;

        return $this;
    }

    public function getContentEn(): ?string
    {
        return $this->contentEn;
    }
}
