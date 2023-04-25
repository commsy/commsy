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

use App\Cron\CronManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('commsy:cron:main', 'main commsy cron')]
class CronCommand extends Command
{
    use LockableTrait;

    public function __construct(
        private readonly CronManager $cronManager
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('contextId', InputArgument::OPTIONAL, 'Context ID (Portal / Server) to be processed in this run')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force run and ignore if already run')
            ->addOption(
                'exclude',
                'ex',
                InputOption::VALUE_REQUIRED,
                'Exclude tasks from execution (separated by ,)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }

        $excludeOption = $input->getOption('exclude');
        $exclude = [];
        if ($excludeOption) {
            $exclude = explode(',', (string) $input->getOption('exclude'));
        }

        $io = new SymfonyStyle($input, $output);
        $this->cronManager->run($io, $exclude, $input->getOption('force'));

        $this->release();

        return Command::SUCCESS;
    }
}
