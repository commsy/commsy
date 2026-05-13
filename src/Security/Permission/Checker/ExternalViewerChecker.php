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

namespace App\Security\Permission\Checker;

use App\Entity\ExternalViewer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Checks the `external_viewer` table — a per-item allow-list of
 * usernames who get SEE access without holding a room membership.
 * Used by {@see ItemViewChecker} as the fourth branch of cs_item::maySee.
 *
 * The table has a composite primary key on (item_id, user_id), so a
 * direct {@see \Doctrine\ORM\EntityManagerInterface::find()} suffices
 * — no custom query needed.
 */
final readonly class ExternalViewerChecker
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function isViewerOf(int $itemId, string $username): bool
    {
        return $this->entityManager
            ->getRepository(ExternalViewer::class)
            ->find(['itemId' => $itemId, 'userId' => $username]) !== null;
    }
}
