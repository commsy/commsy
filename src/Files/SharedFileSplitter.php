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

namespace App\Files;

use App\Entity\Files;
use App\Repository\FilesRepository;
use App\Repository\ItemLinkFileRepository;
use App\Services\CurrentUserResolver;
use App\Utils\FileService;
use Doctrine\DBAL\Connection;
use RuntimeException;

/**
 * A new version of an entry links the files of the one before instead of
 * copying them, so versions share their files. A file changed in place —
 * the online office does that — would change in every version at once.
 * The version about to change it therefore first gets a copy of its own;
 * the others keep the file as it was.
 */
final readonly class SharedFileSplitter
{
    public function __construct(
        private ItemLinkFileRepository $itemLinkFileRepository,
        private FilesRepository $filesRepository,
        private FileService $fileService,
        private FileDeleter $fileDeleter,
        private CurrentUserResolver $currentUserResolver,
        private Connection $connection,
    ) {
    }

    /**
     * The file the given version may change: the file itself when nothing
     * else carries it, otherwise a copy that replaces it in this version.
     */
    public function fileToChange(Files $file, int $itemId, int $versionId): Files
    {
        $fileId = $file->getFilesId();
        if (!$this->itemLinkFileRepository->isLinkedElsewhere($fileId, $itemId, $versionId)) {
            return $file;
        }

        // Without content on disk there is nothing to copy; the editor
        // reports the missing file on its own.
        $source = $this->fileService->makeAbsolute($file);
        if (!is_file($source)) {
            return $file;
        }

        $copy = $this->fileService->getNewFile();
        $copy->setPortalId((int) $file->getPortal()?->getId());
        $copy->setContextID($file->getContextId());
        $copy->setPostFile(['tmp_name' => $source, 'name' => $file->getFilename()]);
        $copy->save();
        $copyId = (int) $copy->getFileID();

        $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);
        $this->connection->transactional(function (Connection $connection) use ($itemId, $versionId, $fileId, $copyId, $deleterId): void {
            $connection->createQueryBuilder()
                ->insert('item_link_file')
                ->values(['item_iid' => ':itemId', 'item_vid' => ':versionId', 'file_id' => ':fileId'])
                ->setParameter('itemId', $itemId)
                ->setParameter('versionId', $versionId)
                ->setParameter('fileId', $copyId)
                ->executeStatement();

            // The original stays alive through the links of the others.
            $this->fileDeleter->detachFromItem($itemId, $deleterId, $versionId, $fileId);
        });

        $copied = $this->filesRepository->find($copyId);
        if (null === $copied) {
            throw new RuntimeException('The copy of file '.$fileId.' was not stored.');
        }

        return $copied;
    }
}
