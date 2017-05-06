<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\Invitations;

use Doctrine\ORM\EntityManager;

class InvitationsService
{

    /**
     * @var EntityManager $em
     */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function generateInvitationCode($authSourceItem, $contextId, $email) {
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

    public function confirmInvitationCode($authSourceItem, $invitationCode) {
        $repository = $this->em->getRepository('CommsyBundle:Invitations');
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

    public function getInvitedEmailAdressesByContextId ($authSourceItem, $contextId) {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:Invitations');
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

    public function removeInvitedEmailAdresses ($authSourceItem, $email) {
        $repository = $this->em->getRepository('CommsyBundle:Invitations');
        $query = $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.authSourceId = :authSourceId AND invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('email', $email)
            ->getQuery();
        $query->getResult();
    }
}