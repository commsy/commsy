<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

final class FixAccounts extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        // delete accounts not having a portal user
        $sql = '
            DELETE a FROM accounts a
            LEFT JOIN user u
            USE INDEX FOR JOIN (unique_non_soft_deleted_idx)
            ON u.user_id = a.username AND u.auth_source = a.auth_source_id AND u.context_id = a.context_id
            WHERE u.item_id IS NULL AND a.username != "root"
        ';
        $this->executeSQL($sql, $io);

        // Delete accounts not having a private room user
        $sql = '
            DELETE a FROM accounts a
            LEFT JOIN (user u INNER JOIN room_privat rp)
            ON (u.user_id = a.username AND u.auth_source = a.auth_source_id AND u.context_id = rp.item_id)
            WHERE u.item_id IS NULL AND a.username != "root"
        ';
        $this->executeSQL($sql, $io);

        // delete all users not having an account
        // TODO: more testing and re-enabling
//        $this->executeSQL('CREATE OR REPLACE INDEX tmp ON accounts (username, auth_source_id)', $io);
//        $sql = '
//            DELETE u FROM user u
//            LEFT JOIN accounts a ON u.user_id = a.username AND u.auth_source = a.auth_source_id
//            WHERE a.id IS NULL
//        ';
//        $this->executeSQL($sql, $io);
//        $this->executeSQL('DROP INDEX IF EXISTS tmp ON accounts', $io);

        // TODO: Needs more testing???
//        // fix firstname / lastname on users
//        $sql = '
//            UPDATE user u
//            INNER JOIN user pu
//            ON u.user_id = pu.user_id AND u.auth_source = pu.auth_source
//            INNER JOIN portal p ON pu.context_id = p.id
//            SET u.firstname = pu.firstname, u.lastname = pu.lastname
//            WHERE
//            u.item_id != pu.item_id AND
//            u.firstname != pu.firstname AND
//            u.lastname != pu.lastname AND
//            u.not_deleted = 1 AND
//            pu.not_deleted = 1 AND
//            u.creation_date > pu.creation_date AND
//            pu.firstname != "" AND
//            pu.lastname != ""
//        ';
//        $this->executeSQL($sql, $io);

        // delete users created before the related portal user
        $sql = '
            DELETE u FROM user u
            INNER JOIN user pu
            ON u.user_id = pu.user_id AND u.auth_source = pu.auth_source
            INNER JOIN portal p ON pu.context_id = p.id
            WHERE
            u.item_id != pu.item_id AND
            pu.not_deleted = 1 AND
            u.not_deleted = 1 AND
            u.creation_date < pu.creation_date
        ';
        $this->executeSQL($sql, $io);

        return true;
    }
}
