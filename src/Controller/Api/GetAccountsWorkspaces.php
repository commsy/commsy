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

namespace App\Controller\Api;

use App\Entity\Account;
use App\Repository\RoomRepository;

class GetAccountsWorkspaces
{
    public function __construct(private readonly RoomRepository $roomRepository)
    {
    }

    public function __invoke(Account $data): array
    {
        return $this->roomRepository->getActiveRoomsByAccount($data);
    }
}
