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

namespace App\Command;

use App\Services\CalendarsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExternalCalendarsCommand extends Command
{
    protected static $defaultName = 'commsy:cron:externalcalendars';
    protected static $defaultDescription = 'commsy external calendars cron';

    public function __construct(private CalendarsService $calendarsService)
    {
        parent::__construct();
    }

    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Fetching dates from external calendars ...</info>');

        // get calendars
        $calendars = $this->calendarsService->getListExternalCalendars();
        foreach ($calendars as $calendar) {
            // get external calendars
            $output->write('<info>'.$calendar->getTitle().'</info>');

            if (filter_var($calendar->getExternalUrl(), FILTER_VALIDATE_URL)) {
                // fetch and parse data from external calendars
                $result = $this->calendarsService->importEvents(fopen(str_ireplace('webcal://', 'http://', $calendar->getExternalUrl()), 'r'), $calendar, true);
                if (true !== $result) {
                    $output->write('<info>... Error: '.$result.'</info>');
                }
            } else {
                $output->write('<info> ... Error: no valid url</info>');
            }
            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
