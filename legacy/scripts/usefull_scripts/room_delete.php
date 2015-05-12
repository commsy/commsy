#!/usr/bin/php
<?php
	if ($argc < 2 || !is_string($argv[1]) || in_array('-h', $argv)) {
?>

Das ist ein Kommandozeilenprogramm zum Löschen von Räumen in CommSy. Als 
Bitte geben Sie als Paramter den Pfad zu einer CSV-Datei mit einer Liste von Raum-ID's an.

  Benutzung:
  <?php echo $argv[0]; ?> <file> <options>
  
  <file> Pfad zur CSV-Datei mit der Liste der Raum-ID's
  
  <options> Eine Kombination der folgenden Optionen:
  -exec						> Schreibt die Änderungen in die Datebank
  -d'<delimiter>'				> Delimiter (1 Zeichen)
  -e'<enclosure>'				> Enclosure (1 Zeichen)
  -keep						> In diesem Modus bleiben die gelisteten Räume erhalten, alle anderen werden gelöscht.
  						Im Normalfall werden alle gelisteten Räume gelöscht.
  -h						> Hilfe
  
<?php
} else {
	mb_internal_encoding('UTF-8');
	
	function nl() {
		echo "\n";
	}

	function deleteRoom(cs_environment $environment, cs_room_item $roomItem, $exec = false) {
		if ($exec) {
			if ($roomItem) {
				global $environment;
				$contextId = $roomItem->getContextID();
				$environment->setCurrentContextID($contextId);
							
				if ($roomItem->isCommunityRoom()) {
					$communityManager = $environment->getCommunityManager();
					$communityRoomItem = $communityManager->getItem($roomItem->getItemID());
					
					if ($communityRoomItem) {
						$communityRoomItem->delete();
					}
				} else if ($roomItem->isProjectRoom()) {
					$projectManager = $environment->getProjectManager();
					$projectRoomItem = $projectManager->getItem($roomItem->getItemID());
					
					if ($projectRoomItem) {
						$projectRoomItem->delete();
					}
				}
			}
		} else {
			usleep(1000);
		}
	}
	
	nl();
	
	$fileName = $argv[1];
	$delimiter = ',';
	$enclosure = '"';
	$exec = false;
	$keep = false;
	foreach ($argv as $arg) {
		if ($arg == '-exec') {
			$exec = true;
			continue;
		}
		
		if ($arg == '-keep') {
			$keep = true;
			continue;
		}
		
		if (mb_substr($arg, 0, 2) == '-d') {
			$delimiter = mb_substr($arg, 2);
			continue;
		}
		
		if (mb_substr($arg, 0, 2) == '-e') {
			$enclosure = mb_substr($arg, 2);
			continue;
		}
	}
	
	if (!file_exists($fileName)) {
		echo "Datei $fileName nicht gefunden!"; nl();
		exit;
	}
	
	$fileHandle = fopen($fileName, 'r');
	$csvContent = fgetcsv($fileHandle, 0, $delimiter, $enclosure);
	if (!$csvContent) {
		echo "CSV-Datei konnte nicht geladen werden!"; nl();
		exit;
	}
	fclose($fileHandle);
	
	chdir('../../');

	include_once('etc/cs_config.php');
	include_once('classes/cs_environment.php');
	global $environment;
	$environment = new cs_environment();
	
	$db_connector = $environment->getDBConnector();
	
	$numRooms = sizeof($csvContent);
	
	// get all rooms
	echo "Lade Rauminformationen..."; nl();
	$roomManager = $environment->getRoomManager();
	$roomManager->select();
	$roomList = $roomManager->get();
	
	if ($keep) {
		echo "Die gelisteten $numRooms Räume sollen erhalten bleiben. Alle anderen sollen gelöscht werden!"; nl();
	} else {
		echo "Die gelisteten $numRooms Räume sollen gelöscht werden!"; nl();
	}
	
	echo "korrekt? (ja): ";
	
	if(!$stdIn = fopen("php://stdin","r")) {
		echo "Fehler!"; nl();
		exit();
	}
	
	$input = trim(fgets($stdIn));
	fclose($stdIn);
	
	if ($input !== "ja") {
		echo "Abbruch!"; nl();
		exit();
	}
	
	nl();
	
	if (!$exec) {
		echo "DEMO:"; nl();
	}
	
	// determine rooms to delete
	$deleteList = new cs_list();
	$roomListItem = $roomList->getFirst();
	while ($roomListItem) {
		if ($keep) {
			if (!in_array($roomListItem->getItemID(), $csvContent)) {
				$deleteList->add($roomListItem);
			}
		} else {
			if (in_array($roomListItem->getItemID(), $csvContent)) {
				$deleteList->add($roomListItem);
			}
		}
		
		$roomListItem = $roomList->getNext();
	}
	
	$numRoomsDelete = $deleteList->getCount();
	
	require_once('ProgressBar.php');
	echo ProgressBar::start($numRoomsDelete, "Räume werden gelöscht...");
	
	global $c_send_email;
	$c_send_email = false;
	
	$deleteItem = $deleteList->getFirst();
	while ($deleteItem) {
		echo ProgressBar::next();
		deleteRoom($environment, $deleteItem, $exec);
		
		$deleteItem = $deleteList->getNext();
	}
	
	nl(); nl();
	
	echo "Fertig!"; nl();
}