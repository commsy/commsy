<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 02.09.17
 * Time: 19:02
 */

/**
 * TODO: files are not migrated
 * TODO: assessment?
 * TODO: external_viewer?
 * TODO: noticed
 * TODO: reader
 */

class MigrationFactory
{
    private static $migrations = [];

    public static function registerMigration($migrationClassName)
    {
        $types = $migrationClassName::getType();
        foreach ($types as $type) {
            self::$migrations[$type] = $migrationClassName;
        }
    }

    public static function findMigration($type)
    {
        if (isset(self::$migrations[$type])) {
            $instance = new self::$migrations[$type];

            if (!$instance instanceof MigrationStrategy) {
                throw new Exception("Strategy does not implement MigrationStrategy Interface");
            }

            return $instance;
        }

        throw new Exception('no migration found for type ' . $type);
    }
}

interface MigrationStrategy
{
    public static function getType();
    public function ignore($connection, array $item);
    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom);
}

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
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$returnsMultiple) {
            if (sizeof($items) > 1) {
                throw new Exception("found more than one item");
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

    /**
     * TODO: Setting creator and modifier to the new private room user is not correct in all cases.
     * TODO: e.g. annotations created in portfolios
     */
    protected function move($connection, $table, array $item, $newPortalPrivateRoom, $noContextId = false)
    {
        // change context to new private room
        $item['context_id'] = $newPortalPrivateRoom['item_id'];

        // change creator_id
        $item['creator_id'] = $newPortalPrivateRoom['userItemId'];

        // change modifier id
        if (isset($item['modifier_id'])) {
            $item['modifier_id'] = $newPortalPrivateRoom['userItemId'];
        }

        // modify item table
        echo "Updating entry in items table: ";
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
            ':itemId' => $item['item_id'],
            ':contextId' => $item['context_id'],
        ])) {
            var_dump($stmt->errorInfo());
            return false;
        } else {
            echo "ok\n";
        }

        if ($noContextId) {
            unset($item['context_id']);
        }

        // modify concrete table
        echo "Updating entry in " . $table . " table: ";
        $updateTableSQL = '
            UPDATE
            ' . $table . ' as t
            SET
            t.context_id = :contextId
            WHERE
            t.item_id = :itemId
        ';
        $stmt = $connection->prepare($updateTableSQL);
        if (!$stmt->execute([
            ':itemId' => $item['item_id'],
            ':contextId' => $item['context_id'],
        ])) {
            var_dump($stmt->errorInfo());
            return false;
        } else {
            echo "ok\n";
        }

        return true;
    }

    /**
     * TODO: consider material versions of files
     */
    protected function copyFiles($connection, array $item, $newPortalPrivateRoom, $oldPortalPrivateRoom) {

        // all existing files from that item
        $sql = '
          SELECT 
          f.* 
          FROM 
          item_link_file AS l 
          INNER JOIN 
          files AS f 
          ON 
          f.`files_id` = l.file_id 
          WHERE
          l.item_iid = :itemId
        ';

        $stmt = $connection->prepare($sql);
        $stmt->execute([ 'itemId' => $item['item_id'] ]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // all links from item
        foreach ($files AS $file) {

            // change context id to new room context id
            echo "Change file context id";

            $insertSQL = '
            INSERT INTO files(
                context_id,
                modification_date
            ) VALUES (
                :contextId,
                :modificationDate
            )
            ';

            $stmt = $connection->prepare($insertSQL);
            if (!$stmt->execute([
                ':contextId' => $newPortalPrivateRoom['context_id'],
                ':modificationDate' => $item['modification_date']
            ])) {
                var_dump($stmt->errorInfo());
                return false;
            } else {
                echo "ok\n";
            }

            echo "copying file\n";

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
            $oldFilePath = 'var/'.$oldPortalContextId.'/'.$oldPrivateRoomIdFirst.'/'.$oldPrivateRoomIdLast.'/'.$fileId.'.'.$ext;
            $newFilePath = 'var/'.$newPortalContextId.'/'.$privateRoomIdFirst.'/'.$privateRoomIdLast.'/'.$fileId.'.'.$ext;

            if (file_exists($oldFilePath)) {
                // copy file
                if (!copy($oldFilePath, $newFilePath)) {
                    throw new Exception("copying file failed");
                }
            } else {
                //throw new Exception("file does not exist");
                echo "file $oldFilePath does not exist\n";
            }

            return true;

        }

        return true;
    }
}

//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////

class IgnoreStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['user', 'project', 'community'];
    }

    public function ignore($connection, array $item)
    {
        return true;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
    }
}


class TagStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['tag'];
    }

    public function ignore($connection, array $item)
    {
        $tag = $this->findById($connection, 'tag', $item['item_id']);
        if ($tag) {
            if ($tag['title'] == 'CS_TAG_ROOT') {
                echo "Ignoring CS_TAG_ROOT\n";
                return true;
            }

            return false;
        } else {
            echo "no tags found - skipping\n";
        }

        return true;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $tag = $this->findById($connection, 'tag', $item['item_id']);
        if ($tag) {
            echo "Moving tag from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'tag', $tag, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no tag found - skipping\n";
        }
    }
}

class LinkItemStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['link_item'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    /**
     * TODO: what about links to users?
     */
    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $linkItem = $this->findById($connection, 'link_items', $item['item_id']);
        if ($linkItem) {
            echo "Moving link item from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'link_items', $linkItem, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no link items found - skipping\n";
        }
    }
}

class MaterialStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['material'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $materials = $this->findById($connection, 'materials', $item['item_id'], true);
        if ($materials) {
            foreach ($materials as $material) {
                echo "Moving material from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
                if (!$this->move($connection, 'materials', $material, $newPortalPrivateRoom)) {
                    throw new Exception("move failed");
                } else {
                    echo "ok\n";
                }
            }
        } else {
            echo "no materials found - skipping\n";
        }
    }
}

class LabelStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['label'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $label = $this->findById($connection, 'label', $item['item_id']);
        if ($label) {
            echo "Moving label from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'labels', $label, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no label found - skipping\n";
        }
    }
}

class DiscussionStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['discussion'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $discussion = $this->findById($connection, 'discussions', $item['item_id']);
        if ($discussion) {
            echo "Moving discussion from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'discussions', $discussion, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no discussion found - skipping\n";
        }
    }
}

class DiscussionArticleStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['discarticle'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $discussionArticle = $this->findById($connection, 'discussionarticles', $item['item_id']);
        if ($discussionArticle) {
            echo "Moving discussion article from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'discussionarticles', $discussionArticle, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no discussion article found - skipping\n";
        }
    }
}

class DateStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['date'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $date = $this->findById($connection, 'dates', $item['item_id']);
        if ($date) {
            echo "Moving date from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'dates', $date, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no date found - skipping\n";
        }
    }
}

class SectionStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['section'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $sections = $this->findById($connection, 'section', $item['item_id'], true);
        if ($sections) {
            foreach ($sections as $section) {
                echo "Moving section from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
                if (!$this->move($connection, 'section', $section, $newPortalPrivateRoom)) {
                    throw new Exception("move failed");
                }
            }
        } else {
            echo "no sections found - skipping\n";
        }
    }
}

class AnnotationStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['annotation'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $annotation = $this->findById($connection, 'annotations', $item['item_id']);
        if ($annotation) {
            echo "Moving annotation from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'annotations', $annotation, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no annotation found - skipping\n";
        }
    }
}

class AnnouncementStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['announcement'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $announcement = $this->findById($connection, 'announcement', $item['item_id']);
        if ($announcement) {
            echo "Moving announcement from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'announcement', $announcement, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no announcement found - skipping\n";
        }
    }
}

class PortfolioStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['portfolio'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $portfolio = $this->findById($connection, 'portfolio', $item['item_id']);
        if ($portfolio) {
            echo "Moving portfolio from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'portfolio', $portfolio, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no portfolio found - skipping\n";
        }
    }
}

class TodoStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['todo'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $todo = $this->findById($connection, 'todos', $item['item_id']);
        if ($todo) {
            echo "Moving todo from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'todos', $todo, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no todo found - skipping\n";
        }
    }
}

class StepStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return ['step'];
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom, array $oldPortalPrivateRoom)
    {
        $step = $this->findById($connection, 'step', $item['item_id']);
        if ($step) {
            echo "Moving step from " . $oldPortalPrivateRoom['item_id'] . " to " . $newPortalPrivateRoom['item_id'] . "\n";
            if (!$this->move($connection, 'step', $step, $newPortalPrivateRoom)) {
                throw new Exception("move failed");
            }
        } else {
            echo "no step found - skipping\n";
        }
    }
}

//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////

$dsn = 'mysql:dbname=schh;host=commsy8_db';
$user = 'commsy';
$password = 'commsy';

$connection = new PDO($dsn, $user, $password);

MigrationFactory::registerMigration(IgnoreStrategy::class);
MigrationFactory::registerMigration(TagStrategy::class);
MigrationFactory::registerMigration(LinkItemStrategy::class);
MigrationFactory::registerMigration(MaterialStrategy::class);
MigrationFactory::registerMigration(LabelStrategy::class);
MigrationFactory::registerMigration(DiscussionStrategy::class);
MigrationFactory::registerMigration(DiscussionArticleStrategy::class);
MigrationFactory::registerMigration(DateStrategy::class);
MigrationFactory::registerMigration(SectionStrategy::class);
MigrationFactory::registerMigration(AnnotationStrategy::class);
MigrationFactory::registerMigration(TodoStrategy::class);
MigrationFactory::registerMigration(AnnouncementStrategy::class);
MigrationFactory::registerMigration(StepStrategy::class);
MigrationFactory::registerMigration(PortfolioStrategy::class);

processPrivateRooms($connection);

function processPrivateRoom($connection, $oldPortalPrivateRoom, array $newPortalPrivateRoom)
{
    echo "Looking for content in private room " . $oldPortalPrivateRoom['item_id'] . ": ";

    $itemsSQL = '
        SELECT
        i.*
        FROM
        items AS i
        WHERE
        i.context_id = :contextId AND
        i.deletion_date IS NULL
    ';
    $stmt = $connection->prepare($itemsSQL);
    $stmt->execute([ 'contextId' => $oldPortalPrivateRoom['item_id'] ]);
    $oldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$oldItems) {
        echo "nothing found - skipping\n";
    } else {
        echo "found " . sizeof($oldItems) . " items\n";

        foreach ($oldItems as $oldItem) {
            $migration = MigrationFactory::findMigration($oldItem['type']);
            if (!$migration->ignore($connection, $oldItem)) {
                echo "Migrating item " . $oldItem['item_id'] . " of type " . $oldItem['type'] . "\n";
                $migration->migrate($connection, $oldItem, $newPortalPrivateRoom, $oldPortalPrivateRoom);
            } else {
                echo "Item with id " . $oldItem['item_id'] . " of type " . $oldItem['type'] . " is ignored - skipping\n";
            }
            echo "\n";
        }
    }

    echo "\n";
}

function processPrivateRooms($connection)
{
    echo "Collecting private rooms of 5640232\n";

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
        p.deletion_date IS NULL
    ';
    $stmt = $connection->prepare($newPortalPrivateRoomsSQL);
    $stmt->execute([ ':contextId' => 5640232 ]);
    $newPortalPrivateRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . sizeof($newPortalPrivateRooms) . " privat rooms\n";

    foreach ($newPortalPrivateRooms as $newPortalPrivateRoom) {
        echo "Processing private room on new portal " . $newPortalPrivateRoom['item_id']  . "\n";
        echo "Looking for related private room on old portal: ";

        $oldPortalPrivateRoomsSQL = '
            SELECT
            p.*
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
        $oldPortalPrivateRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $numOldPortalPrivateRooms = sizeof($oldPortalPrivateRooms);
        if ($numOldPortalPrivateRooms == 1) {
            echo "found - processing\n";
            processPrivateRoom($connection, $oldPortalPrivateRooms[0], $newPortalPrivateRoom);

        } else {
            if ($numOldPortalPrivateRooms == 0) {
                echo "error - none found\n";
            } else {
                echo "error - multiple found\n";
            }
        }

        echo "\n";
    }
}
