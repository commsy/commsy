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

use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single notification addressed to one {@see Account}.
 *
 * Rows are materialised at the moment the source event happens (fan-out: one
 * row per recipient), not derived on read. Display-relevant data is snapshotted
 * ({@see $title}, {@see $roomTitle}, {@see $actorName}) so the render path needs
 * no legacy item lookups. The read state ({@see $readAt}) is owned by the
 * notification itself and is intentionally decoupled from the item read tracking
 * in {@see Reader}.
 *
 * {@see $recipient} is FK-bound with ON DELETE CASCADE, so deleting an account
 * removes its notifications automatically.
 */
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'notification_recipient_idx', columns: ['recipient_id', 'read_at', 'created_at'])]
#[ORM\Index(name: 'notification_source_item_idx', columns: ['source_item_id'])]
#[ORM\UniqueConstraint(name: 'notification_recipient_item_idx', columns: ['recipient_id', 'type', 'source_item_id'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Account $recipient;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 32, enumType: NotificationType::class)]
    private NotificationType $type;

    /**
     * Room (context) the event happened in.
     */
    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    /**
     * The item that triggered the notification. Nullable so future, non-item
     * notification types can reuse this table.
     */
    #[ORM\Column(name: 'source_item_id', type: Types::INTEGER, nullable: true)]
    private ?int $sourceItemId;

    /**
     * Rubric of the source item (announcement, material, date, …).
     */
    #[ORM\Column(name: 'source_item_type', type: Types::STRING, length: 32, nullable: true)]
    private ?string $sourceItemType;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(name: 'room_title', type: Types::STRING, length: 255)]
    private string $roomTitle;

    #[ORM\Column(name: 'actor_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $actorName;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'read_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct(
        Account $recipient,
        NotificationType $type,
        int $contextId,
        string $title,
        string $roomTitle,
        \DateTimeImmutable $createdAt,
        ?int $sourceItemId = null,
        ?string $sourceItemType = null,
        ?string $actorName = null,
    ) {
        $this->recipient = $recipient;
        $this->type = $type;
        $this->contextId = $contextId;
        $this->title = $title;
        $this->roomTitle = $roomTitle;
        $this->createdAt = $createdAt;
        $this->sourceItemId = $sourceItemId;
        $this->sourceItemType = $sourceItemType;
        $this->actorName = $actorName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): Account
    {
        return $this->recipient;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function getSourceItemId(): ?int
    {
        return $this->sourceItemId;
    }

    public function getSourceItemType(): ?string
    {
        return $this->sourceItemType;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRoomTitle(): string
    {
        return $this->roomTitle;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function isUnread(): bool
    {
        return $this->readAt === null;
    }

    /**
     * Mark the notification read. Idempotent: the first read timestamp wins, so
     * re-reading never moves the marker.
     */
    public function markRead(\DateTimeImmutable $now): void
    {
        if ($this->readAt === null) {
            $this->readAt = $now;
        }
    }
}
