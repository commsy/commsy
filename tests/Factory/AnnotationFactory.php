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

use App\Entity\Annotations;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Annotations>
 *
 * Creates an annotation through the legacy manager chain
 * (`cs_annotations_manager::getNewItem()` + `save()`), mirroring real runtime
 * behaviour. Same pattern as {@see AnnouncementFactory}: we run the legacy
 * save inside `afterInstantiate()` and copy the generated id back onto the
 * draft via reflection (no setter — `#[GeneratedValue]`).
 *
 * Required factory inputs:
 *  - `room`       App\Entity\Room — the containing context
 *  - `creator`    App\Entity\User — priming the legacy current-user slot
 *  - `linkedItemId` int           — id of the item the annotation hangs on
 */
final class AnnotationFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Annotations::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'description' => self::faker()->sentence(),
            'room' => null,
            'creator' => null,
            'linkedItemId' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(
                Instantiator::withConstructor()->allowExtra('room', 'creator', 'linkedItemId', 'description')
            )
            ->afterInstantiate(function(Annotations $annotation, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;
                $linkedItemId = $attributes['linkedItemId'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('AnnotationFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('AnnotationFactory requires "creator" (App\Entity\User).');
                }
                if (!is_int($linkedItemId) || $linkedItemId <= 0) {
                    throw new LogicException('AnnotationFactory requires "linkedItemId" (int > 0).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $item = $env->getAnnotationManager()->getNewItem();
                $item->setContextID($room->getItemId());
                $item->setLinkedItemID($linkedItemId);
                $description = $attributes['description'] ?? null;
                if (is_string($description) && $description !== '') {
                    $item->setDescription($description);
                }

                // Promote the legacy swallowed trigger_error into a real failure.
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
                    throw new LogicException('Legacy save() did not return a valid annotation id.');
                }

                $idProperty = new ReflectionProperty(Annotations::class, 'itemId');
                $idProperty->setValue($annotation, $itemId);
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
