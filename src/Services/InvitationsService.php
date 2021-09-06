<?php

namespace App\Services;

use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Invitations;
use App\Entity\Portal;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class InvitationsService
{
    /**
     * @var EntityManagerInterface $entityManager
     */
    private EntityManagerInterface $entityManager;

    /**
     * InvitationsService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Portal $portal
     * @return bool
     */
    public function invitationsEnabled(Portal $portal): bool
    {
        $authSources = $portal->getAuthSources();

        /** @var AuthSourceLocal $localSource */
        $localAuthSource = $authSources->filter(function (AuthSource $authSource) {
            return $authSource instanceof AuthSourceLocal;
        })->first();

        return $localAuthSource->getAddAccount() === AuthSource::ADD_ACCOUNT_INVITE;
    }

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param string $email
     * @return bool
     */
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

        if (count($invitations) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Deletes expired invitations
     *
     * @return int
     */
    public function deleteExpiredInvitations(): int
    {
        $repository = $this->entityManager->getRepository(Invitations::class);
        return $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.expirationDate < :expirationDate')
            ->setParameter('expirationDate', new DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param int $contextId
     * @param string $email
     * @return string
     */
    public function generateInvitationCode(AuthSourceLocal $authSourceLocal, int $contextId, string $email): string
    {
        $invitationCode = md5(rand() . time() . rand());

        $invitation = new Invitations();
        $invitation->setEmail($email);
        $invitation->setAuthSourceId($authSourceLocal->getId());
        $invitation->setContextId($contextId);
        $invitation->setHash($invitationCode);
        $invitation->setCreationDate(new DateTime());
        $invitation->setExpirationDate(new DateTime('14 day'));

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return $invitationCode;
    }

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param string $invitationCode
     * @return bool
     */
    public function confirmInvitationCode(AuthSourceLocal $authSourceLocal, string $invitationCode): bool
    {
        $invitations = $this->getInvitations($authSourceLocal, $invitationCode);

        if (count($invitations) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param string $invitationCode
     */
    public function getContextIdByAuthAndCode(AuthSourceLocal $authSourceLocal, string $invitationCode)
    {
        $invitations = $this->getInvitations($authSourceLocal, $invitationCode);

        if (count($invitations) > 0) {
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
            ->setParameter('expirationDate', new DateTime())
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param string $invitationCode
     */
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

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param $contextId
     * @return array
     */
    public function getInvitedEmailAdressesByContextId(AuthSourceLocal $authSourceLocal, $contextId): array
    {
        $result = array();

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

    /**
     * @param AuthSourceLocal $authSourceLocal
     * @param $email
     */
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