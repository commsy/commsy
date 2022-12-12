<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller\Api;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Repository\AccountsRepository;
use App\Repository\PortalRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GetAccountsCheckLocalLogin
{
    public function __construct(
        private PortalRepository $portalRepository,
        private AccountsRepository $accountsRepository,
        private UserPasswordHasherInterface $passwordEncoder
    ) {}

    public function __invoke(Account $data): Account
    {
        $portal = $this->portalRepository->findActivePortal($data->getContextId());

        if ($portal) {
            /** @var AuthSourceLocal $localSource */
            $localSource = $portal->getAuthSources()->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceLocal)->first();

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
