#!/usr/bin/php
<?php
	if ($argc != 2 || !is_numeric($argv[1]) || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

Das ist ein Kommandozeilenprogramm zur Generierung von Statistiken.
Bitte geben Sie als Paramter die eindeutige ID eines Gemeinschaftsraums ein.

  Benutzung:
  <?php echo $argv[0]; ?> <option>

  <option> muss die ID eines Gemeinschaftsraumes sein, für
  den Sie die Statistik generieren möchten.
  Mit den Optionen --help, -help, -h oder -? bekommen Sie diese Hilfe.

<?php
} else {
	function nl()
	{
		echo "\n";
	}

	function getRoomInformation($portalId)
	{
		$query = "SELECT * FROM "
	}

	chdir('../../');
	
	mb_internal_encoding('UTF-8');

	include_once('etc/cs_config.php');
	$DB_Name     = $db['normal']['database'];
	$DB_Hostname = $db['normal']['host'];
	$DB_Username = $db['normal']['user'];
	$DB_Password = $db['normal']['password'];

	ini_set('mysql.connect_timeout', -1);
	ini_set('default_socket_timeout', -1);

	mysql_connect($DB_Hostname, $DB_Username, $DB_Password);
	mysql_select_db($DB_Name);
	mysql_set_charset('utf8');
	mysql_query("SET NAMES 'utf8'");

	$communityRoomId = $argv[1];

	echo "Generiere Statistik für Gemeinschaftsraum mit ID " . $communityRoomId; nl();
	nl();

	$query = "SELECT item_id FROM room WHERE room.context_id = $communityRoomId";
	$res = mysql_query($query);
	$portalIds = array();
	while ($row = mysql_fetch_row($res)) {
		$portalIds[] = $row['item_id'];
	}

	foreach($portalIds as $portalId) {
		$portalInformation = getRoomInformation($portalId);
	}
}