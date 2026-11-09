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
use App\Services\ExternalCalendarFeed;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;

#[AsCommand('commsy:cron:externalcalendars', 'commsy external calendars cron')]
class ExternalCalendarsCommand extends Command
{
    public function __construct(
        private readonly CalendarsService $calendarsService,
        private readonly ExternalCalendarFeed $externalCalendarFeed,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
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

            try {
                $feed = $this->externalCalendarFeed->fetch((string) $calendar->getExternalUrl());
            } catch (InvalidArgumentException) {
                $output->writeln('<info> ... Error: no valid url</info>');
                continue;
            } catch (HttpClientException $exception) {
                $output->writeln('<info> ... Error: '.$exception->getMessage().'</info>');
                continue;
            }

            // parse the data of the external calendar
            $result = $this->calendarsService->importEvents($feed, $calendar, true);
            if (true !== $result) {
                $output->write('<info>... Error: '.$result.'</info>');
            }
            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
