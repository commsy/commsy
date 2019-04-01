<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class SetupDevCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('commsy:setup:dev')
            ->setDescription('Runs all tasks for preparing the development environment')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrationCommand = $this->getApplication()->find('doctrine:migrations:migrate');
        $migrationArguments = [
            'command' => 'doctrine:migrations:migrate',
        ];
        $migrationInput = new ArrayInput($migrationArguments);
        $migrationCommand->run($migrationInput, $output);

        $elasticCommand = $this->getApplication()->find('fos:elastica:populate');
        $elasticArguments = [
            'command' => 'fos:elastica:populate',
        ];
        $elasticInput = new ArrayInput($elasticArguments);
        $elasticCommand->run($elasticInput, $output);

        // run the cache command last, because it will change some class definitions
        // commands running after this are likely to break
        $cacheCommand = $this->getApplication()->find('cache:clear');
        $cacheArguments = [
            'command' => 'cache:clear',
        ];
        $cacheInput = new ArrayInput($cacheArguments);
        $cacheCommand->run($cacheInput, $output);
    }
}