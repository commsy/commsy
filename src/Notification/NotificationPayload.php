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

namespace App\Notification;

/**
 * Rubric-specific, display-only extras snapshotted onto a {@see \App\Entity\Notification}
 * and stored in its JSON payload column.
 *
 * This is the variable part of a notification: each rubric only fills the fields
 * it needs (a date carries start/end/place, a material its author, a todo its
 * status, …), which is why a single JSON column beats one sparse table column
 * per rubric. Kept to JSON-friendly scalars so it round-trips without custom
 * (de)serialisation; dates are ISO-8601 strings and are formatted at render time
 * to stay locale-aware. The queryable/sortable fields (recipient, room, dates,
 * read state) stay as real columns on the entity — only display extras live here.
 */
final readonly class NotificationPayload
{
    public function __construct(
        public ?string $creatorName = null,
        public ?int $actorId = null,
        public ?string $place = null,
        public ?string $dateStart = null,
        public ?string $dateEnd = null,
        public bool $wholeDay = false,
        public ?string $materialAuthor = null,
        public ?string $publishingDate = null,
        public ?string $todoStatus = null,
        /** 'accepted' or 'rejected' on a join-request decision. */
        public ?string $decision = null,
        public bool $hasAttachments = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            creatorName: $data['creatorName'] ?? null,
            actorId: isset($data['actorId']) ? (int) $data['actorId'] : null,
            place: $data['place'] ?? null,
            dateStart: $data['dateStart'] ?? null,
            dateEnd: $data['dateEnd'] ?? null,
            wholeDay: (bool) ($data['wholeDay'] ?? false),
            materialAuthor: $data['materialAuthor'] ?? null,
            publishingDate: $data['publishingDate'] ?? null,
            todoStatus: $data['todoStatus'] ?? null,
            decision: $data['decision'] ?? null,
            hasAttachments: (bool) ($data['hasAttachments'] ?? false),
        );
    }

    /**
     * Only set values are kept, so the stored JSON stays compact.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'creatorName' => $this->creatorName,
            'actorId' => $this->actorId,
            'place' => $this->place,
            'dateStart' => $this->dateStart,
            'dateEnd' => $this->dateEnd,
            'wholeDay' => $this->wholeDay ?: null,
            'materialAuthor' => $this->materialAuthor,
            'publishingDate' => $this->publishingDate,
            'todoStatus' => $this->todoStatus,
            'decision' => $this->decision,
            'hasAttachments' => $this->hasAttachments ?: null,
        ], static fn ($value): bool => $value !== null);
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }
}
