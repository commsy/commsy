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

use App\Files\Cleanup\AuditReport;
use App\Files\Cleanup\FileAudit;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('commsy:files:audit', 'Compares the files table, its links and the files directory; changes nothing')]
class FilesAuditCommand extends Command
{
    public function __construct(private readonly FileAudit $fileAudit)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('csv', null, InputOption::VALUE_REQUIRED, 'Write every finding to this CSV file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->fileAudit->audit();

        AuditReport::render($io, $result);
        if ($csv = $input->getOption('csv')) {
            AuditReport::writeCsv($csv, $result);
            $io->text("Findings written to $csv");
        }

        return Command::SUCCESS;
    }
}
