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

    /**
     * @var array
     */
    private $allDirs;

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
        $io->text('Inspecting physical files');

        $qb = $this->connection->createQueryBuilder()
            ->select('f.*', 'i.context_id as portalId')
            ->from('files', 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')->setMaxResults(1)
            ->where('f.deletion_date IS NULL');
        $files =     $qb->execute();

        $filesDirectory = $this->parameterBag->get('files_directory');

        // TODO: "files()" for files, "directories()" for directories...
//        $finder = new Finder();
//        $finder->files()
//            ->in($filesDirectory)
//            ->followLinks()
//            ->path('/^\d/');

        //TODO: Only use files with full path structure instead of both files and directories?
        $this->folderFiles = array();
        $this->allDirs = array();
        $scannedFileNames = $this->listFolderFiles($filesDirectory);
        $directories = $this->listNestedDirs($filesDirectory);

        //TODO: remove?
        $scannedFiles = $files = array_diff( scandir($filesDirectory), array(".", "..") );
        $scannedFilesNumbers = preg_grep('/^\d/',$scannedFiles);
        $scannedFilesStrings = preg_grep('/^[a-zA-Z]/',$scannedFiles);

        if (!empty($scannedFilesNumbers)) {
            foreach ($scannedFilesNumbers as $file) {
                $relativePathName = $filesDirectory . "/" . $file;
                $io->text($relativePathName);
            }
        }

        if (!empty($scannedFilesStrings)) {
            foreach ($scannedFilesStrings as $file) {
                $relativePathName = $filesDirectory . "/" . $file;
                $haystack = array('temp','portal');
                $needle = $file;
                if (!in_array($needle, $haystack)) {
                    $this->rrmdir($relativePathName);
                }

                $io->text($relativePathName);
            }
        }

        // check first level: must be numeric, can only be 4 digits long
        // e.g. 99/1234: okay; 99/123: wrong; 99/12345: wrong; 99/somefolder: wrong
        if (!empty($directories)) {
            foreach ($directories as $directory) {

                // only check directories here
                if(!is_dir($directory)) {
                    continue;
                }

                //TODO: can path length be assumed to be stable?
                $parts = explode("/", $directory);
                if (sizeof($parts) > 6) {
                    $toBeChecked = $parts[6];

                    // check numeric
                    if (!is_numeric($toBeChecked)) {
                        $this->rrmdir($directory);
                    }

                    // check length being 4
                    if (strlen($toBeChecked) != 4) {
                        $this->rrmdir($directory);
                    }
                }

                if (sizeof($parts) > 7) {
                    $toBeChecked = $parts[7];

                    if (!str_contains($toBeChecked, '_')) {
                        $this->rrmdir($directory);
                    }

                    if (!substr($toBeChecked, 1) == '_') {
                        $this->rrmdir($directory);
                    }

                    if (!is_numeric(substr($toBeChecked, 1))) {
                        $this->rrmdir($directory);
                    }
                }
            }
        }

        if(!empty($scannedFileNames)) {

            foreach ($scannedFileNames as $scannedFile) {

                    $filesDirParts = explode("/",$scannedFile);
                    $toBeChecked = end($filesDirParts);
                    $isValid = false;

                    // if is directory, do not check
                    if (is_dir($scannedFile)) {
                        $isValid = true;
                        //TODO use continue & remove dir at end at all times, if end is reached
                    }

                    // check digit + file extension
                    if (!$isValid and str_contains($toBeChecked,'.') and !str_contains($toBeChecked,'_')) {
                        $extensionParts = explode(".", $toBeChecked);
                        if (is_numeric($extensionParts[0])) {
                            $isValid = true;
                        }
                    }

                // check digit + file extension + file extension contains '_' e.g. '1.jpg_thumbnail'
                if (!$isValid and str_contains($toBeChecked,'.') and str_contains($toBeChecked,'_')) {
                    $extensionParts = explode(".", $toBeChecked);
                    if (is_numeric($extensionParts[0]) and str_contains(end($extensionParts),'_')) {
                        $isValid = true;
                    }
                }

                    // check cid[roomId]_bginfo|logo|[username]_[filename].[extension]
                    if (!$isValid and str_contains($toBeChecked,'.') and str_contains($toBeChecked,'_')) {
                        //TODO use ending function
                        $extensionParts = explode(".", $toBeChecked);
                        $underscoreParts = explode('_', $extensionParts[0]);

                        // check if third level contains two underscores
                        if (substr_count($extensionParts[0],"_") == 2) {

                            // check if cid + int (e.g. cid12345)
                            if (str_contains($underscoreParts[0],'cid')) {

                                // check if ID string (without CID) is indeed numeric
                                $idStringWithoutCID = substr($underscoreParts[0],3);
                                if (is_numeric($idStringWithoutCID)) {

                                    // check if first three chars of ID string is 'CID'
                                    if ($result = substr($underscoreParts[0], 0, 3)) {
                                        $isValid = true;
                                    }
                                }
                            }
                        }
                    }

                    // delete if no allowed pattern could be found
                    //TODO: always remove in case of 'continue' not being reached
                    if (!$isValid) {
                        $this->rrmdir($scannedFile);
                    }
                }
            }

        return true;
    }

    /**
     * @param $dir
     */
    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }


    /**
     * @param $dir
     */
    private function listFolderFiles($dir){

        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // prevent empty ordered elements
        if (count($ffs) < 1)
            return $this->folderFiles;

        foreach($ffs as $ff){
            array_push($this->folderFiles, $dir . '/' . $ff);
            if(is_dir($dir.'/'.$ff)) $this->listFolderFiles($dir.'/'.$ff);
        }

        return $this->folderFiles;
    }

    private function listNestedDirs($inputDir) {

        $dirs = glob($inputDir . '/*' , GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            array_push($this->allDirs, $dir);
            $this->listNestedDirs($dir);
        }

        return $this->allDirs;
    }

}