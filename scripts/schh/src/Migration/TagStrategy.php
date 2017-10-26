<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:39
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class TagStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['tag'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        $tag = $this->findById($connection, 'tag', $item['item_id']);
        if ($tag) {
            if ($tag['title'] == 'CS_TAG_ROOT') {
                $logger->info('Ignoring CS_TAG_ROOT');
                return true;
            }

            return false;
        } else {
            $logger->info('no tags found - skipping');
        }

        return true;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $tag = $this->findById($connection, 'tag', $item['item_id']);
        if ($tag) {
            $logger->info('Moving tag from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'tag', $tag, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }

            // rewrite tag2tag
            $logger->info('rewriting tag relationships');
            $tag2tagSQL = '
                UPDATE
                tag2tag AS tt
                SET
                tt.context_id = :contextId,
                tt.creator_id = :creatorId,
                tt.modifier_id = :modifierId
                WHERE
                tt.deletion_date IS NULL AND
                (tt.from_item_id = :tagId OR tt.to_item_id = :tagId)
            ';
            $stmt = $connection->prepare($tag2tagSQL);
            if (!$stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':creatorId' => $newPortalPrivateRoom['userItemId'],
                ':modifierId' => $newPortalPrivateRoom['userItemId'],
                ':tagId' => $tag['item_id'],
            ])) {
                $logger->error(var_dump($stmt->errorInfo()));
                throw new \Exception("rewrite failed");
            }

            // check if parent tag is root
            $parentTagSql = '
                SELECT
                t.*
                FROM
                tag AS t
                INNER JOIN
                tag2tag AS tt
                ON
                t.item_id = tt.from_item_id
                WHERE
                tt.to_item_id = :tagId AND
                tt.deletion_date IS NULL AND
                t.deletion_date IS NULL AND
                t.title = "CS_TAG_ROOT"
            ';
            $stmt = $connection->prepare($parentTagSql);
            $stmt->execute([
                ':tagId' => $tag['item_id']
            ]);
            $parentTags = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (sizeof($parentTags == 1)) {
                $parentTag = $parentTags[0];
                if ($parentTag['context_id'] == $oldPortalPrivateRoom['item_id']) {
                    $logger->info('Rewriting parent tag');

                    $tagUpdateSQL = '
                        UPDATE
                        tag2tag AS tt
                        SET
                        tt.from_item_id =
                        (
                            SELECT
                            t.item_id AS rootTagId
                            FROM
                            tag AS t
                            WHERE
                            t.deletion_date IS NULL AND
                            t.title = "CS_TAG_ROOT" AND
                            t.context_id = :newPrivateRoomId
                        )
                        WHERE
                        tt.to_item_id = :tagId AND
                        tt.deletion_date IS NULL
                    ';
                    $stmt = $connection->prepare($tagUpdateSQL);
                    if (!$stmt->execute([
                        ':tagId' => $item['item_id'],
                        ':newPrivateRoomId' => $newPortalPrivateRoom['item_id'],
                    ])) {
                        $logger->error(var_dump($stmt->errorInfo()));
                        throw new \Exception("rewrite failed");
                    }
                }
            }


        } else {
            $logger->info('no tag found - skipping');
        }
    }
}