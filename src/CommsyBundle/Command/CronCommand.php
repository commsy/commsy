<?php
namespace CommsyBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CronCommand extends Command
{
    protected $kernelRootDir;
    protected $legacyEnvironment;

    public function __construct($kernelRootDir, LegacyEnvironment $legacyEnvironment)
    {
        $this->kernelRootDir = $kernelRootDir;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        parent::__construct();
    }

    protected function configure()
    {

        $this
            ->setName('commsy:cron:main')
            ->setDescription('main commsy cron')
            ->addArgument(
                'cid',
                InputArgument::OPTIONAL,
                'Which portal do you want to use?'
            )
            // ->addOption(
            //     'yell',
            //     null,
            //     InputOption::VALUE_NONE,
            //     'If set, the task will yell in uppercase letters'
            // )
        ;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cid = $input->getArgument('cid');
        if ($cid) {
            $kernelRootDir = $this->kernelRootDir;
            $legacyEnvironment = $this->legacyEnvironment;
            // include cron.php
            chdir($kernelRootDir.'/../legacy/');
            include_once($this->kernelRootDir.'/../web/cron_new.php');
        } else {
            $output->writeln('Please set context id');
        }

        
    }
}