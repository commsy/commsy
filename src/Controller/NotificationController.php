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
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The user-facing "Benachrichtigungen" area: a portal-scoped list of the
 * current account's notifications. Notifications are always read scoped to the
 * authenticated account, so the portalId in the path is for URL structure only
 * and never widens what a user can see.
 */
class NotificationController extends AbstractController
{
    private const PAGE_SIZE = 30;

    #[Route(path: '/portal/{portalId}/notifications', name: 'app_notification_list')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Security $security, NotificationRepository $notificationRepository): Response
    {
        /** @var Account $account */
        $account = $security->getUser();

        return $this->render('notification/list.html.twig', [
            'notifications' => $notificationRepository->findForAccountPaginated($account, 1, self::PAGE_SIZE),
            'unreadCount' => $notificationRepository->countUnreadForAccount($account),
        ]);
    }
}
