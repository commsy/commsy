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

namespace App\Lock;

use App\Entity\Account;
use App\Entity\Lock;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class LockManager
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private Security $security,
        private TokenGeneratorInterface $tokenGenerator,
        private EntityManagerInterface $entityManager,
        private ItemService $itemService,
        LegacyEnvironment $environment
    ) {
        $this->legacyEnvironment = $environment->getEnvironment();
    }

    public function isLockValid(int $itemId, string $token): bool
    {
        $lockRepository = $this->entityManager->getRepository(Lock::class);

        $user = $this->security->getUser();
        if ($user instanceof Account) {
            /** @var ?Lock $lock */
            $lock = $lockRepository->findOneBy(['itemId' => $itemId, 'token' => $token, 'account' => $user]);
            if (!$lock) {
                return false;
            }
        }

        return true;
    }

    public function userCanLock(int $itemId): bool
    {
        $user = $this->security->getUser();
        if ($user instanceof Account) {
            $lockRepository = $this->entityManager->getRepository(Lock::class);

            /** @var ?Lock $lock */
            $lock = $lockRepository->findOneBy(['itemId' => $itemId]);
            if (!$lock || $this->isLockExpired($lock->getLockDate())) {
                return true;
            }

            return $lock->getAccount() === $user;
        }

        return false;
    }

    public function getToken(int $itemId): ?string
    {
        $lockRepository = $this->entityManager->getRepository(Lock::class);

        /** @var ?Lock $lock */
        $lock = $lockRepository->findOneBy(['itemId' => $itemId]);

        return $lock?->getToken();
    }

    public function lockEntry(int $itemId): void
    {
        $lockRepository = $this->entityManager->getRepository(Lock::class);

        /** @var ?Lock $lock */
        $lock = $lockRepository->findOneBy(['itemId' => $itemId]);
        if ($lock && $this->isLockExpired($lock->getLockDate())) {
            $this->unlockEntry($itemId);
            $lock = null;
        }

        if (!$lock) {
            $user = $this->security->getUser();
            if ($user instanceof Account) {
                $lock = new Lock();
                $lock->setItemId($itemId);
                $lock->setAccount($user);
                $lock->setToken($this->tokenGenerator->generateToken());

                $this->entityManager->persist($lock);
                $this->entityManager->flush();
            }
        }
    }

    public function unlockEntry(int $itemId): void
    {
        $lockRepository = $this->entityManager->getRepository(Lock::class);
        $lock = $lockRepository->findOneBy(['itemId' => $itemId]);

        if ($lock) {
            $this->entityManager->remove($lock);
            $this->entityManager->flush();
        }
    }

    public function getItemIdForLock(int $itemId): int
    {
        $item = $this->itemService->getTypedItem($itemId);

        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            return $item->getLinkedItemID();
        }

        return $itemId;
    }

    public function supportsLocking(int $itemId): bool
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $baseItem = $itemManager->getItem($itemId);

        if ($baseItem->isDraft()) {
            return false;
        }

        return in_array($baseItem->getItemType(), [
            CS_MATERIAL_TYPE, CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE,
            CS_GROUP_TYPE, CS_TODO_TYPE, CS_TOPIC_TYPE,
            CS_SECTION_TYPE, CS_STEP_TYPE
        ]);
    }

    public function isLockExpired(DateTimeInterface $lockDate): bool
    {
        $compare = new DateTime();
        $compare->modify('-20 minutes');

        return $compare >= $lockDate;
    }
}
