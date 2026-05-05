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
use App\Entity\User;
use App\Services\LegacyEnvironment;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Materials>
 */
final class MaterialFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Materials::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(4),
            'description' => self::faker()->paragraph(),
            'room' => null,
            'creator' => null,
            // Pushes the entry into the future. Used to characterize the
            // isNotActivated() branch in cs_item::maySee — only mods and
            // the creator can see deactivated entries.
            'activationDate' => null,
            // Whitespace-separated user_ids on items in private rooms get
            // SEE-rights via cs_item::mayExternalSee + the external_viewer
            // table. Pass an array of usernames; persistence runs through
            // cs_item::persistExternalViewer() during save().
            'externalViewers' => [],
            // Defaults to private editing (creator only). Pass true to
            // make the material editable by any room user — sets the
            // legacy `public` column to 1.
            'public' => false,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra(
                'room',
                'creator',
                'activationDate',
                'externalViewers',
                'public',
            ))
            ->afterInstantiate(function(Materials $material, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('MaterialFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('MaterialFactory requires "creator" (App\Entity\User).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $item = $env->getMaterialManager()->getNewItem();
                $item->setTitle($material->getTitle());
                if ($material->getDescription() !== null) {
                    $item->setDescription($material->getDescription());
                }
                $item->setContextID($room->getItemId());

                if (!empty($attributes['activationDate'])) {
                    $activation = $attributes['activationDate'];
                    if ($activation instanceof \DateTimeInterface) {
                        $activation = $activation->format('Y-m-d H:i:s');
                    }
                    $item->setActivationDate($activation);
                }
                if (!empty($attributes['externalViewers'])) {
                    $item->setExternalViewerAccounts($attributes['externalViewers']);
                }
                if (!empty($attributes['public'])) {
                    $item->setPublic(1);
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

                $idProperty = new ReflectionProperty(Materials::class, 'itemId');
                $idProperty->setValue($material, $itemId);
                $versionProperty = new ReflectionProperty(Materials::class, 'versionId');
                $versionProperty->setValue($material, (int) $item->getVersionID());
                $material->setContextId($room->getItemId());
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
