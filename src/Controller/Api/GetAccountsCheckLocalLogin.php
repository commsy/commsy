<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Repository\AccountsRepository;
use App\Repository\PortalRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class GetAccountsCheckLocalLogin
{
    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var AccountsRepository
     */
    private AccountsRepository $accountsRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        PortalRepository $portalRepository,
        AccountsRepository $accountsRepository,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->portalRepository = $portalRepository;
        $this->accountsRepository = $accountsRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function __invoke(Account $data): Account
    {
        $portal = $this->portalRepository->findActivePortal($data->getContextId());

        if ($portal) {
            /** @var AuthSourceLocal $localSource */
            $localSource = $portal->getAuthSources()->filter(function (AuthSource $authSource) {
                return $authSource instanceof AuthSourceLocal;
            })->first();

            if ($localSource) {
                $account = $this->accountsRepository->findOneByCredentials(
                    $data->getUsername(),
                    $data->getContextId(),
                    $localSource
                );

                if ($account) {
                    if ($this->passwordEncoder->isPasswordValid($account, $data->getPassword())) {
                        return $account;
                    }
                }
            }
        }

        throw new NotFoundHttpException('Account not found');
    }
}