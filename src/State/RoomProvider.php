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
use ApiPlatform\State\ProviderInterface;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Room\RoomType;
use Override;

/**
 * @implements ProviderInterface<Room[]|Room|null>
 */
readonly class RoomProvider implements ProviderInterface
{
    /**
     * The rooms the interface knows. Anything else — private rooms, user rooms —
     * answers 404, and so must whatever hangs under a room.
     */
    public const ROOM_TYPES = [RoomType::Project, RoomType::Community, RoomType::GroupRoom];

    public function __construct(
        private RoomRepository $roomRepository
    ) {
    }

    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->roomRepository->findBy(['type' => self::ROOM_TYPES]);
        }

        return $this->roomRepository->findOneBy([
            'itemId' => $uriVariables['itemId'],
            'type' => self::ROOM_TYPES,
        ]);
    }
}
