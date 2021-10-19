<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.04.18
 * Time: 15:55
 */

namespace App\Database;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class FixPhysicalFiles implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getPriority()
    {
        return 100;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $io->text('Inspecting physical files');

//        $qb = $this->em->getConnection()->createQueryBuilder()
//            ->select('f.*', 'i.context_id as portalId')
//            ->from('files', 'f')
//            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')
//            ->where('f.deletion_date IS NULL');

        $finder = new Finder();
        $finder->files()
            ->in('files/')
            ->followLinks()
            ->path('/^\d/');

        foreach ($finder as $file) {
            $io->text($file->getRelativePathname());
        }

        return true;
    }
}