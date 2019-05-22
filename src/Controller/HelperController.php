<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-02-07
 * Time: 16:02
 */

namespace App\Controller;


use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HelperController extends Controller
{
    /**
     * Redirects the user to the private room specific url for the all rooms overview
     * without the need of knowing it beforehand.
     *
     * @Route("/portal/{portalId}/helper/allrooms")
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
}