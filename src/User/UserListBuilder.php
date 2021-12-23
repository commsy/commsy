<?php

namespace App\User;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use LogicException;

class UserListBuilder
{
    /**
     * @var Account|null
     */
    private ?Account $account = null;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var array
     */
    private array $contextIds;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        UserService $userService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;

        $this->reset();
    }

    public function reset(): self
    {
        $this->contextIds = [];
        return $this;
    }

    public function fromAccount(Account $account): self
    {
        $this->account = $account;
        return $this;
    }

    public function withPortalUser(): self
    {
        if (!$this->account) {
            throw new LogicException("You must provide an account object.");
        }

        if ($this->account->getContextId() !== 99) {
            $this->contextIds[] = $this->account->getContextId();
        }

        return $this;
    }

    public function withProjectRoomUser(): self
    {
        if (!$this->account) {
            throw new LogicException("You must provide an account object.");
        }

        $portalUser = $this->userService->getPortalUser($this->account);

        $projectManager = $this->legacyEnvironment->getProjectManager();
        $projectManager->reset();
        $projectManager->setAllStatusLimit(true);
        $projectRooms = $projectManager->getRelatedProjectListForUser($portalUser, $portalUser->getContextID());

        $this->contextIds = array_merge($this->contextIds, $projectRooms->getIDArray());

        return $this;
    }

    public function withCommunityRoomUser(): self
    {
        if (!$this->account) {
            throw new LogicException("You must provide an account object.");
        }

        $portalUser = $this->userService->getPortalUser($this->account);

        $communityManager = $this->legacyEnvironment->getCommunityManager();
        $communityManager->reset();
        $communityManager->setAllStatusLimit(true);
        $communityRooms = $communityManager->getRelatedCommunityRooms($portalUser, $portalUser->getContextID());

        $this->contextIds = array_merge($this->contextIds, $communityRooms->getIDArray());

        return $this;
    }

    public function withUserRoomUser(): self
    {
        if (!$this->account) {
            throw new LogicException("You must provide an account object.");
        }

        $portalUser = $this->userService->getPortalUser($this->account);

        $userroomManager = $this->legacyEnvironment->getUserRoomManager();
        $userRooms = $userroomManager->getRelatedUserroomListForUser($portalUser);

        $this->contextIds = array_merge($this->contextIds, $userRooms->getIDArray());

        return $this;
    }

    public function withPrivateRoomUser(): self
    {
        if (!$this->account) {
            throw new LogicException("You must provide an account object.");
        }

        $portalUser = $this->userService->getPortalUser($this->account);

        $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
        $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($portalUser, $portalUser->getContextID());

        if ($privateRoom) {
            $this->contextIds[] = $privateRoom->getItemID();
        }

        return $this;
    }

    public function getList(): cs_list
    {
        if (!$this->account) {
            throw new LogicException("You must provide an account object.");
        }

        $this->contextIds = array_unique($this->contextIds);

        // NOTE: we reindex the $roomIds array (so that its array values start from 0) since cs_user_manager->_performQuery()
        //       for some reason requires a _context_array_limit array to start with index 0
        $this->contextIds = array_values($this->contextIds);

        // gather IDs of all related users
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextArrayLimit($this->contextIds);
        $userManager->setUserIDLimit($this->account->getUsername());
        $userManager->setAuthSourceLimit($this->account->getAuthSource()->getId());
        $userManager->select();

        $userList = $userManager->get();
        $this->reset();

        return $userList;
    }
}