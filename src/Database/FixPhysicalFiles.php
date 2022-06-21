<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.04.18
 * Time: 15:55
 */

namespace App\Database;


use App\Repository\FilesRepository;
use App\Repository\ItemRepository;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use App\Repository\ZzzFilesRepository;
use App\Repository\ZzzItemRepository;
use App\Repository\ZzzRoomRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FixPhysicalFiles implements DatabaseCheck
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var RoomRepository
     */
    private RoomRepository $roomRepository;

    /**
     * @var ZzzRoomRepository
     */
    private ZzzRoomRepository $zzzRoomRepository;

    /**
     * @var FilesRepository
     */
    private FilesRepository $filesRespository;

    /**
     * @var ZzzFilesRepository
     */
    private ZzzFilesRepository $zzzFilesRepository;

    /**
     * @var ItemRepository
     */
    private ItemRepository $itemRepository;

    /**
     * @var ZzzItemRepository
     */
    private ZzzItemRepository $zzzItemRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $cleanupLogger;

    public function __construct(
        ParameterBagInterface $parameterBag,
        PortalRepository $portalRepository,
        RoomRepository $roomRepository,
        ZzzRoomRepository $zzzRoomRepository,
        FilesRepository $filesRespository,
        ZzzFilesRepository $zzzFilesRepository,
        ItemRepository $itemRepository,
        ZzzItemRepository $zzzItemRepository,
        LoggerInterface $cleanupLogger
    ) {
        $this->parameterBag = $parameterBag;
        $this->portalRepository = $portalRepository;
        $this->roomRepository = $roomRepository;
        $this->zzzRoomRepository = $zzzRoomRepository;
        $this->filesRespository = $filesRespository;
        $this->zzzFilesRepository = $zzzFilesRepository;
        $this->itemRepository = $itemRepository;
        $this->zzzItemRepository = $zzzItemRepository;
        $this->cleanupLogger = $cleanupLogger;
    }

    public function getPriority()
    {
        return 100;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        // io for logs
        $io->text('Inspecting physical files');

        // filesystem for removing directories / files
        $filesystem = new Filesystem();

        // base directory to be checked
        $filesDirectory = $this->parameterBag->get('files_directory');

        // finder instances for directories and files
        $directories = (new Finder())->directories()
            ->in($filesDirectory);

        $finderFileNames = (new Finder())->files()
            ->in($filesDirectory);

        if ($directories->hasResults()) {

            $allowedFirstLevelFolderNames = $this->getValidFirstLevelFolderNames();

            // only remove dirs at the end of all checks
            $markedForRemoval = [];

            // iterate dirs; attention: those are finder instances
            foreach ($directories as $directory) {
                $relativePathName = $directory->getRelativePathname();

                $relativePathNameExp = explode('/', $relativePathName);
                $level = count($relativePathNameExp);

                // check if file is associated with existing portal and delete orphans
                switch ($level) {
                    case 1:
                        $firstLevelFolderName = $relativePathNameExp[0];

                        if (!in_array($firstLevelFolderName, $allowedFirstLevelFolderNames)) {
                            $markedForRemoval[] = $directory;
                        }

                        break;

                    // check second level: must be numeric, can only be 4 digits long
                    // e.g. 99/1234: okay; 99/123: wrong; 99/12345: wrong; 99/somefolder: wrong
                    case 2:
                        $secondLevelFolderName = $relativePathNameExp[1];

                        if (!is_numeric($secondLevelFolderName) || strlen($secondLevelFolderName) != 4) {
                            $markedForRemoval[] = $directory;
                        }

                        break;

                    // check third level
                    // check on e.g. 123_
                    case 3:
                        $secondLevelFolderName = $relativePathNameExp[1];
                        $thirdLevelFolderName = $relativePathNameExp[2];

                        if (!preg_match('/\d+_/', $thirdLevelFolderName)) {
                            $markedForRemoval[] = $directory;
                            break;
                        }

                        $lookupId = $secondLevelFolderName . substr($thirdLevelFolderName, 0, -1);
                        if (!$this->roomExists($lookupId)) {
                            $markedForRemoval[] = $directory;
                        }

                        break;

                    default:
                        // Delete all other folds
                        $markedForRemoval[] = $directory;
                }
            }

            // remove all dirs marked for removal
            foreach ($markedForRemoval as $removal) {
                if ($io->isVerbose()) {
                    $io->note('Deleting ' . $removal);
                }
                $this->cleanupLogger->info('Deleting ' . $removal);
                $filesystem->remove($removal);
            }
        }

        if ($finderFileNames->hasResults()) {
            foreach ($finderFileNames as $file) {
                $filename = $file->getFilename();
                $filenameWithoutExtension = $file->getFilenameWithoutExtension();

                if (!empty($file->getExtension())) {
                    // check digit + file extension
                    if (!str_contains($filename, '_')) {
                        if (is_numeric($filenameWithoutExtension)) {
                            $relativePathNameExp = explode('/', $file->getRelativePath());
                            $secondLevelFolderName = $relativePathNameExp[1];
                            $thirdLevelFolderName = $relativePathNameExp[2];
                            $contextId = $secondLevelFolderName . substr($thirdLevelFolderName, 0, -1);

                            if ($this->fileExists($filenameWithoutExtension, $contextId)) {
                                continue;
                            }
                        }
                    }

                    // check digit + file extension + file extension contains '_' e.g. '1.jpg_thumbnail'
                    if (str_contains($filename, '_')) {
                        if (is_numeric($filenameWithoutExtension) &&
                            str_contains($file->getExtension(), '_')) {
                            continue;
                        }
                    }

                    // check cid[roomId]_bginfo|logo|[username]_[filename].[extension]
                    if (preg_match('/cid\d+_(bginfo|logo|.+)_.*/', $filenameWithoutExtension)) {
                        continue;
                    }
                }

                // delete if no allowed pattern could be found
                if ($io->isVerbose()) {
                    $io->note('Deleting ' . $file);
                }
                $this->cleanupLogger->info('Deleting ' . $file);
                $filesystem->remove($file);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    private function getValidFirstLevelFolderNames(): array
    {
        $validNames = [99, 'temp'];

        $portals = $this->portalRepository->findAll();

        foreach ($portals as $portal) {
            $validNames[] = $portal->getId();
        }

        $projectAndUserRoomIds = array_merge(
            $this->roomRepository->getProjectAndUserRoomIds(),
            $this->zzzRoomRepository->getProjectAndUserRoomIds(),
        );

        return array_merge($validNames, $projectAndUserRoomIds);
    }

    /**
     * @param int $fileId
     * @param int $contextId
     * @return bool
     */
    private function fileExists(int $fileId, int $contextId): bool
    {
        try {
            if ($this->filesRespository->getNumFiles($fileId, $contextId) > 0) {
                return true;
            }

            return $this->zzzFilesRepository->getNumFiles($fileId, $contextId) > 0;
        } catch (NoResultException|NonUniqueResultException $e) {
            return true;
        }
    }

    /**
     * @param int $roomId
     * @return bool
     */
    private function roomExists(int $roomId): bool
    {
        try {
            if ($this->itemRepository->getNumItems($roomId) > 0) {
                return true;
            }

            return $this->zzzItemRepository->getNumItems($roomId) > 0;
        } catch (NoResultException|NonUniqueResultException $e) {
            return true;
        }
    }
}