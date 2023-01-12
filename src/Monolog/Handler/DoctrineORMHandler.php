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

namespace App\Monolog\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;

/**
 * Monolog Handler to write messages into the database using
 * Doctrine ORM.
 */
class DoctrineORMHandler extends AbstractProcessingHandler
{
    public function __construct(private EntityManagerInterface $em, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->pushProcessor(new WebProcessor());
    }

    protected function write(array $record): void
    {
        $record = $record['formatted'];

        dump($record);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter();
    }
}
