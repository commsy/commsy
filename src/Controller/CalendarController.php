<?php

namespace App\Controller;

use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\CalendarEditType;
use App\Entity\Calendars;
use App\Event\CommsyEditEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CalendarController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class CalendarController extends AbstractController
{

    /**
     * @var cs_environment
     */
    protected $legacyEnvironment;

    /**
     * @var CalendarsService
     */
    protected CalendarsService $calendarsService;

    /**
     * @var TranslatorInterface
     */
    protected  TranslatorInterface $translator;

    /**
     * @var RoomService
     */
    protected RoomService $roomService;

    /**
     * @var EventDispatcherInterface
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * CalendarController constructor.
     * @param LegacyEnvironment $legacyEnvironment
     * @param CalendarsService $calendarsService
     * @param TranslatorInterface $translator
     */
    public function __construct(LegacyEnvironment $legacyEnvironment,
                                CalendarsService $calendarsService,
                                TranslatorInterface $translator,
                                RoomService $roomService,
                                EventDispatcherInterface $eventDispatcher)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->calendarsService = $calendarsService;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @Route("/room/{roomId}/calendar/edit/{calendarId}")
     * @Template()
     * @Security("is_granted('CALENDARS_EDIT') and is_granted('RUBRIC_SEE', 'date')")
     * @param Request $request
     * @param int $roomId
     * @param int $calendarId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        int $roomId,
        int $calendarId = null
    ) {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);
        if (!$roomItem) {
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $roomItem = $privateRoomManager->getItem($roomId);
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Calendars');

        if ($calendarId) {
            $calendar = $repository->findOneById($calendarId);
        } else {
            $calendar = new Calendars();
            $calendar->setContextId($roomId);
            $calendar->setCreatorId($this->legacyEnvironment->getCurrentUserId());
            $calendar->setSynctoken(0);
        }


        $editForm = $this->createForm(CalendarEditType::class, $calendar, [
            'editExternalUrl' => ($roomItem->usersCanSetExternalCalendarsUrl() || $this->legacyEnvironment->getCurrentUser()->isModerator()),
            'confirm-delete' => $this->translator->trans('confirm-delete', array(), 'calendar'),
            'confirm-delete-cancel' => $this->translator->trans('confirm-delete-cancel', array(), 'calendar'),
            'confirm-delete-confirm' => $this->translator->trans('confirm-delete-confirm', array(), 'calendar'),
        ]);


        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            if ($calendar->getExternalUrl()) {
                $calendar->setExternalUrl(str_ireplace('webcals://', 'https://', $calendar->getExternalUrl()));
                $calendar->setExternalUrl(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()));
            }

            if ($editForm->getClickedButton()->getName() == 'delete') {
                $this->calendarsService->removeCalendar($this->roomService, $calendar);
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

        $this->eventDispatcher->dispatch(new CommsyEditEvent($calendar), CommsyEditEvent::EDIT );

        return [
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'calendars' => $calendars,
            'calendarId' => $calendarId,
        ];
    }
}
