<?php

namespace App\WOPI\Permission;

use App\Entity\Account;
use App\Entity\Files;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionResolver
{
    public function __construct(
        private Security $security
    ) {
    }

    public function resolve(Files $file): WOPIPermission
    {
        $user = $this->security->getUser();
        if (!$user instanceof Account) {
            return WOPIPermission::VIEW;
        }

        $itemId = $file->getItemLink()->getItemId();
        if ($this->security->isGranted('ITEM_EDIT', $itemId)) {
            return WOPIPermission::EDIT;
        }

        return WOPIPermission::VIEW;
    }
}
