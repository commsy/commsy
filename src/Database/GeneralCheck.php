<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Database;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GeneralCheck implements DatabaseCheck
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function getPriority(): int
    {
        return 999;
    }

    protected function executeSQL(string $sql, SymfonyStyle $io): Result
    {
        if ($io->isVerbose()) {
            $io->note('Executing '.$sql);
        }

        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery();
    }
}
