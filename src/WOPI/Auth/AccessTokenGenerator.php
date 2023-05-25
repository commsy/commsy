<?php

namespace App\WOPI\Auth;

use App\Entity\Account;
use App\Entity\Files;
use App\WOPI\Permission\WOPIPermission;
use DateTimeImmutable;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class AccessTokenGenerator
{
    public const TOKEN_VALID_NUM_HOURS = 10;

    public function __construct(
        private JWTTokenManagerInterface $tokenManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function generateToken(Account $account, Files $file, WOPIPermission $permission): string
    {
        $validHours = self::TOKEN_VALID_NUM_HOURS;
        $expiration = new DateTimeImmutable("+$validHours hour");

        $user = JWTUser::createFromPayload($account->getUsername(), []);
        return $this->tokenManager->createFromPayload($user, [
            'exp' => $expiration->getTimestamp(),
            'aid' => $account->getId(),
            'fid' => $file->getFilesId(),
            'permission' => $permission->value,
        ]);
    }
}
