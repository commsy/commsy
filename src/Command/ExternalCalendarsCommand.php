<?php

namespace App\Command;

use App\Services\CalendarsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExternalCalendarsCommand extends Command
{
    private $calendarsService;

    public function __construct(CalendarsService $calendarsService)
    {
        $this->calendarsService = $calendarsService;

        parent::__construct();
    }

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

        // get calendars
        $calendars = $this->calendarsService->getListExternalCalendars();
        foreach ($calendars as $calendar) {
            // get external calendars
            $output->write('<info>' . $calendar->getTitle() . '</info>');

            if (filter_var($calendar->getExternalUrl(), FILTER_VALIDATE_URL)) {
                // fetch and parse data from external calendars
                $result = $this->calendarsService->importEvents(fopen(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()), 'r'), $calendar, true);
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
