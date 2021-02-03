<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200617133036 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update portal table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table portal drop column `type`;');
        $this->addSql('alter table portal drop column url;');
        $this->addSql('alter table portal add description_de text null after title;');
        $this->addSql('alter table portal add description_en text null after description_de;');
        $this->addSql('alter table portal add terms_de text null after description_en;');
        $this->addSql('alter table portal add terms_en text null after terms_de;');
        $this->addSql('alter table portal add logo_filename varchar(255) null after terms_en;');
    }

    public function postUp(Schema $schema): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $qb = $queryBuilder
            ->select('p.id', 'p.extras')
            ->from('portal', '`p`');
        $portals = $qb->execute();

        foreach ($portals as $portal) {
            $extras = DbConverter::convertToPHPValue($portal['extras']);

            $this->connection->update('portal', [
                'description_de' => $extras['DESCRIPTION']['de'] ?? '',
                'description_en' => $extras['DESCRIPTION']['en'] ?? '',
                'terms_de' => $extras['AGBTEXTARRAY']['DE'] ?? '',
                'terms_en' => $extras['AGBTEXTARRAY']['EN'] ?? '',
            ], [
                'id' => $portal['id'],
            ]);

            unset($extras['DESCRIPTION']);
            unset($extras['AGBTEXTARRAY']);
            unset($extras['CSCOLOR']);
            unset($extras['BGCOLOR']);
            unset($extras['TABLEHEADERCOLOR']);
            unset($extras['TABLEBODYCOLOR']);
            unset($extras['LINKCOLOR']);
            unset($extras['LINKBACKGROUNDCOLOR']);
            unset($extras['COLOR']);
            unset($extras['SHOWADS']);
            unset($extras['SHOWGOOGLEADS']);
            unset($extras['SHOWAMAZONADS']);
            unset($extras['SPONSORS']);
            unset($extras['SPONSORTITLE']);
            unset($extras['DESCRIPTION_WELLCOME_1']);
            unset($extras['DESCRIPTION_WELLCOME_2']);
            unset($extras['WIKIHOMELINK']);
            unset($extras['WIKIPORTALLINK']);
            unset($extras['WIKISKIN']);
            unset($extras['WIKITITLE']);
            unset($extras['WIKIADMINPW']);
            unset($extras['WIKIEDITPW']);
            unset($extras['WIKIREADPW']);
            unset($extras['WIKISHOWLOGIN']);
            unset($extras['WIKIEXISTS']);
            unset($extras['WIKILINK']);
            unset($extras['WIKIUSECOMMSYLOGIN']);
            unset($extras['WIKICOMMUNITYREADACCESS']);
            unset($extras['WIKICOMMUNITYWRITEACCESS']);
            unset($extras['WIKIPORTALREADACCESS']);
            unset($extras['WIKIENABLEFCKEDITOR']);
            unset($extras['WIKIENABLESITEMAP']);
            unset($extras['WIKIENABLESTATISTIC']);
            unset($extras['WIKIENABLESEARCH']);
            unset($extras['WIKIENABLERSS']);
            unset($extras['WIKIENABLECALENDAR']);
            unset($extras['WIKIENABLEGALLERY']);
            unset($extras['WIKIENABLENOTICE']);
            unset($extras['WIKIENABLEPDF']);
            unset($extras['WIKIENABLERATER']);
            unset($extras['WIKIENABLELISTCATEGORIES']);
            unset($extras['WIKINEWPAGETEMPLATE']);
            unset($extras['WIKIENABLESWF']);
            unset($extras['WIKIENABLEWMPLAYER']);
            unset($extras['WIKIENABLEQUICKTIME']);
            unset($extras['WIKIENABLEYOUTUBEGOOGLEVIMOEO']);
            unset($extras['WIKIENABLEDISCUSSION']);
            unset($extras['WIKIENABLEDISCUSSIONNOTIFICATION']);
            unset($extras['WIKIENABLEDISCUSSIONNOTIFICATIONGROUPS']);
            unset($extras['WIKI_SECTIONEDIT']);
            unset($extras['WIKI_SECTIONEDIT_HEADER']);
            unset($extras['CALDAV']);

            $this->connection->update('portal', [
                'extras' => sizeof($extras) === 0 ? null : serialize($extras),
            ], [
                'id' => $portal['id'],
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
