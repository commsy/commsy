#!/usr/bin/php
<?php
	if ($argc < 2 || !is_string($argv[1]) || in_array('-h', $argv)) {
?>

Das ist ein Kommandozeilenprogramm zum Umziehen von Räumen in CommSy.
Bitte geben Sie als Paramter den Pfad zu einer CSV-Datei mit einer Liste von Raum-ID's an.

  Benutzung:
  <?php echo $argv[0]; ?> <file> <oldPortalId> <newPortalId> <options>
  
  <file> Pfad zur CSV-Datei mit der Liste der Raum-ID's
  
  <options> Eine Kombination der folgenden Optionen:
  -exec						> Schreibt die Änderungen in die Datebank
  -d'<delimiter>'				> Delimiter (1 Zeichen)
  -e'<enclosure>'				> Enclosure (1 Zeichen)
  -h						> Hilfe
  
<?php
} else {
	mb_internal_encoding('UTF-8');

	require_once('ProgressBar.php');

	// ignore php notices
	error_reporting(E_ALL ^ E_NOTICE);
	
	function nl() {
		echo "\n";
	}

	function moveRoom(cs_environment $environment, cs_list $afterList, cs_room_item $roomItem, $exec = false, $newPortalId) {
		$linkedProjectedRoomItems = new cs_list();

		if ($exec) {
			if ($roomItem) {
				global $environment;

				$oldPortalId = $roomItem->getContextID();

				$portalManager = $environment->getPortalManager();
				$oldPortal = $portalManager->getItem($oldPortalId);

				$userManager = $environment->getUserManager();

				$roomList = new cs_list();
				$roomList->add($roomItem);

				// search for associated project rooms in list after this entry
				if ($roomItem->isCommunityRoom()) {
					$projectRoomList = $roomItem->getProjectRoomList();

					$afterListItem = $afterList->getFirst();
					while ($afterListItem) {
						if ($projectRoomList->inList($afterListItem)) {
							$linkedProjectedRoomItems->add($afterListItem);
						}

						$afterListItem = $afterList->getNext();
					}

					$roomList->addList($linkedProjectedRoomItems);
				} else if ($roomItem->isGrouproomActive()) {
					$groupManager = $environment->getGroupManager();
					$groupManager->setContextLimit($roomItem->getItemID());
					$groupManager->select();
					$groupList = $groupManager->get();

					if ($groupList->isNotEmpty()) {
						$groupItem = $groupList->getFirst();

						while($groupItem) {
							if ($groupItem->isGroupRoomActivated()) {
								$groupRoomItem = $groupItem->getGroupRoomItem();

								if (isset($groupRoomItem) && !empty($groupRoomItem)) {
									$roomList->add($groupRoomItem);
								}
							}

							$groupItem = $groupList->getNext();
						}
					}
				}

				$roomNameArray = array();
				$userArray = array();
				$authSourceArray = array();

				$roomListItem = $roomList->getFirst();
				while ($roomListItem) {

					$roomNameArray[$roomListItem->getItemID()] = $roomListItem->getTitle();

					$userManager->resetLimits();
					$userManager->setContextLimit($roomListItem->getItemID());
					$userManager->select();
					$userList = $userManager->get();

					if ($userList->isNotEmpty()) {
						$userItem = $userList->getFirst();

						while($userItem) {
							$authSourceArray[$userItem->getAuthSource()] = $userItem->getAuthSource();
							$userIdTest = $userItem->getUserID();

							if (!empty($userIdTest)) {
								$userRoomArray[$userItem->getUserID() . "__CS__" . $userItem->getAuthSource()] = $roomListItem->getItemID();

								if (empty($userArray[$userItem->getUserID()])) {
									$portalUserItem = $userItem->getRelatedCommSyUserItem();

									if (isset($portalUserItem)) {
										$userArray[$userItem->getUserID() . "__CS__" . $userItem->getAuthSource()] = $portalUserItem;
									}
								}

								$userIdTest = $userItem->getUserID();
							}

							$userItem = $userList->getNext();
						}
					}

					$roomListItem = $roomList->getNext();
				}

				$authSourceTranslationArray = array();
				$authSourceFindArray = array();
				$authSourceItemArray = array();

				$newPortal = $portalManager->getItem($newPortalId);
				$authSourceListNew = $newPortal->getAuthSourceList();

				foreach ($authSourceArray as $authSourceId) {
					$authSourceManager = $environment->getAuthSourceManager();

					$authSourceItemOld = $authSourceManager->getItem($authSourceId);
					$authSourceItemArray[$authSourceItemOld->getItemID()] = $authSourceItemOld;

					if (!$authSourceListNew->isEmpty()) {
						$authSourceItemNew = $authSourceListNew->getFirst();

						while ($authSourceItemNew) {
							$authSourceItemArray[$authSourceItemNew->getItemID()] = $authSourceItemNew;

							if ($authSourceItemOld->isCommSyDefault() && $authSourceItemNew->isCommSyDefault()) {
								$authSourceTranslationArray[$authSourceItemOld->getItemID()] = $authSourceItemNew->getItemID();
							}

							$authSourceItemNew = $authSourceListNew->getNext();
						}
					}
				}

				$authSourceNotTranslationArray = array();
				foreach ($authSourceArray as $authSourceId) {
					if (!array_key_exists($authSourceId, $authSourceTranslationArray)) {
						$authSourceNotTranslationArray[] = $authSourceId;
					}
				}

				$authSourceManager = $environment->getAuthSourceManager();
				foreach ($authSourceNotTranslationArray as $authSourceId) {
					$authSourceItemOld = $authSourceManager->getItem($authSourceId);
					
					$authSourceItemNew = clone $authSourceItemOld;
					$authSourceItemNew->setItemID('');
					$authSourceItemNew->setContextID($newPortalId);
					$authSourceItemNew->save();

					$authSourceTranslationArray[$authSourceItemOld->getItemID()] = $authSourceItemNew->getItemID();
					$authSourceItemArray[$authSourceItemNew->getItemID()] = $authSourceItemNew;
				}

				$userArrayAll = $userArray;
				$userArrayNew = array();

				$failure = false;
				$userChangeArray = array();
				foreach ($userArrayAll as $key => $userItem) {
					// does the user exist on current portal?
					$authentication = $environment->getAuthenticationObject();

					$userId = $userItem->getUserID();
					$authSource = $userItem->getAuthSource();
					$first = true;
					$go = true;

					while ($go) {
						$userManager->resetLimits();
						$userManager->setContextLimit($newPortalId);
						$userManager->setAuthSourceLimit($authSourceTranslationArray[$authSource]);
						$userManager->setUserIDLimit($userId);
						$userManager->select();
						$userList = $userManager->get();

						// commsy auth source, user id already exists
						if ($userList->isNotEmpty() && $userList->getCount() == 1) {
							$userItem2 = $userList->getFirst();

							// email is equal
							if ($userItem2->getEmail() == $userItem->getEmail()) {
								unset($userArray[$userItem->getUserID() . "__CS__" . $userItem->getAuthSource()]);

								if ($userItem->getUserID() != $userId) {
									$userChangeArray[$userItem->getUserID() . "__CS__" . $authSourceTranslationArray[$authSource]] = $userId;
								} else {
									$userArrayNoChange[$userItem->getUserID() . "__CS__" . $authSourceTranslationArray[$authSource]] = $userItem;
								}

								$go = false;
							} else {
								// generate new user id
								if ($first) {
									$first = false;
									$userId .= '1';
								} else {
									$count = $userId{mb_strlen($userId) - 1};
									$count = (int) $count;
									$count++;

									$userId = mb_substr($userId, 0, mb_strlen($userId) - 1);
									$userId .= $count;
								}
							}
						} elseif ($userList->isNotEmpty() && $userList->getCount() > 1) {
							include_once('functions/error_functions.php');
							trigger_error('ERROR: multiple user id ' . $userId . ' for one portal',E_USER_WARNING);
							$go = false;
							$failure = true;
						} else {
							// find free user id
							if ($userItem->getUserID() != $userId) {
								$userChangeArray[$userItem->getUserID() . "__CS__" . $authSourceTranslationArray[$authSource]] = $userId;
							} else {
								$userArrayNoChange[$userItem->getUserID() . "__CS__" . $authSourceTranslationArray[$authSource]] = $userItem;
							}

							$go = false;
						}
					}
				}

				if ($failure) {
					die("ERROR");
				}

				// commsy auth source
				// copy auth (user_id and password) and user (normal information) items
				foreach ($userArray as $key => $userItem) {
					$keyArray = explode("__CS__", $key);

					$userIdKey = $keyArray[0];
					$authSourceKey = $keyArray[1]; // old auth source

					$authManager = $authentication->getAuthManager($userItem->getAuthSource());
					$authManager->setContextLimit($oldPortalId);
					$authItemOld = $authManager->getItem($userItem->getUserID());

					if (!empty($authItemOld)) {
						$authItemNew = clone $authItemOld;
						$authItemNew->setPortalID($newPortalId);
						$authItemNew->setAuthSourceID($authSourceTranslationArray[$authSourceKey]);

						if (!empty($userChangeArray[$userIdKey . "__CS__" . $authSourceTranslationArray[$authSourceKey]])) {
							$authItemNew->setUserID($userChangeArray[$userIdKey . "__CS__" . $authSourceTranslationArray[$authSourceKey]]);
						}

						$authManager = $authentication->getAuthManager($authSourceTranslationArray[$authSourceKey]);
						$authManager->setContextLimit($newPortalId);
						$userIdAuthNew = $authItemNew->getUserID();

						if (!empty($userIdAuthNew)) {
							$authManager->save($authItemNew);
						}
					}

					unset ($userIdAuthNew);

					$userItemNew = $userItem->cloneData();
					$userItemNew->setContextID($newPortalId);

					$tempUser = $userManager->getItem($userItem->getCreatorID());
					$userItemNew->setCreatorItem($tempUser);

					if (!empty($userChangeArray[$userIdKey . "__CS__" . $authSourceTranslationArray[$authSourceKey]])) {
						$userItemNew->setUserID($userChangeArray[$userIdKey . "__CS__" . $authSourceTranslationArray[$authSourceKey]]);
					}

					$userIdUserNew = $userItemNew->getUserID();

					$userItemNew->setAuthSource($authSourceTranslationArray[$authSourceKey]);

					if (!empty($userIdUserNew)) {
						$userItemNew->save();
						$userItemNew->setCreatorID2ItemID();
					}
				}

				// external auth sources
				foreach ($userArrayNew as $key => $userItem) {
					$keyArray = explode("__CS__", $key);

					$userIdKey = $keyArray[0];
					$authSourceKey = $keyArray[1];

					$userItemnew = $userItem->cloneData();
					$userItemNew->setContextID($newPortalId);

					$tempUser = $userManager->getItem($userItem->getCreatorID());
					$userItemNew->setCreatorItem($tempUser);

					$userIdUserNew = $userItemNew->getUserID();

					$userItemNew->setAuthSource($authSourceKey);

					if (!empty($userIdUserNew)) {
						$userItemNew->save();
						$userItemNew->setCreatorID2ItemID();
					}
				}

				// change user ids of user in rooms to move
				// and cahnge auth source of user in rooms to move
				$roomListItem = $roomList->getFirst();
				while ($roomListItem) {
					$userManager = $environment->getUserManager();
					$userManager->resetLimits();
					$userManager->setContextLimit($roomListItem->getItemID());
					$userManager->select();

					$userList = $userManager->get();
					if ($userList->isNotEmpty()) {
						$userItem = $userList->getFirst();

						while ($userItem) {
							$userIdTest = $userItem->getUserID();

							if (!empty($userIdTest) && !empty($userChangeArray[$userIdTest . "__CS__" . $authSourceTranslationArray[$userItem->getAuthSource()]])) {
								$userItem->setUserID($userChangeArray[$userIdTest . "__CS__" . $authSourceTranslationArray[$userItem->getAuthSource()]]);
							}

							$newAuthSourceForUser = $authSourceTranslationArray[$userItem->getAuthSource()];
							$userItem->setAuthSource($newAuthSourceForUser);

							$userItem->setChangeModificationOnSave(false);
							$userItem->setSaveWithoutLinkModifier();
							$userItem->save();

							$userItem = $userList->getNext();
						}
					}

					// delete old links from community room to project rooms
					// before saving on new potal
					if ($linkedProjectedRoomItems->isEmpty() && $roomListItem->isCommunityRoom()) {
						$roomListItem->setProjectListByID(array());
						$roomListItem->save();
					}

					// move files from old portal folder to new portal folder
					$oldContext = $roomListItem->getContextID();
					$newContext = $newPortalId;
					if ($oldContext != $newContext) {
						$discManager = $environment->getDiscManager();
						$discManager->moveFiles($roomListItem->getItemID(), $oldContext, $newContext);
					}

					$roomListItem->setContextID($newPortalId);

					// set link between project and community room
					if ($linkedProjectedRoomItems->isNotEmpty() && $roomListItem->isProjectRoom()) {
						$tempArray = array();
						$tempArray[] = $roomItem->getItemID();
						$roomListItem->setCommunityListByID($tempArray);
					}

					// save room with new context id
					$roomListItem->save();
					echo ProgressBar::next();

					$roomListItem = $roomList->getNext();
				}
			}
		} else {
			usleep(1000);
			echo ProgressBar::next();
		}

		return $linkedProjectedRoomItems;
	}
	
	nl();
	
	$fileName = $argv[1];
	$oldPortalId = $argv[2];
	$newPortalId = $argv[3];
	$delimiter = ',';
	$enclosure = '"';
	$exec = false;
	foreach ($argv as $arg) {
		if ($arg == '-exec') {
			$exec = true;
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

	if (!isset($oldPortalId)) {
		echo "Sie müssen die Id des alten Portals angeben!"; nl();
		exit;
	}

	if (!isset($newPortalId)) {
		echo "Sie müssen die Id des neuen Portals angeben!"; nl();
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
	$environment->setCurrentContextID($newPortalId);
	
	$db_connector = $environment->getDBConnector();

	global $c_send_email;
	$c_send_email = false;
	
	// get all rooms
	echo "Lade Rauminformationen..."; nl(); nl();
	$roomManager = $environment->getRoomManager();
	$roomManager->setContextLimit($oldPortalId);
	$roomManager->select();
	$roomList = $roomManager->get();

	// determine rooms to move
	$moveList = new cs_list();
	$moveIdArray = array();
	$roomListItem = $roomList->getFirst();
	while ($roomListItem) {
		if (in_array($roomListItem->getItemID(), $csvContent)) {
			// ensure community rooms are listed before project rooms
			if ($roomListItem->isCommunityRoom()) {
				$moveList->reverse();
				$moveList->add($roomListItem);
				$moveList->reverse();
			} else {
				$moveList->add($roomListItem);
			}

			$moveIdArray[] = $roomListItem->getItemID();
		}
		
		$roomListItem = $roomList->getNext();
	}
	
	$numRoomsMoved = $moveList->getCount();

	// check for rooms, that were unable to find
	if ($numRoomsMoved !== sizeof($csvContent)) {
		$diff = array_diff($csvContent, $moveIdArray);

		echo "Die folgenden Raum ID's konnten nicht gefunden werden. Ggf. sind diese Räume gelöscht."; nl();
		foreach ($diff as $notFoundId) {
			echo "- " . $notFoundId; nl();
		}
		nl();
	}

	
	echo "Die gelisteten $numRoomsMoved Räume sollen umgezogen werden!"; nl();
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
	
	echo ProgressBar::start($numRoomsMoved, "Räume werden umgezogen...");
	
	global $c_send_email;
	$c_send_email = false;
	
	$moveItem = $moveList->getFirst();
	$index = 0;
	while ($moveItem) {
		$afterList = new cs_list();
		if (($index + 1) < $moveList->getCount()) {
			$afterList = $moveList->getSubList($index + 1, $moveList->getCount() - ($index + 1));
		}

		$handledProjectList = moveRoom($environment, $afterList, $moveItem, $exec, $newPortalId);

		if ($handledProjectList->isNotEmpty()) {
			$handledProjectItem = $handledProjectList->getFirst();

			while ($handledProjectItem) {
				$moveList->removeElement($handledProjectItem);

				$handledProjectItem = $handledProjectList->getNext();
			}
		}
		
		$moveItem = $moveList->getNext();
		$index++;
	}
	
	nl(); nl();
	
	echo "Fertig!"; nl();
}