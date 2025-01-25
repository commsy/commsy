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

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;

final readonly class GlobalTestState
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke()
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('UPDATE accounts SET password_md5 = NULL, password = :password WHERE id = 1', [
            'password' => '$2a$12$B66lysmWqS4bOd2gypsHT.tif.UANr4sERFiRfteQKHKfU4I0AMli',
        ]);
    }
}
