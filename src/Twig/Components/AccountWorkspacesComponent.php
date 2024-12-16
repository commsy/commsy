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

namespace App\Twig\Components;

use App\Entity\Account;
use App\Repository\UserRepository;
use App\Utils\RoomService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class AccountWorkspacesComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public Account $account;

    #[LiveProp(writable: true)]
    public string $filterArchived = 'all';

    #[LiveProp(writable: true)]
    public string $filterType = 'all';

    #[LiveProp(writable: true)]
    public string $filterUserStatus = 'all';

    public function __construct(
        private readonly RoomService $roomService,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
    ) {}

    public function getUsers(): iterable
    {
        return $this->userRepository->findAllByRoomStatus(
            $this->account,
            $this->filterArchived,
            $this->filterType,
            $this->filterUserStatus
        );
    }
}
