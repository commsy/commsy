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

declare(strict_types=1);

namespace App\Files\Cleanup;

use App\Files\FilesDirectory;
use DateTimeImmutable;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Files the cleanup takes off the disk are not deleted but moved here, below
 * the files directory, keeping their relative path. Each cleanup run gets a
 * folder of its own named after its time, with a manifest of what it moved
 * and why — written before the move, so nothing ends up in a run without
 * being listed. A run can be moved back as a whole; after
 * {@see self::RETENTION_DAYS} it is removed for good.
 */
readonly class FileQuarantine
{
    public const string DIRECTORY = '_quarantine';
    public const int RETENTION_DAYS = 90;

    private const string MANIFEST = 'manifest.jsonl';
    private const string RUN_NAME = 'Ymd-His';

    public function __construct(
        private FilesDirectory $filesDirectory,
        private LoggerInterface $cleanupLogger,
    ) {
    }

    /**
     * Starts a run: the folder the moves of one cleanup go to.
     */
    public function begin(DateTimeImmutable $now = new DateTimeImmutable()): string
    {
        new Filesystem()->mkdir($this->root(), 0770);

        // mkdir claims a name atomically, so two runs never share one
        $name = $now->format(self::RUN_NAME);
        for ($suffix = 2; !@mkdir($this->runDirectory($name), 0770); ++$suffix) {
            if ($suffix > 1000) {
                throw new RuntimeException('Cannot create a quarantine run.');
            }
            $name = $now->format(self::RUN_NAME).'-'.$suffix;
        }

        return $name;
    }

    /**
     * Moves a file or a directory, given relative to the files directory,
     * into the run and notes it in the run's manifest.
     */
    public function move(string $run, string $relativePath, string $reason): void
    {
        $source = $this->filesDirectory->root().'/'.$relativePath;
        if (null === $this->filesDirectory->relativeOf($source) || str_starts_with($relativePath, self::DIRECTORY.'/')) {
            throw new RuntimeException("Refusing to move '$relativePath': not inside the files directory.");
        }
        if (is_link($source) || !file_exists($source)) {
            throw new RuntimeException("Refusing to move '$relativePath': missing or a link.");
        }

        $this->appendToManifest($run, [
            'path' => $relativePath,
            'raw' => base64_encode($relativePath),
            'reason' => $reason,
            'directory' => is_dir($source),
        ]);

        $target = $this->runDirectory($run).'/'.$relativePath;
        $filesystem = new Filesystem();
        $filesystem->mkdir(\dirname($target), 0770);
        $filesystem->rename($source, $target);
        $this->cleanupLogger->info("Quarantined '$relativePath' in run $run: $reason");
    }

    /**
     * The runs present, oldest first.
     *
     * @return list<array{name: string, entries: int, bytes: int}>
     */
    public function runs(): array
    {
        $root = $this->root();
        if (!is_dir($root)) {
            return [];
        }

        $runs = [];
        foreach (new Finder()->directories()->in($root)->depth(0)->sortByName() as $directory) {
            $name = $directory->getFilename();
            $bytes = 0;
            foreach (new Finder()->files()->in($directory->getPathname())->ignoreDotFiles(false) as $file) {
                if (self::MANIFEST !== $file->getRelativePathname()) {
                    $bytes += $file->getSize();
                }
            }
            try {
                $entries = \count($this->manifest($name));
            } catch (RuntimeException) {
                $entries = -1;
            }
            $runs[] = ['name' => $name, 'entries' => $entries, 'bytes' => $bytes];
        }

        return $runs;
    }

    /**
     * Moves a run back. An entry whose original place is taken again stays
     * in the run and is reported. The run is only removed once nothing but
     * its manifest and empty folders are left in it.
     *
     * @return array{restored: list<string>, conflicts: list<string>}
     */
    public function restore(string $run): array
    {
        $runDirectory = $this->existingRunDirectory($run);
        $filesystem = new Filesystem();
        $restored = [];
        $conflicts = [];

        foreach ($this->manifest($run) as $entry) {
            $path = $entry['path'];
            $target = $this->filesDirectory->root().'/'.$path;
            if (null === $this->filesDirectory->relativeOf($target) || str_starts_with($path, self::DIRECTORY.'/')) {
                $conflicts[] = $path;
                continue;
            }

            $source = $runDirectory.'/'.$path;
            if (!file_exists($source)) {
                continue;
            }
            if (file_exists($target)) {
                $conflicts[] = $path;
                continue;
            }

            $filesystem->mkdir(\dirname($target), 0770);
            $filesystem->rename($source, $target);
            $restored[] = $path;
            $this->cleanupLogger->info("Restored '$path' from run $run");
        }

        $left = new Finder()->files()->in($runDirectory)->ignoreDotFiles(false)->notName(self::MANIFEST);
        if ([] === $conflicts && !$left->hasResults()) {
            $filesystem->remove($runDirectory);
        }

        return ['restored' => $restored, 'conflicts' => $conflicts];
    }

    /**
     * Removes for good every run older than the given number of days.
     *
     * @return list<string> the runs removed
     */
    public function purge(int $days = self::RETENTION_DAYS, DateTimeImmutable $now = new DateTimeImmutable()): array
    {
        $cutoff = $now->modify("-$days days");
        $removed = [];
        foreach ($this->runs() as $run) {
            $startedAt = DateTimeImmutable::createFromFormat(self::RUN_NAME, substr($run['name'], 0, 15));
            if (false === $startedAt || $startedAt >= $cutoff) {
                continue;
            }

            new Filesystem()->remove($this->runDirectory($run['name']));
            $removed[] = $run['name'];
            $this->cleanupLogger->info("Purged quarantine run {$run['name']}");
        }

        return $removed;
    }

    /**
     * @return list<array{path: string, reason: string, directory: bool}>
     */
    public function manifest(string $run): array
    {
        $file = $this->existingRunDirectory($run).'/'.self::MANIFEST;
        if (!is_file($file)) {
            return [];
        }

        $entries = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            try {
                $entry = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new RuntimeException("The manifest of run $run is damaged.");
            }
            $raw = base64_decode((string) ($entry['raw'] ?? ''), true);
            $entries[] = [
                'path' => false === $raw || '' === $raw ? (string) $entry['path'] : $raw,
                'reason' => (string) $entry['reason'],
                'directory' => (bool) $entry['directory'],
            ];
        }

        return $entries;
    }

    private function root(): string
    {
        return $this->filesDirectory->root().'/'.self::DIRECTORY;
    }

    private function runDirectory(string $run): string
    {
        return $this->root().'/'.$run;
    }

    private function existingRunDirectory(string $run): string
    {
        $directory = Path::canonicalize($this->runDirectory($run));
        if (!Path::isBasePath($this->root(), $directory) || $directory === $this->root() || !is_dir($directory)) {
            throw new RuntimeException("There is no quarantine run '$run'.");
        }

        return $directory;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function appendToManifest(string $run, array $entry): void
    {
        // the readable path may lose bytes that are not UTF-8; `raw` keeps them
        $line = json_encode($entry, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
        if (false === @file_put_contents($this->runDirectory($run).'/'.self::MANIFEST, $line, FILE_APPEND | LOCK_EX)) {
            throw new RuntimeException("Cannot write the manifest of run $run.");
        }
    }
}
