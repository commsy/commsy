<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Form\Type\CalendarEditType;
use CommsyBundle\Entity\Calendars;

use CommsyBundle\Event\CommsyEditEvent;

class CalendarController extends Controller
{
    /**
     * @Route("/room/{roomId}/calendar/edit/{calendarId}")
     * @Template()
     * @Security("is_granted('CALENDARS_EDIT')")
     */
    public function editAction($roomId, $calendarId = null, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Calendars');

        if ($calendarId) {
            $calendar = $repository->findOneById($calendarId);
        } else {
            $calendar = new Calendars();
            $calendar->setContextId($roomId);
            $calendar->setCreatorId($legacyEnvironment->getCurrentUserId());
        }

        $editForm = $this->createForm(CalendarEditType::class, $calendar, [
            'editExternalUrl' => ($roomItem->usersCanSetExternalCalendarsUrl() || $legacyEnvironment->getCurrentUser()->isModerator()),
        ]);


        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            $em->persist($calendar);

            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->redirectToRoute('commsy_calendar_edit', [
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
