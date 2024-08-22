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
use App\Message\CronTaskRun;
use App\Repository\CronTaskRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CronManager
{
    private cs_environment $legacyEnvironment;

    private string $projectDir;

    /**
     * @param CronTaskInterface[] $cronTasks
     */
    public function __construct(
        readonly private EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly CronTaskRepository $cronTaskRepository,
        #[AutowireIterator('app.cron_task')]
        private readonly iterable $cronTasks,
        ParameterBagInterface $parameterBag,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->projectDir = $parameterBag->get('kernel.project_dir');
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(SymfonyStyle $output, array $exclude, bool $force = false): void
    {
        chdir($this->projectDir.'/legacy/');
        $this->legacyEnvironment->setCacheOff();

        $cronTaskRepository = $this->entityManager->getRepository(CronTask::class);
        $lastCronRuns = $cronTaskRepository->findAll();

        $output->section('running crons');

        $cronTasks = iterator_to_array($this->cronTasks);
        foreach ($cronTasks as $cronTask) {
            /* @var CronTaskInterface $cronTask */

            try {
                $cronTaskRef = new ReflectionClass($cronTask);
                if (in_array($cronTaskRef->getShortName(), $exclude)) {
                    $output->note($cronTask->getSummary().' - skipped by exclusion');
                    continue;
                }
            } catch (ReflectionException) {
            }

            $cronRun = array_filter($lastCronRuns, fn (CronTask $task) => $task->getName() === $cronTask::class);
            $lastRun = !empty($cronRun) ? DateTimeImmutable::createFromMutable(current($cronRun)->getLastRun()) : null;

            $cmp = (new DateTimeImmutable())->sub(new DateInterval('PT23H'));
            if (!$force && $lastRun && $lastRun >= $cmp) {
                $output->note($cronTask->getSummary().' - skipped');
            } else {
                try {
                    $output->note($cronTask->getSummary().' - dispatching');
                    $this->messageBus->dispatch(new CronTaskRun($cronTask::class));
                } catch (ExceptionInterface $e) {
                    $output->error($cronTask->getSummary().' - '.$e->getMessage());
                }
            }
        }
    }

    public function execute(string $className): void
    {
        $cron = $this->getCronByClassName($className);

        // Get the last time this cron was run
        $allCronRuns = $this->cronTaskRepository->findAll();
        $cronRun = array_filter($allCronRuns, fn (CronTask $task) => $task->getName() === $className);
        $lastRun = !empty($cronRun) ? DateTimeImmutable::createFromMutable(current($cronRun)->getLastRun()) : null;

        $cron?->run($lastRun);
    }

    public function updateLastRun(string $className): void
    {
        // Get the last time this cron was run
        $allCronRuns = $this->cronTaskRepository->findAll();
        $cronRun = array_filter($allCronRuns, fn (CronTask $task) => $task->getName() === $className);

        if (!empty($cronRun)) {
            $taskEntity = current($cronRun);
        } else {
            $taskEntity = new CronTask();
            $taskEntity->setName($className);
        }
        $taskEntity->setLastRun(new DateTimeImmutable());
        $this->entityManager->persist($taskEntity);
        $this->entityManager->flush();
    }

    private function getCronByClassName(string $className): ?CronTaskInterface
    {
        $cronCollection = new ArrayCollection(iterator_to_array($this->cronTasks));
        $cron = $cronCollection->findFirst(fn (int $key, CronTaskInterface $cronTask) => $cronTask::class === $className);

        return $cron instanceof CronTaskInterface ? $cron : null;
    }
}
