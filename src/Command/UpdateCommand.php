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

namespace App\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(name: 'commsy:update', description: 'Run application updates')]
class UpdateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->entityManager->getConnection();

        $io->title('Starting Application Update');

        try {
            $schemaManager = $conn->createSchemaManager();
            if ($schemaManager->tablesExist(['migration_versions'])) {
                $needsUpdate = (int) $conn->fetchOne(<<<'SQL'
                    SELECT COUNT(*) FROM migration_versions WHERE version LIKE 'DoctrineMigrations\\\\%'
                SQL);

                if ($needsUpdate > 0) {
                    $io->section('Synchronizing migration metadata...');
                    $conn->executeStatement(<<<'SQL'
                        UPDATE migration_versions
                        SET version = REPLACE(version, 'DoctrineMigrations\\', 'App\\Migrations\\')
                        WHERE version LIKE 'DoctrineMigrations\\\\%';
                    SQL);
                    $io->success('Namespaces in the database have been updated.');
                } else {
                    $io->success('Migration table update not necessary.');
                }
            }
        } catch (Exception $e) {
            $io->note('Migration table not found or update not necessary: ' . $e->getMessage());
        }

        // 2. Run Doctrine Migrations
        $io->section('Running database migrations...');
        $migrateCommand = $this->getApplication()->find('doctrine:migrations:migrate');
        $migrateInput = new ArrayInput([
            '--no-interaction' => true,
            '--all-or-nothing' => true,
        ]);

        $migrateInput->setInteractive(false);
        if ($migrateCommand->run($migrateInput, $output) !== 0) {
            $io->error('Migrations failed!');
            return Command::FAILURE;
        }

        $io->success('Update completed successfully!');
        return Command::SUCCESS;
    }
}
