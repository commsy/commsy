<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:15
 */

namespace CommsyBundle\Database;


use CommsyBundle\Database\Resolve\ResolutionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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

        // sort checks by priority, highest will be executed first
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

                $problems = $check->findProblems($io, $input->getOption('limit'));

                // check is ok
                if (sizeof($problems) === 0) {
                    $io->success('Check finished without any problems.');
                    continue;
                }

                // check found problems
                $io->warning('Check found ' . sizeof($problems) . ' problems!');

                // prepare choice question for resolution
                $helper = $command->getHelper('question');

                $resolutions = ['skip'];
                $resolutionStrategies = $check->getResolutionStrategies();
                foreach ($resolutionStrategies as $resolutionStrategy) {
                    $resolutions[] = $resolutionStrategy->getKey();
                }

                $resolutionQuestion = new ChoiceQuestion('How do you want to resolve the issues?', $resolutions, 0);
                $resolutionQuestion->setErrorMessage('Resolution %s is invalid.');

                $pickedResolution = $helper->ask($input, $output, $resolutionQuestion);

                // handle skipping
                if ($pickedResolution === 'skip') {
                    $io->text('skipping...');
                    continue;
                }

                // resolve with strategy
                $io->text('trying to resolve...');

                $strategies = array_filter($resolutionStrategies, function(ResolutionInterface $strategy) use ($pickedResolution) {
                    return $strategy->getKey() === $pickedResolution;
                });
                if ($strategies[0]->resolve($problems)) {
                    $io->success('Check resolved problems');
                } else {
                    $io->warning('Check failed resolving problems, aborting...');
                    break;
                }
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }

        }
    }
}