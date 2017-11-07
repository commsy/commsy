<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 07.11.17
 * Time: 13:25
 */

namespace CommSy\Command;


use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class FixPortfolioCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('commsy:fix-portfolio')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dsn = 'mysql:dbname=schh;host=commsy8_db';
        $user = 'commsy';
        $password = 'commsy';

        $connection = new \PDO($dsn, $user, $password);

        $verbosityLevelMap = [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
        ];

        $logger = new ConsoleLogger($output, $verbosityLevelMap);

        $portfolios = $this->findPortfolios($connection, $logger);
        $this->processPortfolios($portfolios, $connection, $logger);

    }

    private function findPortfolios(\PDO $connection, LoggerInterface $logger)
    {
        $logger->info('Collecting all portfolios');

        $portfolioSQL = '
            SELECT
            p.*
            FROM
            portfolio AS p
            WHERE
            p.deletion_date IS NULL
        ';

        $stmt = $connection->prepare($portfolioSQL);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function isPortfolioInOldPortal($creatorId, \PDO $connection, LoggerInterface $logger)
    {
        $checkSQL = '
            SELECT
            rp.*
            FROM
            user AS u
            INNER JOIN
            room_privat AS rp
            ON
            u.context_id = rp.item_id
            WHERE
            u.deletion_date IS NULL AND
            rp.deletion_date IS NULL AND
            rp.context_id = :oldPortalId AND
            u.item_id = :creatorId
        ';

        $stmt = $connection->prepare($checkSQL);
        $stmt->execute([
            ':oldPortalId' => 276082,
            ':creatorId' => $creatorId,
        ]);

        $privateRooms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return sizeof($privateRooms) > 0;
    }

    private function processPortfolios($portfolios, \PDO $connection, LoggerInterface $logger)
    {
        $logger->info('found ' . sizeof($portfolios) . ' portfolios');

        // check if creator belongs to old portal
        $logger->info('Filtering portfolios with creator in old portal context');
        foreach ($portfolios as $index => $portfolio) {
            if (!$this->isPortfolioInOldPortal($portfolio['creator_id'], $connection, $logger)) {
                unset($portfolios[$index]);
            }
        }
        $logger->info(sizeof($portfolios) . ' matched');

        // check for creator in new portal
        $logger->info('Filtering portfolios with creator in new portal context');
        foreach ($portfolios as $index => $portfolio) {
            $creatorId = $portfolio['creator_id'];

            $newPrivateRoomUserId = $this->findNewPrivateRoomUserId($connection, $creatorId);
            if (!$newPrivateRoomUserId) {
                unset($portfolios[$index]);
            }
        }
        $logger->info(sizeof($portfolios) . ' matched');

        // update porfolios
        foreach ($portfolios as $portfolio) {
            $this->updatePortfolio($portfolio, $connection, $logger);
        }
    }

    private function updatePortfolio($portfolio, \PDO $connection, LoggerInterface $logger)
    {
        $newCreatorId = $this->findNewPrivateRoomUserId($connection, $portfolio['creator_id']);

        // modify concrete table
        $logger->info('Updating portfolio ' . $portfolio['item_id']);
        $updateTableSQL = '
            UPDATE
            portfolio as p
            SET
            p.creator_id = :newCreatorId,
            p.modifier_id = :newCreatorId
            WHERE
            p.item_id = :portfolioId
        ';

        $stmt = $connection->prepare($updateTableSQL);
        if (!$stmt->execute([
            ':newCreatorId' => $newCreatorId,
            ':portfolioId' => $portfolio['item_id'],
        ])) {
            $logger->error(var_dump($stmt->errorInfo()));
            return false;
        }

        return true;
    }

    private function findNewPrivateRoomUserId($connection, $oldPrivateRoomUserItemId)
    {
        $oldPrivateRoomUserSQL = '
            SELECT
            u.*
            FROM
            user AS u
            INNER JOIN
            room_privat AS p
            ON
            p.item_id = u.context_id
            WHERE
            p.context_id = :contextId AND
            u.deletion_date IS NULL AND
            u.item_id = :userItemId AND
            u.auth_source = :authSource
        ';
        $stmt = $connection->prepare($oldPrivateRoomUserSQL);
        $stmt->execute([
            ':contextId' => 276082,
            ':userItemId' => $oldPrivateRoomUserItemId,
            ':authSource' => 509944,
        ]);
        $oldPrivateRoomUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $numOldPrivateRoomUsers = sizeof($oldPrivateRoomUsers);
        if ($numOldPrivateRoomUsers == 1) {
            $oldPrivateRoomUser = $oldPrivateRoomUsers[0];

            $newPrivateRoomUserSQL = '
                SELECT
                u.*
                FROM
                user AS u
                INNER JOIN
                room_privat AS p
                ON
                p.item_id = u.context_id
                WHERE
                p.context_id = :contextId AND
                u.deletion_date IS NULL AND
                u.user_id = :userId AND
                u.auth_source = :authSource
            ';
            $stmt = $connection->prepare($newPrivateRoomUserSQL);
            $stmt->execute([
                ':contextId' => 5640232,
                ':userId' => $oldPrivateRoomUser['user_id'],
                ':authSource' => 5640233,
            ]);

            $newPrivateRoomUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $numNewPrivateRoomUsers = sizeof($newPrivateRoomUsers);
            if ($numNewPrivateRoomUsers == 1) {
                $newPrivateRoomUser = $newPrivateRoomUsers[0];

                return $newPrivateRoomUser['item_id'];
            }
        }

        return false;
    }
}