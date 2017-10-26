<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:42
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class PortfolioStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['portfolio'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $portfolio = $this->findById($connection, 'portfolio', $item['item_id']);
        if ($portfolio) {
            $logger->info('Moving portfolio from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'portfolio', $portfolio, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }
        } else {
            $logger->info('no portfolio found - skipping');
        }
    }

    /**
     * Overrides move method of AbstractStrategy
     *
     * An portfolio exists in the portal context and not in the private room one
     */
    protected function move($connection, $table, array $item, array $newPortalPrivateRoom, LoggerInterface $logger)
    {
        // modify item table
        if (!$this->updateItemTable($connection, $item['item_id'], 5640232, $logger)) {
            return false;
        }

        // modify link_modifier_item
        if (!$this->updateLinkModifier($connection, $item['item_id'], $logger)) {
            return false;
        }

        // modify concrete table
        $logger->info('Updating entry in ' . $table . ' table');
        $updateTableSQL = '
            UPDATE
            ' . $table . ' as t
            SET
        ';

        $parameters = [
            ':itemId' => $item['item_id'],
            ':creatorId' => $newPortalPrivateRoom['userItemId'],
        ];

        if (isset($item['modifier_id'])) {
            $newModifierId = $this->findNewPrivateRoomUserId($connection, $item['modifier_id'], $logger);
            if ($newModifierId) {
                $updateTableSQL .= 't.modifier_id = :modifierId,';
                $parameters[':modifierId'] = $newModifierId;
            }
        }

        $updateTableSQL .= '
            t.creator_id = :creatorId
            WHERE
            t.item_id = :itemId
        ';
        $stmt = $connection->prepare($updateTableSQL);
        if (!$stmt->execute($parameters)) {
            $logger->error(var_dump($stmt->errorInfo()));
            return false;
        }

        return true;
    }
}