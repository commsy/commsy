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
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function resolve($problems)
    {
        $qb = $this->em->getConnection()->createQueryBuilder()
            ->delete('files');

        foreach ($problems as $problem)
        {
            $fileId = $problem->getObject();
            $qb->orWhere("files.files_id = :file$fileId");
            $qb->setParameter(":file$fileId", $fileId);
        }
        $qb->execute();

        $qb = $this->em->getConnection()->createQueryBuilder()
            ->delete('item_link_file');

        foreach ($problems as $problem)
        {
            $fileId = $problem->getObject();
            $qb->orWhere("item_link_file.file_id = :file$fileId");
            $qb->setParameter(":file$fileId", $fileId);
        }
        $qb->execute();

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