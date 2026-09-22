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

use App\Export\RoomExportStatus;
use App\Repository\RoomExportRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One run of a room export: what was asked for, how it went, and where the
 * archive ended up.
 *
 * The room is a relation that cascades — export rows of a room that no longer
 * exists have nothing left to say, and the database enforces that no matter
 * which code path removed the room.
 *
 * The requesting person is *not* a relation. A room membership disappears when
 * someone leaves the room, and the record of who asked for an archive of the
 * room's content should outlive that. Id and name are kept as copies for the
 * same reason the audit log keeps them: without the name the row would degrade
 * to "user 4711".
 *
 * Only the file name is stored, never the full path. Where exports live is the
 * business of {@see \App\Export\RoomExportStorage}, and a path baked into a row
 * would survive a change of that decision.
 */
#[ORM\Entity(repositoryClass: RoomExportRepository::class)]
#[ORM\Table(name: 'room_export')]
#[ORM\Index(name: 'room_export_room_status_idx', columns: ['room_id', 'status'])]
class RoomExport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'item_id', nullable: false, onDelete: 'CASCADE')]
    private Room $room;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 16, enumType: RoomExportStatus::class)]
    private RoomExportStatus $status = RoomExportStatus::Pending;

    #[ORM\Column(name: 'requested_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $requestedAt;

    #[ORM\Column(name: 'finished_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'requested_by_user_id', type: Types::INTEGER, nullable: true)]
    private ?int $requestedByUserId = null;

    #[ORM\Column(name: 'requested_by_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $requestedByName = null;

    #[ORM\Column(name: 'filename', type: Types::STRING, length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(name: 'size_in_bytes', type: Types::INTEGER, nullable: true)]
    private ?int $sizeInBytes = null;

    /**
     * Short enough for the settings page. Anything worth diagnosing goes to the
     * log, not here.
     */
    #[ORM\Column(name: 'failure_reason', type: Types::STRING, length: 255, nullable: true)]
    private ?string $failureReason = null;

    public function __construct(Room $room, ?int $requestedByUserId = null, ?string $requestedByName = null)
    {
        $this->room = $room;
        $this->requestedByUserId = $requestedByUserId;
        $this->requestedByName = $requestedByName;
        $this->requestedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function getStatus(): RoomExportStatus
    {
        return $this->status;
    }

    public function getRequestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getRequestedByUserId(): ?int
    {
        return $this->requestedByUserId;
    }

    public function getRequestedByName(): ?string
    {
        return $this->requestedByName;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getSizeInBytes(): ?int
    {
        return $this->sizeInBytes;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function markRunning(): void
    {
        $this->status = RoomExportStatus::Running;
    }

    public function markFinished(string $filename, int $sizeInBytes): void
    {
        $this->status = RoomExportStatus::Finished;
        $this->filename = $filename;
        $this->sizeInBytes = $sizeInBytes;
        $this->finishedAt = new DateTimeImmutable();
        $this->failureReason = null;
    }

    public function markFailed(string $reason): void
    {
        $this->status = RoomExportStatus::Failed;
        $this->failureReason = mb_substr($reason, 0, 255);
        $this->finishedAt = new DateTimeImmutable();
    }

    /**
     * No worker came back with a result. Settling the row frees the room for a
     * new export, and a late delivery of the same message finds the row settled
     * and stops.
     */
    public function markAbandoned(): void
    {
        $this->status = RoomExportStatus::Abandoned;
        $this->finishedAt = new DateTimeImmutable();
    }
}
