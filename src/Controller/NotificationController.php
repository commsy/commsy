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
use App\Entity\Notification;
use App\Notification\NotificationLinkResolver;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function Symfony\Component\Clock\now;

/**
 * The user-facing "Benachrichtigungen" area: a portal-scoped list of the
 * current account's notifications, plus per-item open and mark-all-read.
 * Every action is scoped to the authenticated account, so the portalId in the
 * path is for URL structure only and never widens what a user can reach.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    private const PAGE_SIZE = 30;

    public function __construct(
        private readonly Security $security,
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    #[Route(path: '/portal/{portalId}/notifications', name: 'app_notification_list')]
    public function list(): Response
    {
        $account = $this->account();

        return $this->render('notification/list.html.twig', [
            'notifications' => $this->notificationRepository->findForAccountPaginated($account, 1, self::PAGE_SIZE),
            'unreadCount' => $this->notificationRepository->countUnreadForAccount($account),
        ]);
    }

    /**
     * Click-through: marks the notification read and redirects to its entry
     * (or back to the list when the entry can no longer be resolved).
     */
    #[Route(path: '/portal/{portalId}/notifications/{id}', name: 'app_notification_open', requirements: ['id' => '\d+'])]
    public function open(int $portalId, int $id, NotificationLinkResolver $linkResolver): Response
    {
        $notification = $this->ownedNotification($id);

        $notification->markRead(now());
        $this->notificationRepository->save($notification);

        return $this->redirect(
            $linkResolver->resolve($notification)
            ?? $this->generateUrl('app_notification_list', ['portalId' => $portalId])
        );
    }

    #[Route(path: '/portal/{portalId}/notifications/read-all', name: 'app_notification_read_all', methods: ['POST'])]
    public function readAll(int $portalId, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('notification_read_all', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->notificationRepository->markAllReadForAccount($this->account(), now());

        return $this->redirectToRoute('app_notification_list', ['portalId' => $portalId]);
    }

    private function account(): Account
    {
        /** @var Account $account */
        $account = $this->security->getUser();

        return $account;
    }

    private function ownedNotification(int $id): Notification
    {
        $notification = $this->notificationRepository->find($id);
        if ($notification === null || $notification->getRecipient()->getId() !== $this->account()->getId()) {
            throw $this->createNotFoundException('Notification not found.');
        }

        return $notification;
    }
}
