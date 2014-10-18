<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdUser extends MediabirdModel {
	var $name = "User";
	
	var $sessionTimeout = 60; //seconds after which a user will still be regarded online

	var $stateProperties = array(
		'cardId',
		'checkOut',
		'checkInId',
		'editing'
	);
	
	/**
	 * Validate session update
	 * This does not alter User records
	 */
	function validate($data,&$cache,&$reason) {
		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->stateProperties,true);
		
		$referredCardIds = array();
		
		//checkOut must be given as integer
		if(property_exists($data,'checkOut') && !is_int($data->checkOut)) {
			$validates = false;
		}
		
		if($validates && property_exists($data,'cardId')) {
			//cardId must be integer
			if(is_int($data->cardId)) {
				$checkOut = property_exists($data,'checkOut') && $data->checkOut===1;
				$cardId = $data->cardId;
				$referredCardIds []= $cardId;
			}
			else {
				$validates = false;
			}
		}
		
		if($validates && property_exists($data,'checkOut') && !property_exists($data,'cardId')) {
			$validates = false;
		}
		
		if($validates && property_exists($data,'editing')) {
			//editing must be integer
			if(is_int($data->editing)) {
				$editingCard = $data->editing=='1';
			}
			else {
				$validates = false;
			}
		}
		
		if($validates && property_exists($data,'checkInId')) {
			//id of card that is to be checked in must be integer
			if(is_int($data->checkInId)) {
				$checkInId = $data->checkInId;
				if(!in_array($checkInId,$referredCardIds)) {
					$referredCardIds []= $checkInId;
				}
			}
			else {
				$validates = false;
			}
		}
		
		//check if any cards are being referred (otherwise nothing to do!)
		if($validates && count($referredCardIds)==0) {
			$validates = false;
		}

		//check if user can access referred cards
		if($validates) {
			$minMask = ((isset($checkOut) && $checkOut) || isset($checkInId)) ? MediabirdTopicAccessConstants::allowEditingContent : MediabirdTopicAccessConstants::allowViewingCards;
			$select = "id IN (".join(",",$referredCardIds).") AND topic_id IN (
				SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask>=$minMask AND user_id=$this->userId
			)";
			
			$cardRecords = $this->db->getRecords(MediabirdConfig::tableName("Card",true),$select);
			
			if(!$cardRecords || count($cardRecords) != count($referredCardIds)) {
				$reason = MediabirdConstants::invalidPage;
				$validates = false;
			}
		}
		
		if($validates) {
			$validTopicIds = array();
			foreach($cardRecords as $cardRecord) {
				$validTopicIds []= intval($cardRecord->topic_id);
			}
			$cache['state']['validTopicIds'] = $validTopicIds;
			
			if(isset($cardId)) {
				$cache['state']['cardId'] = $cardId;
				$cache['state']['checkOut'] = $checkOut;
			}
			if(isset($checkInId)) {
				$cache['state']['checkInId'] = $checkInId;
			}
		}
		
		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}

		return $validates;
	}
	/**
	 * Perform session update
	 * This does not alter User records
	 */
	function update($data,$cache,&$changes) {
		extract($cache['state']);
		$okay = true;
		//saves which card is current card in session and check out if asked
		if(isset($cardId)) {
			if($checkOut) {
				$changes['checkOutResult'] = $this->controller->Content->checkContent($cardId,true,$changes);
			}

			if(isset($_SESSION['mb_db_sess_id'])) {
				$sessionId = intval($_SESSION['mb_db_sess_id']);
				$sessionRecord = (object)null;
				$sessionRecord->id = $sessionId;
				$sessionRecord->card_id = intval($_SESSION['mb_db_sess_card']);
				$sessionRecord->modified = intval($_SESSION['mb_db_sess_time']);
			}
			else {
				//search for session
				if(!$sessionRecord = $this->db->getRecord(MediabirdConfig::tableName('Session',true),"user_id=$this->userId")) {
					$sessionRecord = (object)null;
					$sessionRecord->user_id = $this->userId;
					if($sessionId = $this->db->insertRecord(MediabirdConfig::tableName('Session',true),$sessionRecord)) {
						$sessionRecord->id = $sessionId;
					}
					else {
						unset($sessionRecord);
					}
				}
			}

			$time = time();
				
			//check if card id has changed
			$needsRenewal = !property_exists($sessionRecord,"card_id") || $sessionRecord->card_id != $cardId;
			//check if session record needs to be refreshed in order not to get timed out
			$needsRefresh = $needsRenewal || ($sessionRecord->modified < $time - MediabirdConstants::sessionRefreshTime);

			//update session record if required
			if(isset($sessionRecord) && $needsRefresh) {

				if ($needsRenewal) {
					$sessionRecord->card_id = $cardId;
				}
				$sessionRecord->modified = $time;

				if(isset($editingCard)) {
					$sessionRecord->editing = $editingCard ? 1 : 0;
				}

				if($this->db->updateRecord(MediabirdConfig::tableName('Session',true),$sessionRecord)) {
					$_SESSION['mb_db_sess_id'] = intval($sessionRecord->id);
					$_SESSION['mb_db_sess_card'] = intval($sessionRecord->card_id);
					$_SESSION['mb_db_sess_time'] = intval($sessionRecord->modified);
				}
			}
		}

		//check in if asked
		if(isset($checkInId)) {
			$changes['checkInResult'] = $this->controller->Content->checkContent($checkInId,false,$changes);
		}
		
		return MediabirdConstants::processed;
	}

	/**
	 * Loads list of buddies
	 */
	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['users']['fromTime']) ? $data['users']['fromTime'] : 0;
		$fromTopic = isset($data['users']['fromTopic']) ? $data['users']['fromTopic'] : 0;
		$loadStatesOnly = isset($data['users']['states']) ? $data['users']['states'] : false;

		$loadedIds = isset($data['users']['loadedIds']) ? array_values($data['users']['loadedIds']) : array();
		$avoidIds = isset($data['users']['avoidIds']) ? array_values($data['users']['avoidIds']) : array();
		
		$ids = isset($data['users']['restrictIds']) ? array_values($data['users']['restrictIds']) : array();

		$users = array();

		//if we're loading states, we'll refer to session table which
		//refers to users using user_id rather than id itself
		$className = $loadStatesOnly ? "Session" : "User";
		$userIdColumn = $loadStatesOnly ? "user_id" : "id";

		//select users the current user knows
		$select = "$userIdColumn IN (SELECT user_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask>0 AND";

		//restrict to a given topic if desired
		if($fromTopic>0) {
			$select .= " topic_id=$fromTopic AND";
		}

		//select users from topics the current user has access to
		if($topics = $this->db->getRecords(MediabirdConfig::tableName('Right',true),"user_id=$this->userId AND mask>0",'','topic_id')) {
			$topicIds = array();
			foreach($topics as $topic) {
				if(!in_array($topic->topic_id,$topicIds)) {
					$topicIds []= $topic->topic_id;
				}
			}
			$select .= " topic_id IN (".join(",",$topicIds)."))";
		}
		else {
			//no known users
			return true;
		}

		//only load certain users if desired
		if(count($ids)>0) {
			$select = "$userIdColumn IN (".join(",",$ids).") AND ".$select;
		}
		
		if(count($avoidIds)>0) {
			$select = "$userIdColumn NOT IN (".join(",",$avoidIds).") AND ".$select;
		}
			
		$objects = array();
		if($records = $this->db->getRecords(MediabirdConfig::tableName($className,true),$select)) {
			$time = time();
			foreach($records as $record) {
				$obj = (object)null;
				$obj->id = intval($record->id);
					
				MediabirdUtility::arrayRemove($loadedIds,$obj->id);

				if($loadStatesOnly) {
					//will be saved as int
					$obj->modified = intval($record->modified); 
				}
				else {
					//will be saved as datetime or int
					$obj->modified = $this->db->timestamp($record->modified);
				}
					
				if($obj->modified > $fromTime){
					if($loadStatesOnly) {
						$obj->userId = intval($record->user_id);
						$obj->online = $obj->modified > ($time - $this->sessionTimeout);
						$obj->editing = intval($record->editing);
						$obj->cardId = $obj->online ? intval($record->card_id) : 0;
					}
					else {
						$obj->active = intval($record->active);
						$obj->name = $record->name;
						$obj->email = $record->email;
						$obj->lastLogin = $this->db->timestamp($record->last_login);
						$obj->picUrl = $record->pic_url;
					}
					$objects []= $obj;
				}
			}
		}
			
		if(count($objects)>0) {
			$results[strtolower($className).'s'] = $objects;
		}
		if(count($loadedIds)>0) {
			$results['removed'.$className.'Ids'] = $loadedIds;
		}
		return true;
	}

	/**
	 * Loads external users (CommSy, Moodle or others) into the $results array
	 * Note: the resulting items feature properties different from the ones ordinary Mediabird user objects do
	 * @param $results
	 * @return unknown_type
	 */
	function loadExternalUsers(&$results) {
		//collect external users that are mediabird members and return them if desired
		$externalUsers = array();
		if (method_exists($this->controller->auth, 'getKnownUsers')) {
			$externalUsersTemp = $this->controller->auth->getKnownUsers();

			foreach ($externalUsersTemp as $externalTemp) {
				$found = false;
				if (property_exists($externalTemp, 'mb_id') && isset($results['users'])) {
					foreach ($results['users'] as $user) {
						if ($user->id == $externalTemp->mb_id) {
							$found = true;
							break;
						}
					}
				}

				//only add external user if he/she is not known to current user
				if (!$found) {
					$externalUsers []= $externalTemp;
				}
			}

			$results['externalUsers'] = $externalUsers;
		}
		return true;
	}

	/**
	 * Converts a database user to an array representation
	 * @param $record
	 * @return Array
	 */
	function userFromRecord($record) {
		$user = array (
			'name'=>$record->name,
			'settings'=>$record->settings,
			'id'=>intval($record->id),
			'lastLogin'=>$this->db->timestamp($record->last_login)
		);
		if(isset($record->email)) {
			$user['email']=$record->email;
		}

		return $user;
	}
	
	var $settingParams = array(
		'notepad', //main view
		'trainer', //trainer
		'display'  //note display
	);
	
	function updateSettings($settings,&$results) {
		//fixme: validate this (define what is allowed in here first)
		$userRecord = (object)array(
			'id'=>$this->userId,
			'settings'=>json_encode($settings)
		);
		if($this->db->updateRecord(MediabirdConfig::tableName("User",true),$userRecord)) {
			$results['settings'] = $settings;
			return true;
		}
		else {
			return false;
		}
	}
}
?>
