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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class CronCleanTemporary implements CronTaskInterface
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $temporaryFolders = [
            'var/temp',
            'files/temp',
        ];

        $filesystem = new Filesystem();

        $projectDir = $this->parameterBag->get('kernel.project_dir');

        try {
            foreach ($temporaryFolders as $temporaryFolder) {
                $dir = $projectDir.'/'.$temporaryFolder;
                $filesystem->remove($dir);
                $filesystem->mkdir($dir, 0750);
            }
        } catch (IOExceptionInterface) {
        }
    }

    public function getSummary(): string
    {
        return 'Clean temporary files';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}
