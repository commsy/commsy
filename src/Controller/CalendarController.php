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

use App\Entity\Calendars;
use App\Event\CommsyEditEvent;
use App\Form\Type\CalendarEditType;
use App\Repository\CalendarsRepository;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CalendarController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
#[IsGranted('RUBRIC_DATE')]
class CalendarController extends AbstractController
{
    #[Route(path: '/room/{roomId}/calendar/edit/{calendarId}')]
    #[IsGranted('CALENDARS_EDIT')]
    public function edit(
        Request $request,
        CalendarsRepository $calendarsRepository,
        ManagerRegistry $doctrine,
        CalendarsService $calendarsService,
        TranslatorInterface $translator,
        RoomService $roomService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $calendarId = null
    ): Response {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        if (!$roomItem) {
            $privateRoomManager = $legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);
        }

        if ($calendarId) {
            $calendar = $calendarsRepository->findOneById($calendarId);
        } else {
            $calendar = new Calendars();
            $calendar->setContextId($roomId);
            $calendar->setCreatorId($legacyEnvironment->getCurrentUserId());
            $calendar->setSynctoken(0);
        }

        $editForm = $this->createForm(CalendarEditType::class, $calendar, [
            'editExternalUrl' => ($roomItem->usersCanSetExternalCalendarsUrl() || $legacyEnvironment->getCurrentUser()->isModerator()),
            'confirm-delete' => $translator->trans('confirm-delete', [], 'calendar'),
            'confirm-delete-cancel' => $translator->trans('confirm-delete-cancel', [], 'calendar'),
            'confirm-delete-confirm' => $translator->trans('confirm-delete-confirm', [], 'calendar'),
        ]);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            if ($calendar->getExternalUrl()) {
                $calendar->setExternalUrl(str_ireplace('webcals://', 'https://', (string) $calendar->getExternalUrl()));
                $calendar->setExternalUrl(str_ireplace('webcal://', 'http://', (string) $calendar->getExternalUrl()));
            }

            if ('delete' == $editForm->getClickedButton()->getName()) {
                $calendarsService->removeCalendar($roomService, $calendar);
            } else {
                $doctrine->getManager()->persist($calendar);
            }

            // actually executes the queries (i.e. the INSERT query)
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('app_calendar_edit', [
                'roomId' => $roomId,
            ]);
        }

        $calendars = $calendarsRepository->findBy(['context_id' => $roomId]);

        $eventDispatcher->dispatch(new CommsyEditEvent($calendar), CommsyEditEvent::EDIT);

        return $this->render('calendar/edit.html.twig', [
            'editForm' => $editForm,
            'roomId' => $roomId,
            'calendars' => $calendars,
            'calendarId' => $calendarId,
        ]);
    }
}
