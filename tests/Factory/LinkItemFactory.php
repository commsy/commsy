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

use App\Entity\LinkItems;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use cs_item;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<LinkItems>
 */
final class LinkItemFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return LinkItems::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'room' => null,
            'creator' => null,
            'firstItemId' => null,
            'secondItemId' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(
                Instantiator::withConstructor()->allowExtra('room', 'creator', 'firstItemId', 'secondItemId')
            )
            ->afterInstantiate(function(LinkItems $linkItem, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;
                $firstItemId = $attributes['firstItemId'] ?? null;
                $secondItemId = $attributes['secondItemId'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('LinkItemFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('LinkItemFactory requires "creator" (App\Entity\User).');
                }
                if (!is_int($firstItemId) || $firstItemId <= 0) {
                    throw new LogicException('LinkItemFactory requires "firstItemId" (int > 0).');
                }
                if (!is_int($secondItemId) || $secondItemId <= 0) {
                    throw new LogicException('LinkItemFactory requires "secondItemId" (int > 0).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $firstLegacy = $env->getItemManager()->getItem($firstItemId);
                $secondLegacy = $env->getItemManager()->getItem($secondItemId);
                if (!$firstLegacy instanceof cs_item || !$secondLegacy instanceof cs_item) {
                    throw new LogicException('LinkItemFactory could not resolve legacy items for the given ids.');
                }

                $item = $env->getLinkItemManager()->getNewItem();
                $item->setFirstLinkedItem($firstLegacy);
                $item->setSecondLinkedItem($secondLegacy);
                $item->setContextID($room->getItemId());

                set_error_handler(function(int $errno, string $errstr): bool {
                    throw new LogicException(sprintf('Legacy save() warning: %s', $errstr));
                }, E_USER_WARNING);
                try {
                    $item->save();
                } finally {
                    restore_error_handler();
                }

                $itemId = (int) $item->getItemID();
                if ($itemId <= 0) {
                    throw new LogicException('Legacy save() did not return a valid link_item id.');
                }

                $idProperty = new ReflectionProperty(LinkItems::class, 'itemId');
                $idProperty->setValue($linkItem, $itemId);
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
