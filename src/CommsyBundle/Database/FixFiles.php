<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 19.04.18
 * Time: 19:09
 */

namespace CommsyBundle\Database;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FixFiles implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $fixes = [];

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 202;
    }

    public function check(SymfonyStyle $io)
    {
        $io->text('Inspecting files');

        $qb = $this->em->getConnection()->createQueryBuilder()
            ->select('f.*', 'i.context_id as portalId')
            ->from('files', 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')
            ->where('f.deletion_date IS NOT NULL');

        $files = $qb->execute();
        $discManager = $this->legacyEnvironment->getDiscManager();
        $fileSystem = new Filesystem();

        foreach ($files as $file) {
            $io->text('Looking for physical file for id "' . $file['files_id'] . '"');

            $discManager->setPortalID($file['portalId']);
            $discManager->setContextID($file['context_id']);
            $filePath = $discManager->getFilePath() . $file['files_id'] . '.' . $this->getFileExtension($file);

            if (!$fileSystem->exists($filePath)) {
                $io->warning('Missing physical file - "' . $file['files_id'] . '" - " was expected to be found in "' . $filePath);

                $this->fixes[] = [
                    $file['files_id']
                ];
            }
        }

        return sizeof($this->fixes) === 0;
    }

    public function resolve(SymfonyStyle $io)
    {

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