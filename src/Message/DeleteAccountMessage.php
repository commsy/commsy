<?php

namespace App\Message;

final readonly class DeleteAccountMessage
{
    public function __construct(
        private int $accountId,
    ) {}

    public function getAccountId(): int
    {
        return $this->accountId;
    }
}
