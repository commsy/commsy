<?
// create_table_links_items.php
// Erzeugt die link_items Tabelle


include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function createLinkItemTable () {
   global $test, $success, $error;

   $select_query  = "CREATE TABLE link_items ( item_id int(11) NOT NULL default '0',
                    room_id int(11) NOT NULL default '0',
                    campus_id int(11) NOT NULL default '0',
                    creator_id int(11) NOT NULL default '0',
                    deleter_id int(11) default NULL,
                    creation_date datetime NOT NULL default '0000-00-00 00:00:00',
                    deletion_date datetime default NULL,
                    modification_date datetime default NULL,
                    first_item_id int(11) NOT NULL default '0',
                    first_item_type varchar(15) default '',
                    second_item_id int(11) NOT NULL default '0',
                    second_item_type varchar(15) default '',
                    PRIMARY KEY  (item_id),
                    KEY campus_id (campus_id),
                    KEY room_id (room_id),
                    KEY creator_id (creator_id)) TYPE=MyISAM;";
   $success = select("select * from link_items where 1=2"); //table exists
   if($test  == FALSE) {
         if(!$success) {
            $success = select($select_query);
            if($success == FALSE) {
               echo "<br /><br /><b>mysql complains: ".$error."</b><br /><br />";
            }
         } else {
            echo "<br />table 'link_items' already exists";
         }
      }


}

createLinkItemTable();

?>