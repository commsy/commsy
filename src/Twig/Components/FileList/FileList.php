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
use App\Event\CommsyEditEvent;
use App\Files\FileDeleter;
use App\Form\Type\UploadDropzoneType;
use App\Office\OfficeFileFactory;
use App\Repository\FilesRepository;
use App\Services\CurrentUserResolver;
use App\Services\FileUploader;
use App\Services\LegacyEnvironment;
use App\Twig\Components\DTO\FileDto;
use App\Utils\ItemService;
use App\WOPI\Discovery\DiscoveryService;
use cs_file_item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class FileList extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use ComponentWithFormTrait;

    public const int INITIAL_IMAGE_THRESHOLD = 5;
    public const int INITIAL_FILE_THRESHOLD = 20;

    #[LiveProp]
    public int $itemId;

    #[LiveProp]
    public ?int $versionId = null;

    /** @var FileDto[] */
    #[LiveProp]
    public array $files = [];

    #[LiveProp]
    public bool $draft = false;

    #[LiveProp(writable: true)]
    public string $filterFileExtensions = 'all';

    #[LiveProp(writable: true)]
    public string $filterFileName = '';

    #[LiveProp(writable: true)]
    public bool $imageLimit = true;

    #[LiveProp(writable: true)]
    public bool $fileLimit = true;

    #[LiveProp]
    public int $imageCount;

    #[LiveProp]
    public int $fileCount;

    public function __construct(
        private readonly DiscoveryService $discoveryService,
        private readonly ItemService $itemService,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->refreshFiles();
    }

    public function getFileList(bool $includeImages = false, bool $includeNonImages = false): iterable
    {
        // filter by name
        $this->files = array_filter($this->files, fn (FileDto $file) =>
            $this->filterFileName === '' ||
            strstr($file->filename, $this->filterFileName)
        );

        // filter by extensions
        $this->files = array_filter($this->files, fn (FileDto $file) =>
            $this->filterFileExtensions === 'all' ||
            $file->extension === $this->filterFileExtensions
        );

        // filter by type
        $files = $this->filterByType($this->files, $includeImages, $includeNonImages);

        // order
        usort($files, fn (FileDto $first, FileDto $second) =>
            $first->fileId < $second->fileId ? -1 : 1
        );

        // limit
        $length = $includeImages && $this->imageLimit ? self::INITIAL_IMAGE_THRESHOLD :
            ($includeNonImages && $this->fileLimit ? self::INITIAL_FILE_THRESHOLD : null);
        return array_slice($files, 0, $length);
    }

    protected function instantiateForm(): FormInterface
    {
        $item = $this->itemService->getItem($this->itemId);

        return $this->createFormBuilder()
            ->add('upload', UploadDropzoneType::class, [
                'upload_url' => $this->generateUrl('app_upload_attach', [
                    'roomId' => $item->getContextID(),
                    'itemId' => $this->itemId,
                    'versionId' => $this->versionId,
                ])
            ])
            ->getForm();
    }

    #[LiveListener('FileListItem:fileRenamed')]
    public function renameFile(#[LiveArg] array $file): void
    {
        $this->refreshFiles();
        $this->updateIndex();
    }

    #[LiveListener('FileListItem:fileRemoved')]
    public function removeFile(
        FileDeleter $fileDeleter,
        CurrentUserResolver $currentUserResolver,
        #[LiveArg] int $fileId
    ): void
    {
        $this->files = array_filter($this->files, fn(FileDto $file) =>
            $file->fileId != $fileId
        );

        $deleterId = (int) ($currentUserResolver->getUser()?->getItemId() ?? 0);
        $fileDeleter->softDeleteFile($fileId, $deleterId);
        $this->updateIndex();
    }

    #[LiveAction]
    public function refreshFiles(): void
    {
        $item = $this->itemService->getTypedItem($this->itemId, $this->versionId);
        $collection = new ArrayCollection($item->getFileList()->to_array());
        $iterator = $collection->getIterator();

        $iterator->uasort(fn (cs_file_item $first, cs_file_item $second) =>
            strcmp($first->getCreationDate(), $second->getCreationDate())
        );

        $this->files = (new ArrayCollection(iterator_to_array($iterator)))->map(function (cs_file_item $file) {
            $dto = new FileDto();
            $dto->fileId = $file->getFileID();
            $dto->contextId = $file->getContextID();
            $dto->extension = $file->getExtension();
            $dto->filename = $file->getFileName();
            $dto->filenameNoExt = $file->getFilenameWithoutExtension();
            $dto->fileSize = $file->getFileSize();

            return $dto;
        })->toArray();

        $this->imageCount = count($this->filterByType($this->files, true, false));
        $this->fileCount = count($this->filterByType($this->files, false, true));
    }

    #[LiveAction]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function createOfficeFile(
        OfficeFileFactory $fileFactory,
        FileUploader $fileUploader,
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager,
        #[LiveArg] string $type,
        /** @noinspection PhpUnusedParameterInspection */
        #[LiveArg] int $itemId
    ): void
    {
        $environment = $legacyEnvironment->getEnvironment();

        try {
            $file = $fileFactory->create($type);
            $uploadedFile = new UploadedFile($file->getPathname(), $file->getFilename());
            $fileId = $fileUploader->upload($uploadedFile, $environment->getCurrentPortalID(), $environment->getCurrentContextID());

            // rename
            /** @var FilesRepository $filesRepository */
            $filesRepository = $entityManager->getRepository(Files::class);

            /** @var Files $file */
            $file = $filesRepository->findOneBy(['filesId' => $fileId]);
            $file->setFilename($this->translator->trans('form.new_filename', [], 'form') . '.' . $uploadedFile->getExtension());
            $entityManager->persist($file);
            $entityManager->flush();

            // link new file to item
            $item = $this->itemService->getTypedItem($this->itemId);
            $item->setFileIDArray(array_merge($item->getFileIDArray(), [$fileId]));
            $item->setModificatorItem($environment->getCurrentUserItem());
            $item->save();

            // Update live prop
            $this->refreshFiles();

            $this->updateIndex();

        } catch (Exception $e) {
        }
    }

    public function getAllFileExtensions(): array
    {
        $item = $this->itemService->getTypedItem($this->itemId, $this->versionId);
        $collection = new ArrayCollection($item->getFileList()->to_array());

        $extensions = $collection->map(fn (cs_file_item $file) => $file->getExtension());
        $extensions = array_unique($extensions->toArray());
        sort($extensions);

        return $extensions;
    }

    private function updateIndex(): void
    {
        $item = $this->itemService->getItem($this->itemId);
        $this->eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);
    }

    /**
     * @param FileDto[] $files
     * @return FileDto[]
     */
    private function filterByType(array $files, bool $includeImages, bool $includeNonImages): array
    {
        return array_filter($this->files, function (FileDto $file) use ($includeImages, $includeNonImages) {
            $imageExtension = in_array(strtolower($file->extension), ['jpg', 'jpeg', 'png', 'gif']);

            if (!$includeImages && $imageExtension) return false;
            if (!$includeNonImages && !$imageExtension) return false;

            return true;
        });
    }
}
