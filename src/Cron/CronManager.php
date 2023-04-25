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

namespace App\Cron;

use App\Cron\Tasks\CronTaskInterface;
use App\Entity\CronTask;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CronManager
{
    private readonly cs_environment $legacyEnvironment;

    /**
     * @param CronTaskInterface[] $cronTasks
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        private readonly string $projectDir,
        private readonly iterable $cronTasks
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(SymfonyStyle $output, array $exclude, bool $force = false)
    {
        chdir($this->projectDir.'/legacy/');
        $this->legacyEnvironment->setCacheOff();

        $stopwatch = new Stopwatch();

        $cronTaskRepository = $this->entityManager->getRepository(CronTask::class);
        $lastCronRuns = $cronTaskRepository->findAll();

        $output->section('running crons');
        $stopwatch->openSection();

        $cronTasks = iterator_to_array($this->cronTasks);
        usort($cronTasks, $this->sortByPriority(...));
        foreach ($cronTasks as $cronTask) {
            try {
                $cronTaskRef = new ReflectionClass($cronTask);
                if (in_array($cronTaskRef->getShortName(), $exclude)) {
                    $output->note($cronTask->getSummary().' - skipped by exclusion');
                    continue;
                }
            } catch (ReflectionException $e) {
            }

            /* @var CronTaskInterface $cronTask */
            $stopwatch->start($cronTask->getSummary());

            $cronRun = array_filter($lastCronRuns, fn (CronTask $task) => $task->getName() === $cronTask::class);
            $lastRun = !empty($cronRun) ? DateTimeImmutable::createFromMutable(current($cronRun)->getLastRun()) : null;

            $cmp = (new DateTimeImmutable())->sub(new DateInterval('PT23H'));
            if (!$force && $lastRun && $lastRun >= $cmp) {
                $output->note($cronTask->getSummary().' - skipped');
            } else {
                try {
                    $output->note($cronTask->getSummary().' - running');
                    $cronTask->run($lastRun);

                    $event = $stopwatch->stop($cronTask->getSummary());
                    $output->success($cronTask->getSummary().' - '.$event);

                    $taskEntity = null;
                    if (!empty($cronRun)) {
                        $taskEntity = current($cronRun);
                    } else {
                        $taskEntity = new CronTask();
                        $taskEntity->setName($cronTask::class);
                    }
                    $taskEntity->setLastRun(new DateTimeImmutable());
                    $this->entityManager->persist($taskEntity);
                    $this->entityManager->flush();
                } catch (Exception $e) {
                    $output->error($cronTask->getSummary().' - '.$e->getMessage());
                }
            }
        }

        $stopwatch->stopSection('running crons');
    }

    private function sortByPriority(CronTaskInterface $a, CronTaskInterface $b): int
    {
        return $b->getPriority() <=> $a->getPriority();
    }
}
