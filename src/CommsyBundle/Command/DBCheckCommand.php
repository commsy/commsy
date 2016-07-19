<?php
namespace CommsyBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;

class DBCheckCommand extends Command
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('commsy:db:check')
            ->setDescription('Checks the database tables')
            ->addOption(
                'fix',
                null,
                InputOption::VALUE_NONE,
                'Try to fix found errors'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getHelper('formatter');

        
        $output->writeln('<info>foo</info>');
    }
}