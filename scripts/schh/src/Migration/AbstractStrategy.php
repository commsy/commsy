<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:38
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class AbstractStrategy
{
    protected function findById($connection, $table, $itemId, $returnsMultiple = false)
    {
        $sql = '
            SELECT
            t.*
            FROM
            ' . $table . ' AS t
            WHERE
            t.item_id = :itemId
        ';
        $stmt = $connection->prepare($sql);
        $stmt->execute([ 'itemId' => $itemId ]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!$returnsMultiple) {
            if (sizeof($items) > 1) {
                throw new \Exception("found more than one item");
            }

            if (sizeof($items) == 1) {
                return $items[0];
            }
        } else {
            if (sizeof($items) >= 1) {
                return $items;
            }
        }

        return null;
    }

    protected function findNewPrivateRoomUserId($connection, $oldPrivateRoomUserItemId, LoggerInterface $logger)
    {
        $logger->info('Trying to find new private room user');
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

        $logger->warning('new private room user not found');
        return false;
    }

    protected function updateItemTable($connection, $itemId, $newContextId, LoggerInterface $logger)
    {
        $logger->info('Updating entry in items table');

        $updateItemsSQL = '
            UPDATE
            items AS i
            SET
            i.context_id = :contextId
            WHERE
            i.item_id = :itemId
        ';
        $stmt = $connection->prepare($updateItemsSQL);
        if (!$stmt->execute([
            ':itemId' => $itemId,
            ':contextId' => $newContextId,
        ])) {
            $logger->error(var_dump($stmt->errorInfo()));
            return false;
        }

        return true;
    }

    protected function updateLinkModifier($connection, $itemId, LoggerInterface $logger)
    {
        $logger->info('Update entry in link_modifier_item table');
        $selectModifierSQL = '
            SELECT
            *
            FROM
            link_modifier_item AS l
            WHERE
            l.item_id = :itemId
        ';
        $stmt = $connection->prepare($selectModifierSQL);
        $stmt->execute([
            ':itemId' => $itemId,
        ]);
        $linkModifierItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($linkModifierItems as $linkModifierItem) {
            $newModifierId = $this->findNewPrivateRoomUserId($connection, $linkModifierItem['modifier_id'], $logger);

            if ($newModifierId) {
                $updateModifierSQL = '
                    UPDATE
                    link_modifier_item AS l
                    SET
                    l.modifier_id = :modifierId
                    WHERE
                    l.item_id = :itemId AND
                    l.modifier_id = :oldUserId
                ';
                $stmt = $connection->prepare($updateModifierSQL);
                if (!$stmt->execute([
                    ':itemId' => $itemId,
                    ':modifierId' => $newModifierId,
                    ':oldUserId' => $linkModifierItem['modifier_id'],
                ])) {
                    $logger->error(var_dump($stmt->errorInfo()));
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * TODO: Setting creator and modifier to the new private room user is not correct in all cases.
     * TODO: e.g. annotations created in portfolios
     */
    protected function move($connection, $table, array $item, array $newPortalPrivateRoom, LoggerInterface $logger)
    {
        // modify item table
        if (!$this->updateItemTable($connection, $item['item_id'], $newPortalPrivateRoom['item_id'], $logger)) {
            return false;
        }

        // modify link_modifier_item
        if (!$this->updateLinkModifier($connection, $item['item_id'], $logger)) {
            return false;
        }

        // modify concrete table
        $logger->info('Updating entry in ' . $table . ' table: ');
        $updateTableSQL = '
            UPDATE
            ' . $table . ' as t
            SET
        ';

        $newCreatorId = $this->findNewPrivateRoomUserId($connection, $item['creator_id'], $logger);

        $parameters = [
            ':itemId' => $item['item_id'],
            ':creatorId' => $newCreatorId,
        ];

        $updateTableSQL .= 't.context_id = :contextId,';
        $parameters[':contextId'] = $newPortalPrivateRoom['item_id'];

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

    protected function copyFiles($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $versionId = 0;

        if (isset($item['version_id'])) {
            $versionId = $item['version_id'];
        }

        // all existing files from that item
        $sql = '
          SELECT 
          f.* 
          FROM 
          item_link_file AS l 
          INNER JOIN 
          files AS f 
          ON 
          f.files_id = l.file_id 
          WHERE
          l.item_iid = :itemId AND
          l.item_vid = :versionId
        ';

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':itemId' => $item['item_id'],
            ':versionId' => $versionId,
        ]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($files) {
            // all links from item
            foreach ($files AS $file) {
                // change context id to new room context id
                $logger->info('Updating context id of file ' . $file['files_id']);

                $updateSql = '
                    UPDATE
                    files AS f  
                    SET
                    f.context_id = :contextId,
                    f.creator_id = :creatorId
                    WHERE
                    f.files_id = :fileId
                ';
                $stmt = $connection->prepare($updateSql);
                if (!$stmt->execute([
                    ':fileId' => $file['files_id'],
                    ':contextId' => $newPortalPrivateRoom['item_id'],
                    ':creatorId' => $newPortalPrivateRoom['userItemId'],
                ])) {
                    $logger->error(var_dump($stmt->errorInfo()));
                    return false;
                }

                $logger->info('Copying file ' . $file['files_id']);

                // old file information
                $fileId = $file['files_id'];
                $filename = $file['filename'];
                $ext = end(explode('.', $filename));

                // old files
                $oldPortalContextId = $oldPortalPrivateRoom['context_id'];
                $oldPrivateRoomId = $item['context_id'];
                $oldPrivateRoomIdFirst = substr($oldPrivateRoomId,0,4);
                $oldPrivateRoomIdLast  = substr($oldPrivateRoomId, 4, strlen($oldPrivateRoomId));

                // new files
                $newPortalContextId = $newPortalPrivateRoom['context_id']; // 5640232
                $newPrivateRoomId = $newPortalPrivateRoom['item_id'];
                $privateRoomIdFirst = substr($newPrivateRoomId,0,4);
                $privateRoomIdLast  = substr($newPrivateRoomId, 4, strlen($newPrivateRoomId));

                // define new and old file path
                $basePath = '../../';
                $oldFilePath = $basePath . 'var/'.$oldPortalContextId.'/'.$oldPrivateRoomIdFirst.'/'.$oldPrivateRoomIdLast.'_/'.$fileId.'.'.$ext;
                $newFilePath = $basePath . 'var/'.$newPortalContextId.'/'.$privateRoomIdFirst.'/'.$privateRoomIdLast.'_/'.$fileId.'.'.$ext;

                $fs = new Filesystem();
                if ($fs->exists($oldFilePath)) {
                    try {
                        $fs->copy($oldFilePath, $newFilePath);
                    } catch (IOException $e) {
                        $logger->error('copying file failed');
                        throw new \Exception('copying file failed');
                    }
                } else {
                    $logger->warning('file ' . $oldFilePath . ' not found');
                }
            }
        } else {
            $logger->info('no file found');
        }

        return true;
    }
}