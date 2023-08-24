<?php

namespace App\WOPI\REST;

use App\Entity\Files;
use App\Utils\FileService;
use App\WOPI\Permission\WOPIPermission;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class WOPICheckFileInfo
{
    public function __construct(
        private Security $security,
        private FileService $fileService,
        private TokenStorageInterface $tokenStorage,
        private JWTTokenManagerInterface $tokenManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function generateResponse(Files $file): CheckFileInfoResponse
    {
        $account = $this->security->getUser();
        if (!$account instanceof JWTUser) {
            throw new Exception('No valid account found.');
        }

        $token = $this->tokenManager->decode($this->tokenStorage->getToken());

        $sha1 = sha1_file($this->fileService->makeAbsolute($file));
        $writeable = $token['permission'] === WOPIPermission::EDIT->value;

        return (new CheckFileInfoResponse())
            ->setBaseFileName($file->getFilename())
            ->setSize($file->getSize())
            ->setOwnerId($file->getCreatorId())
            ->setSize($file->getSize())
            ->setUserId($token['aid'])
            ->setVersion($sha1)
            ->setUserFriendlyName($account->getUsername())
            ->setReadOnly(!$writeable)
            ->setUserCanWrite($writeable)
            ->setSupportsLocks(true);
    }
}
