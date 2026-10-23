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

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\RoomRepository;
use App\Room\Vocabulary\VocabularyKind;
use LogicException;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves a room's vocabulary on the REST interface, from the kinds the room
 * export reads, so the two cannot disagree about what an id means.
 *
 * A list is the whole vocabulary of its kind, never a page.
 *
 * @implements ProviderInterface<object>
 */
readonly class VocabularyProvider implements ProviderInterface
{
    /**
     * @param iterable<VocabularyKind> $kinds
     */
    public function __construct(
        #[AutowireIterator('app.room.vocabulary')]
        private iterable $kinds,
        private RoomRepository $rooms,
    ) {
    }

    /**
     * @return list<object>
     */
    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $kind = $this->kindFor($operation->getClass());

        $room = $this->rooms->findOneOfTypes((int) $uriVariables['roomId'], RoomProvider::ROOM_TYPES)
            ?? throw new NotFoundHttpException('Room not found.');

        return $kind->of($room);
    }

    private function kindFor(?string $class): VocabularyKind
    {
        foreach ($this->kinds as $kind) {
            if ($class === $kind->termClass()) {
                return $kind;
            }
        }

        throw new LogicException(sprintf('No vocabulary kind serves "%s".', $class));
    }
}
