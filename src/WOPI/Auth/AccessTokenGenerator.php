<?php

namespace App\WOPI\Auth;

use App\Entity\Account;
use App\Entity\Files;
use DateTimeImmutable;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class AccessTokenGenerator
{
    public function __construct(
        private Security $security,
        private JWTTokenManagerInterface $tokenManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function generateToken(Files $file, string $permission, int $validHours): string
    {
        $account = $this->security->getUser();
        if (!$account instanceof Account) {
            throw new Exception('No valid account found for token generation.');
        }

        $expiration = new DateTimeImmutable("+$validHours hour");

        $user = JWTUser::createFromPayload($account->getUsername(), []);
        return $this->tokenManager->createFromPayload($user, [
            'exp' => $expiration->getTimestamp(),
            'aid' => $account->getId(),
            'fid' => $file->getFilesId(),
            'permission' => $permission,
        ]);
    }
}
