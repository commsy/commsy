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

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What {@see FileAudit} found, with the two warnings that stop the cleanup
 * from acting on a picture that cannot be right.
 */
#[Exclude]
final readonly class AuditResult
{
    /**
     * @param list<Finding> $findings
     * @param bool          $storageSuspicious  so many files are missing that the storage is
     *                                          more likely not mounted than the files lost
     * @param bool          $databaseSuspicious much of the disk unknown to the database, or ids
     *                                          on disk it never handed out: more likely the
     *                                          wrong or an older database than stray files
     */
    public function __construct(
        public array $findings,
        public bool $storageSuspicious,
        public bool $databaseSuspicious,
    ) {
    }

    /**
     * @return list<Finding>
     */
    public function of(FindingKind $kind): array
    {
        return array_values(array_filter($this->findings, static fn (Finding $finding): bool => $finding->kind === $kind));
    }

    /**
     * Count and size per kind, every kind listed.
     *
     * @return array<string, array{count: int, bytes: int}>
     */
    public function summary(): array
    {
        $summary = [];
        foreach (FindingKind::cases() as $kind) {
            $summary[$kind->value] = ['count' => 0, 'bytes' => 0];
        }
        foreach ($this->findings as $finding) {
            ++$summary[$finding->kind->value]['count'];
            $summary[$finding->kind->value]['bytes'] += $finding->bytes;
        }

        return $summary;
    }
}
