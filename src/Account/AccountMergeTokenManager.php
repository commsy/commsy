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

namespace App\Account;

use App\Entity\Account;
use App\Entity\AccountMergeToken;
use App\Repository\AccountMergeTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Issues and validates single-use, short-lived account-merge tokens. The raw
 * token is returned only on creation (to be mailed to the old account A); only
 * its SHA-256 hash is persisted, so a database/backup leak cannot be used to
 * confirm a merge.
 */
class AccountMergeTokenManager
{
    /**
     * Time-to-live of a merge token. Deliberately short-lived: it legitimises a
     * destructive merge, so it must not stay valid for long.
     */
    public const TTL = 'PT1H';

    public function __construct(
        private readonly AccountMergeTokenRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Creates a token for merging $fromAccount (A, deleted) into $intoAccount
     * (N, surviving) and returns the RAW token to embed in the confirmation mail.
     */
    public function create(Account $fromAccount, Account $intoAccount): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $now = new \DateTimeImmutable();

        // Associate managed references so persisting the token never treats the
        // (possibly detached / proxy) caller-supplied accounts as new entities.
        $this->repository->save(new AccountMergeToken(
            self::hashToken($rawToken),
            $this->entityManager->getReference(Account::class, $fromAccount->getId()),
            $this->entityManager->getReference(Account::class, $intoAccount->getId()),
            $now->add(new \DateInterval(self::TTL)),
            $now,
        ));

        return $rawToken;
    }

    /**
     * Resolves a raw token to its (unexpired) record, or null when unknown or
     * expired. Expired records are removed on access.
     */
    public function findValid(string $rawToken): ?AccountMergeToken
    {
        if ('' === $rawToken) {
            return null;
        }

        $token = $this->repository->findOneByTokenHash(self::hashToken($rawToken));
        if (null === $token) {
            return null;
        }

        if ($token->isExpired(new \DateTimeImmutable())) {
            $this->repository->remove($token);

            return null;
        }

        return $token;
    }

    /**
     * Single-use consumption: removes the token record.
     */
    public function consume(AccountMergeToken $token): void
    {
        $this->repository->remove($token);
    }

    private static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
