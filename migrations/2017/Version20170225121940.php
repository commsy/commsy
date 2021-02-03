<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170225121940 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->write('updating discussion articles');
        $this->updateDiscussionArticles('discussions', 'discussionarticles');

        $this->write('updating archived discussion articles');
        $this->updateDiscussionArticles('zzz_discussions', 'zzz_discussionarticles');

        $this->addSql('UPDATE discussions SET discussion_type = "threaded"');
        $this->addSql('UPDATE zzz_discussions SET discussion_type = "threaded"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function updateDiscussionArticles($dTable, $daTable)
    {
//        # CommSy 8
//
//        ## linear
//
//        first: ''
//        second: '1'
//        third: '1'
//
//        ## threaded
//
//        first: '1'
//        answer to first: '1.1001'
//        answer to answer to first: '1.1001.1001'
//        second answer to first: '1.1002'
//
//
//        # CommSy 9 old
//
//        first: '1'
//        second: '2'
//
//        # CommSy 9 new
//
//        first: '0001'
//        answer to first: '0001.0001'
//        answer to answer to first: '0001.0001.0001'
//        second answer to first: '0001.0002'
//        second: '0002'
//        answer to second: '0002.0001'

        $dQueryBuilder = $this->connection->createQueryBuilder();

        // find all discussions
        $qb = $dQueryBuilder
            ->select('d.item_id', 'd.discussion_type')
            ->from($dTable, 'd');
        $discussions = $qb->execute();

        foreach ($discussions as $discussion) {
            $discussionId = $discussion['item_id'];
            $discussionType = $discussion['discussion_type'];

            // find all discussion articles
            $daQueryBuilder = $this->connection->createQueryBuilder();
            $qb = $daQueryBuilder
                ->select('da.item_id', 'da.position')
                ->from($daTable, 'da')
                ->where('da.discussion_id = :discussionId')
                ->orderBy('da.item_id', 'ASC')
                ->setParameter(':discussionId', $discussionId);
            $articles = $qb->execute();

            if ($discussionType == 'simple') {
                $numericPosition = 1;

                foreach ($articles as $article) {
                    $articleId = $article['item_id'];

                    $newPosition = sprintf('%1$04d', $numericPosition++);
                    $this->connection->update($daTable, [
                        'position' => $newPosition,
                    ], [
                        'item_id' => $articleId,
                    ]);
                }
            } else {
                foreach ($articles as $article) {
                    $articleId = $article['item_id'];
                    $articlePosition = $article['position'];

                    $numDots = substr_count($articlePosition, '.');
                    $oldLevel = $numDots;

                    if ($oldLevel == 0) {
                        $newPosition = sprintf('%1$04d', $articlePosition);
                    } else {
                        $expPosition = explode('.', $articlePosition);
                        $newPosition = '';
                        for ($i = 1; $i <= $oldLevel; $i++) {
                            $currentPosition = $expPosition[$i];

                            $newCurrentPosition = $currentPosition % 1000;
                            $newPosition .= sprintf('%1$04d', $newCurrentPosition) . '.';
                        }

                        $newPosition = trim($newPosition, '.');
                    }

                    $this->connection->update($daTable, [
                        'position' => $newPosition,
                    ], [
                        'item_id' => $articleId,
                    ]);
                }
            }
        }
    }
}
