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

use App\Entity\Links;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use LogicException;
use ReflectionProperty;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Links>
 */
final class LinkFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Links::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'room' => null,
            'creator' => null,
            'fromItemId' => null,
            'toItemId' => null,
            'linkType' => 'relevant_for',
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(
                Instantiator::withConstructor()->allowExtra('room', 'creator', 'fromItemId', 'toItemId', 'linkType')
            )
            ->afterInstantiate(function(Links $link, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;
                $fromItemId = $attributes['fromItemId'] ?? null;
                $toItemId = $attributes['toItemId'] ?? null;
                $linkType = $attributes['linkType'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('LinkFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('LinkFactory requires "creator" (App\Entity\User).');
                }
                if (!is_int($fromItemId) || $fromItemId <= 0) {
                    throw new LogicException('LinkFactory requires "fromItemId" (int > 0).');
                }
                if (!is_int($toItemId) || $toItemId <= 0) {
                    throw new LogicException('LinkFactory requires "toItemId" (int > 0).');
                }
                if (!is_string($linkType) || $linkType === '') {
                    throw new LogicException('LinkFactory requires "linkType" (non-empty string).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                set_error_handler(function(int $errno, string $errstr): bool {
                    throw new LogicException(sprintf('Legacy save() warning: %s', $errstr));
                }, E_USER_WARNING);
                try {
                    $env->getLinkManager()->save([
                        'from_item_id' => $fromItemId,
                        'from_version_id' => 0,
                        'to_item_id' => $toItemId,
                        'to_version_id' => 0,
                        'link_type' => $linkType,
                        'room_id' => $room->getItemId(),
                    ]);
                } finally {
                    restore_error_handler();
                }

                $map = [
                    'fromItemId' => $fromItemId,
                    'fromVersionId' => 0,
                    'toItemId' => $toItemId,
                    'toVersionId' => 0,
                    'linkType' => $linkType,
                ];
                foreach ($map as $prop => $value) {
                    $p = new ReflectionProperty(Links::class, $prop);
                    $p->setValue($link, $value);
                }
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
