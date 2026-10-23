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

use App\Files\Cleanup\AuditReport;
use App\Files\Cleanup\FileAudit;
use App\Files\Cleanup\FileCleaner;
use App\Files\Cleanup\FileCleanupLock;
use App\Files\Cleanup\FileQuarantine;
use App\Files\Cleanup\FindingKind;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ValueError;

#[AsCommand('commsy:files:clean', 'Cleans up what the file audit finds; a dry run unless --execute is given', help: <<<'TXT'
Rows of the files table are only marked deleted; the nightly final deletion
removes them and their files after the retention. Files without a row are
moved into the quarantine below the files directory and removed for good
after %d days; commsy:files:quarantine lists and restores them.
Files missing on disk are only ended when named: --kind=missing-on-disk.
TXT)]
class FilesCleanCommand extends Command
{
    public function __construct(
        private readonly FileAudit $fileAudit,
        private readonly FileCleaner $fileCleaner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $default = array_values(array_map(
            static fn (FindingKind $kind): string => $kind->value,
            array_filter(FindingKind::cases(), static fn (FindingKind $kind): bool => $kind->cleanedByDefault()),
        ));

        $this
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Act; without it nothing changes')
            ->addOption('kind', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Kinds to act on', $default)
            ->addOption('ignore-warnings', null, InputOption::VALUE_NONE, 'Act even where the audit looks implausible');
        $this->setHelp(sprintf($this->getHelp(), FileQuarantine::RETENTION_DAYS));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $kinds = array_map(FindingKind::from(...), $input->getOption('kind'));
        } catch (ValueError) {
            $io->error('Unknown kind. Known: '.implode(', ', array_map(static fn (FindingKind $kind): string => $kind->value, FindingKind::cases())));

            return Command::INVALID;
        }

        $lock = FileCleanupLock::create();
        if (!$lock->acquire()) {
            $io->error('Another file cleanup is running.');

            return Command::FAILURE;
        }

        try {
            $result = $this->fileAudit->audit();
            AuditReport::render($io, $result);

            if (!$input->getOption('execute')) {
                $io->note('Dry run: nothing changed. Kinds that would be acted on: '.implode(', ', array_map(static fn (FindingKind $kind): string => $kind->value, $kinds)));

                return Command::SUCCESS;
            }

            $outcome = $this->fileCleaner->clean($result, $kinds, (bool) $input->getOption('ignore-warnings'));
            foreach ($outcome['done'] as $kind => $count) {
                $io->text("$kind: $count");
            }
            foreach ($outcome['skipped'] as $kind => $reason) {
                $io->warning("$kind skipped: $reason");
            }
            if (null !== $outcome['run']) {
                $io->text("Moved into quarantine run {$outcome['run']}.");
            }

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
