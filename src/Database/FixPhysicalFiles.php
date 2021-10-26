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
        $io->text('Inspecting physical files');

        $qb = $this->connection->createQueryBuilder()
            ->select('f.*', 'i.context_id as portalId')
            ->from('files', 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')->setMaxResults(1)
            ->where('f.deletion_date IS NULL');
        $files = $qb->execute();

        $filesDirectory = $this->parameterBag->get('files_directory');

        $finder = new Finder();
        $finder->files()
            ->in($filesDirectory)
            ->followLinks()
            ->path('/^\d/');

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $io->text($file->getRelativePathname());
            }
        }

        return true;
    }
}