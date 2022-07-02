<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630130300 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
         // Inserts items
        $this->addSql('Insert into items(
                        context_id,
                        type,
                        deleter_id,
                        deletion_date,
                        modification_date,
                        draft)
                    Select
                        1,
                        \'grouproom-temp\',
                        item_id,
                        null,
                        modification_date,
                        0
                    from labels
                    where type = \'group\'
                      and deletion_date is null
                      and  extras like \'%"GROUP_ROOM_ACTIVE";s:2:"-1"%\';');
        //Inserts rooms
        $this->addSql('Insert into  room (
                                        item_id,
                                        context_id,
                                        creator_id,
                                        modifier_id,
                                        deleter_id,
                                        creation_date,
                                        modification_date,
                                        deletion_date,
                                        title,
                                        extras,
                                        status,
                                        activity,
                                        type,
                                        public,
                                        is_open_for_guests,
                                        continuous,
                                        template,
                                        contact_persons,
                                        room_description,
                                        lastlogin,
                                        activity_state,
                                        activity_state_updated
                                    )
                                    Select i.item_id,
                                           1,
                                           l.creator_id,
                                           l.modifier_id,
                                           l.item_id,
                                           l.creation_date,
                                           l.modification_date,
                                           l.deletion_date,
                                           l.name,
                                           CONCAT(\'a:7:{s:20:"PROJECT_ROOM_ITEM_ID";i:\', l.context_id, \';s:15:"CHECKNEWMEMBERS";i:-1;s:8:"LANGUAGE";s:4:"user";s:18:"HTMLTEXTAREASTATUS";i:3;s:10:"RSS_STATUS";i:-1;s:13:"GROUP_ITEM_ID";i:\', l.item_id, \';s:12:"LOGOFILENAME";s:0:"";}\' ),
                                            1,
                                            0,
                                            \'grouproom-temp\',
                                            0,
                                            0,
                                            -1,
                                            -1,
                                           concat(u.firstname, \' \', u.lastname ),
                                            \'\',
                                            null,
                                            \'active\',
                                            null
                                    from labels l inner join items i on i.deleter_id = l.item_id and i.type = \'grouproom-temp\'
                                                  inner join user u on u.item_id = l.creator_id;');

        // Update to relation between labels and rooms
        $this->addSql('update  labels l
                            inner join room r on l.item_id = r.deleter_id
                            set l.extras = CONCAT(\'a:2:{s:17:"GROUP_ROOM_ACTIVE";s:1:"1";s:13:"GROUP_ROOM_ID";i:\', r.item_id, \';}\')
                            where r.type = \'grouproom-temp\';');

        // Inserts items to users
        $this->addSql('Insert into items(
                            context_id,
                            type,
                            deleter_id,
                            deletion_date,
                            modification_date,
                            draft)
                        Select
                            r.item_id,
                            \'user-temp\',
                            r.creator_id,
                            null,
                            r.modification_date,
                            0
                        from room r
                        where r.type = \'grouproom-temp\';');

        $this->addSql('insert into user(item_id,
                 context_id,
                 creator_id,
                 modifier_id,
                 deleter_id,
                 creation_date,
                 modification_date,
                 deletion_date,
                 user_id,
                 status,
                 is_contact,
                 firstname,
                 lastname,
                 email,
                 city,
                 lastlogin,
                 visible,
                 extras,
                 auth_source,
                 description,
                 expire_date,
                 use_portal_email)
                Select i.item_id,
                       i.context_id,
                       i.deleter_id,
                       i.deleter_id,
                       null,
                       i.modification_date,
                       i.modification_date,
                       null,
                       (Select user_id from user u where u.item_id =  i.deleter_id limit 1),
                       3,
                       0,
                       (Select firstname from user u where u.item_id =  i.deleter_id limit 1),
                       (Select lastname from user u where u.item_id =  i.deleter_id limit 1),
                       (Select email from user u where u.item_id =  i.deleter_id limit 1),
                       \'\',
                       null,
                       1,
                       \'a:5:{s:8:"LANGUAGE";s:2:"de";s:19:"AGB_ACCEPTANCE_DATE";s:0:"";s:15:"ACCOUNTWANTMAIL";s:3:"yes";s:12:"ROOMWANTMAIL";s:3:"yes";s:15:"PUBLISHWANTMAIL";s:3:"yes";}\',
                       101,
                       \'\',
                       null,
                       0
                from items  i where i.type =\'user-temp\';');

        //Updates temps
        $this->addSql('update items set deleter_id = null, type = \'grouproom\' where type = \'grouproom-temp\';');
        $this->addSql('update room set deleter_id = null, type = \'grouproom\' where type = \'grouproom-temp\';');
        $this->addSql('update items set deleter_id = null, type = type =\'user\' where type =\'user-temp\';');


    }

    public function down(Schema $schema): void
    {

    }
}
