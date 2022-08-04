<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220804120000 extends AbstractMigration
{


    public function up(Schema $schema): void
    {
        $this->addSql('
            update items  i
            inner join labels l on i.item_id = l.item_id
            set i.activation_date = current_date, l.activation_date = current_date
            where l.type = \'group\' and i.type = \'label\'
            and (l.activation_date = \'9998-11-30 00:00:00.0\' or i.activation_date = \'9998-11-30 00:00:00.0\');  
        ');

        $this->addSql('
         update items  i
         inner join room r on i.item_id = r.item_id
         set i.activation_date = current_date, r.modification_date = current_date
         where r.type = \'grouproom\' and i.type = \'grouproom\'
         and (r.modification_date = \'9998-11-30 00:00:00.0\' or i.activation_date = \'9998-11-30 00:00:00.0\');
        ');
    }

    public function down(Schema $schema): void
    {

    }
}
