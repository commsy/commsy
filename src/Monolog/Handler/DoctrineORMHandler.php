<?php

namespace App\Monolog\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Formatter\NormalizerFormatter;

use Doctrine\ORM\EntityManager;

/**
 * Monolog Handler to write messages into the database using
 * Doctrine ORM.
 */
class DoctrineORMHandler extends AbstractProcessingHandler
{
    /**
     * @var EntityManager $em
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager, $level = Logger::DEBUG, $bubble = true)
    {
        $this->em = $entityManager;

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