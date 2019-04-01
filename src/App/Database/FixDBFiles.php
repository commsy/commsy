<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 19.04.18
 * Time: 19:09
 */

namespace App\Database;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use App\Database\Resolve\DeleteDatabaseFilesResolution;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FixDBFiles implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 100;
    }

    public function findProblems(SymfonyStyle $io, int $limit)
    {
        $io->text('Inspecting files');

        $qb = $this->em->getConnection()->createQueryBuilder()
            ->select('f.*', 'i.context_id as portalId')
            ->from('files', 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')
            ->where('f.deletion_date IS NULL');

        $files = $qb->execute();
        $discManager = $this->legacyEnvironment->getDiscManager();
        $fileSystem = new Filesystem();

        $problems = [];

        foreach ($files as $file) {
            if ($io->isVerbose()) {
                $io->text('Looking for physical file with id "' . $file['files_id'] . '"');
            }

            $discManager->setPortalID($file['portalId']);
            $discManager->setContextID($file['context_id']);
            $filePath = $discManager->getFilePath() . $file['files_id'] . '.' . $this->getFileExtension($file);

            if (!$fileSystem->exists($filePath)) {
                $io->warning('Missing physical file - "' . $file['files_id'] . '" - " was expected to be found in "' . $filePath);

                $problems[] = new DatabaseProblem($file['files_id']);

                if ($limit > 0 && sizeof($problems) === $limit) {
                    $io->warning('Number of problems found reached limit -> early return. Please rerun the command.');
                    return $problems;
                }
            }
        }

        return $problems;
    }

    public function getResolutionStrategies()
    {
        return [
            new DeleteDatabaseFilesResolution($this->em),
        ];
    }

    private function getFileExtension($file)
    {
        require_once('functions/text_functions.php');
        $filename = cs_utf8_encode(rawurldecode($file['filename']));

        if (!empty($filename)) {
            return cs_strtolower(mb_substr(strrchr($filename, '.'), 1));
        }
    }
}