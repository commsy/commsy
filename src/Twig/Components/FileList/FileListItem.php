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

namespace App\Twig\Components\FileList;

use App\Entity\Files;
use App\Twig\Components\DTO\FileDto;
use App\WOPI\Discovery\DiscoveryService;
use App\WOPI\Permission\WOPIPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class FileListItem
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: ['filenameNoExt'])]
    public FileDto $file;

    #[LiveProp]
    public int $itemId;

    #[LiveProp]
    public bool $isImage = false;

    #[LiveProp]
    public bool $renameMode = false;

    #[LiveProp]
    public bool $deleteMode = false;

    public function __construct(
        private readonly DiscoveryService $discoveryService,
    ) {
    }

    #[LiveAction]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    #[IsGranted('ITEM_FILE_LOCK', subject: 'itemId')]
    public function renameFile(
        /** @noinspection PhpUnusedParameterInspection */
        #[LiveArg] int $itemId,
        EntityManagerInterface $entityManager
    ): void
    {
        if ($this->renameMode) {
            $fileRepository = $entityManager->getRepository(Files::class);

            /** @var Files $file */
            $file = $fileRepository->findOneBy(['filesId' => $this->file->fileId]);
            $file->setFilename("{$this->file->filenameNoExt}.{$this->file->extension}");
            $this->file->filename = "{$this->file->filenameNoExt}.{$this->file->extension}";

            $entityManager->persist($file);
            $entityManager->flush();

            $this->emitUp('FileListItem:fileRenamed', [
                'file' => $this->file,
            ]);
        }

        $this->renameMode = !$this->renameMode;
    }

    #[LiveAction]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    #[IsGranted('ITEM_FILE_LOCK', subject: 'itemId')]
    public function removeFile(
        /** @noinspection PhpUnusedParameterInspection */
        #[LiveArg] int $itemId,
        #[LiveArg] bool $confirmed = false
    ): void
    {
        if ($confirmed) {
            $this->emitUp('FileListItem:fileRemoved', [
                'fileId' => $this->file->fileId,
            ]);
        }

        $this->deleteMode = !$this->deleteMode;
    }

    public function supportsOnlineOffice(FileDto $file): bool
    {
        $discovery = $this->discoveryService->getWOPIDiscovery();
        if (!$discovery) {
            return false;
        }

        $app = $this->discoveryService->findApp($discovery, $file->extension, WOPIPermission::VIEW->value);
        if (!$app) {
            return false;
        }

        return true;
    }
}
