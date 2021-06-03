<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DBFixRelationsCommand extends Command
{
    protected static $defaultName = 'commsy:db:fix-relations';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $conn = $this->entityManager->getConnection();

        $this->fixCreator($io, $conn);
        $this->fixModifier($io, $conn);
        $this->fixContext($io, $conn);

        $io->success('Done!');

        return 0;
    }

    private function runQuery($io, $conn, $query)
    {
        $stmt = $conn->prepare($query);

        $io->note('Executing: ' . trim($query));
        $stmt->execute();
    }

    private function fixCreator($io, $conn)
    {
        $tablesWithCreator = ['annotations', 'announcement', 'assessments', 'auth_source', 'dates', 'discussionarticles',
        'discussions', 'files', 'labels', 'link_items', 'materials', 'portfolio', 'room', 'room_privat', 'section',
        'server', 'step', 'tag', 'tag2tag', 'tasks', 'todos', 'user'];

        foreach ($tablesWithCreator as $tableWithCreator) {
            $this->runQuery($io, $conn, "
                UPDATE $tableWithCreator AS t LEFT JOIN user AS u ON t.creator_id = u.item_id SET t.creator_id = NULL
                WHERE t.creator_id IS NOT NULL AND u.item_id IS NULL;
            ");
        }
    }

    private function fixModifier($io, $conn)
    {
        $tablesWithModifier = ['annotations', 'announcement', 'auth_source', 'dates', 'discussionarticles',
            'discussions', 'labels', 'materials', 'portfolio', 'room', 'room_privat', 'section',
            'server', 'step', 'tag', 'tag2tag', 'todos', 'user'];

        foreach ($tablesWithModifier as $tableWithModifier) {
            $this->runQuery($io, $conn, "
                UPDATE $tableWithModifier AS t LEFT JOIN user AS u ON t.modifier_id = u.item_id SET t.modifier_id = NULL
                WHERE t.modifier_id IS NOT NULL AND u.item_id IS NULL;
            ");
        }

        $this->runQuery($io, $conn, "
            DELETE t FROM link_modifier_item AS t LEFT JOIN user AS u ON t.modifier_id = u.item_id
            WHERE t.modifier_id IS NOT NULL AND u.item_id IS NULL;
        ");
    }

    private function fixContext($io, $conn)
    {
        $tablesWithContext = ['annotations', 'announcement', 'assessments', 'auth_source', 'calendars', 'dates',
            'discussionarticles', 'discussions', 'files', 'invitations', 'labels', 'licenses', 'link_items',
            'links', 'materials', 'room', 'room_privat', 'section', 'step', 'tag', 'tag2tag', 'tasks',
            'terms', 'todos', 'translation',  'user'];

        foreach ($tablesWithContext as $tableWithContext) {
            $this->runQuery($io, $conn, "
                DELETE t FROM $tableWithContext AS t
                LEFT JOIN room AS c1 ON t.context_id = c1.item_id AND c1.deleter_id IS NULL AND c1.deletion_date IS NULL
                LEFT JOIN room_privat AS c2 ON t.context_id = c2.item_id AND c2.deleter_id IS NULL AND c2.deletion_date IS NULL
                LEFT JOIN portal AS c3 ON t.context_id = c3.item_id AND c3.deleter_id IS NULL AND c3.deletion_date IS NULL
                LEFT JOIN server AS c4 ON t.context_id = c4.item_id AND c4.deleter_id IS NULL AND c4.deletion_date IS NULL
                WHERE t.context_id IS NOT NULL
                AND c1.item_id IS NULL AND c2.item_id IS NULL AND c3.item_id IS NULL AND c4.item_id IS NULL;
            ");
        }

        $this->runQuery($io, $conn, "
            DELETE t FROM items AS t
            LEFT JOIN room AS c1 ON t.context_id = c1.item_id AND c1.deleter_id IS NULL AND c1.deletion_date IS NULL
            LEFT JOIN room_privat AS c2 ON t.context_id = c2.item_id AND c2.deleter_id IS NULL AND c2.deletion_date IS NULL
            LEFT JOIN portal AS c3 ON t.context_id = c3.item_id AND c3.deleter_id IS NULL AND c3.deletion_date IS NULL
            LEFT JOIN server AS c4 ON t.context_id = c4.item_id AND c4.deleter_id IS NULL AND c4.deletion_date IS NULL
            WHERE t.context_id IS NOT NULL AND t.type != 'server'
            AND c1.item_id IS NULL AND c2.item_id IS NULL AND c3.item_id IS NULL AND c4.item_id IS NULL;
        ");
    }
}
