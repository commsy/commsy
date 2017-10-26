<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 25.10.17
 * Time: 13:40
 */

namespace CommSy\Migration;


use Psr\Log\LoggerInterface;

class DiscussionArticleStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['discarticle'];
    }

    public function ignore(\PDO $connection, array $item, LoggerInterface $logger)
    {
        return false;
    }

    public function migrate(\PDO $connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom, LoggerInterface $logger)
    {
        $discussionArticle = $this->findById($connection, 'discussionarticles', $item['item_id']);
        if ($discussionArticle) {
            $logger->info('Moving discussion article from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->move($connection, 'discussionarticles', $discussionArticle, $newPortalPrivateRoom, $logger)) {
                throw new \Exception("move failed");
            }

            $logger->info('Copying discussion article files from ' . $oldPortalPrivateRoom['item_id'] . ' to ' . $newPortalPrivateRoom['item_id']);
            if (!$this->copyFiles($connection, $discussionArticle, $newPortalPrivateRoom, $oldPortalPrivateRoom, $logger)) {
                throw new \Exception("copy failed");
            }
        } else {
            $logger->info('no discussion article found - skipping');
        }
    }
}