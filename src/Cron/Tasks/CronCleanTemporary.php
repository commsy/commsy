<?php

namespace App\Cron\Tasks;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class CronCleanTemporary implements CronTaskInterface
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
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
                $dir = $projectDir . '/' . $temporaryFolder;
                $filesystem->remove($dir);
                $filesystem->mkdir($dir, 0750);
            }
        } catch (IOExceptionInterface $exception) {
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