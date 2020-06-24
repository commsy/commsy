<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-02-07
 * Time: 16:02
 */

namespace App\Controller;


use App\Entity\RoomPrivat;
use App\Security\Authorization\Voter\RootVoter;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HelperController extends AbstractController
{
    /**
     * Redirects the user to the private room specific url for the all rooms overview
     * without the need of knowing it beforehand.
     *
     * @Route("/portal/{portalId}/helper/allrooms")
     * @param LegacyEnvironment $environment
     * @return RedirectResponse
     */
    public function gotoAllRooms(LegacyEnvironment $environment)
    {
        $legacyEnvironment = $environment->getEnvironment();

        $currentUser = $legacyEnvironment->getCurrentUser();
        if ($currentUser) {
            $privateUserItem = $currentUser->getRelatedPrivateRoomUserItem();

            if ($privateUserItem) {
                return $this->redirectToRoute('app_room_listall', [
                    'roomId' => $privateUserItem->getContextID(),
                ]);
            }
        }
    }

    /**
     * @Route("/portal/{context}/enter")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function portalEnter(
        EntityManagerInterface $entityManager,
        string $context = 'server'
    ) {
        $user = $this->getUser();
        if ($user !== null) {

            // Root (who does not own a private room) will be redirected to "all rooms"
            if ($context !== 'server' && $this->isGranted(RootVoter::ROOT)) {
                return $this->redirectToRoute('app_room_listall', [
                    'roomId' => $context,
                ]);
            }

            // The default redirect to the dashboard.
            if (is_numeric($context)) {
                $privateRoom = $entityManager->getRepository(RoomPrivat::class)
                    ->findByContextIdAndUsername($context, $user->getUsername());
                if ($privateRoom !== null) {
                    return $this->redirectToRoute('app_dashboard_overview', [
                        'roomId' => $privateRoom->getItemId(),
                    ]);
                }
            }
        }

        // If we don't get a valid user, redirect to the list of all portals
        return $this->redirectToRoute('app_server_show');
    }
}