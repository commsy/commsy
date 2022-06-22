<?php

namespace App\Command;

use App\Cron\CronManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCommand extends Command
{
    use LockableTrait;

    /**
     * @var CronManager
     */
    private CronManager $cronManager;

    public function __construct(
        CronManager $cronManager
    ) {
        $this->cronManager = $cronManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('commsy:cron:main')
            ->setDescription('main commsy cron')
            ->addArgument(
                'contextId',
                InputArgument::OPTIONAL,
                'Context ID (Portal / Server) to be processed in this run'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force run and ignore if already run')
            ->addOption(
                'exclude',
                'ex',
                InputOption::VALUE_REQUIRED,
                'Exclude tasks from execution (separated by ,)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $excludeOption = $input->getOption('exclude');
        $exclude = [];
        if ($excludeOption) {
            $exclude = explode(',', $input->getOption('exclude'));
        }

        $io = new SymfonyStyle($input, $output);
        $this->cronManager->run($io, $exclude, $input->getOption('force'));

        $this->release();

        return 0;
    }
}