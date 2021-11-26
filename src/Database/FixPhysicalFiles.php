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

    /**
     * @var array
     */
    private $folderFiles;

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

        // fetch all fileNames with preceding paths - use finder?
        $this->folderFiles = array();
        $scannedFileNames = $this->listFolderFiles($filesDirectory);

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

                //TODO: remove bas dir from rest to ensure stability
                $parts = explode("/", $directory);

                // check first level: must be numeric, can only be 4 digits long
                // e.g. 99/1234: okay; 99/123: wrong; 99/12345: wrong; 99/somefolder: wrong
                if (sizeof($parts) > 6) {
                    $toBeChecked = $parts[6];

                    // check numeric
                    if (!is_numeric($toBeChecked) && file_exists($relativePathName)) {
                        $markedForRemoval[] = $directory;
                    }

                    // check length being 4
                    if (strlen($toBeChecked) != 4 && file_exists($relativePathName)) {
                        $markedForRemoval[] = $directory;
                    }
                }

                // check on e.g. _123
                if (sizeof($parts) > 7) {
                    $toBeChecked = $parts[7];

                    if (!str_contains($toBeChecked, '_') && file_exists($relativePathName)) {
                        $markedForRemoval[] = $directory;
                    }

                    if (!substr($toBeChecked, 1) == '_' && file_exists($relativePathName)) {
                        $markedForRemoval[] = $directory;
                    }

                    if (!is_numeric(substr($toBeChecked, 1)) && file_exists($relativePathName)) {
                        $markedForRemoval[] = $directory;
                    }
                }

                // check if file is associated with existing portal and delete orphans
                if (sizeof($parts) > 5) {
                    $contextId = $parts[5];

                    // exclude the server (99) and 'temp'
                    if ($contextId != '99' and $contextId != 'temp' and $contextId != 'portal') {
                        $qb = $this->connection->createQueryBuilder()
                            ->select('f.*', 'i.context_id as portalId')
                            ->from('files', 'f')
                            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')->setMaxResults(1)
                            ->where('f.deletion_date IS NULL')
                            ->andWhere('f.context_id LIKE ' . $contextId);
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
                    }
                }
            }

            // remove all dirs marked for removal
            foreach ($markedForRemoval as $removal) {
                $filesystem->remove($removal);
            }
        }

        //TODO: Use Finder w. fies() - finder does not find files yet...
        if ($finderFileNames->hasResults()) {
            foreach ($finderFileNames as $finderFileName) {
                $currentName = $finderFileName;
            }
        }

        // check on file names being valid
        if (!empty($scannedFileNames)) {

            if ($finderFileNames->hasResults()) {

                foreach ($scannedFileNames as $scannedFile) {

                    $filesDirParts = explode("/", $scannedFile);
                    $toBeChecked = end($filesDirParts);

                    // if is directory, do not check
                    if (is_dir($scannedFile)) {
                        continue;
                    }

                    // check digit + file extension
                    if (str_contains($toBeChecked, '.') and !str_contains($toBeChecked, '_')) {
                        $extensionParts = explode(".", $toBeChecked);
                        if (is_numeric($extensionParts[0])) {
                            continue;
                        }
                    }

                    // check digit + file extension + file extension contains '_' e.g. '1.jpg_thumbnail'
                    if (str_contains($toBeChecked, '.') and str_contains($toBeChecked, '_')) {
                        $extensionParts = explode(".", $toBeChecked);
                        if (is_numeric($extensionParts[0]) and str_contains(end($extensionParts), '_')) {
                            continue;
                        }
                    }

                    // check cid[roomId]_bginfo|logo|[username]_[filename].[extension]
                    if (str_contains($toBeChecked, '.') and str_contains($toBeChecked, '_')) {
                        //TODO use ending function
                        $extensionParts = explode(".", $toBeChecked);
                        $underscoreParts = explode('_', $extensionParts[0]);

                        // check if third level contains two underscores
                        if (substr_count($extensionParts[0], "_") == 2) {

                            // check if cid + int (e.g. cid12345)
                            if (str_contains($underscoreParts[0], 'cid')) {

                                // check if ID string (without CID) is indeed numeric
                                $idStringWithoutCID = substr($underscoreParts[0], 3);
                                if (is_numeric($idStringWithoutCID)) {

                                    // check if first three chars of ID string is 'CID'
                                    if ($result = substr($underscoreParts[0], 0, 3)) {
                                        continue;
                                    }
                                }
                            }
                        }
                    }

                    // delete if no allowed pattern could be found
                    if (file_exists($scannedFile)) {
                        $filesystem->remove($scannedFile);
                    }

                }
            }
        }

        return true;
    }

    /**
     * @param $dir
     */
    private function listFolderFiles($dir)
    {

        // make instance variable
        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // prevent empty ordered elements
        if (count($ffs) < 1) {
            return $this->folderFiles;
        }

        // recursively deepen path
        foreach ($ffs as $ff) {
            array_push($this->folderFiles, $dir . '/' . $ff);
            if (is_dir($dir . '/' . $ff)) {
                $this->listFolderFiles($dir . '/' . $ff);
            }
        }

        return $this->folderFiles;
    }
}