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

namespace App\Controller;

use App\Entity\Account;
use App\Entity\RoomPrivat;
use App\Security\Authorization\Voter\RootVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HelperController extends AbstractController
{
    /**
     * @throws NoResultException|NonUniqueResultException
     */
    #[Route(path: '/portal/{context}/enter')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function portalEnter(
        EntityManagerInterface $entityManager,
        string $context = 'server'
    ): RedirectResponse {
        /** @var Account $account */
        $account = $this->getUser();
        if (null === $account) {
            throw new \LogicException('There must be a valid user at this point');
        }

        // Root (who does not own a private room) will be redirected to "all rooms"
        if ('server' !== $context && $this->isGranted(RootVoter::ROOT)) {
            return $this->redirectToRoute('app_room_listall', [
                'roomId' => $context,
            ]);
        }

        // If $context is a number or string representing a number
        if (is_numeric($context)) {
            $privateRoom = $entityManager->getRepository(RoomPrivat::class)
                ->findOneByPortalIdAndAccount($context, $account);

            // The default redirect to the dashboard.
            if ($privateRoom) {
                return $this->redirectToRoute('app_dashboard_overview', [
                    'roomId' => $privateRoom->getItemId(),
                ]);
            }
        }

        // If we don't get a valid user, redirect to the list of all portals
        return $this->redirectToRoute('app_server_show');
    }
}
