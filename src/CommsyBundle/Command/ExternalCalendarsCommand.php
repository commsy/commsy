<?php

namespace CommsyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Sabre\VObject;

class ExternalCalendarsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('commsy:cron:externalcalendars')
            ->setDescription('commsy external calendars cron')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Fetching dates from external calendars ...</info>');
        $container = $this->getContainer();
        $legacyEnvironment = $container->get('commsy_legacy.environment')->getEnvironment();
        $serverItem = $legacyEnvironment->getServerItem();
        $portalIds = $serverItem->getPortalIDArray();
        foreach ($portalIds as $portalId) {
            $legacyEnvironment->setCurrentPortalID($portalId);
            $rooms = $legacyEnvironment->getCurrentPortalItem()->getRoomList()->to_array();
            foreach ($rooms as $room) {
                $this->getExternalCalendarsForContext($room, $output);
            }
            $privateRooms = $legacyEnvironment->getCurrentPortalItem()->getPrivateRoomList()->to_array();
            foreach ($privateRooms as $privateRoom) {
                $this->getExternalCalendarsForContext($privateRoom, $output);
            }
        }
    }

    private function getExternalCalendarsForContext ($context, OutputInterface $output) {
        $container = $this->getContainer();
        $calendarsService = $container->get('commsy.calendars_service');
        $dateService = $container->get('commsy_legacy.date_service');

        // get calendars
        $calendars = $calendarsService->getListCalendars($context->getItemId());
        foreach ($calendars as $calendar) {
            // get external calendars
            if ($calendar->getExternalUrl()) {
                $output->writeln('<info>... ' . $calendar->getTitle() . '</info>');

                // delete old entries from database
                $entityManager = $container->get('doctrine.orm.entity_manager');
                $repository = $entityManager->getRepository('CommsyBundle:Dates');
                $repository->createQueryBuilder('dates')
                    ->delete()
                    ->where('dates.calendarId = :calendarId')
                    ->setParameter('calendarId', $calendar->getId())
                    ->getQuery()
                    ->getResult();

                // fetch and parse data from external calendars
                $externalCalendar = VObject\Reader::read(
                    fopen(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()),'r')
                );

                // insert new data into database
                foreach ($externalCalendar->VEVENT as $event) {
                    $title = '';
                    if ($event->SUMMARY) {
                        $title = $event->SUMMARY->getValue();
                    }

                    $start = '';
                    $startDatetime = '';
                    if ($event->DTSTART) {
                        $start = $event->DTSTART->getValue();
                        $startDatetime = $event->DTSTART->getDateTime();
                    }

                    $end = '';
                    $endDatetime = '';
                    if ($event->DTEND) {
                        $end = $event->DTEND->getValue();
                        $endDatetime = $event->DTEND->getDateTime();
                    }

                    $location = '';
                    if ($event->LOCATION) {
                        $location = $event->LOCATION->getValue();
                    }

                    $attendee = '';
                    if ($event->ATTENDEE) {
                        $attendeeArray = array();
                        foreach ($event->ATTENDEE as $tempAttendee) {
                            $attendeeArray[] = $event->ATTENDEE->getValue();
                        }
                        $attendee = implode(', ', $attendeeArray);
                    }

                    $date = $dateService->getNewDate();
                    $date->setContextId($context->getItemId());
                    $date->setTitle($title);
                    $date->setDateTime_start($start);
                    $date->setStartingDay($startDatetime->format('d-m-Y'));
                    $date->setStartingTime($startDatetime->format('H:i:s'));
                    $date->setDateTime_end($end);
                    $date->setEndingDay($endDatetime->format('d-m-Y'));
                    $date->setEndingTime($endDatetime->format('H:i:s'));
                    $date->setCalendarId($calendar->getId());
                    $date->setPlace($location);
                    $date->setDescription($attendee);
                    $date->setCreatorId(98);
                    $date->setModifierId(98);
                    $date->save();
                }
            }
        }
    }
}