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

use RuntimeException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shows an {@see AuditResult} on the console and writes its findings as CSV.
 */
final class AuditReport
{
    public static function render(SymfonyStyle $io, AuditResult $result): void
    {
        $rows = [];
        foreach ($result->summary() as $kind => $totals) {
            $rows[] = [$kind, FindingKind::from($kind)->description(), $totals['count'], Helper::formatMemory($totals['bytes'])];
        }
        $io->table(['Kind', 'What', 'Count', 'Size'], $rows);

        if ($result->storageSuspicious) {
            $io->warning('So many files are missing that the storage may not be mounted. Missing files are not ended.');
        }
        if ($result->databaseSuspicious) {
            $io->warning('Much of the disk is unknown to the database, or it holds ids the database never handed out: this may be the wrong or an older database. Nothing is moved off the disk.');
        }
    }

    public static function writeCsv(string $path, AuditResult $result): void
    {
        $handle = fopen($path, 'w');
        if (false === $handle) {
            throw new RuntimeException("Cannot write '$path'.");
        }

        fputcsv($handle, ['kind', 'file_id', 'path', 'bytes', 'reason'], escape: '');
        foreach ($result->findings as $finding) {
            fputcsv($handle, [$finding->kind->value, $finding->fileId, $finding->path, $finding->bytes, $finding->reason], escape: '');
        }
        fclose($handle);
    }
}
