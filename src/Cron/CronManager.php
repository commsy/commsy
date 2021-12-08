<?php

namespace App\Cron;

use App\Cron\Tasks\CronTaskInterface;
use App\Entity\CronTask;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CronManager
{
    /**
     * @var CronTaskInterface[]
     */
    private iterable $cronTasks;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var string
     */
    private string $projectDir;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LegacyEnvironment $legacyEnvironment
     * @param string $projectDir
     * @param iterable $cronTasks
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        string $projectDir,
        iterable $cronTasks
    ) {
        $this->cronTasks = $cronTasks;
        $this->entityManager = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->projectDir = $projectDir;
    }

    public function run(SymfonyStyle $output, bool $force = false)
    {
        chdir($this->projectDir . '/legacy/');
        $this->legacyEnvironment->setCacheOff();

        $stopwatch = new Stopwatch();

        $cronTaskRepository = $this->entityManager->getRepository(CronTask::class);
        $lastCronRuns = $cronTaskRepository->findAll();

        $output->section('running crons');
        $stopwatch->openSection();

        $cronTasks = iterator_to_array($this->cronTasks);
        usort($cronTasks, [$this, 'sortByPriority']);
        foreach ($cronTasks as $cronTask) {
            /** @var CronTaskInterface $cronTask */
            $stopwatch->start($cronTask->getSummary());

            $cronRun = array_filter($lastCronRuns, function (CronTask $task) use ($cronTask) {
                return $task->getName() === get_class($cronTask);
            });
            $lastRun = !empty($cronRun) ? DateTimeImmutable::createFromMutable(current($cronRun)->getLastRun()) : null;

            $cmp = (new DateTimeImmutable())->sub(new DateInterval('PT23H'));
            if (!$force && $lastRun && $lastRun >= $cmp) {
                $output->note($cronTask->getSummary() . ' - skipped');
            } else {
                try {
                    $output->note($cronTask->getSummary() . ' - running');
                    $cronTask->run($lastRun);

                    $event = $stopwatch->stop($cronTask->getSummary());
                    $output->success($cronTask->getSummary() . ' - ' . $event);

                    $taskEntity = null;
                    if (!empty($cronRun)) {
                        $taskEntity = current($cronRun);
                    } else {
                        $taskEntity = new CronTask();
                        $taskEntity->setName(get_class($cronTask));
                    }
                    $taskEntity->setLastRun(new DateTimeImmutable());
                    $this->entityManager->persist($taskEntity);
                    $this->entityManager->flush();
                } catch (Exception $e) {
                    $output->error($cronTask->getSummary() . ' - ' . $e->getMessage());
                }
            }
        }

        $stopwatch->stopSection('running crons');
    }

    private function sortByPriority(CronTaskInterface $a, CronTaskInterface $b): int
    {
        if ($a->getPriority() === $b->getPriority()) {
            return 0;
        }

        return ($a->getPriority() > $b->getPriority()) ? -1 : 1;
    }
}