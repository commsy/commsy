<?php
namespace CommsyBundle\Migrations;

use Doctrine\ORM\EntityManager;

class FilesMigration
{
    const BATCH_SIZE = 20;

    private $em;
    private $legacyEnvironment;

    public function __construct(EntityManager $em, $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function populateFilePath()
    {
        $filesQuery = $this->em->createQuery('SELECT f FROM CommsyBundle:Files f');
        $this->updateFilePath($filesQuery->iterate());

        $archivedFilesQuery = $this->em->createQuery('SELECT f FROM CommsyBundle:ZzzFiles f');
        $this->updateFilePath($archivedFilesQuery->iterate());
    }

    private function updateFilePath($iterableResult)
    {
        $discManager = $this->legacyEnvironment->getDiscManager();
        $itemManager = $this->legacyEnvironment->getItemManager();

        $i = 0;

        foreach ($iterableResult as $row) {
            $file = $row[0];

            $fileContextId = $file->getContextId();

            $legacyFileContextItem = $itemManager->getItem($fileContextId);

            if ($legacyFileContextItem) {
                $portalId = $legacyFileContextItem->getContextId();

                $fileExtension = substr(strrchr($file->getFilename(), '.'), 1);

                $filePath = $discManager->getFilePath($portalId, $fileContextId);
                $filePath .= $file->getFilesId();
                $filePath .= '.' . $fileExtension;

                $filePath = stristr($filePath, 'files');

                $file->setFilepath($filePath);
            }

            if (($i % FilesMigration::BATCH_SIZE) === 0) {
                $this->em->flush(); // Executes all updates.
                $this->em->clear(); // Detaches all objects from Doctrine
            }
            ++$i;
        }

        $this->em->flush(); // Persist objects that did not make up an entire batch
        $this->em->clear();
    }
}