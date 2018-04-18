<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:15
 */

namespace CommsyBundle\Database;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseChecks
{
    /**
     * @var DatabaseCheck[]
     */
    private $checks;

    public function __construct()
    {
        $this->checks = [];
    }

    public function addCheck(DatabaseCheck $check)
    {
        $this->checks[] = $check;
    }

    public function runChecks(Command $command, InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        usort($this->checks, function(DatabaseCheck $a, DatabaseCheck $b) {
            if ($a->getPriority() == $b->getPriority()) {
                return 0;
            }

            return ($a->getPriority() > $b->getPriority()) ? -1 : 1;
        });

        foreach ($this->checks as $check) {
            try {
                $class = new \ReflectionClass($check);
                $io->section('Running check: ' . $class->getShortName());

                if ($check->check($io)) {
                    $io->success('Check finished without any problems');
                } else {
                    $io->warning('Check found some problems');

                    $helper = $command->getHelper('question');
                    $fixQuestion = new ConfirmationQuestion('Do you want to auto-resolve these issues? ', false);

                    if ($helper->ask($input, $output, $fixQuestion)) {
                        $io->text('trying to resolve...');
                        if ($check->resolve($io)) {
                            $io->success('Check resolved problems');
                        } else {
                            $io->warning('Check failed resolving problems, aborting...');
                            break;
                        }
                    } else {
                        $io->text('skipping...');
                    }
                }
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }

        }
    }
}