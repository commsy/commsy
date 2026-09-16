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

use App\Enum\NotificationAction;
use App\Enum\NotificationType;
use App\Notification\NotificationPayload;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single activity event addressed to one {@see Account}.
 *
 * Backs the room/dashboard activity panel: every create and every edit of a
 * feed-relevant entry is logged as its own row (fan-out: one row per recipient
 * per event), so there is intentionally no per-item uniqueness — re-editing an
 * entry adds another notification. {@see $action} says whether the event was a
 * create or an edit.
 *
 * Display data is snapshotted at event time so the render path needs no legacy
 * item lookups and shows the entry as it was when the event happened: the stable
 * columns ({@see $title}, {@see $roomTitle}, {@see $actorName}) plus the variable
 * rubric-specific extras in {@see $payload} (see {@see NotificationPayload}).
 *
 * "Read" is owned here via {@see $readAt} (set when the recipient opens the
 * entry's detail page, regardless of path) and is decoupled from the item read
 * tracking in {@see Reader}. Rows are never dismissed by hand; they leave only
 * through the retention cron or with the item they point at.
 *
 * {@see $recipient} is FK-bound with ON DELETE CASCADE, so deleting an account
 * removes its notifications automatically.
 */
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'notification_recipient_idx', columns: ['recipient_id', 'read_at', 'created_at'])]
#[ORM\Index(name: 'notification_source_item_idx', columns: ['source_item_id'])]
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
     * Whether the reported event created or edited the source item.
     */
    #[ORM\Column(name: 'action', type: Types::STRING, length: 16, enumType: NotificationAction::class)]
    private NotificationAction $action;

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

    /**
     * Rubric-specific, display-only extras (see {@see NotificationPayload}).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'payload', type: Types::JSON, nullable: true)]
    private ?array $payload = [];

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
        NotificationAction $action = NotificationAction::Created,
        NotificationPayload $payload = new NotificationPayload(),
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
        $this->action = $action;
        $this->payload = $payload->toArray();
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

    public function getAction(): NotificationAction
    {
        return $this->action;
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

    public function getPayload(): NotificationPayload
    {
        return NotificationPayload::fromArray($this->payload ?? []);
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
