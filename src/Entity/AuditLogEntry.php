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

declare(strict_types=1);

namespace App\Entity;

use App\Audit\AuditEvent;
use App\Audit\AuditSubjectType;
use App\Repository\AuditLogEntryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One administrative act, recorded for the portal it happened in.
 *
 * Written once and never changed — hence getters only. Both parties are kept
 * twice: as a relation while the account exists, and as a copy of the login
 * name and the person's name that survives deletion and renaming. Without the
 * copy an entry would degrade to "account 4711" and stop being a record of
 * anything.
 *
 * The actor relation is nullable so the row outlives the account; a missing
 * actor login name means no person was behind the act (a cron task). The
 * subject carries no relation at all, so a later event can name something
 * other than an account.
 */
#[ORM\Entity(repositoryClass: AuditLogEntryRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'audit_log_portal_occurred_idx', columns: ['portal_id', 'occurred_at'])]
#[ORM\Index(name: 'audit_log_event_idx', columns: ['event'])]
class AuditLogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Portal::class)]
    #[ORM\JoinColumn(name: 'portal_id', nullable: false, onDelete: 'CASCADE')]
    private Portal $portal;

    #[ORM\Column(name: 'occurred_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $occurredAt;

    #[ORM\Column(name: 'event', type: Types::STRING, length: 64, enumType: AuditEvent::class)]
    private AuditEvent $event;

    #[ORM\Column(name: 'subject_type', type: Types::STRING, length: 32, enumType: AuditSubjectType::class)]
    private AuditSubjectType $subjectType;

    #[ORM\Column(name: 'subject_id', type: Types::INTEGER, nullable: true)]
    private ?int $subjectId;

    #[ORM\Column(name: 'subject_label', type: Types::STRING, length: 255)]
    private string $subjectLabel;

    #[ORM\Column(name: 'subject_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $subjectName;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'actor_account_id', nullable: true, onDelete: 'SET NULL')]
    private ?Account $actor;

    #[ORM\Column(name: 'actor_username', type: Types::STRING, length: 255, nullable: true)]
    private ?string $actorUsername;

    #[ORM\Column(name: 'actor_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $actorName;

    /**
     * @var array<string, scalar>|null
     */
    #[ORM\Column(name: 'details', type: Types::JSON, nullable: true)]
    private ?array $details;

    /**
     * @param array<string, scalar>|null $details
     */
    public function __construct(
        Portal $portal,
        AuditEvent $event,
        AuditSubjectType $subjectType,
        ?int $subjectId,
        string $subjectLabel,
        ?string $subjectName = null,
        ?Account $actor = null,
        ?string $actorUsername = null,
        ?string $actorName = null,
        ?array $details = null,
    ) {
        $this->portal = $portal;
        $this->event = $event;
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->subjectLabel = $subjectLabel;
        $this->subjectName = $subjectName;
        $this->actor = $actor;
        $this->actorUsername = $actorUsername;
        $this->actorName = $actorName;
        $this->details = $details;
        $this->occurredAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPortal(): Portal
    {
        return $this->portal;
    }

    public function getEvent(): AuditEvent
    {
        return $this->event;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSubjectType(): AuditSubjectType
    {
        return $this->subjectType;
    }

    public function getSubjectId(): ?int
    {
        return $this->subjectId;
    }

    public function getSubjectLabel(): string
    {
        return $this->subjectLabel;
    }

    public function getSubjectName(): ?string
    {
        return $this->subjectName;
    }

    public function getActor(): ?Account
    {
        return $this->actor;
    }

    public function getActorUsername(): ?string
    {
        return $this->actorUsername;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    /**
     * @return array<string, scalar>
     */
    public function getDetails(): array
    {
        return $this->details ?? [];
    }
}
