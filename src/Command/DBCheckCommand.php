<?php
namespace App\Command;

use App\Database\DatabaseChecks;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DBCheckCommand extends Command
{
    private DatabaseChecks $databaseChecks;

    public function __construct(DatabaseChecks $databaseChecks)
    {
        parent::__construct();

        $this->databaseChecks = $databaseChecks;
    }

    protected function configure()
    {
        $this
            ->setName('commsy:db:check')
            ->setDescription('Checks the database tables')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit the checks to run'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->databaseChecks->runChecks($input, $output);
    }
}