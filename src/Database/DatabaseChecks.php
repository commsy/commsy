<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:15
 */

namespace App\Database;


use App\Database\Resolve\ResolutionInterface;
use Exception;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseChecks
{
    /**
     * @var DatabaseCheck[]
     */
    private iterable $checks;

    public function __construct(iterable $databaseChecks)
    {
        $this->checks = $databaseChecks;
    }

    public function runChecks(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $checks = iterator_to_array($this->checks);

        // filter by limit
        $limit = $input->getOption('limit');
        if ($limit) {
            $checks = array_filter($checks, function($check) use ($limit) {
                $className = (new ReflectionClass($check))->getShortName();
                return $className === $limit;
            });
        }

        // sort checks by priority, highest will be executed first
        usort($checks, function(DatabaseCheck $a, DatabaseCheck $b) {
            if ($a->getPriority() == $b->getPriority()) {
                return 0;
            }

            return ($a->getPriority() > $b->getPriority()) ? -1 : 1;
        });

        foreach ($checks as $check) {
            try {
                $class = new ReflectionClass($check);
                $io->section('Running check: ' . $class->getShortName());

                if ($check->resolve($io)) {
                    $io->success('Check resolved problems or nothing to fix');
                } else {
                    $io->warning('Check failed resolving problems, aborting...');
                    break;
                }
            } catch (Exception $e) {
                $io->error($e->getMessage());
            }

        }
    }
}