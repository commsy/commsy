<?php

namespace App\Lock;

use App\Entity\Files;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

final class FileLockManager
{
    public const LOCK_DURATION_MINUTES = 30;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry
    ) {
    }

    public function isLocked(Files $file): bool
    {
        if (!$file->getLockingId() || !$file->getLockingDate()) {
            return false;
        }

        $compare = (new DateTimeImmutable())->modify('+' . self::LOCK_DURATION_MINUTES . ' minutes');
        return $file->getLockingDate() < $compare;
    }

    public function lock(Files $file, string $lock): void
    {
        $lockingDate = (new DateTimeImmutable())->modify('+' . self::LOCK_DURATION_MINUTES . ' minutes');
        $file->setLockingDate($lockingDate);
        $file->setLockingId($lock);

        $em = $this->managerRegistry->getManager();
        $em->persist($file);
        $em->flush();
    }

    public function unlock(Files $file): void
    {
        $file->setLockingId(null);
        $file->setLockingDate(null);

        $em = $this->managerRegistry->getManager();
        $em->persist($file);
        $em->flush();
    }

    public function renew(Files $file): void
    {
        $lockingDate = (new DateTimeImmutable())->modify('+' . self::LOCK_DURATION_MINUTES . ' minutes');
        $file->setLockingDate($lockingDate);

        $em = $this->managerRegistry->getManager();
        $em->persist($file);
        $em->flush();

    }
}
