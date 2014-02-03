<?php
/*
 * 	Copyright (C) 2010 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdCheck extends MediabirdModel {
	const modelName = "Check";
	
	const changeTypeCheckPending = "check_pending";
	const changeTypeCheckConfirmed = "check_confirmed";
	
	var $name = self::modelName;
	
	var $updateParams = array(
		'check',
		'checkStatus'
	);
	var $checkProperties = array(
		'userIds'
	);
	var $checkStatusProperties = array(
		'id',
		'status'
	);
	var $validStatusCodes = array(
		0, //unchecked
		1 //checked
	);
	
	function validate($data,&$cache,&$reason) {
		$validates = 
			is_object($data) &&
			MediabirdUtility::checkKeyset($data,$this->updateParams,true);
		
		/* case 1: a new check is to be created!
		 * -> check is an object featuring an array of user ids check->userIds
		 * 
		 * case 2: a state is to be changed
		 * -> checkStatus is an object featuring state Id and new State
		 */
		
		//case 1 and 2 are exclusive, so disallow specifying checkStatus and check at the same time
		if($validates && property_exists($data,'check') && property_exists($data,'checkStatus')) {
			$validates = false;
		}
		
		//case 1: validate check
	  	if($validates && property_exists($data,'check')) {
	  		$check = $data->check;
	  		
	  		$validates = 
	  			MediabirdUtility::checkKeyset($check,$this->checkProperties,false) &&
	  			is_array($check->userIds) &&
	  			MediabirdUtility::checkUnique($check->userIds);
	  		
	  		//check if all user ids are integer
	  		if($validates) {
		  		foreach($check->userIds as $userId) {
		  			if(!is_int($userId)) {
		  				$validates = false;
		  				break;
		  			}
		  		}
	  		}
	  		
	  		//check if at least one user id was given
	  		if($validates && sizeof($check->userIds)==0) {
	  			$validates = false;
	  		}
	  			
	  		//count matching records in DB
	  		if($validates) {
	  			//select users that are to be checked
	  			$select = "id IN (".join(",",$check->userIds).") AND ";
	  			
	  			//select users the current user knows
				$select .= "id IN (SELECT user_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask>0 AND";
		
				$count = 0;
				
				//select users from topics the current user has access to
				if($topics = $this->db->getRecords(MediabirdConfig::tableName('Right',true),"user_id=$this->userId AND mask>0",'','topic_id')) {
					$topicIds = array();
					foreach($topics as $topic) {
						if(!in_array($topic->topic_id,$topicIds)) {
							$topicIds []= $topic->topic_id;
						}
					}
					$select .= " topic_id IN (".join(",",$topicIds)."))";
					
					$count = $this->db->countRecords(MediabirdConfig::tableName('User',true),$select);
				}
				else {
					$validates = false;
				}
				
	  			if($validates && $count!=sizeof($check->userIds)) {
	  				$validates = false;
	  			}
	  		}
	  	}
	  	
	  	//case 2: validate checkStatus
	  	if($validates && property_exists($data,'checkStatus')) {
	  		$checkStatus = $data->checkStatus;

	  		$validates = 
	  			MediabirdUtility::checkKeyset($checkStatus,$this->checkStatusProperties,false);
	  		
	  		//check if status is valid
	  		if($validates && !in_array($checkStatus->status,$this->validStatusCodes,true)) {
	  			$validates = false;
	  		}
	  			
	  		//check if state belongs to current user
			$select = "id=$checkStatus->id AND user_id=$this->userId";
	  		if($validates && $this->db->countRecords(MediabirdConfig::tableName('CheckStatus',true),$select)!=1) {
	  			$validates = false;
	  		}
	  	}
	  	
	  	if(!$validates && !isset($reason)) {
	  		$reason = MediabirdConstants::invalidData;
	  	}
	  	 
	  	return $validates;
	}

	function update($data,$cache,&$changes) {
		$time = time();
		if(property_exists($data,'check')) {
			//CREATE CHECK
			
			//create check record
			$checkRecord = (object)array(
				'user_id'=>$this->userId
			);
			//set modification and creation time
			$checkRecord->modified = $checkRecord->created = $this->db->datetime($time);
			
			//set modifier
			$checkRecord->modifier = $this->userId;
			
			//insert into database
			$checkRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Check",true),$checkRecord);
			
			//create object
			$check = (object)array(
				'id'=>intval($checkRecord->id),
				'userId'=>$checkRecord->user_id,
				'modified'=>$time,
				'modifier'=>$checkRecord->modifier,
				'checkStatusses'=>array() //holds check state objects
			);
			
			//check if succeeded
			if($checkRecord->id) {
				//create check state record
				$checkStatusRecordTemplate = (object)array(
					'check_id'=>$checkRecord->id,
					'status_code'=>$this->validStatusCodes[0]
				);
				
				//set modification date
				$checkStatusRecordTemplate->modified = $checkStatusRecordTemplate->created = $this->db->datetime($time);
				
				//insert check state records for each user id
				foreach($data->check->userIds as $userId) {
					//clone record
					$checkStatusRecord = clone $checkStatusRecordTemplate;
					
					$checkStatusRecord->user_id = $userId;
					
					//insert record
					if($checkStatusRecord->id = $this->db->insertRecord(MediabirdConfig::tableName('CheckStatus',true),$checkStatusRecord)) {

						//store object
						$check->checkStatusses []= (object)array(
							'id'=>intval($checkStatusRecord->id),
							'userId'=>intval($checkStatusRecord->user_id),
							'status'=>$checkStatusRecord->status_code,
							'modified'=>$time			
						);
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
				
				//return results
				$changes['checks'] = array(
					$check
				);
			}
			else {
				return MediabirdConstants::serverError;
			}
		}
		
		if(property_exists($data,'checkStatus')) {
			//UPDATE CHECKSTATUS
			
			//get record first, then update record and finally update check!
			
			$select = "id=".$data->checkStatus->id;
			
			if($checkStatusRecord = $this->db->getRecord(MediabirdConfig::tableName("CheckStatus",true),$select)) {

				//now check if status has changed
				if($checkStatusRecord->status_code != $data->checkStatus->status) {
					//alter status
					
					$checkStatusRecord->status_code = $data->checkStatus->status;
					$checkStatusRecord->modified = $this->db->datetime($time);
					
					if($this->db->updateRecord(MediabirdConfig::tableName("CheckStatus",true),$checkStatusRecord)) {
						//load whole check
				
						$select = "id=".$checkStatusRecord->check_id;
						
						if($checkRecord = $this->db->getRecord(MediabirdConfig::tableName("Check",true),$select)) {
						
							//update check modification date
							$checkRecord->modified = $this->db->datetime($time);
							$checkRecord->modifier = $this->userId;
				
							if($this->db->updateRecord(MediabirdConfig::tableName("Check",true),$checkRecord)) {
								$check = (object)array(
									'modified' => $time,
									'modifier' => $checkRecord->modifier,
									'id' => intval($checkRecord->id),
									'userId' => intval($checkRecord->user_id)
								);
								
								//send statusses and modification date back if newer than fromTime
								$check->checkStatusses = array();
									
								//load check statusses
								$select = "check_id=$check->id";
								if($checkStatusRecords = $this->db->getRecords(MediabirdConfig::tableName("CheckStatus",true),$select)) {
									foreach($checkStatusRecords as $checkStatusRecord) {
										$checkStatus = (object) array(
												'id'=>intval($checkStatusRecord->id),
												'status'=>intval($checkStatusRecord->status_code),
												'userId'=>intval($checkStatusRecord->user_id),
												'modified'=>$this->db->timestamp($checkStatusRecord->modified)
										);
											
										$check->checkStatusses []= $checkStatus;
									}
								}
								
								$changes['checks'] = array(
									$check
								);
							
							}
							else {
								return MediabirdConstants::serverError;
							}
						}
						else {
							return MediabirdConstants::serverError;
						}
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
			}
			else {
				return MediabirdConstants::serverError;
			}
		}
		
		return MediabirdConstants::processed;
	}
	
	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['checks']['fromTime']) ? $data['checks']['fromTime'] : 0;
		$fromTopic = isset($data['checks']['fromTopic']) ? $data['checks']['fromTopic'] : 0;
			
		$checkLoadedIds = isset($data['checks']['loadedIds']) ? array_values($data['checks']['loadedIds']) : array();
			
		$ids = isset($data['checks']['restrictIds']) ? array_values($data['checks']['restrictIds']) : array();
			
		if($fromTopic == 0) {
			$select = "(user_id=$this->userId OR id IN (
				SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='check' AND marker_id IN (
					SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$this->userId OR shared=1) AND topic_id IN (
						SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
					)
				)
			))";
			
			if(count($ids)>0) {
				$select = "id IN (".join(",",$ids).") AND ".$select;
			}
		}
		else {
			$select = "id IN (
				SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='check' AND marker_id IN (
					SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$this->userId OR shared=1) AND topic_id=$fromTopic
				)
			)";	
		}
			
		$checks = array();
			
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Check",true),$select)) {
			foreach($records as $record) {
				$hasChanges = false; //defines if this question should be returned or not
				
				$check = (object)null;
				$check->id = intval($record->id);
				
				MediabirdUtility::arrayRemove($checkLoadedIds,$check->id);
				
				$check->modified = $this->db->timestamp($record->modified);
					
				if($check->modified > $fromTime) {
					//send statusses and modification date back if newer than fromTime
					$check->checkStatusses = array();
					$check->userId = intval($record->user_id);
					$check->modifier = intval($record->modifier);
					//in case database was upgraded, it won't feature a valid modifier entry
					if($check->modifier==0) {
						$check->modifier = $check->userId;
					}
					$hasChanges = true;
				}
					
				//load check statusses
				if($hasChanges) {
					$select = "check_id=$check->id";
					if($checkStatusRecords = $this->db->getRecords(MediabirdConfig::tableName("CheckStatus",true),$select)) {
						foreach($checkStatusRecords as $checkStatusRecord) {
							$checkStatus = (object) array(
									'id'=>intval($checkStatusRecord->id),
									'status'=>intval($checkStatusRecord->status_code),
									'userId'=>intval($checkStatusRecord->user_id),
									'modified'=>$this->db->timestamp($checkStatusRecord->modified)
							);
								
							$check->checkStatusses []= $checkStatus;
						}
					}
				}
				
				if($hasChanges) {
					$checks []= $check;
				}
			}
		}
			
		if(count($checks)>0) {
			$results['checks'] = $checks;
		}
		if(count($checkLoadedIds)>0) {
			$results['removedCheckIds'] = $checkLoadedIds;
		}
		return true;
	}
	
/**
	 * Returns the change types supported by this model
	 * @return string[]
	 */
	function getAvailableChangeTypes() {
		return array(
			self::changeTypeCheckConfirmed,
			self::changeTypeCheckPending
		);
	}
	
	/**
	 * Determine changes from a given time for the given user
	 * @param $types string[]
	 * @param $since
	 * @param $userId
	 * @return MediabirdChangeInfo[]
	 */
	function getChanges($types,$since=null,$userId=null) {
		$changes = array();
		
		foreach($types as $type) {
			if(	$type==self::changeTypeCheckConfirmed ||
				$type==self::changeTypeCheckPending) {
				
				$itemTypes = array();
					
				//create select clause
				$select = "
					modified>'".$this->db->datetime($since)."' AND 
					(
						user_id=$userId OR 
						id IN (
							SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='check' AND marker_id IN (
								SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$userId OR shared=1) AND topic_id IN (
									SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
								)
							)
						)
					)
				";
				
				$sort = 'modified DESC';
				
				//retrieve matching records from db
				$checkRecords = $this->db->getRecords(
					MediabirdConfig::tableName('Check',true),
					$select,
					$sort
				);
				
				if($checkRecords) {
					$checkIds = array();
					
					foreach($checkRecords as $checkRecord) {
						$checkIds []= intval($checkRecord->id);
					}
					
					if($type==self::changeTypeCheckPending) {
						//count check states that are pending and related to a check from above
						$select = "status=0 AND check_id IN (".join(",",$checkIds).")";
						$count = $this->db->countRecords(MediabirdConfig::tableName("CheckStatus"),$select);
					}
					else if($type==self::changeTypeCheckConfirmed) {
						//count checks that have been confirmed
						$select = "status=0 AND check_id IN (".join(",",$checkIds).")";
						$checkStatusRecords = $this->db->getRecords(MediabirdConfig::tableName("CheckStatus"),$select);
						
						if($checkStatusRecords) {
							foreach($checkStatusRecords as $checkStatusRecord) {
								MediabirdUtility::arrayRemove($checkIds,$checkStatusRecord->check_id);
							}
						}
						
						$count = count($checkIds);
					}
					else {
						continue;
					}
					
					$changeInfo = new MediabirdChangeInfo($this->name,$since,$userId);
					
					$changeInfo->changeType = $type;
					$changeInfo->itemCount = $count;
					
					$changes[$type] []= $changeInfo;
				}
			}
		}
		
		return $changes;
	}
}

?>
