<?php

namespace App\Form\DataTransformer;

use App\Entity\Account;
use App\Utils\UserService;
use cs_user_item;
use Symfony\Component\Form\DataTransformerInterface as DataTransformerInterfaceAlias;

class AccountsToPortalUserIdsTransformer implements DataTransformerInterfaceAlias
{
    /**
     * @var UserService
     */
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Transforms an array of accounts to an array of strings (portal user ids)
     * @param $value
     * @return mixed|void
     */
    public function transform($value)
    {
        if (!is_array($value)) {
            return [];
        }

        $portalUserIds = [];

        foreach ($value as $account) {
            /** @var Account $account */
            /** @var cs_user_item $portalUser */
            $portalUser = $this->userService->getPortalUser($account);

            if ($portalUser) {
                $portalUserIds[$portalUser->getItemID()] = false;
            }
        }

        return $portalUserIds;
    }

    public function reverseTransform($value)
    {
        $i = 5;
        // TODO: Implement reverseTransform() method.
    }
}