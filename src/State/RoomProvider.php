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

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Room\RoomType;
use ArrayIterator;
use Override;

/**
 * @implements ProviderInterface<Room>
 */
readonly class RoomProvider implements ProviderInterface
{
    /**
     * The rooms the interface knows. Anything else — private rooms, user rooms —
     * answers 404, and so must whatever hangs under a room.
     */
    public const ROOM_TYPES = [RoomType::Project, RoomType::Community, RoomType::GroupRoom];

    public function __construct(
        private RoomRepository $roomRepository,
        private Pagination $pagination,
    ) {
    }

    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $limit = $this->pagination->getLimit($operation, $context);

            // A page: all rooms of all portals would otherwise be one list.
            return new TraversablePaginator(
                new ArrayIterator($this->roomRepository->findOfTypes(
                    self::ROOM_TYPES,
                    $this->pagination->getOffset($operation, $context),
                    $limit,
                )),
                $this->pagination->getPage($context),
                $limit,
                $this->roomRepository->countOfTypes(self::ROOM_TYPES),
            );
        }

        return $this->roomRepository->findOneBy([
            'itemId' => $uriVariables['itemId'],
            'type' => self::ROOM_TYPES,
        ]);
    }
}
