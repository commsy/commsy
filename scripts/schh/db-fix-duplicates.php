<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 31.08.17
 * Time: 19:26
 */

$dsn = 'mysql:dbname=schh;host=commsy8_db';
$user = 'commsy';
$password = 'commsy';

$connection = new PDO($dsn, $user, $password);

fixPortalUserDuplicates($connection);
fixUsersWithoutItems($connection);
fixUsersAuthSources($connection);

function deletePrivateRoom($connection, $privateRoom)
{
    $deletePrivateRoomSQL = 'DELETE FROM privat_room WHERE item_id = ' . $privateRoom['item_id'];
    $connection->exec($deletePrivateRoomSQL);
    echo "Eintrag aus privat_room Tabelle gelöscht\n";

    $deleteItemSQL = 'DELETE_FROM items WHERE item_id ' . $privateRoom['item_id'];
    $connection->exec($deleteItemSQL);
    echo "Eintrag aus items Tabelle gelöscht\n";

    deleteUser($connection, $privateRoom['creator_id']);
}

function deleteUser($connection, $userItemId)
{
    $deleteUserSQL = 'DELETE FROM user WHERE item_id = ' . $userItemId;
    $connection->exec($deleteUserSQL);
    echo "Eintrag aus user Tabelle gelöscht\n";

    $deleteItemSQL = 'DELETE FROM items WHERE item_id = ' . $userItemId;
    $connection->exec($deleteItemSQL);
    echo "Eintrag aus items Tabelle gelöscht\n";
}

function addMissingItem($connection, $userItem)
{
    $insertItemSQL = 'INSERT INTO items(item_id, context_id, type, modification_date) VALUES (?, ?, ?, ?)';
    $stmt = $connection->prepare($insertItemSQL);
    $stmt->execute([ $userItem['item_id'], $userItem['context_id'], 'user', $userItem['modification_date'] ]);
    echo "Eintrag in items Tabelle eingefügt\n";
}

function fixPortalUserDuplicates($connection)
{
    $duplicatePortalUserSQL = '
        SELECT
        u.*
        FROM
        user AS u
        WHERE
        u.context_id = 5640232 AND
        u.deletion_date IS NULL
        GROUP BY
        u.user_id
        HAVING
        COUNT(u.user_id) > 1
    ';

    $duplicatePortalUsers = [];
    foreach ($connection->query($duplicatePortalUserSQL, PDO::FETCH_ASSOC) as $row) {
        $duplicatePortalUsers[] = $row;
    }
    echo sizeof($duplicatePortalUsers) . " Duplikate\n";

    foreach ($duplicatePortalUsers as $duplicatePortalUser) {
        $userSQL = '
            SELECT
            u.*
            FROM
            user AS u
            WHERE
            u.context_id = 5640232 AND
            u.user_id = "' . $duplicatePortalUser['user_id'] . '"
        ';

        $users = [];
        foreach ($connection->query($userSQL, PDO::FETCH_ASSOC) as $row) {
            $users[] = $row;
        }

        echo sizeof($users) . " IDs gefunden\n";

        for ($i = 1; $i < sizeof($users); $i++) {
            $userItemId = $users[$i]['item_id'];

            echo "Lösche User - ID: " . $userItemId . "\n";

            deleteUser($connection, $userItemId);
        }

        // find private rooms
        $duplicatePrivateRoomSQL = '
            SELECT
            *
            FROM
            room_privat as p
            INNER JOIN
            user AS u
            ON
            p.item_id = u.context_id
            WHERE
            p.deletion_date IS NULL AND
            p.context_id = 5640232 AND
            u.user_id = "' . $duplicatePortalUser['user_id'] . '" AND
            u.auth_source = "' . $duplicatePortalUser['auth_source'] . '"
        ';

        $duplicatePrivateRooms = [];
        foreach ($connection->query($duplicatePrivateRoomSQL, PDO::FETCH_ASSOC) as $row) {
            $duplicatePrivateRooms[] = $row;
        }

        echo sizeof($duplicatePrivateRooms) . " Private Räume gefunden\n";

        for ($i = 1; $i < sizeof($duplicatePrivateRooms); $i++) {
            $duplicatePrivateRoomId = $duplicatePrivateRooms[$i]['item_id'];

            echo "Lösche Privat Room - ID: " . $duplicatePrivateRoomId . "\n";

            deletePrivateRoom($connection, $duplicatePrivateRooms[$i]);
        }

        echo "\n";
    }

    echo "\n";
}

function fixUsersWithoutItems($connection)
{
    $usersWithoutItemsSQL = '
        SELECT
        u.*
        FROM
        user as u
        LEFT JOIN
        items AS i
        ON
        u.item_id = i.item_id
        WHERE
        i.item_id IS NULL AND
        u.deletion_date IS NULL
    ';

    $usersWithoutItems = [];
    foreach ($connection->query($usersWithoutItemsSQL, PDO::FETCH_ASSOC) as $row) {
        $usersWithoutItems[] = $row;
    }
    echo sizeof($usersWithoutItems) . " Benutzer ohne Items\n";

    foreach ($usersWithoutItems as $usersWithoutItem) {
        echo "Ergänze fehlendes Item für Benutzer " . $usersWithoutItem['user_id'] . "\n";

        addMissingItem($connection, $usersWithoutItem);
    }

    echo "\n";
}

function fixUsersAuthSources($connection)
{
    $usersWithoutAuthSourceSQL = '
        SELECT
        u.*
        FROM
        user as u
        LEFT JOIN
        auth_source AS a
        ON
        u.auth_source = a.item_id
        WHERE
        a.item_id IS NULL AND
        u.deletion_date IS NULL
    ';

    $usersWithoutAuthSources = [];
    foreach ($connection->query($usersWithoutAuthSourceSQL, PDO::FETCH_ASSOC) as $row) {
        $usersWithoutAuthSources[] = $row;
    }
    echo sizeof($usersWithoutAuthSources) . " Benutzer ohne Authentifizierungsquelle\n";

    foreach ($usersWithoutAuthSources as $usersWithoutAuthSource) {
        echo "Lösche Benutzer ohne Authentifizierungsquelle " . $usersWithoutAuthSource['user_id'] . "\n";

        deleteUser($connection, $usersWithoutAuthSource['item_id']);
    }

    echo "\n";
}