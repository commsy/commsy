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

namespace App\Command;

use App\Files\Cleanup\FileCleanupLock;
use App\Files\Cleanup\FileQuarantine;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('commsy:files:quarantine', 'Lists, restores or purges what the file cleanup moved into the quarantine')]
class FilesQuarantineCommand extends Command
{
    public function __construct(private readonly FileQuarantine $quarantine)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'list, show, restore or purge', 'list')
            ->addArgument('run', InputArgument::OPTIONAL, 'The run to show or restore')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Purge runs older than this', FileQuarantine::RETENTION_DAYS)
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Restore or purge; without it nothing changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $run = (string) $input->getArgument('run');

        try {
            return match ($input->getArgument('action')) {
                'list' => $this->list($io),
                'show', 'restore' => '' === $run ? $this->missingRun($io) : $this->show($io, $run, 'restore' === $input->getArgument('action') && $input->getOption('execute')),
                'purge' => $this->purge($io, (int) $input->getOption('days'), (bool) $input->getOption('execute')),
                default => $this->unknownAction($io),
            };
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function list(SymfonyStyle $io): int
    {
        $io->table(['Run', 'Entries', 'Size'], array_map(
            static fn (array $run): array => [$run['name'], $run['entries'], Helper::formatMemory($run['bytes'])],
            $this->quarantine->runs(),
        ));

        return Command::SUCCESS;
    }

    private function show(SymfonyStyle $io, string $run, bool $restore): int
    {
        if (!$restore) {
            $io->table(['Path', 'Reason'], array_map(
                static fn (array $entry): array => [$entry['path'], $entry['reason']],
                $this->quarantine->manifest($run),
            ));

            return Command::SUCCESS;
        }

        $lock = FileCleanupLock::create();
        if (!$lock->acquire()) {
            $io->error('Another file cleanup is running.');

            return Command::FAILURE;
        }

        try {
            $outcome = $this->quarantine->restore($run);
        } finally {
            $lock->release();
        }

        $io->text(\count($outcome['restored']).' restored.');
        if ([] !== $outcome['conflicts']) {
            $io->warning("Taken again, left in the run:\n".implode("\n", $outcome['conflicts']));
        }

        return Command::SUCCESS;
    }

    private function purge(SymfonyStyle $io, int $days, bool $execute): int
    {
        if (!$execute) {
            $io->note("Dry run: runs older than $days days would be removed for good.");
            $this->list($io);

            return Command::SUCCESS;
        }

        $lock = FileCleanupLock::create();
        if (!$lock->acquire()) {
            $io->error('Another file cleanup is running.');

            return Command::FAILURE;
        }

        try {
            $removed = $this->quarantine->purge($days);
        } finally {
            $lock->release();
        }
        $io->text(\count($removed).' runs removed: '.implode(', ', $removed));

        return Command::SUCCESS;
    }

    private function missingRun(SymfonyStyle $io): int
    {
        $io->error('Name the run.');

        return Command::INVALID;
    }

    private function unknownAction(SymfonyStyle $io): int
    {
        $io->error('Unknown action. Use list, show, restore or purge.');

        return Command::INVALID;
    }
}
