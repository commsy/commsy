<?php
if ( !empty($_SERVER["argv"][1]) ) {
   $room_id = $_SERVER["argv"][1];
   
   $is_private_room = false;
   if ( !empty($_SERVER["argv"][2]) ) {
   	if($_SERVER["argv"][2] == 'private'){
   		$is_private_room = true;
   	}
   }

   // setup commsy-environment
	include_once('etc/cs_constants.php');
	include_once('etc/cs_config.php');
	include_once('classes/cs_environment.php');
	$environment = new cs_environment();
	$environment->setCacheOff();
   
   if ( $is_private_room ) {
      $room_manager = $environment->getPrivateRoomManager();
   } else {
      $room_manager = $environment->getRoomManager();
   }
   
   $room_manager->setCacheOff();
   $room = $room_manager->getItem($room_id);
   echo(json_encode($room->runCron()));
}
?>