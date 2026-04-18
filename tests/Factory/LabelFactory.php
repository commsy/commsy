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

use App\Entity\Labels;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Labels>
 *
 * Creates a label through the **legacy** manager chain (`cs_labels_manager`
 * shared between topic/hashtag/buzzword/timepulse/institution/group — the
 * subtype is stored in `labels.type`). This mirrors how HashtagController,
 * LabelService::getNewHashtag, and the other label call sites create rows.
 *
 * `type` defaults to `buzzword` because it is the simplest subtype — no ES
 * indexing (see `cs_label_item::save()`), no grouproom mirror, no activation
 * window. Tests that need another subtype can pass `type: 'topic'` etc.
 *
 * Required factory inputs:
 *  - `room`    App\Entity\Room — the containing context
 *  - `creator` App\Entity\User — priming the legacy current-user slot
 */
final class LabelFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Labels::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->unique()->words(2, true),
            'type' => 'buzzword',
            'room' => null,
            'creator' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra('room', 'creator', 'type'))
            ->afterInstantiate(function(Labels $label, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;
                $type = $attributes['type'] ?? 'buzzword';

                if (!$room instanceof Room) {
                    throw new LogicException('LabelFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('LabelFactory requires "creator" (App\Entity\User).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $item = $env->getLabelManager()->getNewItem();
                $item->setLabelType($type);
                $item->setContextID($room->getItemId());
                $item->setCreatorItem($env->getCurrentUserItem());
                $item->setName($label->getName());
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

                $idProperty = new ReflectionProperty(Labels::class, 'itemId');
                $idProperty->setValue($label, $itemId);
                $label->setContextId($room->getItemId());
                $label->setType($type);
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
