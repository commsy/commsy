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

use App\Entity\Discussionarticles;
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
 * @extends PersistentObjectFactory<Discussionarticles>
 */
final class DiscussionArticleFactory extends PersistentObjectFactory
{
    use PrimesLegacyEnvironment;

    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Discussionarticles::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'description' => self::faker()->paragraph(),
            'room' => null,
            'creator' => null,
            'discussion' => null,
            'position' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(Instantiator::withConstructor()->allowExtra(
                'room',
                'creator',
                'discussion',
                'position',
            ))
            ->afterInstantiate(function(Discussionarticles $article, array $attributes): void {
                $room = $attributes['room'] ?? null;
                $creator = $attributes['creator'] ?? null;
                $discussion = $attributes['discussion'] ?? null;

                if (!$room instanceof Room) {
                    throw new LogicException('DiscussionArticleFactory requires "room" (App\Entity\Room).');
                }
                if (!$creator instanceof User) {
                    throw new LogicException('DiscussionArticleFactory requires "creator" (App\Entity\User).');
                }
                if (!$discussion instanceof Discussions) {
                    throw new LogicException('DiscussionArticleFactory requires "discussion" (App\Entity\Discussions).');
                }

                $env = $this->primeLegacyEnvironment($room, $creator);

                $item = $env->getDiscussionArticlesManager()->getNewItem();
                $item->setContextID($room->getItemId());
                $item->setDiscussionID($discussion->getItemId());
                if ($attributes['description'] !== null) {
                    $item->setDescription((string) $attributes['description']);
                }
                if (!empty($attributes['position'])) {
                    $item->setPosition((string) $attributes['position']);
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

                $idProperty = new ReflectionProperty(Discussionarticles::class, 'itemId');
                $idProperty->setValue($article, $itemId);
                $article->setContextId($room->getItemId());
                $article->setDiscussionId($discussion->getItemId());
            });
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return $this->legacyEnvironment;
    }
}
