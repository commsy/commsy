<?php

namespace App\Security\Voter;

use App\Entity\Files;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class WOPIVoter extends Voter
{
    public const VIEW = 'WOPI_VIEW';
    public const EDIT = 'WOPI_EDIT';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly JWTTokenManagerInterface $tokenManager
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Files;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof JWTUser) {
            return false;
        }

        /** @var Files $file */
        $file = $subject;

        return match ($attribute) {
            self::VIEW => $this->canAccess($file, ['view', 'edit']),
            self::EDIT => $this->canAccess($file, ['edit']),
            default => throw new LogicException('This code should not be reached!')
        };
    }

    private function canAccess(Files $file, array $permissions): bool
    {
        $token = $this->tokenManager->decode($this->tokenStorage->getToken());

        return $token['fid'] === $file->getFilesId() &&
            in_array($token['permission'], $permissions);
    }
}
