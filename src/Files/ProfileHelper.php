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
use Symfony\Component\Finder\Finder;

class ProfileHelper
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/files/temp/user/')]
        private readonly string $uploadDir
    ) {}

    public function getTempProfileImagePath(?Account $account, int $contextId): ?string
    {
        if (!$account) {
            return null;
        }

        $fileDirectory = $this->uploadDir . $account->getId();

        $finder = new Finder();
        $finder
            ->files()
            ->in($fileDirectory)
            ->name("{$this->getProfileImageBaseName($account, $contextId)}*");
        $found = iterator_to_array($finder);
        $firstFound = current($found);
        return !$firstFound ? null : "$fileDirectory/{$firstFound->getFilename()}";
    }

    public function getProfileImageBaseName(Account $account, int $contextId): string
    {
        return "cid{$contextId}_{$account->getUsername()}";
    }
}
