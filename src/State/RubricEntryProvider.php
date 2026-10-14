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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Export\ExportableEntry;
use App\Export\RubricExportSource;
use App\Repository\RoomRepository;
use LogicException;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the entries of every rubric on the REST interface, from the sources
 * the room export reads — so the two cannot disagree about what a room holds,
 * and a rubric that joins the export is ready for the interface by declaring a
 * resource.
 *
 * @implements ProviderInterface<ExportableEntry>
 */
readonly class RubricEntryProvider implements ProviderInterface
{
    /**
     * @param iterable<RubricExportSource> $sources
     */
    public function __construct(
        #[AutowireIterator('app.export.rubric_source')]
        private iterable $sources,
        private RoomRepository $rooms,
    ) {
    }

    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $source = $this->sourceFor($operation->getClass());

        if (!$operation instanceof CollectionOperationInterface) {
            return $source->find((int) $uriVariables['itemId']);
        }

        $room = $this->rooms->findOneBy([
            'itemId' => (int) $uriVariables['roomId'],
            'type' => RoomProvider::ROOM_TYPES,
        ]);

        if (null === $room) {
            throw new NotFoundHttpException('Room not found.');
        }

        return [...$source->entriesFor($room)];
    }

    private function sourceFor(?string $class): RubricExportSource
    {
        foreach ($this->sources as $source) {
            if (null !== $class && is_a($class, $source->entryClass(), true)) {
                return $source;
            }
        }

        throw new LogicException(sprintf('No rubric source serves "%s".', $class));
    }
}
