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
        self::$migrations[$migrationClassName::getType()] = $migrationClassName;
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
    public function migrate($connection, array $item, array $newPortalPrivateRoom);
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
    protected function insert($connection, $table, array $item, $newPortalPrivateRoom, $noContextId = false)
    {
        // change context to new private room
        $item['context_id'] = $newPortalPrivateRoom['item_id'];

        // change creator_id
        $item['creator_id'] = $newPortalPrivateRoom['userItemId'];

        // change modifier id
        if (isset($item['modifier_id'])) {
            $item['modifier_id'] = $newPortalPrivateRoom['userItemId'];
        }

        // insert into items table
        echo "Insert dataset into items table: ";
        $insertSQL = '
            INSERT INTO items(
                context_id,
                type,
                modification_date
            ) VALUES (
                :contextId,
                :type,
                :modificationDate
            )
        ';
        $stmt = $connection->prepare($insertSQL);
        if (!$stmt->execute([
            ':contextId' => $item['context_id'],
            ':type' => $this->getType(),
            ':modificationDate' => $item['modification_date']
        ])) {
            var_dump($stmt->errorInfo());
            return false;
        } else {
            echo "ok\n";
        }

        $lastInsertId = $connection->lastInsertId();

        if (!$lastInsertId) {
            throw new Exception("unexpected last insert id");
        }

        // set item id - primary index
        $item['item_id'] = $lastInsertId;

        if ($noContextId) {
            unset($item['context_id']);
        }

        echo "Insert dataset into $table table: ";
        $fields = '`'.implode('`, `', array_keys($item)).'`';
        $placeholder = substr(str_repeat('?,', sizeof($item)), 0, -1);
        $insertSQL = '
            INSERT INTO ' . $table . '(' . $fields . ') VALUES(' . $placeholder . ');
        ';
        $stmt = $connection->prepare($insertSQL);
        if (!$stmt->execute(array_values($item))) {
            var_dump($stmt->errorInfo());
            return false;
        } else {
            echo "ok\n";
        }

        return $stmt->rowCount() == 1;
    }
}

//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////

class UserStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'user';
    }

    public function ignore($connection, array $item)
    {
        return true;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
    }
}

class ProjectStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'project';
    }

    public function ignore($connection, array $item)
    {
        return true;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
    }
}

class CommunityStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'community';
    }

    public function ignore($connection, array $item)
    {
        return true;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
    }
}


class TagStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'tag';
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

    /**
     * TODO: tag2tag is missing
     */
    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $tag = $this->findById($connection, 'tag', $item['item_id']);
        if ($tag) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    t.*
                    FROM
                    tag AS t
                    WHERE
                    t.context_id = :contextId AND
                    t.deletion_date IS NULL AND 
                    t.title = :title
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $tag['title'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'tag', $tag, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'link_item';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    /**
     * TODO: migrated links contain wrong item ids
     */
    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $linkItem = $this->findById($connection, 'link_items', $item['item_id']);
        if ($linkItem) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    l.*
                    FROM
                    link_items AS l
                    WHERE
                    l.context_id = :contextId AND
                    l.deletion_date IS NULL AND 
                    l.first_item_id = :firstItemId AND
                    l.first_item_type = :firstItemType AND
                    l.second_item_id = :secondItemId AND
                    l.second_item_type = :secondItemType
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':firstItemId' => $linkItem['first_item_id'],
                ':firstItemType' => $linkItem['first_item_type'],
                ':secondItemId' => $linkItem['second_item_id'],
                ':secondItemType' => $linkItem['second_item_type'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'link_items', $linkItem, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
            }
        } else {
            echo "No link items found - skipping\n";
        }
    }
}

class MaterialStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'material';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $materials = $this->findById($connection, 'materials', $item['item_id'], true);
        if ($materials) {
            foreach ($materials as $material) {
                echo "Identifying possible duplicates: ";
                $sql = '
                    SELECT
                    m.*
                    FROM
                    materials AS m
                    WHERE
                    m.context_id = :contextId AND
                    m.deletion_date IS NULL AND
                    m.version_id = :versionId AND 
                    m.title = :title AND
                    m.description = :description
                ';
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    ':contextId' => $newPortalPrivateRoom['item_id'],
                    ':versionId' => $material['version_id'],
                    ':title' => $material['title'],
                    ':description' => $material['description'],
                ]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (sizeof($items) == 0) {
                    echo "non found - copying dataset\n";

                    if (!$this->insert($connection, 'materials', $material, $newPortalPrivateRoom)) {
                        throw new Exception("insert failed");
                    }
                } else {
                    echo "found suspicious entry - skipping\n";
                }
            }
        } else {
            echo "No materials found - skipping\n";
        }
    }
}

class LabelStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'label';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    /**
     * TODO: links are missing
     */
    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $label = $this->findById($connection, 'label', $item['item_id']);
        if ($label) {
            throw new Exception("missing implementation");
        } else {
            echo "no label found - skipping\n";
        }
    }
}

class DiscussionStrategy extends AbstractStrategy implements MigrationStrategy
{
    public static function getType()
    {
        return 'discussion';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $discussion = $this->findById($connection, 'discussions', $item['item_id']);
        if ($discussion) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    d.*
                    FROM
                    discussions AS d
                    WHERE
                    d.context_id = :contextId AND
                    d.deletion_date IS NULL AND 
                    d.title = :title AND
                    d.description = :description
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $discussion['title'],
                ':description' => $discussion['description'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'discussions', $discussion, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'discarticle';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $discussionArticle = $this->findById($connection, 'discussionarticles', $item['item_id']);
        if ($discussionArticle) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    d.*
                    FROM
                    discussionarticles AS d
                    WHERE
                    d.context_id = :contextId AND
                    d.discussion_id = :discussionId AND
                    d.deletion_date IS NULL AND 
                    d.subject = :subject AND
                    d.description = :description AND
                    d.position = :position
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':discussionId' => $discussionArticle['discussion_id'],
                ':subject' => $discussionArticle['subject'],
                ':description' => $discussionArticle['description'],
                ':position' => $discussionArticle['position'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'discussionarticles', $discussionArticle, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'date';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $date = $this->findById($connection, 'dates', $item['item_id']);
        if ($date) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    d.*
                    FROM
                    dates AS d
                    WHERE
                    d.context_id = :contextId AND
                    d.deletion_date IS NULL AND 
                    d.title = :title AND
                    d.description = :description AND
                    d.start_time = :startTime AND
                    d.end_time = :endTime AND
                    d.start_day = :startDay AND
                    d.end_day = :endDay AND
                    d.place = :place AND
                    d.datetime_start = :datetimeStart AND
                    d.datetime_end = :datetimeEnd
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $date['title'],
                ':description' => $date['description'],
                ':startTime' => $date['start_time'],
                ':endTime' => $date['end_time'],
                ':startDay' => $date['start_day'],
                ':endDay' => $date['end_day'],
                ':place' => $date['place'],
                ':datetimeStart' => $date['datetime_start'],
                ':datetimeEnd' => $date['datetime_end'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'dates', $date, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'section';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $sections = $this->findById($connection, 'section', $item['item_id'], true);
        if ($sections) {
            foreach ($sections as $section) {
                echo "Identifying possible duplicates: ";
                $sql = '
                    SELECT
                    s.*
                    FROM
                    section AS s
                    WHERE
                    s.version_id = :versionId AND
                    s.context_id = :contextId AND
                    s.deletion_date IS NULL AND 
                    s.title = :title AND
                    s.description = :description AND
                    s.material_item_id = :materialItemId
                ';
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    ':versionId' => $section['version_id'],
                    ':contextId' => $newPortalPrivateRoom['item_id'],
                    ':title' => $section['title'],
                    ':description' => $section['description'],
                    ':materialItemId' => $section['material_item_id'],
                ]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (sizeof($items) == 0) {
                    echo "non found - copying dataset\n";

                    if (!$this->insert($connection, 'section', $section, $newPortalPrivateRoom)) {
                        throw new Exception("insert failed");
                    }
                } else {
                    echo "found suspicious entry - skipping\n";
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
        return 'annotation';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $annotation = $this->findById($connection, 'annotations', $item['item_id']);
        if ($annotation) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    a.*
                    FROM
                    annotations AS a
                    WHERE
                    d.context_id = :contextId AND
                    d.deletion_date IS NULL AND 
                    d.title = :title AND
                    d.description = :description AND
                    d.linked_item_id = :linkedItemId
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $annotation['title'],
                ':description' => $annotation['description'],
                ':linkedItemId' => $annotation['linked_item_id'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'annotations', $annotation, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'announcement';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $announcement = $this->findById($connection, 'announcement', $item['item_id']);
        if ($announcement) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    a.*
                    FROM
                    announcement AS a
                    WHERE
                    d.context_id = :contextId AND
                    d.deletion_date IS NULL AND 
                    d.title = :title AND
                    d.description = :description AND
                    d.enddate = :endDate
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $announcement['title'],
                ':description' => $announcement['description'],
                ':endDate' => $announcement['enddate'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'announcement', $announcement, $newPortalPrivateRoom)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'portfolio';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    /**
     * TODO: tag_portfolio is missing
     * TODO: annotation_portfolio is missing
     * TODO: template_portfolio is missing
     * TODO: user_portfolio is missing
     */
    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $portfolio = $this->findById($connection, 'portfolio', $item['item_id']);
        if ($portfolio) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    p.*
                    FROM
                    portfolio AS p
                    WHERE
                    p.deletion_date IS NULL AND 
                    p.title = :title AND
                    p.description = :description
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $portfolio['title'],
                ':description' => $portfolio['description'],
                ':endDate' => $portfolio['enddate'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'portfolio', $portfolio, $newPortalPrivateRoom, true)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'todo';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $todo = $this->findById($connection, 'todos', $item['item_id']);
        if ($todo) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    t.*
                    FROM
                    todos AS t
                    WHERE
                    p.deletion_date IS NULL AND 
                    p.title = :title AND
                    p.description = :description AND
                    p.date = :date AND
                    p.status = :status AND
                    p.minutes = :minutes
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $todo['title'],
                ':description' => $todo['description'],
                ':date' => $todo['date'],
                ':status' => $todo['status'],
                ':minutes' => $todo['minutes'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'todos', $todo, $newPortalPrivateRoom, true)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
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
        return 'step';
    }

    public function ignore($connection, array $item)
    {
        return false;
    }

    public function migrate($connection, array $item, array $newPortalPrivateRoom)
    {
        $step = $this->findById($connection, 'step', $item['item_id']);
        if ($step) {
            echo "Identifying possible duplicates: ";
            $sql = '
                    SELECT
                    s.*
                    FROM
                    step AS s
                    WHERE
                    s.deletion_date IS NULL AND 
                    s.title = :title AND
                    s.description = :description AND
                    s.minutes = :minutes AND
                    s.time_type = :timeType AND
                    s.todo_item_id = :todoItemId
                ';
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':contextId' => $newPortalPrivateRoom['item_id'],
                ':title' => $step['title'],
                ':description' => $step['description'],
                ':minutes' => $step['minutes'],
                ':timeType' => $step['time_type'],
                ':todoItemId' => $step['todo_item_id'],
            ]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($items) == 0) {
                echo "non found - copying dataset\n";

                if (!$this->insert($connection, 'step', $step, $newPortalPrivateRoom, true)) {
                    throw new Exception("insert failed");
                }
            } else {
                echo "found suspicious entry - skipping\n";
            }
        } else {
            echo "no todo found - skipping\n";
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

MigrationFactory::registerMigration( UserStrategy::class);
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
MigrationFactory::registerMigration(ProjectStrategy::class);
MigrationFactory::registerMigration(CommunityStrategy::class);
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
                $migration->migrate($connection, $oldItem, $newPortalPrivateRoom);
            } else {
                echo "Item with id " . $oldItem['item_id'] . " of type " . $oldItem['type'] . " is ignored - skipping\n";
            }
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