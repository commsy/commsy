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

namespace App\MessageHandler;

use App\Export\RoomExportArchiver;
use App\Export\RoomExportBuilder;
use App\Export\RoomExportStorage;
use App\Message\RoomExportRequested;
use App\Repository\RoomExportRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * Builds a requested room export.
 *
 * Runs on a worker because how long it takes follows from the size of the room,
 * which nothing knows up front.
 *
 * A failure is recorded on the row and not rethrown. The row is what the
 * moderation sees, and retrying would not help: the same room and the same code
 * would fail the same way, while the row already says so.
 */
#[AsMessageHandler]
final readonly class RoomExportRequestedHandler
{
    public function __construct(
        private RoomExportRepository $roomExports,
        private RoomExportBuilder $builder,
        private RoomExportArchiver $archiver,
        private RoomExportStorage $storage,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RoomExportRequested $message): void
    {
        $export = $this->roomExports->find($message->getRoomExportId());

        if (null === $export) {
            // The room was deleted while the message waited, taking its export
            // rows with it. Nothing left to build.
            return;
        }

        if (!$export->getStatus()->isInProgress()) {
            // Already settled — a duplicate delivery, or a run that finished
            // before this copy of the message arrived.
            return;
        }

        $export->markRunning();
        $this->roomExports->save($export);

        try {
            $document = $this->builder->build($export->getRoom());
            $filename = $this->storage->filenameFor($export);
            $size = $this->archiver->write($this->storage->pathFor($export), $document);

            $export->markFinished($filename, $size);
        } catch (Throwable $e) {
            $this->logger->error('Building a room export failed', [
                'roomExportId' => $export->getId(),
                'roomId' => $export->getRoom()->getItemId(),
                'exception' => $e,
            ]);

            $export->markFailed($e->getMessage());
        }

        $this->roomExports->save($export);
    }
}
