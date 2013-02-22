<?php
/*
 * 	Copyright (C) 2009-2010 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdTopic extends MediabirdModel {
	const modelName = "Topic";
	const changeTypeRightCreation = "right_creation";
	const changeTypeTopicChange = "topic_change";
	
	var $name = self::modelName;
	
	var $cardProperties = array('id','type','title','index','page','uploadId', 'tagIndexes', 'deletedTagIds');
	var $rightProperties = array('id','userId','email','externalId','mask');
	
	var $allowedCardTypes = array(0,1,2,3); //note, pdf, wiki, blog
	
	var $topicMasks = array();
	
	
	/**
	 * Returns the access rights of the current user against the given topic
	 * @param int $topicId Id of topic to check rights against
	 * @return int Bit mask specifying topic access rights of current user
	 */
	function getTopicMask($topicId) {
		if(isset($topicMasks[$topicId])) {
			return $topicMasks[$topicId];
		}

		if($rightRecord = $this->db->getRecord(MediabirdConfig::tableName('Right',true),"topic_id=$topicId AND user_id=$this->userId")) {
			return $topicMasks[$topicId] = intval($rightRecord->mask);
		}
		else {
			return null;
		}
	}
	
	var $updateParams = array(
		'id',
		'title',
		'modified',
		'newTags',
		'cards',
		'deletedCardIds',
		'rights',
		'deletedRightIds'
	);
	
	function validate($data,&$cache,&$reason) {
		
		//make sure only valid properties are set
		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->updateParams,true);
 
		if($validates && MediabirdConfig::$topicCountLimit !== false){
			if(!property_exists($data, 'id')){
				$select = "user_id = $this->userId AND mask = 1023";
				$topicCount = $this->db->countRecords(MediabirdConfig::tableName('Right',true), $select);
				if($topicCount >= MediabirdConfig::$topicCountLimit){
					$validates = false;
					$reason = MediabirdConstants::limitCountReached;
				}
			}
		}
		
		if($validates) {
			if(property_exists($data,'id')) {
				//data->id: integer, user must have access
				if(is_int($data->id)) {
					$select = "id=$data->id";
					$record = $this->db->getRecord(MediabirdConfig::tableName('Topic',true),$select);
					if(!$record) {
						$validates = false;
					}
					
					//get access rights of current user
					$mask = $this->getTopicMask($data->id);
					
					if(!$mask) {
						$reason = MediabirdConstants::accessDenied;
						$validates = false;
					}
					
					// if topic id given: modification date must be supplied as well to make sure this topic hasn't changed in between!
					if($validates && (!property_exists($data,'modified') || !is_int($data->modified))) {
						$validates = false;
					}
					
					// make sure modification date matches database
					if($validates && $data->modified != $this->db->timestamp($record->modified)) {
						$reason = MediabirdConstants::invalidRevision; //fixme: client won't ask for update right now?
						$validates = false;
					}
				}
				else {
					$validates = false;
				}
			}
			else {
				//access rights default to user if topic is being created
				$mask = MediabirdTopicAccessConstants::owner;
				if(!property_exists($data,"title")) {
					$validates = false;
				}
			}

			if($validates && property_exists($data,"title") && !is_string($data->title) && strlen($data->title)>60) {
				$validates = false;
			}
			
			if($validates && property_exists($data,"title") && $mask < MediabirdTopicAccessConstants::allowRename) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}
		}
		
		//data->newTags: array of object
		if($validates && property_exists($data,"newTags")) {
			//if tags are to be added, edit rights are required
			if($mask < MediabirdTopicAccessConstants::allowEditingContent) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}
			
			if($validates && (!is_array($data->newTags) || count($data->newTags)==0)) {
				$validates = false;
			}

			if($validates) {
				$tagLabels = array();
				
				foreach($data->newTags as $tag) {
					// maxlength: 30, minlength: 1
					if(!is_string($tag) || strlen($tag) > 30 || strlen($tag)==0) {
						$validates = false;
						break;
					}
					else {
						$tagLabels[] = strtolower($tag);
					}
				}

				if($validates && !MediabirdUtility::checkUnique($tagLabels)) {
					$validates = false;
				}
			}
		}

		//data->cards: array of object, cards that are to be altered or added

		$cards = array();
		if($validates && property_exists($data,'cards')) {
			if(!is_array($data->cards) || count($data->cards)==0 || !MediabirdUtility::checkUnique($data->cards,"id")) {
				$validates = false;
			}
			
			if($validates) {
				foreach($data->cards as $card) {
					if(!is_object($card) || !MediabirdUtility::checkKeyset($card,$this->cardProperties,true)) {
						$validates = false;
						break;
					}
					//	* if card->id not given, card->type, card->title and card->index must be given
					if(!property_exists($card,'id') && (!property_exists($card,'type') || !property_exists($card,'title') || !property_exists($card,'index'))) {
						$validates = false;
						break;
					}
					
					//do not allow user to add, restructure or edit cards if not permitted
					if(!property_exists($card,'id')) {
						if($mask < MediabirdTopicAccessConstants::allowAddingCards) {
							$reason = MediabirdConstants::accessDenied;
							$validates = false;
							break;
						}
					}
					else {
						if(property_exists($card,'index') && $mask < MediabirdTopicAccessConstants::allowRearrangingCards) {
							$reason = MediabirdConstants::accessDenied;
							$validates = false;
							break;
						}
						//if card is to be altered, edit rights are required
						else if($mask < MediabirdTopicAccessConstants::allowEditingContent) {
							$reason = MediabirdConstants::accessDenied;
							$validates = false;
						}
					}
					
					//check tag indexes if given
					if(property_exists($card, 'tagIndexes')){
						//new tags must be given
						if(!property_exists($data,'newTags')) {
							$validates = false;
							break;
						}
					
						//tag indexes must be given as array
						if(!is_array($card->tagIndexes)) {
							$validates = false;
							break;
						}
						
						//tag indexes must be in valid range and given as int
						foreach($card->tagIndexes as $tagIndex){
							if(!is_int($tagIndex) || $tagIndex >= count($data->newTags) || $tagIndex < 0) {
								$validates = false;
								break;
							}
						}
					}
					
					//check if deleted tag ids are given
					if(property_exists($card, 'deletedTagIds')){
						//deleted tag ids must be given as array
						if(!is_array($card->deletedTagIds)) {
							$validates = false;
							break;
						}
					
						//deleted tag ids must be given as int
						foreach($card->deletedTagIds as $deletedTagId){
							if(!is_int($deletedTagId)){
								$validates = false;
								break;
							}
						}
					}
					
					//if tags have been deleted or inserted, collect all existing tags
					if(property_exists($card, 'deletedTagIds') || property_exists($card, 'tagIndexes')){

						//find tag that are already attached to this card
						$select = "card_id=".$card->id;
						
						//retrieve cardTag records and store them in cache
						$cardTagRecords = $this->db->getRecords(MediabirdConfig::tableName("CardTag",true),$select);
						$cache['cardTagRecords'][$card->id] = $cardTagRecords; 
						
						//check if there are too many tag indexes
						if(property_exists($card, 'tagIndexes') && MediabirdConfig::$tagCountLimit !== false){
							if((count($card->tagIndexes) + count($cardTagRecords)) >= MediabirdConfig::$tagCountLimit){
								$validates = false;
								$reason = MediabirdConstants::limitCountReached;
							}
						}
					}
					
					//	* if card->type given, it must be integer, valid and card->id must not be given
					if(property_exists($card,'type')) {
						if (!is_int($card->type) || !in_array($card->type,$this->allowedCardTypes) || property_exists($card,'id')) {
							$validates = false;
							break;
						}
						
						if($card->type == MediabirdConstants::cardTypeHtml || $card->type == MediabirdConstants::cardTypeWiki || $card->type == MediabirdConstants::cardTypeBlog) { 
							if(property_exists($card,'uploadId') || property_exists($card,'page')) {
								$validates = false;
								break;
							}
						}
						else if($card->type == MediabirdConstants::cardTypePdf) {
							if(!property_exists($card,'uploadId') || !property_exists($card,'page') || !is_int($card->uploadId) || !is_int($card->page)) {
								$validates = false;
								break;
							}
							
							//check if upload is accessible and page refers to a valid number
							try {
								$mustOwn = true;
								
								//check if this upload has already been used in the topic
								if(property_exists($data,"id") && isset($data->id)) {
									$select = "content_type=".MediabirdConstants::cardTypePdf." AND content_id=$card->uploadId AND topic_id=$data->id";
								
									if($this->db->countRecords(MediabirdConfig::tableName("Card",true),$select) > 0) {
										$mustOwn = false;
									}
								}
								
								//check if user is allowed to access the given pdf file
								if(!$this->controller->Files->checkFileAuth($card->uploadId,$mustOwn)) {
									$reason = MediabirdConstants::accessDenied;
									$validates = false;
									break;
								}
							}
							catch(Exception $ex) {
								$reason = MediabirdConstants::serverError;
								$validates = false;
								break;
							}
						}
					}
					

					//	* if card->id given, either card->title or card->index must be given (otherwise nothing would change)
					if(property_exists($card,'id') && !property_exists($card,'title') && !property_exists($card,'index') && !property_exists($card,'tagIndexes') && !property_exists($card, 'deletedTagIds')) {
						$validates = false;
						break;
					}
					//* card->id: integer, optional
					if(property_exists($card,'id') && !is_int($card->id)) {
						$validates = false;
						break;
					}
					//* card->title: string, optional, maxlength 60
					if(property_exists($card,'title') && !is_string($card->title) && strlen($card->title)>60) {
						$validates = false;
						break;
					}
					//* card->index: integer, optional
					if(property_exists($card,'index') && !is_int($card->index)) {
						$validates = false;
						break;
					}
				}
			}
			if($validates) {
				$cards = $data->cards;
			}
		}

		//
		//data->deletedCardIds: array of integer
		$deletedCardIds = array();
		if($validates && property_exists($data,'deletedCardIds')) {
			if(!is_array($data->deletedCardIds) || count($data->deletedCardIds)==0 || !MediabirdUtility::checkUnique($data->deletedCardIds)) {
				$validates = false;
			}
			
			if($validates && $mask < MediabirdTopicAccessConstants::allowRemovingCards) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}
			
			if($validates) {
				foreach($data->deletedCardIds as $deletedCardId) {
					if(!is_int($deletedCardId)) {
						$validates = false;
						break;
					}
				}
			}
			if($validates) {
				$deletedCardIds = $data->deletedCardIds;
			}
		}

		//* altogether, indexes must be a consecutive set of numbers

		if($validates && (count($deletedCardIds)>0 || count($cards)>0)) {
			//get existing cards
			$cardRecords = null;

			if(isset($record)) {
				$select = "topic_id=".$record->id;
				$currentRecords = $this->db->getRecords(MediabirdConfig::tableName('Card',true),$select,'','id,title,index_num,content_type,content_id');
				$cardRecords = $currentRecords ? array_values($currentRecords) : null;				
			}

			if(!$cardRecords) {
				$cardRecords = array();
			}

			//check changes
			$checkedIds = array();
			foreach($cards as $remoteCard) {
				if(property_exists($remoteCard,'id')) {
					//existing one is to be changed
					$found = false;
					foreach($cardRecords as $cardRecord) {
						if($cardRecord->id==$remoteCard->id) {
							//	* ids must be unique
							if(in_array($remoteCard->id,$checkedIds)) {
								//id was given twice
								$validates = false;
								break;
							}
							$checkedIds []= $remoteCard->id;

							$found = true;
							if(property_exists($remoteCard,'index') && $cardRecord->index_num != $remoteCard->index) {
								$cardRecord->index_num = $remoteCard->index;
							}
							break;
						}
					}

					if(!$found) {
						//card was deleted meanwhile or update refers to a card not part of this topic
						$validates = false;
					}
				}
				else {
					$newRecord = (object)null;
					$newRecord->index_num = $remoteCard->index;
					$cardRecords[] = $newRecord;
				}
			}

			//validate cards that are to be deleted
			$deletedContentIds = array();
			
			foreach($deletedCardIds as $deletedCardId) {
				$found = false;
				foreach($cardRecords as $key => $cardRecord) {
					if($cardRecord->id == $deletedCardId) {
						$found = true;
						break;
					}
				}
				if($found) {
					if(	$cardRecord->content_type == MediabirdConstants::cardTypeHtml ||
						$cardRecord->content_type == MediabirdConstants::cardTypeBlog ||
						$cardRecord->content_type == MediabirdConstants::cardTypeWiki) {
						$deletedContentIds []= $cardRecord->content_id;		
					}					
					
					array_splice($cardRecords,$key,1);
				}
				else {
					//card id does not exist or does not belong to this topic!
					$validates = false;
				}
			}

			if($validates && count($deletedContentIds) > 0) {
				$minuteAgo = time()-60;
				$datetime = $this->db->datetime($minuteAgo);
				$select = "id IN (".join(",",$deletedContentIds).") AND locked_by NOT IN (0,$this->userId) AND locked_time>'$datetime'";
				$lockedCount = $this->db->countRecords(MediabirdConfig::tableName("CardContent",true),$select);

				if($lockedCount > 0) {
					$validates = false;
					$reason = MediabirdConstants::locked;
				}
			}
			
			if($validates) {
				$indexes = array();
				$cardIds = array();
	
				//check if card indexes form a complete set
				foreach($cardRecords as $cardRecord) {
					$indexes[] = $cardRecord->index_num;
					if(property_exists($cardRecord,"id")) {
						$cardIds[] = intval($cardRecord->id);
					}
				}
	
				sort($indexes);
	
				foreach($indexes as $key=>$value) {
					if($validates && $key!=$value) {
						//there's a gap
						$validates = false;
						break;
					}
				}
			}

			//	max card count
			if($validates && count($indexes)>MediabirdConstants::maxCardCount) {
				$validates = false;
			}

			if($validates) {
				$cache['cardRecords'] = isset($currentRecords) ? $currentRecords : array();
			}
		}

		if($validates) {
			$cache['cards'] = $cards;
			$cache['deletedCardIds'] = $deletedCardIds;
		}
		
		if($validates) {
			$givenRightIds = array();
		}
		
		//data->rights: array of object
		if($validates && property_exists($data,"rights")) {
			if(!is_array($data->rights) || count($data->rights)==0 || !MediabirdUtility::checkUnique($data->rights,"userId") || !MediabirdUtility::checkUnique($data->rights,"externalId") || !MediabirdUtility::checkUnique($data->rights,"email")) {
				$validates = false;
			}

			//	if given: user must be owner of topic
			if($validates && isset($mask) && $mask != MediabirdTopicAccessConstants::owner) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}

			$givenUserIds = array();

			if($validates) {
				foreach($data->rights as $right) {
					if(!is_object($right) || !MediabirdUtility::checkKeyset($right,$this->rightProperties,true)) {
						$validates = false;
						break;
					}
	
					//if id given, must be integer
					if(property_exists($right,'id')) {
						//must be int and no other properties may be given
						if(!is_int($right->id) || property_exists($right,"userId") || property_exists($right,"externalId") || property_exists($right,"email")) {
							$validates = false;
							break;
						}
						$givenRightIds []= $right->id;
					}
					
					//* either one of right->id, right->userId, right->externalId, right->email must be given
					if(!property_exists($right,"id") && !property_exists($right,"userId") && !property_exists($right,"externalId") && !property_exists($right,"email")) {
						$validates = false;
						break;
					}

					//* right->userId: if given: must be integer, plus: must be among known users of
					if(property_exists($right,"userId")) {
						if(is_int($right->userId)) {
							if($right->userId == $this->userId) {
								$reason = MediabirdConstants::invalidData;
								$validates = false;
								break;
							}
							
							$givenUserIds []= $right->userId;
						}
						else {
							$validates = false;
							break;
						}
					}

					//* right->externalId: if given: must be integer, use external provider
					if(property_exists($right,"externalId") && !is_int($right->externalId)) {
						$validates = false;
						break;
					}

					//* right->email: if given: must be valid email address, search for user using that
					if(property_exists($right,"email") && !MediabirdUtility::checkEmail($right->email)) {
						$validates = false;
						break;
					}

					//* right->mask: integer, must be in valid access mask range (> none and <= owner)

					if(!property_exists($right,"mask") || !is_int($right->mask) || $right->mask <= MediabirdTopicAccessConstants::noAccess || $right->mask > MediabirdTopicAccessConstants::owner) {
						$validates = false;
						break;
					}
				}
			}

			if($validates && count($givenUserIds)>0) {
				$select = "id IN (".join(",",$givenUserIds).") AND id IN (
					SELECT user_id FROM ".MediabirdConfig::tableName('Right')." WHERE 
						mask>0 AND
						topic_id IN (
							SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE user_id=$this->userId AND mask>0
						)
					)";

				if($this->db->countRecords(MediabirdConfig::tableName("User",true),$select) != count($givenUserIds)) {
					//user cannot know that user
					$validates = false;
				}
			}
		}

		//data->deletedRightIds: array of integer
		if($validates && property_exists($data,"deletedRightIds")) {

			//	if given, topic must be given as well
			if(!isset($record)) {
				$validates = false;
			}

			if($validates && (!is_array($data->deletedRightIds) || count($data->deletedRightIds) == 0 || !MediabirdUtility::checkUnique($data->deletedRightIds))) {
				$validates = false;
			}

			//	if more than one right will be removed: user must be owner of topic
			//  if only one right is removed, it could be a leave request, will be checked later
			if($validates && count($data->deletedRightIds)>1 && $mask != MediabirdTopicAccessConstants::owner) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}

			if($validates) {
				foreach($data->deletedRightIds as $deletedRightId) {
					if(is_int($deletedRightId)) {
						$givenRightIds []= $deletedRightId;
					}
					else {
						$validates = false;
						break;
					}
				}
			}

			if($validates && count($givenRightIds)==0) {
				$validates = false;
			}
		}
		
		//retrieve all existing right records if rights are inserted, updated or removed
		if($validates && (property_exists($data,"rights") || property_exists($data,"deletedRightIds"))) {
			$select ="topic_id=$record->id";
			
			$rightRecords = $this->db->getRecords(MediabirdConfig::tableName("Right",true),$select);
			$rightRecords = $rightRecords ? $rightRecords : array(); 
			
			//save for update function
			$cache['rightRecords'] = $rightRecords;
		}
		
		//check if ids of rights that are to be updated or deleted are valid
		if($validates && count($givenRightIds)>0) {
			//check if given right ids are unique
			if(!MediabirdUtility::checkUnique($givenRightIds)) {
				$validates = false;
			}
			
			//check if ids belong to this topic
			if($validates) {
				foreach($givenRightIds as $givenRightId) {
					$found = false;
					foreach($rightRecords as $rightRecord) {
						if ($rightRecord->id == $givenRightId) {
							$found = true;
							break;
						}
					}
					if(!$found) {
						//user cannot change that rightset
						$reason = MediabirdConstants::accessDenied;
						$validates = false;
						break;
					}
					else {
						if($rightRecord->user_id != $this->userId && $mask != MediabirdTopicAccessConstants::owner) {
							$reason = MediabirdConstants::accessDenied;
							$validates = false;
							break;
						}
					}
				}	
			}
		}
		
		//check if at least one owner will be present
		if($validates && (property_exists($data,"rights") || property_exists($data,"deletedRightIds"))) {
		
			
			//check if at least one owner will remain after the changes would have been applied
			$ownerRemaining = false;
			if(!$ownerRemaining) {
				foreach($rightRecords as $rightRecord) {
					//check if this one is obsolete
					$rightId = intval($rightRecord->id);
					
					//if it will be overridden, ignore it
					if(in_array($rightId,$givenRightIds)) {
						continue;
					}
					
					//if it will be deleted, ignore it
					if(property_exists($data,"deletedRightIds") && in_array($rightId,$data->deletedRightIds)) {
						continue;
					}
					
					//if owner will remain, leave the loop
					if($rightRecord->mask == MediabirdTopicAccessConstants::owner) {
						$ownerRemaining = true;
						break;
					}
				}
			}
			
			//check new/altered rights
			if(!$ownerRemaining && property_exists($data,"rights")) {
				foreach($data->rights as $right) {
					if($right->mask == MediabirdTopicAccessConstants::owner) {
						$ownerRemaining = true;
						break;
					}
				}
			}
			
			if(!$ownerRemaining) {
				$validates = false;
			}
		}
		

		if($validates) {
			if(isset($record)) {
				$cache['record'] = $record;
			}
			$cache['mask'] = $mask;	
		}
		
		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}

		return $validates;
	}
	
	/**
	 * Updates a topic with the given data
	 * @param data data from client
	 * @param cache array provided by validate-function
	 * @param changes contains anything which oughts to be sent back to client
	 * @return  
	 * @see source/server/models/MediabirdModel#update($data, $cache, $changes)
	 */
	function update($data,$cache,&$changes) {
		$time = time();
		
		//resulting rights
		$rights = array();
		$rightsChanged = false;
		
		if(property_exists($data,"id")) {
			$record = (object)null;
			$record->id = intval($cache['record']->id);
			$record->title = $cache['record']->title;
			$record->modifier = $cache['record']->modifier;
			$mask = $cache['mask'];
		}
		else {
			//create topic
			$record = (object)null;
			$record->title = $data->title;
			$record->modifier = $this->userId;
			$record->created = $record->modified = $this->db->datetime($time);
				
			if(!$record->id = $this->db->insertRecord(MediabirdConfig::tableName("Topic",true),$record)) {
				return MediabirdConstants::serverError;
			}
				
			//register owner
			$rightRecord = (object)null;
			$rightRecord->user_id = $this->userId;
			$rightRecord->topic_id = $record->id;
			$rightRecord->created = $rightRecord->modified = $this->db->datetime($time);
			
			//save own mask, used below
			$mask = MediabirdTopicAccessConstants::owner;
			$rightRecord->mask = $mask;

			//insert own right-set
			if($rightRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Right",true),$rightRecord)) {
				$right = (object) array(
					'id'=>intval($rightRecord->id),
					'userId'=>$rightRecord->user_id,
					'mask'=>$rightRecord->mask,
					'modified'=>$time
				);
				$rightsChanged = true;
				$rights []= $right;
			}
			else {
				return MediabirdConstants::serverError;
			}
		}
		
		$tags = array();
		if(property_exists($data,"newTags")) {
			$escapedLabels = array();
			
			foreach($data->newTags as $newTag) {
				$escapedLabels []= "'".$this->db->escape($newTag)."'";
			}
			$select = "title IN (".join(",",$escapedLabels).")";
				
			//find tag ids
			$tagRecords = $this->db->getRecords(MediabirdConfig::tableName('Tag',true),$select);
				
			$tagRecords = $tagRecords ? $tagRecords : array();
				
				
			//create tags that were not found
			foreach($data->newTags as $index=>$tag) {
				//search for tag among tag records
				$found = false;
				foreach($tagRecords as $tagRecord) {
					if(strtolower($tag) == strtolower($tagRecord->title)) {
						$found = true;
						break;
					}
				}

				if(!$found) {
					//since this tag is new, select a color and save that one
					if(!isset($tagCount)) {
						$tagCount = $this->db->countRecords(MediabirdConfig::tableName("Tag",true),"1=1");
					}
					$nextColor = MediabirdConstants::$tagColors[$tagCount % count(MediabirdConstants::$tagColors)];
					
					//insert tag
					$tagRecord = (object)null;
					$tagRecord->color = $nextColor;
					$tagRecord->title = $tag; //case-sensitive!
					if($tagRecord->id=$this->db->insertRecord(MediabirdConfig::tableName('Tag',true),$tagRecord)) {
						$tagRecords[] = $tagRecord;
						//save tag info for client in separate array
						$changes['tags'][] = (object)array(
							'id'=>intval($tagRecord->id),
							'color'=>$nextColor,
							'title'=>$tagRecord->title
						);
					}
					else {
						return MediabirdConstants::serverError;	
					}
					

				}
				else {
					//return tag info anyway!
					//this could be important if the tag already exists but hasn't been used by the current user
					$changes['tags'][] = (object)array(
						'id'=>intval($tagRecord->id),
						'color'=>$tagRecord->color,
						'title'=>$tagRecord->title
					);
				}
				//save tag id instead of label to make linking between card and tag easier below
				$newTags[$index] = intval($tagRecord->id);
			}
		}
		

		$cardsChanged = count($cache['cards'])+count($cache['deletedCardIds'])>0;
		//update and insert changed cards
		if($cardsChanged) {
			//all existing cards
			$cardRecords = $cache['cardRecords'];

			
			//cards to be inserted/updated
			$updatedCards = $cache['cards'];
			
			//ids of cards to be deleted
			$deletedCardIds = $cache['deletedCardIds'];
			
			//resulting cards
			$cards = array();
			
			//delete cards to be deleted
			if(count($deletedCardIds)>0) {
				$selectRelations = "marker_id IN (
					SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE card_id IN (".join(",",$deletedCardIds).")
				)";
				$selectCardContents = $selectCardTags = $selectMarkers = "card_id IN (".join(",",$deletedCardIds).")";
				
				//clean relations, markers, card-tags
				$this->db->deleteRecords(MediabirdConfig::tableName("Relation",true),$selectRelations);
				$this->db->deleteRecords(MediabirdConfig::tableName("Marker",true),$selectMarkers);
				$this->db->deleteRecords(MediabirdConfig::tableName("CardTag",true),$selectCardTags);
				$this->db->deleteRecords(MediabirdConfig::tableName("CardContent",true),$selectCardContents);
				
				//clean deleted cards
				$select = "id IN (".join(",",$deletedCardIds).")";

				if($this->db->deleteRecords(MediabirdConfig::tableName("Card",true),$select)) {
					//remove deleted cards from cardRecords array
					foreach($deletedCardIds as $deletedCardId) {
						foreach($cardRecords as $index => $cardRecord) {
							if($cardRecord->id == $deletedCardId) {
								//remove cardRecord to not return this card at all
								array_splice($cardRecords,$index,1);
								break;
							}
						}
					}
				}
				else {
					return MediabirdConstants::serverError;
				}
			}
			
			//insert/update cards
			foreach($updatedCards as $card) {
				if(property_exists($card,'id')) {
					$found = false;
					foreach($cardRecords as $index => $cardRecord) {
						if($cardRecord->id == $card->id) {
							$found = true;
							//remove cardRecord to not return this card twice
							MediabirdUtility::arrayRemove($cardRecords,$cardRecord);
							break;
						}
					}
					if($found) {
						//do not update modification date by default
						$updateModified = false;
						
						//delete orphaned tags
						if(property_exists($card,"deletedTagIds")) {
							//remove tags from database
							$select = "card_id=$card->id AND tag_id IN (".join(",",$card->deletedTagIds).")";
							if(!$this->db->deleteRecords(MediabirdConfig::tableName("CardTag",true),$select)) {
								return MediabirdConstants::serverError;
							}
							$updateModified = true;
						}
					
						
						if(property_exists($card,"title")) {
							$cardRecord->title = $card->title;
							$updateModified = true;
						}
						if(property_exists($card,"index")) {
							$cardRecord->index_num = $card->index;
							// do not force modification update in this case
						}
						
						if(property_exists($card,"tagIndexes")) {
							$updateModified = true;							
						}
							
						if($updateModified) {
							$cardRecord->modified = $this->db->datetime($time);
							$cardRecord->modifier = $this->userId;
						}
						
						if($this->db->updateRecord(MediabirdConfig::tableName("Card",true),$cardRecord)) {
							$card = clone $card;
							//only send back modified value if it was changed
							if($updateModified){
								$card->modified = $time;
								$card->modifier = $cardRecord->modifier;														
							}
							$cards []= $card;
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
					$cardRecord = (object)null;
					
					//custom properties
					$cardRecord->title = $card->title;
					$cardRecord->index_num = $card->index;
					$cardRecord->content_type = $card->type;
					
					if($card->type == MediabirdConstants::cardTypeHtml || $card->type == MediabirdConstants::cardTypeWiki || $card->type == MediabirdConstants::cardTypeBlog) {
						$contentRecord = (object)null;
						$contentRecord->topic_id = $record->id;
						$contentRecord->locked_by = 0;
						$contentRecord->locked_time = $this->db->datetime(0);
						$contentRecord->modifier = $this->userId;
						$contentRecord->modified = $this->db->datetime($time);
						
						if($contentRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("CardContent",true),$contentRecord)) {
							$cardRecord->content_id = $contentRecord->id;
							$cardRecord->content_index = 0; //in the future, this could refer to a version number
						}
						else {
							return MediabirdConstants::serverError;
						}
					}
					else if($card->type==MediabirdConstants::cardTypePdf) {
						$cardRecord->content_id = $card->uploadId;
						$cardRecord->content_index = $card->page;
					}
					
					//standard properties
					$cardRecord->topic_id = $record->id;
					$cardRecord->modifier = $this->userId;
					$cardRecord->created = $cardRecord->modified = $this->db->datetime($time);
			
					
					if($cardRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Card",true),$cardRecord)) {
						//update redundant card_id in content record
						if($card->type == MediabirdConstants::cardTypeHtml || $card->type == MediabirdConstants::cardTypeWiki || $card->type == MediabirdConstants::cardTypeBlog) {
							$contentRecord = (object)array(
								'id'=>$contentRecord->id,
								'card_id'=>$cardRecord->id
							);
							if(!$this->db->updateRecord(MediabirdConfig::tableName("CardContent",true),$contentRecord)) {
								return MediabirdConstants::serverError;
							}	
						}
						
						$card = clone $card;
						$card->id = intval($cardRecord->id);
						$card->modified = $time;
						$card->modifier = $cardRecord->modifier;
						$cards []= $card;
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
				
								
				// collect ids of newTags having been inserted to the database
				if(property_exists($card, 'tagIndexes')){
					$newTagIds = array();
					foreach($card->tagIndexes as $tagIndex){
						$newTagIds[] = $newTags[$tagIndex];
					}
				}
				
				//if tags have been deleted or inserted, collect all existing tags
				if(property_exists($card, 'deletedTagIds') || property_exists($card, 'tagIndexes')){
					// collect new and existing tagIds
					$tagIds = array();
					
					// find tag is already related to card
					$cardTagRecords = $cache['cardTagRecords'][intval($cardRecord->id)];
					if($cardTagRecords) {
						foreach($cardTagRecords as $cardTagRecord) {
							$tagId = intval($cardTagRecord->tag_id);
							
							if(property_exists($card, 'deletedTagIds') && in_array($tagId,$card->deletedTagIds)) {
								//tag has been deleted by this request
								continue;
							}
							
							//remove the existing tags from the array of new tags
							if(isset($newTagIds)) {
								MediabirdUtility::arrayRemove($newTagIds, $tagId);
							}
							//collect all tag ids
							$tagIds []= $tagId;
						}
					}
				}
				
				
				//actually link new tags with the card
				if(property_exists($card, 'tagIndexes')){
					foreach($newTagIds as $newTagId){
						$cardTagRecord = (object)null;
						$cardTagRecord->card_id = $card->id;
						$cardTagRecord->tag_id = $newTagId;
						$cardTagRecord->created = $cardTagRecord->modified = $this->db->datetime($time);
						if(!$cardTagRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("CardTag",true),$cardTagRecord)) {
							return MediabirdConstants::serverError;
						}
						$tagIds[] = $newTagId;
					}
				}
				
				//save tags in card's tags property 
				if(property_exists($card, 'deletedTagIds') || property_exists($card, 'tagIndexes')){
					$card->tags = $tagIds;
					
					unset($card->deletedTagIds);
					unset($card->tagIndexes);
				}
			}
			
			//include remaining cards in return array
			foreach($cardRecords as $cardRecord) {
				$cards []= (object)array(
					'id'=>intval($cardRecord->id)
				);
			}
		}

		if(property_exists($data,"deletedRightIds") || property_exists($data,"rights")) {
			$rightsChanged = true;
			
			//existing right records
			$rightRecords = $cache['rightRecords'];
			
			//delete orphaned rights
			if(property_exists($data,"deletedRightIds")) {
				//delete orphaned rights
				$select = "id IN (".join(",",$data->deletedRightIds).")";
				if($this->db->deleteRecords(MediabirdConfig::tableName('Right',true),$select)) {
					//remove deleted rights from rightRecords array
					foreach($data->deletedRightIds as $deletedRightId) {
						foreach($rightRecords as $rightRecord) {
							if($rightRecord->id == $deletedRightId) {
								//remove cardRecord to not return this card at all
								MediabirdUtility::arrayRemove($rightRecords,$rightRecord);
								break;
							}
						}
					}
				}
				else {
					return MediabirdConstants::serverError;
				}
			}
	
			//insert new rights
			if(property_exists($data,"rights")) {
				$unknownUsers = array();
					
				//copy auth property
				$auth = $this->controller->auth;
				
				foreach($data->rights as $right) {
					//clone object here to allow for using it as return value below
					$right = clone $right;
	
					if(property_exists($right,"id")) {
						$rightRecord = (object)null;
						$rightRecord->id = $right->id;
						$rightRecord->mask = $right->mask;
						$rightRecord->modified = $this->db->datetime($time);
						if($this->db->updateRecord(MediabirdConfig::tableName('Right',true),$rightRecord)) {
							$right->modified = $time;
							$rights []= $right;
						}
						else {
							return MediabirdConstants::serverError;
						}
					}
					else {
						//determine WHO is to be invited
						$userId = null;
						if(property_exists($right,"userId")) {
							$userId = $right->userId;
						}
						else if(property_exists($right,"externalId")) {
							$inviteeUnknown = false; //actual value will be received after the next call
							if (!$userId = $auth->inviteKnownUser($right->externalId, $inviteeUnknown)) {
								$unknownUsers []= $right;
							}
						}
						else if(property_exists($right,"email")) {
							//search if user given in db
							if($userRecord = $this->db->getRecord(MediabirdConfig::tableName('User',true),"email='".$this->db->escape($right->email)."'")) {
								$userId = intval($userRecord->id);
							}
							else {
								//check if invitation possible
								if(!method_exists($auth, "inviteUser")) {
									$unknownUsers []= $right;
								}
								else {
									//invite user
									if(!$userId = $auth->inviteUser($right->email)) {
										$unknownUsers []= $right;
									}
								}
							}
						}
							
						if($userId) {
							//check if user has already got access to the topic
							$select = "topic_id=$record->id AND user_id=$userId";
							if($rightRecord = $this->db->getRecord(MediabirdConfig::tableName('Right',true),$select)) {
								if($rightRecord->mask!=$right->mask) {
									$rightRecord->mask=$right->mask;
									$rightRecord->modified = $this->db->datetime($time);
									if($this->db->updateRecord(MediabirdConfig::tableName('Right',true),$rightRecord)) {
										unset($right->externalId);
										unset($right->email);
										$right->id = intval($rightRecord->id);
										$right->userId = $userId;
										$right->modified = $time;
										$rights []= $right;
									}
									else {
										return MediabirdConstants::serverError;
									}
								}
							}
							else {
								//add new right
								$rightRecord = (object)null;
								$rightRecord->user_id = $userId;
								$rightRecord->topic_id = $record->id;
								$rightRecord->mask = $right->mask;
								$rightRecord->created = $rightRecord->modified = $this->db->datetime($time);
		
								if($rightRecord->id = $this->db->insertRecord(MediabirdConfig::tableName('Right',true),$rightRecord)) {
									unset($right->externalId);
									unset($right->email);
									$right->id = intval($rightRecord->id);
									$right->userId = $userId;
									$right->modified = $time;
									$rights []= $right;
								}
								else {
									return MediabirdConstants::serverError;
								}
							}
						}
					}
				}	
			}
			
			//include remaining cards in return array
			foreach($rightRecords as $rightRecord) {
				//search if this right record was already mentioned in return array
				$found = false;
				foreach($rights as $right) {
					if($right->id==$rightRecord->id) {
						$found = true;
						break;
					}	
				}
				
				if(!$found) {
					$rights []= (object)array(
						'id'=>intval($rightRecord->id)
					);
				}
			}
		}

		$topic = (object)null;
		$topic->id = intval($record->id);
		
		if(property_exists($data,"title") && $record->title != $data->title) {
			$record->title = $data->title;
			$topic->title = $record->title;
		}
		else {
			unset($record->title);
		}
		
		$topic->modified = $time;
		$topic->modifier = $this->userId;

		//update topic record
		$record->modified = $this->db->datetime($topic->modified);
		$record->modifier = $topic->modifier;
		if($this->db->updateRecord(MediabirdConfig::tableName("Topic",true),$record)) {
			$changes['topics'] = array($topic);
				
			if($cardsChanged) {
				$topic->cards = $cards;
			}
			if($rightsChanged) {
				$topic->rights = $rights;
			}
			
			if(!empty($unknownUsers)) {
				$changes['unknownUsers'] = $unknownUsers;
			}
		}
		else {
			return MediabirdConstants::serverError;
		}

		return MediabirdConstants::processed;
	}
	
	/**
	 * 
	 */
	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['topics']['fromTime']) ? $data['topics']['fromTime'] : 0;
		
		$topicLoadedIds = isset($data['topics']['loadedIds']) ? array_values($data['topics']['loadedIds']) : array();
		
		$includeCards = isset($data['topics']['includeCards']) ? $data['topics']['includeCards'] : false;
		
		$ids = isset($data['topics']['restrictIds']) ? array_values($data['topics']['restrictIds']) : array();
			
		//select topics the user can access
		$select = "id IN (
			SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." 
			WHERE 
				user_id=$this->userId AND 
				mask>=".MediabirdTopicAccessConstants::allowViewingCards."
		)";
		
		if(count($ids)>0) {
			$select = "id IN (".join(",",$ids).") AND ".$select; 
		}
		
		$topics = array();
		$referredTagIds = array();
		$checkedPdfs = array();
		
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Topic",true),$select)) {
			foreach($records as $record) {
				$topic = (object)null;
				$topic->id = intval($record->id);
				
				MediabirdUtility::arrayRemove($topicLoadedIds,$topic->id);
				
				$modified = $this->db->timestamp($record->modified);
					
				if($modified > $fromTime) {
					$topic->modified = $modified;
					$topic->title = $record->title;
					$topic->license = intval($record->license);
					$topic->modifier = intval($record->modifier);
				}
				else if(!$includeCards) {
					//ignore rest
					continue;
				}
					
				$select = "topic_id=$record->id";
				
				$cards = array();
				$cardIds = array();
					
				if($cardRecords = $this->db->getRecords(MediabirdConfig::tableName("Card",true),$select,'index_num ASC')) {
					//retrieve cards
					foreach($cardRecords as $cardRecord) {
						$card = (object)null;
						
						$card->id = intval($cardRecord->id);
						
						$cardModified = $this->db->timestamp($cardRecord->modified);
						
						
						if($cardModified > $fromTime) {
							//only collect modified cards for tag retrieval
							$cardIds []= $card->id;
							
							//create empty tags array, will be filled below
							$card->tags = array();
							
							$card->modified = $cardModified;
							$card->title = $cardRecord->title;
							$card->index = intval($cardRecord->index_num);
							$card->modifier = intval($cardRecord->modifier);
							$card->type = intval($cardRecord->content_type);
							if($card->type==MediabirdConstants::cardTypePdf) {
								$card->uploadId = intval($cardRecord->content_id);
								$card->page = intval($cardRecord->content_index);
								
								//only check file auth if it has not been checked for that file
								if(!isset($checkedPdfs[$card->uploadId])) {
									$checkedPdfs[$card->uploadId] = $this->controller->Files->checkFileAuth($card->uploadId);
								}
									
								if(!$checkedPdfs[$card->uploadId]) {
									$card->needsPassword = true;
								}
							}
							
							$cards []= $card;
						}
						else {
   							//return index if topic changed
   							if(property_exists($topic,"modified")) {
   								$card->index = intval($cardRecord->index_num);
   							}
  							
   							$cards []= $card;
						}
					}
					
					//retrieve related CardTags, but only if topic was changed
					if(count($cardIds)>0 && $modified > $fromTime) {
						$select = "card_id IN (".join(",",$cardIds).")";
						
						if($cardTagRecords = $this->db->getRecords(MediabirdConfig::tableName("CardTag",true),$select)) {
							foreach($cardTagRecords as $cardTagRecord) {
								$tagId = intval($cardTagRecord->tag_id);
								
								//save tag id for later retrieval
								if(!in_array($tagId,$referredTagIds)) {
									$referredTagIds []= $tagId;
								}
								
								$cardId = intval($cardTagRecord->card_id);
								
								//attach tags to card objects
								foreach($cards as $card) {
									if($card->id == $cardId) {
										$card->tags []= $tagId;
									}									
								}	
							}
						}
					}
				}

				//save cards in $topic
				$topic->cards = $cards;
				
				//only consider rights if topic has changed
				if($modified > $fromTime) {
				
					//retrieve rights!
					$rights = array();
					if($rightRecords = $this->db->getRecords(MediabirdConfig::tableName('Right',true),"topic_id=$record->id")) {
						foreach($rightRecords as $rightRecord) {
							$right = (object)null;
							$right->id = intval($rightRecord->id);
							
							$right->modified = $this->db->timestamp($rightRecord->modified); 
							
							//this has been commented out since a user that did not know about this topic
							//won't get the complete right set if they have been added later on 
							//if($right->modified > $fromTime) {
							$right->mask = intval($rightRecord->mask);
							$right->userId = intval($rightRecord->user_id);
							$rights []= $right;
							//}
							//else {
							//	//save traffic
							//	unset($right->modified);
							//	$rights []= $right;
							//}
						}
					}
					
					//save rights in $topic
					$topic->rights = $rights;
				}
				
				//add topic if it is to be considered
				$topics []= $topic;
			}
		}
		
		if(count($referredTagIds)>0) {
			$tags = array();
			
			$select = "id IN (".join(",",$referredTagIds).")";
			if($tagRecords = $this->db->getRecords(MediabirdConfig::tableName("Tag",true),$select)) {
				foreach($tagRecords as $tagRecord) {
					$tag = (object)array(
						'id'=>intval($tagRecord->id),
						'color'=>$tagRecord->color,
						'title'=>$tagRecord->title
					);
					$tags []= $tag;
				} 
			}
			
			if(count($tags)>0) {
				$results['tags'] = $tags;
			}
		}
		
		if(count($topics)>0) {
			$results['topics'] = $topics;
		}
		if(count($topicLoadedIds)>0) {
			$results['removedTopicIds'] = $topicLoadedIds;
		}
		return true;
	}
	
		
	function delete($ids,&$results) {
		//check if user is owner of topic
		$select = "topic_id IN (".join(",",$ids).") AND user_id=$this->userId";
		
		if(!$rightRecords = $this->db->getRecords(MediabirdConfig::tableName("Right",true),$select)) {
			return MediabirdConstants::accessDenied;
		}
		else {
			foreach($rightRecords as $rightRecord) {
				if($rightRecord->mask < MediabirdTopicAccessConstants::owner) {
					return MediabirdConstants::accessDenied;
				}
			}
		}
		
		$okay = true;
		
		//now collect affected ids
		$select = "marker_id = ANY
			(SELECT id FROM ".MediabirdConfig::tableName('Marker')." WHERE topic_id IN (".join(",",$ids)."))";
		$okay = $okay && parent::deleteGeneric('Relation',$select,$results);
		
		$select = "card_id = ANY
			(SELECT id FROM ".MediabirdConfig::tableName('Card')." WHERE topic_id IN (".join(",",$ids)."))";
		$okay = $okay && parent::deleteGeneric('CardTag',$select);
		
		$select = "topic_id IN (".join(",",$ids).")";
		$okay = $okay && parent::deleteGeneric('Marker',$select,$results);
		
		$select = "topic_id IN (".join(",",$ids).")";
		$okay = $okay && parent::deleteGeneric('Card',$select,$results);
		
		$select = "topic_id IN (".join(",",$ids).")";
		$okay = $okay && parent::deleteGeneric('CardContent',$select);
		
		$select = "topic_id IN (".join(",",$ids).")";
		$okay = $okay && parent::deleteGeneric('Right',$select,$results);
		
		$select = "id IN (".join(",",$ids).")";
		$okay = $okay && parent::deleteGeneric('Topic',$select,$results);
		
		if($okay) {
			return MediabirdConstants::processed;	
		}
		else {
			return MediabirdConstants::serverError;
		}
	}
	
	/**
	 * Returns the change types supported by this model
	 * @return string[]
	 */
	function getAvailableChangeTypes() {
		return array(
			self::changeTypeRightCreation,
			self::changeTypeTopicChange
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
			
			if($type==self::changeTypeRightCreation) {
				//create select clause
				$select = "user_id=$userId AND created>'".$this->db->datetime($since)."'";
				
				//retrieve matching records from db
				$records = $this->db->getRecords(
					MediabirdConfig::tableName('Right',true),
					$select,
					'modified DESC'
				);
				
				//create change info for each new record
				foreach($records as $record) {
					//get topic record
					$select = "id=".$record->topic_id;
					$topicRecord = $this->db->getRecord(MediabirdConfig::tableName('Topic',true),$select);
					
					if($topicRecord) {
						$changeInfo = new MediabirdChangeInfo($this->name,$since,$userId);
						
						$changeInfo->itemId = $record->id;
						
						$changeInfo->itemCreated = $this->db->timestamp($record->created);
						$changeInfo->itemModified = $this->db->timestamp($record->modified);
						
						$changeInfo->changeType = self::changeTypeRightCreation; 
						$changeInfo->itemId = $record->id;
						$changeInfo->itemTitle = $topicRecord->title;
						$changeInfo->itemModifier = intval($topicRecord->modifier);
						
						$changeInfo->record = $record;
					
						$changes[self::changeTypeRightCreation] []= $changeInfo;
					}
				}
			}
			else if($type==self::changeTypeTopicChange) {
			//create select clause
				$select = "modified>'".$this->db->datetime($since)."' AND id IN (
					SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
				)";
				
				//retrieve matching records from db
				$records = $this->db->getRecords(
					MediabirdConfig::tableName('Topic',true),
					$select,
					'modified DESC'
				);
				
				//create change info for each new record
				foreach($records as $record) {
					$changeInfo = new MediabirdChangeInfo($this->name,$since,$userId);
					$changeInfo->itemId = $record->id;
					
					$changeInfo->itemCreated = $this->db->timestamp($record->created);
					$changeInfo->itemModified = $this->db->timestamp($record->modified);
					
					$changeInfo->itemModifier = intval($record->modifier);
					
					$changeInfo->changeType = self::changeTypeTopicChange; 
					$changeInfo->itemId = $record->id;
					$changeInfo->itemTitle = $record->title;
					
					$changeInfo->itemRecord = $record;
					
					$changes[self::changeTypeTopicChange][]= $changeInfo;
				}
			}
		}
		
		return $changes;
	}
}
?>
