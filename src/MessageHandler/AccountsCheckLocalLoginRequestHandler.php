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

namespace App\MessageHandler;

use App\Dto\LocalLoginInputRequest;
use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Repository\AccountsRepository;
use App\Repository\PortalRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class AccountsCheckLocalLoginRequestHandler
{
    public function __construct(
        private readonly PortalRepository $portalRepository,
        private readonly AccountsRepository $accountsRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function __invoke(LocalLoginInputRequest $data): Account
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
                    if ($this->passwordHasher->isPasswordValid($account, $data->getPassword())) {
                        return $account;
                    }
                }
            }
        }

        throw new NotFoundHttpException('Account not found');
    }
}
