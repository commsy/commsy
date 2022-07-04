<?php

namespace App\Database;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GeneralCheck implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 999;
    }

    protected function executeSQL(string $sql, SymfonyStyle $io): Result
    {
        if ($io->isVerbose()) {
            $io->note('Executing ' . $sql);
        }

        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery();
    }
}