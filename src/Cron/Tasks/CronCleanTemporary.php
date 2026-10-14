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

namespace App\Cron\Tasks;

use DateTimeImmutable;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

/**
 * Removes what has not been touched for {@see self::MAX_AGE_HOURS} from the
 * temporary folders. Younger files may still be waited for, such as an export
 * archive that has yet to be downloaded.
 */
class CronCleanTemporary implements CronTaskInterface
{
    public const MAX_AGE_HOURS = 24;

    /**
     * The task runs about once a day, so something made just after a run is too
     * young for that run and goes with the next: this is how long it can stay.
     */
    public const LATEST_REMOVAL_HOURS = 2 * self::MAX_AGE_HOURS;

    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        foreach (['var/temp', 'files/temp'] as $folder) {
            $dir = $projectDir.'/'.$folder;
            @mkdir($dir, 0750, true);

            // Files and directories alike, deepest first. Sorting reads everything
            // before the first removal, so a directory is judged by its age before
            // its last file goes — removing that file sets its time to now.
            $old = (new Finder())
                ->in($dir)
                ->ignoreUnreadableDirs()
                ->date('< now - '.self::MAX_AGE_HOURS.' hours')
                ->sort(static fn (SplFileInfo $a, SplFileInfo $b): int => strcmp($b->getPathname(), $a->getPathname()));

            foreach ($old as $entry) {
                // rmdir refuses a directory that still holds something, and one
                // that was made a moment ago is not in the list: the archiver
                // makes its folder before it writes the file.
                is_dir($entry->getPathname()) && !is_link($entry->getPathname())
                    ? @rmdir($entry->getPathname())
                    : @unlink($entry->getPathname());
            }
        }
    }

    public function getSummary(): string
    {
        return 'Clean temporary files';
    }
}
