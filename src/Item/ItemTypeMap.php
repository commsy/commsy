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

namespace App\Item;

use App\Entity\Annotations;
use App\Entity\Announcement;
use App\Entity\Assessments;
use App\Entity\Dates;
use App\Entity\Discussionarticles;
use App\Entity\Discussions;
use App\Entity\Labels;
use App\Entity\LinkItems;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Section;
use App\Entity\Server;
use App\Entity\Step;
use App\Entity\Tag;
use App\Entity\Tasks;
use App\Entity\Todos;
use App\Entity\User;
use App\Room\RoomType;
use App\Rubric\RubricType;

/**
 * Translates between the `items.type` discriminator and the entity class
 * that owns the rubric's own table.
 *
 * Pure lookup, no database: knowing that `'material'` means `Materials` is
 * not the same job as loading one, so {@see TypedEntityResolver} keeps the
 * loading and asks here for the class.
 */
final class ItemTypeMap
{
    /**
     * `items.type` → entity class. Mirrors the DiscriminatorMap that used to
     * sit on `Items`; the room flavours share one entity, as they did there.
     *
     * Keys come from the enums that own these discriminator values, so the
     * strings are declared once: {@see RubricType}, {@see ItemType} and
     * {@see RoomType}.
     *
     * @var array<string, class-string>
     */
    private const CLASS_BY_TYPE = [
        RubricType::Annotation->value => Annotations::class,
        RubricType::Announcement->value => Announcement::class,
        ItemType::Assessment->value => Assessments::class,
        RoomType::Community->value => Room::class,
        RubricType::Date->value => Dates::class,
        ItemType::DiscussionArticle->value => Discussionarticles::class,
        RubricType::Discussion->value => Discussions::class,
        RoomType::GroupRoom->value => Room::class,
        RubricType::Label->value => Labels::class,
        ItemType::LinkItem->value => LinkItems::class,
        RubricType::Material->value => Materials::class,
        RoomType::PrivateRoom->value => Room::class,
        RoomType::Project->value => Room::class,
        ItemType::Section->value => Section::class,
        ItemType::Server->value => Server::class,
        ItemType::Step->value => Step::class,
        ItemType::Tag->value => Tag::class,
        ItemType::Task->value => Tasks::class,
        RubricType::Todo->value => Todos::class,
        ItemType::User->value => User::class,
    ];

    /**
     * The reverse of {@see CLASS_BY_TYPE}, derived rather than written out.
     *
     * Classes that more than one type maps to are dropped: an entity cannot
     * say which of the room flavours it is, so `Room` has no honest answer
     * and is better reported as unknown than as an arbitrary one.
     *
     * @var array<class-string, string>
     */
    private readonly array $typeByClass;

    public function __construct()
    {
        $typesPerClass = array_count_values(self::CLASS_BY_TYPE);

        $unambiguous = [];
        foreach (self::CLASS_BY_TYPE as $type => $class) {
            if ($typesPerClass[$class] === 1) {
                $unambiguous[$class] = $type;
            }
        }
        $this->typeByClass = $unambiguous;
    }

    /**
     * The entity class for an `items.type` value, or null when the type has
     * no entity of its own.
     *
     * @return class-string|null
     */
    public function classFor(string $type): ?string
    {
        return self::CLASS_BY_TYPE[$type] ?? null;
    }

    /**
     * The `items.type` value for a rubric entity, or null when the class is
     * not mapped or is shared by several types.
     *
     * Walks up the parent chain so a Doctrine proxy answers like the entity
     * it stands in for.
     */
    public function typeOf(object $entity): ?string
    {
        for ($class = $entity::class; $class !== false; $class = get_parent_class($class)) {
            if (isset($this->typeByClass[$class])) {
                return $this->typeByClass[$class];
            }
        }

        return null;
    }

    /**
     * Every mapped `items.type` value.
     *
     * @return list<string>
     */
    public function knownTypes(): array
    {
        return array_keys(self::CLASS_BY_TYPE);
    }
}
