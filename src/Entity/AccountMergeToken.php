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

namespace App\Entity;

use App\Repository\AccountMergeTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single-use, short-lived token legitimising the merge of an externally
 * authenticated account into another, confirmed via an e-mail link.
 *
 * Only the SHA-256 hash of the token is stored; the raw token lives solely in
 * the confirmation mail. Both account references are FK-bound with
 * ON DELETE CASCADE, so deleting either account (including the merge deleting
 * {@see $fromAccount}) cleans up the token row automatically.
 */
#[ORM\Entity(repositoryClass: AccountMergeTokenRepository::class)]
#[ORM\Table(name: 'account_merge_token')]
#[ORM\UniqueConstraint(name: 'account_merge_token_hash_idx', columns: ['token_hash'])]
class AccountMergeToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'token_hash', type: Types::STRING, length: 64, unique: true)]
    private string $tokenHash;

    /**
     * Account A — merged in and deleted on confirmation.
     */
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'from_account_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Account $fromAccount;

    /**
     * Account N — the surviving account.
     */
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'into_account_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Account $intoAccount;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $tokenHash,
        Account $fromAccount,
        Account $intoAccount,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $createdAt
    ) {
        $this->tokenHash = $tokenHash;
        $this->fromAccount = $fromAccount;
        $this->intoAccount = $intoAccount;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getFromAccount(): Account
    {
        return $this->fromAccount;
    }

    public function getIntoAccount(): Account
    {
        return $this->intoAccount;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt < $now;
    }
}
