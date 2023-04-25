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

namespace App\Database;

use Exception;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseChecks
{
    /**
     * @param DatabaseCheck[] $checks
     */
    public function __construct(private readonly iterable $checks)
    {
    }

    public function runChecks(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $checks = iterator_to_array($this->checks);

        // filter by limit
        $limit = $input->getOption('limit');
        if ($limit) {
            $checks = array_filter($checks, function ($check) use ($limit) {
                $className = (new ReflectionClass($check))->getShortName();

                return $className === $limit;
            });
        }

        // sort checks by priority, highest will be executed first
        usort($checks, fn (DatabaseCheck $a, DatabaseCheck $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($checks as $check) {
            try {
                $class = new ReflectionClass($check);
                $io->section('Running check: '.$class->getShortName());

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
