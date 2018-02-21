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
        $calendarsService = $container->get('commsy.calendars_service');

        // get calendars
        $calendars = $calendarsService->getListExternalCalendars();
        foreach ($calendars as $calendar) {
            // get external calendars
            $output->write('<info>' . $calendar->getTitle() . '</info>');

            if (filter_var($calendar->getExternalUrl(), FILTER_VALIDATE_URL)) {
                // fetch and parse data from external calendars
                $result = $calendarsService->importEvents(fopen(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()), 'r'), $calendar, true);
                if ($result !== true) {
                    $output->write('<info>... Error: ' . $result . '</info>');
                }
            } else {
                $output->write('<info> ... Error: no valid url</info>');
            }
            $output->writeln('');
        }
    }
}
