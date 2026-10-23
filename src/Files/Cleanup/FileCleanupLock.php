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

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * The one lock that keeps a cleanup, a restore and a purge of the quarantine
 * from running at the same time on this host.
 */
final class FileCleanupLock
{
    private const string NAME = 'commsy-files-cleanup';

    public static function create(): LockInterface
    {
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();

        return new LockFactory($store)->createLock(self::NAME);
    }
}
