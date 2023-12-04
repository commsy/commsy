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

namespace App\Database;

use App\Repository\FilesRepository;
use App\Repository\ItemRepository;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FixPhysicalFiles implements DatabaseCheck
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly PortalRepository $portalRepository,
        private readonly RoomRepository $roomRepository,
        private readonly FilesRepository $filesRespository,
        private readonly ItemRepository $itemRepository,
        private readonly LoggerInterface $cleanupLogger
    ) {
    }

    public function getPriority(): int
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

                        if (!is_numeric($secondLevelFolderName) || 4 != strlen($secondLevelFolderName)) {
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

                        $lookupId = $secondLevelFolderName.substr($thirdLevelFolderName, 0, -1);
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
                    $io->note('Deleting '.$removal);
                }
                $this->cleanupLogger->info('Deleting '.$removal);
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
                            $contextId = $secondLevelFolderName.substr($thirdLevelFolderName, 0, -1);

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
                    $io->note('Deleting '.$file);
                }
                $this->cleanupLogger->info('Deleting '.$file);
                $filesystem->remove($file);
            }
        }

        return true;
    }

    private function getValidFirstLevelFolderNames(): array
    {
        $validNames = [99, 'temp'];

        $portals = $this->portalRepository->findAll();

        foreach ($portals as $portal) {
            $validNames[] = $portal->getId();
        }

        return array_merge($validNames, $this->roomRepository->getProjectAndUserRoomIds());
    }

    private function fileExists(int $fileId, int $contextId): bool
    {
        try {
            return $this->filesRespository->getNumFiles($fileId, $contextId) > 0;
        } catch (NoResultException|NonUniqueResultException) {
            return true;
        }
    }

    private function roomExists(int $roomId): bool
    {
        try {
            return $this->itemRepository->getNumItems($roomId) > 0;
        } catch (NoResultException|NonUniqueResultException) {
            return true;
        }
    }
}
