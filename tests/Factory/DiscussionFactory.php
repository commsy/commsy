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

namespace Tests\Factory;

use App\Entity\Discussions;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Discussions>
 *
 * Creates a discussion through the **legacy** manager chain
 * (`cs_discussion_manager::getNewItem()` + `save()`), mirroring the path a
 * controller takes when a user opens a new discussion.
 *
 * Required inputs:
 *  - `room`    App\Entity\Room — the containing context
 *  - `creator` App\Entity\User — priming the legacy current-user slot
 */
final class DiscussionFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Discussions::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(4),
            'description' => self::faker()->paragraph(),
            'room' => null,
            'creator' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('room', 'creator'))
            ->afterInstantiate(function(Discussions $discussion, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('DiscussionFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('DiscussionFactory requires "creator" (App\Entity\User).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $item = $env->getDiscussionManager()->getNewItem();
                $item->setTitle($discussion->getTitle());
                if ($discussion->getDescription() !== null) {
                    $item->setDescription($discussion->getDescription());
                }
                $item->setContextID($room->getItemId());

                set_error_handler(function(int $errno, string $errstr): bool {
                    throw new LogicException(sprintf('Legacy save() warning: %s', $errstr));
                }, E_USER_WARNING);
                try {
                    $item->save();
                } finally {
                    restore_error_handler();
                }

                $itemId = $item->getItemID();
                if ($itemId <= 0) {
                    throw new LogicException('Legacy save() did not return a valid item id.');
                }

                $idProperty = new ReflectionProperty(Discussions::class, 'itemId');
                $idProperty->setValue($discussion, $itemId);
                $discussion->setContextId($room->getItemId());
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
