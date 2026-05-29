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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Hash.
 */
#[ORM\Entity]
#[ORM\Table(name: 'hash')]
#[ORM\Index(columns: ['rss'], name: 'rss')]
#[ORM\Index(columns: ['ical'], name: 'ical')]
#[ORM\Index(columns: ['merge_token'], name: 'merge_token')]
class Hash
{
    #[ORM\Column(name: 'user_item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $userId;

    #[ORM\Column(name: 'rss', type: Types::STRING, length: 32)]
    private string $rss;

    #[ORM\Column(name: 'ical', type: Types::STRING, length: 32)]
    private string $ical;

    /**
     * Single-use, short-lived token legitimising an account merge initiated by
     * the user owning this row (the surviving account). Null when no merge is pending.
     */
    #[ORM\Column(name: 'merge_token', type: Types::STRING, length: 64, nullable: true)]
    private ?string $mergeToken = null;

    /**
     * Account to be merged in and deleted (the "old" account A).
     */
    #[ORM\Column(name: 'merge_from_account_id', type: Types::INTEGER, nullable: true)]
    private ?int $mergeFromAccountId = null;

    /**
     * Surviving account the content is merged into (the "new" account N).
     */
    #[ORM\Column(name: 'merge_into_account_id', type: Types::INTEGER, nullable: true)]
    private ?int $mergeIntoAccountId = null;

    /**
     * Hard expiry of the merge token; merge requests are rejected after this point.
     */
    #[ORM\Column(name: 'merge_expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $mergeExpiresAt = null;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): Hash
    {
        $this->userId = $userId;
        return $this;
    }

    public function getRss(): string
    {
        return $this->rss;
    }

    public function setRss(string $rss): Hash
    {
        $this->rss = $rss;
        return $this;
    }

    public function getIcal(): string
    {
        return $this->ical;
    }

    public function setIcal(string $ical): Hash
    {
        $this->ical = $ical;
        return $this;
    }

    public function getMergeToken(): ?string
    {
        return $this->mergeToken;
    }

    public function setMergeToken(?string $mergeToken): Hash
    {
        $this->mergeToken = $mergeToken;
        return $this;
    }

    public function getMergeFromAccountId(): ?int
    {
        return $this->mergeFromAccountId;
    }

    public function setMergeFromAccountId(?int $mergeFromAccountId): Hash
    {
        $this->mergeFromAccountId = $mergeFromAccountId;
        return $this;
    }

    public function getMergeIntoAccountId(): ?int
    {
        return $this->mergeIntoAccountId;
    }

    public function setMergeIntoAccountId(?int $mergeIntoAccountId): Hash
    {
        $this->mergeIntoAccountId = $mergeIntoAccountId;
        return $this;
    }

    public function getMergeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->mergeExpiresAt;
    }

    public function setMergeExpiresAt(?\DateTimeImmutable $mergeExpiresAt): Hash
    {
        $this->mergeExpiresAt = $mergeExpiresAt;
        return $this;
    }

    /**
     * Clears all merge-token fields (single-use consumption / cancellation).
     */
    public function clearMerge(): Hash
    {
        $this->mergeToken = null;
        $this->mergeFromAccountId = null;
        $this->mergeIntoAccountId = null;
        $this->mergeExpiresAt = null;
        return $this;
    }
}
