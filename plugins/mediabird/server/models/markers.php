<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

/**
 * Markers model
 * Note: this model is called Markers and not Marker
 * because it will always deal with an array of markers 
 * rather than a single marker itself!
 * @author fabian
 *
 */
class MediabirdMarkers extends MediabirdModel {
	var $name = "Markers";
	
	var $validTools = array('reference','question','repetition','template','checkbox');
	var $rangeObjProperties = array('pos','tag','id');
	var $areaObjProperties = array('l','b','t','r');
	
	var $updateParams = array('markers','deletedMarkerIds');
	var $markerProperties = array('id','cardId','tool','shared','range','modified','relations');
	
	var $relationTypes = array(
		'question'=>array(
			'protected'=>true, //only owner can edit, relation must feature user_id
			'creatable'=>false, //if true, dummy records of this type can be inserted without further data being given
			'shared'=>true //other users can access this relation (user_id=0)
		),
		'flashcard'=>array(
			'protected'=>true,
			'creatable'=>true,
			'shared'=>false //other users cannot access this relation (user_id=[user id])
		),
		'link'=>array(
			'protected'=>true,
			'creatable'=>false,
			'shared'=>true
		),
		'check'=>array(
			'protected'=>true,
			'creatable'=>false,
			'shared'=>true
		)
	);
	
	var $dependentTypes = array('flashcard');
	
	function validate($data,&$cache,&$reason) {
		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->updateParams,true);

		$referredCardIds = array();
		$referredMarkerIds = array();
		if($validates && property_exists($data,'markers')) {
			if(!is_array($data->markers) || count($data->markers) == 0 || !MediabirdUtility::checkUnique($data->markers,'id')) {
				$validates = false;
				break;
			}
			
			//array of valid relation types
			$validTypes = array_keys($this->relationTypes);

			foreach($data->markers as $marker) {
				//	data->markers: array of object
				if(!is_object($marker) || !MediabirdUtility::checkKeyset($marker,$this->markerProperties,true)) {
					$validates = false;
					break;
				}

				//either id or {cardId,tool} must be given
				if(!property_exists($marker,'id') && (!property_exists($marker,'cardId') || !property_exists($marker,'tool'))) {
					$validates = false;
					break;
				}
				//id and {cardId,tool} must not be given at the same time
				if(property_exists($marker,'id') && (property_exists($marker,'cardId') || property_exists($marker,'tool'))) {
					$validates = false;
					break;
				}
				
				//check if cardId is valid
				if(property_exists($marker,'cardId')) {
					if(!is_int($marker->cardId)) {
						$validates = false;
						break;
					}
					if(array_search($marker->cardId,$referredCardIds)===false) {
						$referredCardIds []= $marker->cardId;
					}
				}
				
				//check if tool is valid
				if(property_exists($marker,'tool') && !in_array($marker->tool,$this->validTools,true)) {
					$validates = false;
					break;
				}

				//check if id is integer and collect it to check if it is valid later on
				if(property_exists($marker,'id')) {
					if(!is_int($marker->id)) {
						$validates = false;
						break;
					}
					else {
						$referredMarkerIds []= $marker->id;
					}
					
					// modification date must be supplied as well to make sure this card hasn't changed in between!
					if($validates && (!property_exists($marker,'modified') || !is_int($marker->modified))) {
						$validates = false;
						break;
					}					
				}

				//marker->shared: 1 or 0
				if(property_exists($marker,'shared') && !in_array($marker->shared,array(0,1),true)) {
					$validates = false;
					break;
				}
			}
		}
			
		if($validates && property_exists($data,'deletedMarkerIds')) {
			if(!is_array($data->deletedMarkerIds) || !MediabirdUtility::checkUnique($data->deletedMarkerIds)) {
				foreach($data->deletedMarkerIds as $deletedMarkerId) {
					if(!is_int($deletedMarkerId)) {
						$validates = false;
						break;
					}
					
					if(in_array($deletedMarkerId,$referredMarkerIds)) {
						//markers cannot be altered and deleted at once
						$validates = false;
						break;
					}
				}
				
				if($validates) {
					//check if user is permitted to delete the given markers 
					$select = "id IN (".join(",",$data->deletedMarkerIds).") AND (shared=1 OR user_id=$this->userId) AND topic_id IN (
						SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowAlteringMarkers."
					)";
					
					if($this->db->countRecords(MediabirdConfig::tableName("Marker",true),$select)!=count($data->deletedMarkerIds)) {
						$reason = MediabirdConstants::accessDenied;
						$validates = false;
					}
				}
			}
		}
		
		if($validates && count($referredCardIds)>0) {
		//only allow to insert markers onto cards where the user has at least view rights (edit rights checked below)
			$select = "id IN (".join(",",$referredCardIds).") AND topic_id IN (
				SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
			)";
			$cardRecords = $this->db->getRecords(MediabirdConfig::tableName("Card",true),$select,'','id,topic_id,content_type');
			if(!$cardRecords || count($cardRecords)!=count($referredCardIds)) {
				$validates = false;
			}
			else {
				$cache['referredCardRecords'] = $cardRecords;
			}
		}
		
		//ids must be valid <=> the user has access to
		if($validates) {
			$markerRecords = array();
			if (count($referredMarkerIds)>0) {
				$select = "id IN (".join(",",$referredMarkerIds).") AND (shared=1 OR user_id=$this->userId) AND topic_id IN (
					SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
				)";
				$markerRecords = $this->db->getRecords(MediabirdConfig::tableName("Marker",true),$select);
				if(!$markerRecords || count($markerRecords)!=count($referredMarkerIds)) {
					$validates = false;
				}
				
				//check if modification date of markers matches the one in the database
				if($validates) {
					foreach($markerRecords as $markerRecord) {
						$modified = $this->db->timestamp($markerRecord->modified);
	
						foreach($data->markers as $marker) {
							if($marker->id == $markerRecord->id && $marker->modified != $modified) {
								$reason = MediabirdConstants::invalidRevision;
								$validates = false;
								break;
							}
						}
					}
				}
				
				if($validates) {
					$cache['markerRecords'] = $markerRecords;
				}
			}
		}
		
		//topic ids whose markers or cards are attempted to be edited 
		$writeReferredTopicIds = array();
		
		//once we've loaded related card records (if marker does not exist) 
		//or marker records, we can savely determine the card type (pdf or html)
		//to validate the range property of the given markers
		if($validates && property_exists($data,'markers')) {
			foreach($data->markers as $marker) {
				//check if range given
				if(property_exists($marker,'range')) {
					//check if marker already exists or is to be created
					$found = false;
					if(property_exists($marker,'id')) {
						//find type using marker record
						$found = false;
						foreach($markerRecords as $markerRecord) {
							if($markerRecord->id == $marker->id) {
								$found = true;
								$contentType = intval($markerRecord->card_type);
							}
						}
					}
					else {
						//find type using card record
						$found = false;
						foreach($cardRecords as $cardRecord) {
							if($cardRecord->id == $marker->cardId) {
								$found = true;
								$contentType = intval($cardRecord->content_type);
							}
						}
					}
						
					if(!$found){
						$validates = false;
						break;
					}
				
					//now either cardRecord or markerRecord has been set
					if($validates) {
						if(property_exists($marker,'id')) {
							//if marker requires marker owner rights (marker is to be unshared), determine if user owns marker
							if(property_exists($marker,'shared') && $marker->shared == 0 && $markerRecord->user_id != $this->userId) {
								$reason = MediabirdConstants::accessDenied;
								$validates =false;
								break;
							} 
							
							//check if topic of marker requires topic edit rights (marker is to be shared)
							if(property_exists($marker,'shared') && $marker->shared == 1) {
								if(!in_array($markerRecord->topic_id,$writeReferredTopicIds)) {
									$writeReferredTopicIds []= $markerRecord->topic_id;
								}
							}
							//if user is not the owner, they need edit rights 
							else if($markerRecord->user_id != $this->userId) {
								//user does not own the marker, so we need topic access rights
								if(!in_array($markerRecord->topic_id,$writeReferredTopicIds)) {
									$writeReferredTopicIds []= $markerRecord->topic_id;
								}
							}
						}
						else {
							//check if topic of card (where marker is to be inserted)
							//requires edit rights (marker is to be shared)
							if($marker->shared == 1) {
								//user tries to share the marker, so check for topic edit rights
								if(!in_array($cardRecord->topic_id,$writeReferredTopicIds)) {
									$writeReferredTopicIds []= $cardRecord->topic_id;
								}
							}
						}
					}
					
					if(!$this->validateRange($marker->range,$contentType)) {
						$validates = false;
						break;
					}
				}
			
				//	check if relations are valid
				if(property_exists($marker,'relations')) {
					//if no relations given, forget it
					if(!is_array($marker->relations) || count($marker->relations)==0) {
						$validates = false;
						break;
					}
						
					//note: marker->relations must be an array of objects featuring type and id, whereas id refers to a related object (not to an id from the relations table)
					$referredObjects = array();
					foreach($marker->relations as $relation) {
						if(!is_object($relation) || !MediabirdUtility::checkKeyset($relation,array('type','id'),true)) {
							$validates = false;
							break;
						}
						if(!property_exists($relation,'type') || !in_array($relation->type,$validTypes)) {
							$validates = false;
							break;
						}
						if(!property_exists($relation,'id') && !$this->relationTypes[$relation->type]['creatable']) {
							$validates = false;
							break;
						}
						if(property_exists($relation,'id') && (!is_int($relation->id) || !in_array($relation->type,$validTypes,true))) {
							$validates = false;
							break;
						}

						if(property_exists($relation,'id')) {
							$referredObjects[$relation->type][]=$relation->id;
						}
					}
						
					//check that relations of the particular type are accessible
					foreach($referredObjects as $type => $relationIds) {
						//relations that are to be linked must either
						//	* be owned by the user (in case they feature user_id)
						//	* be shared with the user

						$options = $this->relationTypes[$type];

						$select = "id IN (".join(",",$relationIds).")";

						if($options['protected']) {
							//prepend user_id-condition
							$select = "user_id=$this->userId AND ".$select;
						}
						else {
							$select = $select." AND id IN (
								SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='$type' AND marker_id IN (
									SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$this->userId OR shared=1) AND topic_id IN (
										SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowAlteringMarkers."
									)
								)
							)";
						}

						if($this->db->countRecords(MediabirdConfig::tableName(ucfirst($type),true),$select)!=count($relationIds)) {
							$validates = false;
							break;
						}
					}
						
					if(!$validates) {
						break;
					}
				}

				//note: deletedRelationIds refer to id values from the relations table
				if($validates && property_exists($marker,'deletedRelationIds')) {
					if(!is_array($marker->deletedRelationIds) || count($marker->deletedRelationIds)==0 || !MediabirdUtility::checkUnique($marker->deletedRelationIds)) {
						$validates = false;
						break;
					}
						
					//ids must be given as integer
					foreach($marker->deletedRelationIds as $deletedRelationId) {
						if(!is_int($deletedRelationId))	{
							$validates = false;
							break;
						}
					}
						
					if($validates) {
						$select = "marker_id=$marker->id AND id IN (".join(",".$marker->deletedRelationIds).")";
						if($this->db->countRecords(MediabirdConfig::tableName("Relation",true),$select)!=count($marker->deletedRelationIds)) {
							$validates = false;
							break;
						}
					}
				}
			}
		}
		
		if($validates && count($writeReferredTopicIds) > 0) {
			$select = "id IN (".join(",",$writeReferredTopicIds).") AND id IN (	
				SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowAlteringMarkers."
			)";
			if($this->db->countRecords(MediabirdConfig::tableName("Topic",true),$select)!=count($writeReferredTopicIds)) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}
		}

		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}

		return $validates;
	}
	
	
	private function validateRange($range,$contentType) {
		if (!is_string($range)) {
			return false;
		}
			
		if(!$ranges = json_decode($range)) {
			return false;
		}
			
		if (!is_array($ranges)) {
			return false;
		}
			
		foreach($ranges as $rangeArray) {
			if($contentType==MediabirdConstants::cardTypeHtml || $contentType==MediabirdConstants::cardTypeWiki || $contentType==MediabirdConstants::cardTypeBlog) {
				if(!is_array($rangeArray)) {
					return false;
				}
				foreach($rangeArray as $rangeObj) {
					if(!MediabirdUtility::checkKeyset($rangeObj,$this->rangeObjProperties)) {
						return false;
					}
						
					if(!is_int($rangeObj->pos) || !is_string($rangeObj->id) || !is_string($rangeObj->tag)) {
						return false;
					}
				}
			}
			else if($contentType==MediabirdConstants::cardTypePdf) {
				if(!MediabirdUtility::checkKeyset($rangeArray,$this->areaObjProperties)) {
					return false;
				}	
				
				//check if all properties are integer
				foreach($rangeArray as $value) {
					if(!is_int($value)) {
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	function update($data,$cache,&$changes) {
		$time = time();
		if(property_exists($data,'markers')) {
			$markers = array();
			$changedCards = array();
			$markerRecords = isset($cache['markerRecords'])?$cache['markerRecords']:array();
			foreach($data->markers as $remoteMarker) {
				if(property_exists($remoteMarker,'id')) {
	
					//search for marker record
					$found = false;
					foreach($markerRecords as $markerRecord) {
						if($markerRecord->id==$remoteMarker->id) {
							$found = true;
							break;
						}
					}
					if($found) {			
						if(property_exists($remoteMarker,'shared')) {
							$markerRecord->shared = $remoteMarker->shared;
						}
						if(property_exists($remoteMarker,'range')) {
							$markerRecord->range_store = $remoteMarker->range;
						}
						$markerRecord->id = intval($markerRecord->id);
						$markerRecord->modified = $this->db->datetime($time);
						if($this->db->updateRecord(MediabirdConfig::tableName("Marker",true),$markerRecord)) {
							$marker = (object)array(
								'id'=>$markerRecord->id,
								'range'=>$markerRecord->range_store,
								'modified'=>$time,
								'shared'=>intval($markerRecord->shared)
							);
							if(property_exists($markerRecord,'card_id') && isset($markerRecord->card_id)){
								$changedCard = (object)array(
									'id'=>$markerRecord->card_id,
								);
								$changedCards[]=$changedCard;
							}
							$markers []= $marker;
						}
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
				else {
					//search card record as well
					
					$cardRecords = $cache['referredCardRecords'];
					$found = false;
					foreach($cardRecords as $cardRecord) {
						if($cardRecord->id==$remoteMarker->cardId) {
							$found = true;
							break;
						}
					}
					
					if(!$found) {
						return MediabirdConstants::serverError;
					}
						
						
					//create new
					$markerRecord = (object)null;
					$markerRecord->user_id = $this->userId;
					$markerRecord->range_store = $remoteMarker->range;
					$markerRecord->tool = $remoteMarker->tool;
					$markerRecord->shared = $remoteMarker->shared;
					$markerRecord->card_id = $remoteMarker->cardId;
					$markerRecord->created = $markerRecord->modified = $this->db->datetime($time);
					
					//redundant values
					$markerRecord->topic_id = $cardRecord->topic_id;
					$markerRecord->card_type = $cardRecord->content_type;
					
					//insert record
					if($markerRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Marker",true),$markerRecord)) {
						$marker = (object)array(
							'id'=>intval($markerRecord->id),
							'range'=>$markerRecord->range_store,
							'modified'=>$time,
							'tool'=>$remoteMarker->tool,
							'shared'=>$markerRecord->shared,
							'cardId'=>$markerRecord->card_id,
							'userId'=>$markerRecord->user_id
						);
						$markers []= $marker;
						$changedCard = (object)null;
						$changedCard->id = $markerRecord->card_id;
						$changedCards []= $changedCard;
					}
				}
				
				if(property_exists($remoteMarker,'relations')) {
					if(property_exists($remoteMarker,'id')) {
						$select = "marker_id=$remoteMarker->id";
						$relationRecords = $this->db->getRecords(MediabirdConfig::tableName("Relation",true),$select);
					}
					$relationRecords = isset($relationRecords) && !empty($relationRecords) ? $relationRecords : array();	
				
					$marker->relations = array();
						
					foreach($remoteMarker->relations as $relation) {
						//check if relation already exists
						if(!property_exists($relation,'id')) {
							$className = ucfirst($relation->type);
							$relation->id = $this->controller->$className->createDefault();
							if(!$relation->id) {
								continue;
							}
						}
						
						//load options for type
						$options = $this->relationTypes[$relation->type];
						
						$found = false;
						foreach($relationRecords as $relationRecord) {
							if(	$options['shared'] == true &&
								$relationRecord->relation_type == $relation->type && 
								$relationRecord->relation_id == $relation->id
							) {
								$found=true;
							}
						}
						if(!$found) {
							
							$relationRecord = (object)null;
							$relationRecord->relation_id = $relation->id;
							$relationRecord->relation_type = $relation->type;
							$relationRecord->marker_id = $marker->id;
							$relationRecord->user_id = $options['shared'] ? 0 : $this->userId;
							//redundant value
							$relationRecord->topic_id = $markerRecord->topic_id;
							$relationRecord->created = $relationRecord->modified = $this->db->datetime($time);
							//insert new record
							if($relationRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Relation",true),$relationRecord)) {
								$relation = clone $relation;
								
								//id from the relations table
								$relation->id = intval($relationRecord->id);
								//id from one of the data tables
								$relation->relatedId = intval($relationRecord->relation_id);
								
								$marker->relations []= $relation;
							}
						}
					}
				}
				
				if(property_exists($marker,'deletedRelationIds')) {
					//delete dependent objects
					//fixme: this needs to be tested in a test case (since flashcards will never be deleted by client
					foreach($this->dependentTypes as $type) {
						$selectObjects = "id IN (
							SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='$type' AND id IN (".join(",",$marker->deletedRelationIds).")
							)";
						$this->db->deleteRecords(MediabirdConfig::tableName(ucfirst($type),true),$selectObjects);
					}
					
					if($this->db->deleteRecords(MediabirdConfig::tableName("Relation",true),$marker->deletedRelationIds)) {
						$marker->deletedRelationIds = $deletedRelationIds;
					}
				}
			}
			foreach($changedCards as $changedCard){
				$changedCard->modified = $this->db->datetime($time);
				$changedCard->modifier = $this->userId;
				if($this->db->updateRecord(MediabirdConfig::tableName("Card",true),$changedCard)){
					$updatedModifiedCard = (object)array(
						'id'=>$changedCard->id,
						'modified'=>$time,
						'modifier'=>$changedCard->modifier
					);
					$updatedModifiedCards []= $updatedModifiedCard;
				}
			}
			$changes['markers'] = $markers;
			$changes['cards'] = $updatedModifiedCards;
		}
		
		if(property_exists($data,'deletedMarkerIds')) {
			//delete dependent objects
			foreach($this->dependentTypes as $type) {
				$selectObjects = "id IN (
					SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='$type' AND marker_id IN (".join(",",$data->deletedMarkerIds).")
					)";
				$this->db->deleteRecords(MediabirdConfig::tableName(ucfirst($type),true),$selectObjects);
			}
			//delete relations
			$selectRelations = "marker_id IN (".join(",",$data->deletedMarkerIds).")";
			$this->db->deleteRecords(MediabirdConfig::tableName("Relation",true),$selectRelations);
			
			$select = "id IN (".join(",",$data->deletedMarkerIds).")";
			if($this->db->deleteRecords(MediabirdConfig::tableName("Marker",true),$select)) {
				$deletedMarkerIds = array_values($data->deletedMarkerIds);
			}
			else {
				MediabirdConstants::serverError;
			}
			$changes['removedMarkerIds'] = $deletedMarkerIds;
		}
		
		return MediabirdConstants::processed;
	}
	
	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['markers']['fromTime']) ? $data['markers']['fromTime'] : 0;
		$fromTopic = isset($data['markers']['fromTopic']) ? $data['markers']['fromTopic'] : 0;
		
		$markerLoadedIds = isset($data['markers']['loadedIds']) ? array_values($data['markers']['loadedIds']) : array();
		$relationLoadedIds = isset($data['relations']['loadedIds']) ? array_values($data['relations']['loadedIds']) : array();
		
		$ids = isset($data['markers']['restrictIds']) ? array_values($data['markers']['restrictIds']) : array();
		$parentIds = isset($data['markers']['parentIds']) ? array_values($data['markers']['parentIds']) : array();
		$returnAllMarkerIds = isset($data['markers']['returnAllIds']) ? $data['markers']['returnAllIds'] : false;
		
		$select = "user_id=$this->userId OR (shared=1 AND topic_id IN (
			SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
		))";
		
		if(count($ids)>0) {
			$select = "id IN (".join(",",$ids).") AND (".$select.")"; 
		}
		else if(count($parentIds)>0) {
			$select = "card_id IN (".join(",",$parentIds).") AND (".$select.")";
		}
		else if($fromTopic > 0) {
			$select = "topic_id=$fromTopic AND (".$select.")";
		}
		
		//if no loaded ids are given, save time by including modified condition into sql query
		if($fromTime > 0 && count($markerLoadedIds)==0 && count($relationLoadedIds)==0 && !$returnAllMarkerIds) {
			$select = "modified>'".$this->db->datetime($fromTime)."' AND $select";
		}

		$markers = array();
		
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Marker",true),$select)) {
			foreach($records as $record) {
				$marker = (object)null;
				$marker->id = intval($record->id);
				
				//remove marker from the check list
				MediabirdUtility::arrayRemove($markerLoadedIds,$marker->id);
				
				$marker->modified = $this->db->timestamp($record->modified);
				
				//only load further details if marker was updated within the desired time frame
				if($marker->modified > $fromTime) {
					$marker->tool = $record->tool;
					$marker->range = $record->range_store;
					$marker->shared = intval($record->shared);
					$marker->cardId = intval($record->card_id);
					$marker->userId = intval($record->user_id);
					
					//load relations
					$select = "marker_id=$marker->id AND user_id IN (0,$this->userId)";
					if($relationRecords = $this->db->getRecords(MediabirdConfig::tableName("Relation",true),$select)) {
						$marker->relations = array();
						foreach($relationRecords as $relationRecord) {
							$relation = (object)null;
							$relation->id = intval($relationRecord->id);
							
							//remove relation from the check list
							MediabirdUtility::arrayRemove($relationLoadedIds,$relationRecord->id);
							
							$relation->modified = $this->db->timestamp($relationRecord->modified);

							//do not let returning relations depend on their modified value
							//this will render the post-loading broken
							//id from one of the data tables
							$relation->relatedId = intval($relationRecord->relation_id);
							//name of the data table
							$relation->type = $relationRecord->relation_type;
							
							$marker->relations []= $relation;
						}
						
						//now check if any of the relations misses any dependency
						//example: questions need one flashcard per user, this will be created here (the object itself won't be returned)
						if($fromTime==0) {
							$time = time();
							
							$dbRelations = array_values($marker->relations);
							foreach($dbRelations as $relation) {
								$type = $relation->type;
								
								$className = ucfirst($type);
								if(method_exists($this->controller->$className,"checkDependencies")) {
									$requiredRelations = $this->controller->$className->checkDependencies($relation,$marker->relations);
									if($requiredRelations && count($requiredRelations)) {
										foreach($requiredRelations as $relationDummy) {
											$relationRecord = (object)null;
											$relationRecord->relation_id = $relationDummy->relatedId;
											$relationRecord->relation_type = $relationDummy->type;
											$relationRecord->marker_id = $marker->id;
											$relationRecord->user_id = $relationDummy->shared ? 0 : $this->userId;
											//redundant value
											$relationRecord->topic_id = $record->topic_id;
											$relationRecord->created = $relationRecord->modified = $this->db->datetime($time);
											//insert new record
											if($relationRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Relation",true),$relationRecord)) {
												$newRelation = (object)null;
												$newRelation->id = intval($relationRecord->id);
							
												$newRelation->modified = $time;
												$newRelation->relatedId = $relationDummy->relatedId;
												$newRelation->type = $relationDummy->type;
																
												$marker->relations []= $newRelation;
											}
										}
									}
								}
							}
						}
					}
				
					$markers []= $marker;
				}
				else if($returnAllMarkerIds){
   					//save traffic
  					unset($marker->modified);
   					$markers []= $marker;
				}
			}
		}

		if(count($markers)>0 || $returnAllMarkerIds) {
			$results['markers'] = $markers;
		}
		if(count($markerLoadedIds)>0) {
			$results['removedMarkerIds'] = $markerLoadedIds;
		}
		if(count($relationLoadedIds)>0) {
			$results['removedRelationIds'] = $relationLoadedIds;
		}
		return true;
	}
	
	
	private $findMarkerParams = array('ids');
	/**
	 * Determines marker ids and card ids of markers related to a given set of object ids
	 */
	function findMarkers($data,$relationType,&$results) {
		$validates = 
			is_object($data) &&
			MediabirdUtility::checkKeyset($data,$this->findMarkerParams,true) && 
			is_array($data->ids);
		
		if($validates) {
			foreach($data->ids as $id) {
				if(!is_int($id)) {
					$validates = false;
					break;
				}
			}
		}
		
		if($validates) {
			//enough validation!
			
			$select = "id IN (
				SELECT marker_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='".$relationType."' AND relation_id IN (".join(",",$data->ids).")
			) AND (
				user_id=$this->userId OR (shared=1 AND topic_id IN (
					SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
				))
			)";
			
			//array to store infos about the markers
			$markerInfos = array();
			
			//now get all related markers 
			$markerRecords = $this->db->getRecords(MediabirdConfig::tableName("Marker",true),$select,'','id,card_id');
	
			if($markerRecords) {
				
				foreach($markerRecords as $markerRecord) {
					$markerInfo = (object)array(
						'id'=>intval($markerRecord->id),
						'cardId'=>intval($markerRecord->card_id)
					);
					$markerInfos []= $markerInfo;
				}
				
			}
			if(isset($results['markerInfos'])) {
				$results['markerInfos'] = array_merge($results['markerInfos'],$markerInfos);
			}
			else {
				$results['markerInfos'] = $markerInfos;
			}
			return true;
		}
		else {
			$results['r'] = MediabirdConstants::invalidData;
			return false;
		}
	}
}
?>
