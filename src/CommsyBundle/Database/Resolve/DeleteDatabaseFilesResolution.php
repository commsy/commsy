<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 23.04.18
 * Time: 16:33
 */

namespace CommsyBundle\Database\Resolve;

use Doctrine\ORM\EntityManagerInterface;

class DeleteDatabaseFilesResolution implements ResolutionInterface
{
    private static $BATCH_SIZE = 1000;

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function resolve($problems)
    {
        $filesQb = $this->em->getConnection()->createQueryBuilder()
            ->delete('files');
        $ilfQb = $this->em->getConnection()->createQueryBuilder()
            ->delete('item_link_file');

        foreach ($problems as $num => $problem)
        {
            $fileId = $problem->getObject();
            $filesQb->orWhere("files.files_id = :file$fileId");
            $filesQb->setParameter(":file$fileId", $fileId);

            $fileId = $problem->getObject();
            $ilfQb->orWhere("item_link_file.file_id = :file$fileId");
            $ilfQb->setParameter(":file$fileId", $fileId);

            if (($num % self::$BATCH_SIZE) === 0 ) {
                $filesQb->execute();
                $ilfQb->execute();

                $filesQb = $this->em->getConnection()->createQueryBuilder()
                    ->delete('files');
                $ilfQb = $this->em->getConnection()->createQueryBuilder()
                    ->delete('item_link_file');
            }
        }

        $filesQb->execute();
        $ilfQb->execute();

        return true;
    }

    public function getKey()
    {
        return 'delete';
    }

    public function getDescription()
    {
        return '';
    }
}