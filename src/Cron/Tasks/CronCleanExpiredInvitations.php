<?php

namespace App\Cron\Tasks;

use App\Services\InvitationsService;
use DateTimeImmutable;

class CronCleanExpiredInvitations implements CronTaskInterface
{
    /**
     * @var InvitationsService
     */
    private InvitationsService $invitationsService;

    public function __construct(InvitationsService $invitationsService)
    {
        $this->invitationsService = $invitationsService;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        $this->invitationsService->deleteExpiredInvitations();
    }

    public function getSummary(): string
    {
        return 'Delete expired invitations';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}