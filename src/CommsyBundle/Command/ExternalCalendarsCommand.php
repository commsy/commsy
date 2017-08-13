<?php

namespace CommsyBundle\Command;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        // get calendars
        $calendars = $calendarsService->getListCalendars($context->getItemId());
        foreach ($calendars as $calendar) {
            // get external calendars
            if ($calendar->getExternalUrl()) {
                $output->writeln('<info>... ' . $calendar->getTitle() . '</info>');

                // delete old entries from database
                $entityManagerDates = $container->get('doctrine.orm.entity_manager');
                $repositoryDates = $entityManagerDates->getRepository('CommsyBundle:Dates');
                $oldDateItems = $repositoryDates->createQueryBuilder('dates')
                    ->select()
                    ->where('dates.calendarId = :calendarId')
                    ->setParameter('calendarId', $calendar->getId())
                    ->getQuery()
                    ->getResult();

                $removeIds = array();
                foreach ($oldDateItems as $oldDateItem) {
                    $removeIds[] = $oldDateItem->getItemId();
                    $entityManagerDates->remove($oldDateItem);
                }
                $entityManagerDates->flush();


                $entityManagerItems = $container->get('doctrine.orm.entity_manager');
                $repositoryItems = $entityManagerItems->getRepository('CommsyBundle:Items');
                $repositoryItems->createQueryBuilder('items')
                    ->delete()
                    ->where("items.itemId IN(:removeIds)")
                    ->setParameter('removeIds', $removeIds)
                    ->getQuery()
                    ->getResult();

                // fetch and parse data from external calendars
                $calendarsService->importEvents(fopen(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()), 'r'), $calendar, true);
            }
        }
    }
}