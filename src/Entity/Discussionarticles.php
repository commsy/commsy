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

use App\Utils\EntityUsersTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'discussionarticles')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['discussion_id'], name: 'discussion_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Discussionarticles
{
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'discussion_id', type: Types::INTEGER)]
    private int $discussionId;

    #[ORM\ManyToOne(targetEntity: 'Discussions', inversedBy: 'discussionarticles')]
    #[ORM\JoinColumn(name: 'discussion_id', referencedColumnName: 'item_id', nullable: false)]
    private Discussions $discussion;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $modificationDate = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'position', type: Types::STRING, length: 255)]
    private string $position = '1';

    #[ORM\Column(name: 'extras', type: Types::ARRAY, length: 65535, nullable: true)]
    private ?array $extras = null;

    /**
     * Multi-state legacy flag, NOT a boolean (DB column is `tinyint(11)`).
     * Known values:
     *   1  → public-readable article (legacy "world view" flag)
     *   0  → private to room (default)
     *  -2  → tombstone for an article with answers: body is replaced
     *        with placeholder text, row stays alive to preserve the
     *        thread hierarchy. Written by
     *        {@see \App\Rubric\Discussion\DiscussionDeleter::deleteArticle()}.
     *  -1  → defensively read by `cs_*_item::getDescription()` via the
     *        `COMMON_AUTOMATIC_DELETE_DESCRIPTION` translation key, but
     *        no writer for this value exists in the current codebase
     *        (no `setPublic(-1)`, no raw SQL). Likely a relic of an
     *        older deletion path; the reader code may be dead too.
     * Overloading `public` as a tombstone marker is misuse — replacing
     * it with a dedicated column is tracked as a follow-up.
     */
    #[ORM\Column(name: 'public', type: Types::INTEGER)]
    private int $public = 0;

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
    }

    public function setDiscussion(Discussions $discussion): static
    {
        $this->discussion = $discussion;

        return $this;
    }

    public function getDiscussion(): Discussions
    {
        return $this->discussion;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setDiscussionId(int $discussionId): static
    {
        $this->discussionId = $discussionId;

        return $this;
    }

    public function getDiscussionId(): int
    {
        return $this->discussionId;
    }

    public function setCreationDate(DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setModificationDate(?DateTimeInterface $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?DateTimeInterface
    {
        return $this->modificationDate;
    }

    public function setDeletionDate(?DateTimeInterface $deletionDate): static
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getDeletionDate(): ?DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function setPublic(int $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): int
    {
        return $this->public;
    }

    /**
     * Convenience predicate for the tombstone state set by
     * {@see \App\Rubric\Discussion\DiscussionDeleter::deleteArticle}
     * on articles whose content was overwritten because they had
     * answers (`public = -2`). Mirrors
     * `cs_discussionarticle_item::getHasOverwrittenContent()`.
     */
    public function hasOverwrittenContent(): bool
    {
        return $this->public === -2;
    }
}
