<?php

namespace App\Services;

use App\Entity\Invitations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class InvitationsService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    private $serviceContainer;

    public function __construct(EntityManagerInterface $entityManager, Container $container)
    {
        $this->em = $entityManager;
        $this->serviceContainer = $container;
    }

    public function invitationsEnabled () {
        $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();

        $authSourceManager = $legacyEnvironment->getAuthSourceManager();
        $authSourceManager->setContextLimit($legacyEnvironment->getCurrentPortalId());
        $authSourceManager->select();
        $authSourceArray = $authSourceManager->get()->to_array();

        foreach ($authSourceArray as $authSourceItem) {
            if ($authSourceItem->isCommSyDefault()) {
                if ($authSourceItem->allowAddAccountInvitation()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function existsInvitationForEmailAddress ($authSourceItem, $email) {
        $repository = $this->em->getRepository('CommsyBundle:Invitations');
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

    public function redeemInvitation($authSourceItem, $invitationCode, $email){
        $repository = $this->em->getRepository('CommsyBundle:Invitations');
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