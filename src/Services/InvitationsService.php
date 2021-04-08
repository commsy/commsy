<?php

namespace App\Services;

use App\Entity\AuthSource;
use App\Entity\Invitations;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;

class InvitationsService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function invitationsEnabled(Portal $portal): bool
    {
        $authSources = $portal->getAuthSources();

        /** @var AuthSource $localAuthSource */
        $localAuthSource = $authSources->filter(function(AuthSource $authSource) {
            return $authSource->getType() === 'local';
        })->first();

        return $localAuthSource->isAddAccount() === AuthSource::ADD_ACCOUNT_INVITE;
    }

    public function existsInvitationForEmailAddress($authSourceItem, $email): bool
    {
        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getId())
            ->setParameter('email', $email)
            ->getQuery();
        $invitations = $query->getResult();

        if (count($invitations) > 0) {
            return true;
        }

        return false;
    }

    public function generateInvitationCode($authSourceItem, $contextId, $email): string
    {
        $invitationCode = md5(rand() . time() . rand());

        $invitation = new Invitations();
        $invitation->setEmail($email);
        $invitation->setAuthSourceId($authSourceItem->getId());
        $invitation->setContextId($contextId);
        $invitation->setHash($invitationCode);
        $invitation->setCreationDate(new \DateTime());
        $invitation->setExpirationDate(new \DateTime('14 day'));

        $this->em->persist($invitation);
        $this->em->flush();

        return $invitationCode;
    }

    public function confirmInvitationCode(AuthSource $authSourceItem, $invitationCode): bool
    {
        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.hash = :invitationCode')
            ->andWhere('invitations.expirationDate >= :expirationDate')
            ->setParameter('authSourceId', $authSourceItem->getId())
            ->setParameter('invitationCode', $invitationCode)
            ->setParameter('expirationDate', new \DateTime())
            ->getQuery();
        $invitations = $query->getResult();

        if (count($invitations) > 0) {
            return true;
        }

        return false;
    }

    public function redeemInvitation(AuthSource $authSourceItem, string $invitationCode): void
    {
        $repository = $this->em->getRepository(Invitations::class);
        /** @var Invitations $invitation */
        $invitation = $repository->findOneBy([
            'authSourceId' => $authSourceItem->getId(),
            'hash' => $invitationCode,
        ]);

        $this->em->remove($invitation);
        $this->em->flush();
    }

    public function getInvitedEmailAdressesByContextId($authSourceItem, $contextId): array
    {
        $result = array();

        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.contextId = :contextId')
            ->setParameter('authSourceId', $authSourceItem->getId())
            ->setParameter('contextId', $contextId)
            ->orderBy('invitations.email', 'ASC')
            ->getQuery();
        $invitations = $query->getResult();

        foreach ($invitations as $invitation) {
            $result[] = $invitation->getEmail();
        }

        return $result;

    }

    public function removeInvitedEmailAdresses($authSourceItem, $email): void
    {
        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getId())
            ->setParameter('email', $email)
            ->getQuery();
        $query->getResult();
    }
}