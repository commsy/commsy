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

use App\Database\DatabaseChecks;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('commsy:db:check', 'Checks the database tables')]
class DBCheckCommand extends Command
{
    public function __construct(private readonly DatabaseChecks $databaseChecks)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the checks to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->databaseChecks->runChecks($input, $output);

        return Command::SUCCESS;
    }
}
