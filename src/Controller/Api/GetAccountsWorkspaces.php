<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Repository\RoomRepository;

class GetAccountsWorkspaces
{
    /**
     * @var RoomRepository
     */
    private RoomRepository $roomRepository;

    public function __construct(
        RoomRepository $roomRepository
    ) {
        $this->roomRepository = $roomRepository;
    }

    public function __invoke(Account $data): array
    {
        return $this->roomRepository->getActiveRoomsByAccount($data);
    }
}