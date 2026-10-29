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

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes entries with a context id that does not exist.
 */
class FixContext extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $tablesWithContext = ['annotations', 'announcement', 'assessments', 'calendars', 'dates',
            'discussionarticles', 'discussions', 'invitations', 'labels', 'licenses', 'link_items',
            'links', 'materials', 'room', 'section', 'step', 'tag', 'tag2tag', 'tasks',
            'terms', 'todos', 'translation'];

        foreach ($tablesWithContext as $tableWithContext) {
            $sql = "
                DELETE t FROM $tableWithContext AS t
                LEFT JOIN room AS c1 ON t.context_id = c1.item_id
                LEFT JOIN portal AS c4 ON t.context_id = c4.id
                LEFT JOIN server AS c5 ON t.context_id = c5.item_id
                WHERE t.context_id IS NOT NULL
                AND c1.item_id IS NULL AND c4.id IS NULL AND c5.item_id IS NULL;
            ";
            $this->executeSQL($sql, $io);
        }

        $this->deleteProfilesInVanishedContexts($io);

        // file links (foreign key)
        $sql = '
            DELETE ilf FROM files AS f
            LEFT JOIN item_link_file AS ilf ON f.files_id = ilf.file_id
            LEFT JOIN room AS c1 ON f.context_id = c1.item_id
            LEFT JOIN portal AS c4 ON f.context_id = c4.id
            LEFT JOIN server AS c5 ON f.context_id = c5.item_id
            WHERE f.context_id IS NOT NULL
            AND c1.item_id IS NULL AND c4.id IS NULL AND c5.item_id IS NULL;
        ';
        $this->executeSQL($sql, $io);

        // files
        $sql = '
            DELETE f FROM files AS f
            LEFT JOIN item_link_file AS ilf ON f.files_id = ilf.file_id
            LEFT JOIN room AS c1 ON f.context_id = c1.item_id
            LEFT JOIN portal AS c4 ON f.context_id = c4.id
            LEFT JOIN server AS c5 ON f.context_id = c5.item_id
            WHERE f.context_id IS NOT NULL
            AND c1.item_id IS NULL AND c4.id IS NULL AND c5.item_id IS NULL;
        ';
        $this->executeSQL($sql, $io);

        $sql = "
            DELETE t FROM items AS t
            LEFT JOIN room AS c1 ON t.context_id = c1.item_id
            LEFT JOIN portal AS c3 ON t.context_id = c3.id
            LEFT JOIN server AS c4 ON t.context_id = c4.item_id
            WHERE t.context_id IS NOT NULL AND t.type != 'server'
            AND c1.item_id IS NULL AND c3.id IS NULL AND c4.item_id IS NULL;
        ";
        $this->executeSQL($sql, $io);

        // Accounts and sign-in sources need no check: their foreign keys to
        // the portal refuse to leave them behind.

        return true;
    }

    /**
     * Profiles may still be named as creator or modifier of rooms and
     * profiles, whose foreign keys refuse the deletion; those references are
     * cleared first.
     */
    private function deleteProfilesInVanishedContexts(SymfonyStyle $io): void
    {
        $connection = $this->entityManager->getConnection();
        $itemIds = array_map('intval', $connection->fetchFirstColumn('
            SELECT t.item_id FROM user AS t
            LEFT JOIN room AS c1 ON t.context_id = c1.item_id
            LEFT JOIN portal AS c4 ON t.context_id = c4.id
            LEFT JOIN server AS c5 ON t.context_id = c5.item_id
            WHERE t.context_id IS NOT NULL
            AND c1.item_id IS NULL AND c4.id IS NULL AND c5.item_id IS NULL
        '));

        foreach (array_chunk($itemIds, 1000) as $chunk) {
            foreach (['room', 'user'] as $table) {
                foreach (['creator_id', 'modifier_id'] as $column) {
                    $connection->executeStatement(
                        "UPDATE $table SET $column = NULL WHERE $column IN (:itemIds)",
                        ['itemIds' => $chunk],
                        ['itemIds' => ArrayParameterType::INTEGER],
                    );
                }
            }
            $connection->executeStatement(
                'DELETE FROM user WHERE item_id IN (:itemIds)',
                ['itemIds' => $chunk],
                ['itemIds' => ArrayParameterType::INTEGER],
            );
        }

        if ($io->isVerbose()) {
            $io->note(sprintf('Deleted %d profiles in vanished contexts', count($itemIds)));
        }
    }
}
