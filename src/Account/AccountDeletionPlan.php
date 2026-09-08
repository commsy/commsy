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

namespace App\Account;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Everything an account deletion needs to know, read while its rows are
 * still alive.
 *
 * The membership graph and the private-room lookup both filter on
 * `deletion_date`, so neither can be resolved once stamping has begun.
 * Collecting them into one value first makes that ordering a property of
 * the code rather than something a comment has to keep asking for.
 */
#[Exclude]
final readonly class AccountDeletionPlan
{
    /**
     * @param int[] $membershipItemIds room memberships to end, from the
     *                                 official graph plus the orphan sweep
     * @param int   $countFromGraph    reported in the deletion log
     */
    public function __construct(
        public int $deleterId,
        public array $membershipItemIds,
        public ?int $portalUserItemId,
        public ?int $privateRoomId,
        public int $countFromGraph,
        public int $countFromSweep,
    ) {}
}
