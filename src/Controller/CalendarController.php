<?php

namespace App\Controller;

use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\CalendarEditType;
use App\Entity\Calendars;
use App\Event\CommsyEditEvent;

/**
 * Class CalendarController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class CalendarController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/calendar/edit/{calendarId}")
     * @Template()
     * @Security("is_granted('CALENDARS_EDIT') and is_granted('RUBRIC_SEE', 'date')")
     * @param Request $request
     * @param CalendarsService $calendarsService
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int $calendarId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        CalendarsService $calendarsService,
        LegacyEnvironment $environment,
        RoomService $roomService,
        int $roomId,
        int $calendarId = null
    ) {
        $legacyEnvironment = $environment->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        if (!$roomItem) {
            $privateRoomManager = $legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');

        if ($calendarId) {
            $calendar = $repository->findOneById($calendarId);
        } else {
            $calendar = new Calendars();
            $calendar->setContextId($roomId);
            $calendar->setCreatorId($legacyEnvironment->getCurrentUserId());
            $calendar->setSynctoken(0);
        }

        $translator = $this->get('translator');

        $editForm = $this->createForm(CalendarEditType::class, $calendar, [
            'editExternalUrl' => ($roomItem->usersCanSetExternalCalendarsUrl() || $legacyEnvironment->getCurrentUser()->isModerator()),
            'confirm-delete' => $translator->trans('confirm-delete', array(), 'calendar'),
            'confirm-delete-cancel' => $translator->trans('confirm-delete-cancel', array(), 'calendar'),
            'confirm-delete-confirm' => $translator->trans('confirm-delete-confirm', array(), 'calendar'),
        ]);


        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            if ($calendar->getExternalUrl()) {
                $calendar->setExternalUrl(str_ireplace('webcals://', 'https://', $calendar->getExternalUrl()));
                $calendar->setExternalUrl(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()));
            }

            if ($editForm->getClickedButton()->getName() == 'delete') {
                $calendarsService->removeCalendar($roomService, $calendar);
            } else {
                $em->persist($calendar);
            }

            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->redirectToRoute('app_calendar_edit', [
                'roomId' => $roomId,
            ]);
        }

        $calendars = $repository->findBy(array('context_id' => $roomId));

        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch('commsy.edit', new CommsyEditEvent(null));

        return [
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'calendars' => $calendars,
            'calendarId' => $calendarId,
        ];
    }
}
