<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.04.18
 * Time: 15:55
 */

namespace App\Database;


use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FixPhysicalFiles implements DatabaseCheck
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    public function __construct(
        Connection $connection,
        ParameterBagInterface $parameterBag
    ) {
        $this->connection = $connection;
        $this->parameterBag = $parameterBag;
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

            // only remove dirs at the end of all checks
            $markedForRemoval = array();

            // iterate dirs; attention: those are finder instances
            foreach ($directories as $directory) {
                $relativePathName = $directory->getRelativePathname();

                $relativePathNameExp = explode('/', $relativePathName);
                $level = count($relativePathNameExp);

                // check if file is associated with existing portal and delete orphans
                switch ($level) {
                    case 1:
                        $contextId = $relativePathNameExp[0];

                        // exclude folders
                        if ($contextId == 99 || $contextId == 'temp' || $contextId == 'portal') {
                            continue;
                        }

                        $qb = $this->connection->createQueryBuilder()
                            ->select('f.*', 'i.context_id as portalId')
                            ->from('files', 'f')
                            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')
                            ->where('f.deletion_date IS NULL')
                            ->andWhere('f.context_id = :contextId')
                            ->setParameter('contextId', $contextId);
                        $files = $qb->execute();

                        if (!is_array($files)) {

                            // local file system
                            $files = $files->fetchAllAssociative();
                            if (!count($files) > 0) {
                                $markedForRemoval[] = $directory;
                            }
                        } else {
                            // testing file system
                            $hit = false;
                            foreach ($files as $file) {
                                if (in_array($contextId, $file)) {
                                    $hit = true;
                                    continue;
                                }
                            }

                            if (!$hit) {
                                $markedForRemoval[] = $directory;
                            }
                        }

                        break;

                    // check first level: must be numeric, can only be 4 digits long
                    // e.g. 99/1234: okay; 99/123: wrong; 99/12345: wrong; 99/somefolder: wrong
                    case 2:
                        $toBeChecked = $relativePathNameExp[1];

                        // check numeric
                        if (!is_numeric($toBeChecked) && file_exists($relativePathName)) {
                            $markedForRemoval[] = $directory;
                        }

                        // check length being 4
                        if (strlen($toBeChecked) != 4 && file_exists($relativePathName)) {
                            $markedForRemoval[] = $directory;
                        }

                        break;

                    // check on e.g. _123
                    case 3:
                        $toBeChecked = $relativePathNameExp[2];

                        if (!str_contains($toBeChecked, '_') && file_exists($relativePathName)) {
                            $markedForRemoval[] = $directory;
                        }

                        if (!substr($toBeChecked, 1) == '_' && file_exists($relativePathName)) {
                            $markedForRemoval[] = $directory;
                        }

                        if (!is_numeric(substr($toBeChecked, 1)) && file_exists($relativePathName)) {
                            $markedForRemoval[] = $directory;
                        }
                        break;

                }
            }

            // remove all dirs marked for removal
            foreach ($markedForRemoval as $removal) {
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
                            continue;
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
                    if (str_contains($filename, '_')) {
                        // TODO use ending function
                        $underscoreParts = explode('_', $filenameWithoutExtension);

                        // check if third level contains two underscores
                        if (substr_count($filenameWithoutExtension, "_") == 2) {

                            // check if cid + int (e.g. cid12345)
                            if (str_contains($underscoreParts[0], 'cid')) {

                                // check if ID string (without CID) is indeed numeric
                                $idStringWithoutCID = substr($underscoreParts[0], 3);
                                if (is_numeric($idStringWithoutCID)) {
                                    // check if first three chars of ID string is 'CID'
                                    if (substr($underscoreParts[0], 0, 3)) {
                                        continue;
                                    }
                                }
                            }
                        }
                    }
                }

                // delete if no allowed pattern could be found
                $filesystem->remove($file);
            }
        }

        return true;
    }
}