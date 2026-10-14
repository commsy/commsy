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

use ApiPlatform\Metadata\ApiProperty;
use App\Utils\EntityUsersTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use App\Export\ExportableEntry;
use App\Repository\DiscussionarticlesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DiscussionarticlesRepository::class)]
#[ORM\Table(name: 'discussionarticles')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['discussion_id'], name: 'discussion_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Discussionarticles implements ExportableEntry
{
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'discussion_id', type: Types::INTEGER)]
    private int $discussionId;

    #[ORM\ManyToOne(targetEntity: 'Discussions', inversedBy: 'discussionarticles')]
    #[ORM\JoinColumn(name: 'discussion_id', referencedColumnName: 'item_id', nullable: false)]
    private Discussions $discussion;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'When it was written.')]
    private DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'When it was last changed; null if no date is stored.')]
    private ?DateTimeInterface $modificationDate = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The text, as HTML.')]
    private ?string $description = null;

    #[ORM\Column(name: 'position', type: Types::STRING, length: 255)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'Where it stands in the conversation: four-digit numbers, nested with dots for '
            .'answers — 0001, 0002, and 0002.0001 for an answer to the second. Sorted as text they '
            .'give the order of the conversation.',
    )]
    private string $position = '1';

    #[ORM\Column(name: 'extras', type: Types::ARRAY, length: 65535, nullable: true)]
    private ?array $extras = null;

    /**
     * Multi-state legacy flag, NOT a boolean (DB column is `tinyint(11)`).
     * Known values:
     *   1  → public-readable article (legacy "world view" flag)
     *   0  → private to room (default)
     *
     * The values -1 and -2 used to be smuggled in here as redaction
     * markers. They are gone: a redacted entry now carries the
     * placeholder text in its own columns, so nothing has to be
     * inferred from the edit permission.
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

    /**
     * An article lives in the room its discussion lives in.
     */
    public function getRoomId(): int
    {
        return (int) $this->contextId;
    }

    /**
     * Only material and section keep versions.
     */
    public function getVersionId(): int
    {
        return self::UNVERSIONED;
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
}
