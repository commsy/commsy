<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:53
 */

namespace CommSy\Command;


use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use CommSy\Migration as Migration;

class MovePrivateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('commsy:move-private')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * TODO: assessment?
         * TODO: external_viewer?
         * TODO: noticed
         * TODO: reader
         */

        $dsn = 'mysql:dbname=schh;host=commsy8_db';
        $user = 'commsy';
        $password = 'commsy';

        $connection = new \PDO($dsn, $user, $password);

        Migration\MigrationFactory::registerMigration(Migration\IgnoreStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\TagStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\LinkItemStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\MaterialStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\LabelStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\DiscussionStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\DiscussionArticleStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\DateStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\SectionStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\AnnotationStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\TodoStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\AnnouncementStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\StepStrategy::class);
        Migration\MigrationFactory::registerMigration(Migration\PortfolioStrategy::class);

        $verbosityLevelMap = [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
        ];

        $logger = new ConsoleLogger($output, $verbosityLevelMap);

        $this->processPrivateRooms($connection, $logger);
    }

    private function processPrivateRooms($connection, LoggerInterface $logger)
    {
        $logger->info('Collecting private rooms of 5640232');

        $newPortalPrivateRoomsSQL = '
            SELECT
            p.*,
            u.item_id AS userItemId,
            u.user_id
            FROM
            room_privat AS p
            INNER JOIN
            user AS u
            ON
            p.item_id = u.context_id
            WHERE
            p.context_id = :contextId AND
            p.deletion_date IS NULL AND u.user_id = "movemove"
        ';

        $stmt = $connection->prepare($newPortalPrivateRoomsSQL);
        $stmt->execute([ ':contextId' => 5640232 ]);
        $newPortalPrivateRooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $logger->info('Found ' . sizeof($newPortalPrivateRooms) . ' private rooms');

        foreach ($newPortalPrivateRooms as $newPortalPrivateRoom) {
            $logger->info('Processing private room on new portal ' . $newPortalPrivateRoom['item_id']);
            $logger->info('Looking for related private room on old portal');

            $oldPortalPrivateRoomsSQL = '
                SELECT
                p.*,
                u.item_id AS userItemId
                FROM
                room_privat AS p
                INNER JOIN
                user AS u
                ON
                p.item_id = u.context_id
                WHERE
                p.context_id = :contextId AND
                p.deletion_date IS NULL AND
                u.user_id = :userId AND
                u.auth_source = :authSource
            ';

            $stmt = $connection->prepare($oldPortalPrivateRoomsSQL);
            $stmt->execute([
                ':contextId' => 276082,
                ':userId' =>  $newPortalPrivateRoom['user_id'],
                ':authSource' => 509944,
            ]);
            $oldPortalPrivateRooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $numOldPortalPrivateRooms = sizeof($oldPortalPrivateRooms);
            if ($numOldPortalPrivateRooms == 1) {
                $logger->info('private room found');
                $this->processPrivateRoom($connection, $oldPortalPrivateRooms[0], $newPortalPrivateRoom, $logger);
            } else {
                if ($numOldPortalPrivateRooms == 0) {
                    $logger->info('none found');
                } else {
                    $logger->error('multiple found');
                }
            }
        }
    }

    private function processPrivateRoom($connection, $oldPortalPrivateRoom, array $newPortalPrivateRoom, LoggerInterface $logger)
    {
        $logger->info('Looking for content in private room ' . $oldPortalPrivateRoom['item_id']);

        $itemsSQL = '
            SELECT
            i.*
            FROM
            items AS i
            LEFT JOIN
            portfolio AS p
            ON
            i.item_id = p.item_id
            WHERE
            (
              i.context_id = :contextId OR
              (
                i.context_id = :oldPortalId AND
                i.type = "portfolio" AND 
                p.creator_id = :oldPrivateRoomUserId
              )
            ) AND
            i.deletion_date IS NULL
        ';

        $stmt = $connection->prepare($itemsSQL);
        $stmt->execute([
            'contextId' => $oldPortalPrivateRoom['item_id'],
            'oldPortalId' => 276082,
            'oldPrivateRoomUserId' => $oldPortalPrivateRoom['userItemId'],
        ]);
        $oldItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!$oldItems) {
            $logger->info('nothing found - skipping');
        } else {
            $logger->info('found ' . sizeof($oldItems) . ' items');

            foreach ($oldItems as $oldItem) {
                $migration = Migration\MigrationFactory::findMigration($oldItem['type'], $logger);
                if (!$migration->ignore($connection, $oldItem, $logger)) {
                    $logger->info('Migrating item ' . $oldItem['item_id'] . ' of type ' . $oldItem['type']);
                    $migration->migrate($connection, $oldItem, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger);
                } else {
                    $logger->info('Item with id ' . $oldItem['item_id'] . ' of type ' . $oldItem['type'] . ' is ignored - skipping');
                }
            }
        }

        echo "\n";
    }
}