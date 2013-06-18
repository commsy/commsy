<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2012 Dr. Iver Jackewitz
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

// headline
$this->_flushHeadline('db: clean user in rooms from default server email address and hastochangeemail-flag');

$success = true;

$server_item = $this->_environment->getServerItem();
$server_mail = $server_item->getDefaultSenderAddress();

if ( !empty($server_mail) ) {
	$sql = "SELECT item_id, context_id, user_id from user WHERE email = '".$server_mail."'";
	$result = $this->_select($sql);
	if ( !empty($result) ) {
		$user_manager = $this->_environment->getUserManager();
		$count = count($result);
		$this->_initProgressBar($count);
		$count_cannot = 0;
		foreach ( $result as $row ) {
			if ( !empty($row['item_id']) ) {
				$user_item = $user_manager->getItem($row['item_id']);
				if ( isset($user_item) ) {
					$user_portal_item = $user_item->getRelatedCommSyUserItem();
					if ( isset($user_portal_item) ) {
						$portal_mail = $user_portal_item->getEmail();
						if ( $portal_mail != $user_item->getEmail() ) {
							$user_item->setEmail($portal_mail);
							$user_item->unsetHasToChangeEmail();
							$user_item->setChangeModificationOnSave(false);
							$user_item->save();
						} else {
							$count_cannot++;
						}
					}
				}
			}
			$this->_updateProgressBar($count);
		}
	}
}

if ( $count_cannot > 0 ) {
   $this->_flushHTML(BRLF);
	$this->_flushHTML('can not clean email at '.$count_cannot.' accounts');
}

$this->_flushHTML(BRLF);
?>