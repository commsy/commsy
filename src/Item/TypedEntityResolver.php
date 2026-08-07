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
use App\Entity\Items;
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
use App\Repository\MaterialsRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Resolves an item id to its rubric entity.
 *
 * This used to be Doctrine's job: `Items` declared an InheritanceType('JOINED')
 * with a DiscriminatorMap naming the classes below. But none of them extends
 * `Items`, so Doctrine registered no subclasses and hydrated only the `items`
 * columns onto the mapped class — every field owned by the rubric table stayed
 * uninitialised. With typed properties that is not a partial object, it is a
 * crash waiting for the first read (see #5009 and Materials::$versionId).
 *
 * The mapping is therefore explicit here, and loading goes through the rubric's
 * own repository, which knows how that rubric is keyed.
 */
final readonly class TypedEntityResolver
{
    /**
     * `items.type` → entity class. Mirrors the former DiscriminatorMap; the
     * three room flavours share one entity, as they did there.
     *
     * @var array<string, class-string>
     */
    private const CLASS_BY_TYPE = [
        'annotation' => Annotations::class,
        'announcement' => Announcement::class,
        'assessments' => Assessments::class,
        'community' => Room::class,
        'date' => Dates::class,
        'discarticle' => Discussionarticles::class,
        'discussion' => Discussions::class,
        'grouproom' => Room::class,
        'label' => Labels::class,
        'link_item' => LinkItems::class,
        'material' => Materials::class,
        'privateroom' => Room::class,
        'project' => Room::class,
        'section' => Section::class,
        'server' => Server::class,
        'step' => Step::class,
        'tag' => Tag::class,
        'task' => Tasks::class,
        'todo' => Todos::class,
        'user' => User::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MaterialsRepository $materialsRepository,
    ) {
    }

    /**
     * The rubric entity for `$itemId`, or null when the row is unknown or its
     * type has no entity of its own.
     */
    public function find(int $itemId): ?object
    {
        $type = $this->entityManager->getRepository(Items::class)
            ->find($itemId)?->getType();

        if ($type === null) {
            return null;
        }

        $class = self::CLASS_BY_TYPE[$type] ?? null;
        if ($class === null) {
            return null;
        }

        // Materials and their sections are the only versioned rubrics: item_id
        // alone does not identify a row there, so pick the latest version
        // instead of an arbitrary one.
        if ($class === Materials::class) {
            return $this->materialsRepository->findLatestVersionByItemId($itemId);
        }

        if ($class === Section::class) {
            return $this->entityManager->getRepository(Section::class)
                ->findOneBy(['itemId' => $itemId], ['versionId' => 'DESC']);
        }

        return $this->entityManager->getRepository($class)
            ->findOneBy(['itemId' => $itemId]);
    }
}
