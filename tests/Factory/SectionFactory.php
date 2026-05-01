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

use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Section;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Section>
 */
final class SectionFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Section::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(3),
            'description' => self::faker()->paragraph(),
            'room' => null,
            'creator' => null,
            'material' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra(
                'room',
                'creator',
                'material',
            ))
            ->afterInstantiate(function(Section $section, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;
                $material = $attributes['material'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('SectionFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('SectionFactory requires "creator" (App\Entity\User).');
                }
                if (!$material instanceof Materials) {
                    throw new LogicException('SectionFactory requires "material" (App\Entity\Materials).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $item = $env->getSectionManager()->getNewItem();
                $item->setContextID($room->getItemId());
                $item->setLinkedItemID($material->getItemId());
                // Sections live inside a specific material version — inherit the parent's.
                $item->setVersionID((int) $material->getVersionId());
                $item->setTitle($section->getTitle());
                if ($section->getDescription() !== null) {
                    $item->setDescription($section->getDescription());
                }

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

                $idProperty = new ReflectionProperty(Section::class, 'itemId');
                $idProperty->setValue($section, $itemId);
                $section->setVersionId((int) $item->getVersionID());
                $section->setContextId($room->getItemId());
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
