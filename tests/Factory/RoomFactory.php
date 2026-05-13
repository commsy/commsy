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

namespace Tests\Factory;

use App\Entity\Room;
use App\Room\RoomStatus;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Room>
 */
final class RoomFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Room::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'archived' => false,
            'status' => 1,
            'title' => self::faker()->word(),
            'type' => self::faker()->randomElement(['project', 'community'])
        ];
    }

    public function project(): static
    {
        return $this->with(['type' => 'project']);
    }

    public function community(): static
    {
        return $this->with(['type' => 'community']);
    }

    public function groupRoom(): static
    {
        return $this->with(['type' => 'grouproom']);
    }

    public function userRoom(): static
    {
        return $this->with(['type' => 'userroom']);
    }

    public function privateRoom(): static
    {
        return $this->with(['type' => 'privateroom']);
    }

    public function archived(): static
    {
        return $this->with(['archived' => true]);
    }

    public function locked(): static
    {
        return $this->with(['status' => RoomStatus::LOCKED->value]);
    }

    /**
     * status 4 — locked by a portal moderator. Distinct from {@see locked()}
     * (status 3): only portal moderators can edit such rooms; room
     * moderators cannot, even though they can normally edit anything in
     * their room. See ItemVoter::canEdit + cs_context_item::isLockedByModerator.
     */
    public function lockedByModerator(): static
    {
        return $this->with(['status' => RoomStatus::LOCKED_PORTAL_MOD->value]);
    }

    public function closed(): static
    {
        return $this->with(['status' => RoomStatus::CLOSED->value]);
    }

    public function openForGuests(): static
    {
        return $this->with(['openForGuests' => true]);
    }

    public function deleted(): static
    {
        return $this->with(['deletionDate' => new DateTime()]);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->afterInstantiate(function(Room $room): void {
                $conn = $this->entityManager->getConnection();
                $now = new DateTimeImmutable()->format('Y-m-d H:i:s');

                $deleterId = $room->getDeleter()?->getItemId();
                $deletionDate = $room->getDeletionDate() ? $room->getDeletionDate()->format('Y-m-d H:i:s') : null;

                // Insert in items
                $conn->insert('items', [
                    'context_id' => $room->getContextId(),
                    'modification_date' => $now,
                    'type' => $room->getType(),
                    'deleter_id' => $deleterId,
                    'deletion_date' => $deletionDate,
                ]);

                $itemId = (int) $conn->lastInsertId();
                $room->setItemId($itemId);

                // 2) Insert in room
                $conn->insert('room', [
                    'item_id' => $itemId,
                    'context_id' => $room->getContextId(),
                    'portal_id' => $room->getPortal()->getId(),
                    'title' => $room->getTitle(),
                    'extras' => $room->getExtras() ? serialize($room->getExtras()) : null,
                    'status' => $room->getStatus(),
                    'archived' => (int) $room->isArchived(),
                    'activity' => $room->getActivity(),
                    'type' => $room->getType(),
                    'is_open_for_guests' => (int) $room->getOpenForGuests(),
                    'continuous' => (int) $room->isContinuous(),
                    'template' => (int) $room->isTemplate(),
                    'contact_persons' => $room->getContactPersons(),
                    'room_description' => $room->getRoomDescription(),
                    'deleter_id' => $deleterId,
                    'deletion_date' => $deletionDate,
                    'lastlogin' => null,
                    'activity_state' => $room->getActivityState(),
                    'activity_state_updated' => null,
                    'creation_date' => $now,
                    'modification_date' => $now,
                ]);
            });
    }
}
