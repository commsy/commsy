<?PHP
include_once('../../etc/cs_config.php');
$db['database'] = $db['normal']['database'];
$db['host'] = $db['normal']['host'];
$db['user'] = $db['normal']['user'];
$db['password'] = $db['normal']['password'];

if(empty($db['database'])) {
   echo "Error: No Database defined. You have to configure this script in file ".__FILE__." before executing it.";
   break;
}

##########


// connect to db
mysql_connect($db['host'], $db['user'], $db['password']);
mysql_select_db($db['database']);

   $query[] = 'DROP TABLE IF EXISTS chat';
   $query[] = 'CREATE TABLE chat (
  creator_id int(11) default NULL,
  creation_date datetime NOT NULL default "0000-00-00 00:00:00",
  message text NOT NULL,
  room_id int(11) NOT NULL default "0",
  campus_id int(11) NOT NULL default "0",
  creator_name varchar(100) NOT NULL default "?",
  KEY creator_id (creator_id),
  KEY room_id (room_id),
  KEY campus_id (campus_id),
  KEY creation_date (creation_date)
) TYPE=MyISAM';

$query[] = 'DROP TABLE IF EXISTS chat_session';
$query[] = 'CREATE TABLE chat_session (
  user_id varchar(15) NOT NULL default "",
  campus_id int(11) NOT NULL default "0",
  room_id int(11) default NULL,
  KEY  (user_id, campus_id, room_id)
) TYPE=MyISAM';
$error = '';
foreach($query as $q) {
   $result = mysql_query($q);
   if(!$result) {
      $error = mysql_error();
      break;
   }
}

if(!empty($error)) {
   echo $error;
} else {
   echo "success";
}
?>