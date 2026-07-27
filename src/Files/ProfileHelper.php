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

namespace App\Files;

use App\Entity\Account;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ProfileHelper
{
    public function __construct(
        #[Autowire('%files_directory%/temp/user/')]
        private readonly string $uploadDir
    ) {}

    public function getTempProfileImagePaths(?Account $account, int $contextId): array
    {
        if (!$account) {
            return [];
        }

        $fileDirectory = $this->uploadDir . $account->getId();
        $filesystem = new Filesystem();
        if (!$filesystem->exists($fileDirectory)) {
            return [];
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($fileDirectory)
            ->name("{$this->getProfileImageBaseName($account, $contextId)}*")
            ->sortByModifiedTime()->reverseSorting();
        return iterator_to_array($finder);
    }

    public function getTempProfileImagePath(?Account $account, int $contextId): ?string
    {
        if (!$account) {
            return null;
        }

        $fileDirectory = $this->uploadDir . $account->getId();
        $firstFound = current($this->getTempProfileImagePaths($account, $contextId));
        return !$firstFound ? null : "$fileDirectory/{$firstFound->getFilename()}";
    }

    public function getProfileImageBaseName(Account $account, int $contextId): string
    {
        return "cid{$contextId}_{$account->getUsername()}";
    }

    public function deleteAllTemporaryUserFiles(Account $account, int $contextId): void
    {
        $files = $this->getTempProfileImagePaths($account, $contextId);

        $filesystem = new Filesystem();
        foreach ($files as $file) {
            $filesystem->remove($file->getPathname());
        }
    }
}
