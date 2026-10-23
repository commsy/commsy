<?php

namespace App\WOPI\Permission;

use App\Entity\Account;
use Symfony\Bundle\SecurityBundle\Security;

readonly class PermissionResolver
{
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * Edit when the account may edit the entry the file is opened from.
     */
    public function resolve(int $itemId): WOPIPermission
    {
        $user = $this->security->getUser();
        if (!$user instanceof Account) {
            return WOPIPermission::VIEW;
        }

        if ($this->security->isGranted('ITEM_EDIT', $itemId)) {
            return WOPIPermission::EDIT;
        }

        return WOPIPermission::VIEW;
    }
}
