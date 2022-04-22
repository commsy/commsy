<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Repository\AccountsRepository;
use App\Repository\AuthSourceRepository;
use App\Repository\PortalRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function __construct(
        PortalRepository $portalRepository,
        AccountsRepository $accountsRepository
    ) {
        $this->portalRepository = $portalRepository;
        $this->accountsRepository = $accountsRepository;
    }

    public function __invoke(Account $data): Account
    {
        var_dump($data);

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
                    return $account;
                }
            }
        }

        throw new NotFoundHttpException('Account not found');
    }
}