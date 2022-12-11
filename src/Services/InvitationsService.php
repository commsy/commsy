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

namespace App\Services;

use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Invitations;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;

class InvitationsService
{
    /**
     * InvitationsService constructor.
     */
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function invitationsEnabled(Portal $portal): bool
    {
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceLocal $localSource */
        $localAuthSource = $authSources->filter(fn (AuthSource $authSource) => $authSource instanceof AuthSourceLocal)->first();

        return AuthSource::ADD_ACCOUNT_INVITE === $localAuthSource->getAddAccount();
    }

    public function existsInvitationForEmailAddress(AuthSourceLocal $authSourceLocal, string $email): bool
    {
        $repository = $this->entityManager->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.email = :email')
            ->setParameter('authSourceId', $authSourceLocal->getId())
            ->setParameter('email', $email)
            ->getQuery();
        $invitations = $query->getResult();

        if ((is_countable($invitations) ? count($invitations) : 0) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Deletes expired invitations.
     */
    public function deleteExpiredInvitations(): int
    {
        $repository = $this->entityManager->getRepository(Invitations::class);

        return $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.expirationDate < :expirationDate')
            ->setParameter('expirationDate', new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function generateInvitationCode(AuthSourceLocal $authSourceLocal, int $contextId, string $email): string
    {
        $invitationCode = md5(random_int(0, mt_getrandmax()).time().random_int(0, mt_getrandmax()));

        $invitation = new Invitations();
        $invitation->setEmail($email);
        $invitation->setAuthSourceId($authSourceLocal->getId());
        $invitation->setContextId($contextId);
        $invitation->setHash($invitationCode);
        $invitation->setCreationDate(new \DateTime());
        $invitation->setExpirationDate(new \DateTime('14 day'));

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return $invitationCode;
    }

    public function confirmInvitationCode(AuthSourceLocal $authSourceLocal, string $invitationCode): bool
    {
        $invitations = $this->getInvitations($authSourceLocal, $invitationCode);

        if ((is_countable($invitations) ? count($invitations) : 0) > 0) {
            return true;
        }

        return false;
    }

    public function getContextIdByAuthAndCode(AuthSourceLocal $authSourceLocal, string $invitationCode)
    {
        $invitations = $this->getInvitations($authSourceLocal, $invitationCode);

        if ((is_countable($invitations) ? count($invitations) : 0) > 0) {
            $invitation = array_pop($invitations);

            return $invitation->getContextId();
        }
    }

    private function getInvitations(AuthSourceLocal $authSourceLocal, string $invitationCode)
    {
        $repository = $this->entityManager->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.hash = :invitationCode')
            ->andWhere('invitations.expirationDate >= :expirationDate')
            ->setParameter('authSourceId', $authSourceLocal->getId())
            ->setParameter('invitationCode', $invitationCode)
            ->setParameter('expirationDate', new \DateTime())
            ->getQuery();

        return $query->getResult();
    }

    public function redeemInvitation(AuthSourceLocal $authSourceLocal, string $invitationCode): void
    {
        $repository = $this->entityManager->getRepository(Invitations::class);
        /** @var Invitations $invitation */
        $invitation = $repository->findOneBy([
            'authSourceId' => $authSourceLocal->getId(),
            'hash' => $invitationCode,
        ]);

        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
    }

    public function getInvitedEmailAdressesByContextId(AuthSourceLocal $authSourceLocal, $contextId): array
    {
        $result = [];

        $repository = $this->entityManager->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.contextId = :contextId')
            ->setParameter('authSourceId', $authSourceLocal->getId())
            ->setParameter('contextId', $contextId)
            ->orderBy('invitations.email', 'ASC')
            ->getQuery();
        $invitations = $query->getResult();

        foreach ($invitations as $invitation) {
            $result[] = $invitation->getEmail();
        }

        return $result;
    }

    public function removeInvitedEmailAdresses(AuthSourceLocal $authSourceLocal, $email): void
    {
        $repository = $this->entityManager->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.email = :email')
            ->setParameter('authSourceId', $authSourceLocal->getId())
            ->setParameter('email', $email)
            ->getQuery();
        $query->getResult();
    }
}
