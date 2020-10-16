<?php

namespace App\Services;

use App\Entity\AuthSource;
use App\Entity\Invitations;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

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

        $localAuthSourceExtras = $localAuthSource->getExtras();
        $configValue = ($localAuthSourceExtras['CONFIGURATION']['ADD_ACCOUNT_INVITATION']) ?? 0;

        return $configValue === 1;
    }

    public function existsInvitationForEmailAddress($authSourceItem, $email): bool
    {
        $repository = $this->em->getRepository('App:Invitations');
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId AND invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('email', $email)
            ->getQuery();
        $invitations = $query->getResult();

        if (sizeof($invitations) > 0) {
            return true;
        }

        return false;
    }

    public function generateInvitationCode($authSourceItem, $contextId, $email): string
    {
        $invitationCode = md5(rand().time().rand());

        $invitation = new Invitations();
        $invitation->setEmail($email);
        $invitation->setAuthSourceId($authSourceItem->getItemId());
        $invitation->setContextId($contextId);
        $invitation->setHash($invitationCode);
        $invitation->setCreationDate(new \DateTime());
        $invitation->setExpirationDate(new \DateTime('14 day'));

        $this->em->persist($invitation);
        $this->em->flush();

        return $invitationCode;
    }

    public function confirmInvitationCode($authSourceItem, $invitationCode): bool
    {
        $repository = $this->em->getRepository('App:Invitations');
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId AND invitations.hash = :invitationCode AND invitations.expirationDate >= :expirationDate AND invitations.redeemed = :redeemed')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('invitationCode', $invitationCode)
            ->setParameter('expirationDate', new \DateTime())
            ->setParameter('redeemed', false)
            ->getQuery();
        $invitations = $query->getResult();

        if (sizeof($invitations) > 0) {
            return true;
        }

        return false;
    }

    public function redeemInvitation($authSourceItem, $invitationCode, $email): void
    {
        $repository = $this->em->getRepository('App:Invitations');
        $query = $repository->createQueryBuilder('invitations')
            ->update()
            ->set('invitations.redeemed', true)
            ->where('invitations.authSourceId = :authSourceId AND (invitations.hash = :hash OR invitations.email = :email)')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('hash', $invitationCode)
            ->setParameter('email', $email)
            ->getQuery();
        $query->getResult();
    }

    public function getInvitedEmailAdressesByContextId($authSourceItem, $contextId): array
    {
        $result = array();

        $repository = $this->em->getRepository('App:Invitations');
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId AND invitations.contextId = :contextId')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
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
        $repository = $this->em->getRepository('App:Invitations');
        $query = $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.authSourceId = :authSourceId AND invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('email', $email)
            ->getQuery();
        $query->getResult();
    }
}