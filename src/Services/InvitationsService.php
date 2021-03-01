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

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(EntityManagerInterface $entityManager, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function invitationsEnabled()
    {
        $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
        $authSourceManager->setContextLimit($this->legacyEnvironment->getCurrentPortalId());
        $authSourceManager->select();

        /** @var \cs_list $authSources */
        $authSources = $authSourceManager->get();
        foreach ($authSources as $authSource) {
            if ($authSource->isCommSyDefault() && $authSource->allowAddAccountInvitation()) {
                return true;
            }
        }

        return false;
    }

    public function existsInvitationForEmailAddress($authSourceItem, $email)
    {
        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('email', $email)
            ->getQuery();
        $invitations = $query->getResult();

        if (count($invitations) > 0) {
            return true;
        }

        return false;
    }

    public function generateInvitationCode($authSourceItem, $contextId, $email)
    {
        $invitationCode = md5(rand() . time() . rand());

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

    public function confirmInvitationCode($authSourceItem, $invitationCode)
    {
        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.hash = :invitationCode')
            ->andWhere('invitations.expirationDate >= :expirationDate')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('invitationCode', $invitationCode)
            ->setParameter('expirationDate', new \DateTime())
            ->getQuery();
        $invitations = $query->getResult();

        if (count($invitations) > 0) {
            return true;
        }

        return false;
    }

    public function redeemInvitation(\cs_auth_source_item $authSourceItem, string $invitationCode)
    {
        $repository = $this->em->getRepository(Invitations::class);
        /** @var Invitations $invitation */
        $invitation = $repository->findOneBy([
            'authSourceId' => $authSourceItem->getItemId(),
            'hash' => $invitationCode,
        ]);

        $this->em->remove($invitation);
        $this->em->flush();
    }

    public function getInvitedEmailAdressesByContextId($authSourceItem, $contextId)
    {
        $result = array();

        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->select()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.contextId = :contextId')
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

    public function removeInvitedEmailAdresses($authSourceItem, $email)
    {
        $repository = $this->em->getRepository(Invitations::class);
        $query = $repository->createQueryBuilder('invitations')
            ->delete()
            ->where('invitations.authSourceId = :authSourceId')
            ->andWhere('invitations.email = :email')
            ->setParameter('authSourceId', $authSourceItem->getItemId())
            ->setParameter('email', $email)
            ->getQuery();
        $query->getResult();
    }
}