<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdContent extends MediabirdModel {
	const maxCardSize = 16384;
	
	var $name = "Content";
	
	var $updateParams = array('id','checkout','content','modified');

	/**
	 * Checks content of a card in or out
	 * @param $cardId
	 * @param $checkOut
	 * @param $results
	 * @return unknown_type
	 */
	function checkContent($cardId,$checkOut,&$results) {
		$data = (object)array(
			'id'=>$cardId,
			'checkout'=>$checkOut?1:0 //check-out or in
		);
		$cache = array();
		$reason = null;
		if(!$this->validate($data,$cache,$reason)) {
			return $reason;
		}
		else {
			return $this->update($data,$cache,$results);
		}
	}
	
	function validate($data,&$cache,&$reason) {
		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->updateParams,true);
			
		//id must be integer
		if($validates && (!property_exists($data,"id") || !is_int($data->id))) {
			$validates = false;
		}

		if($validates && property_exists($data,"content") && ((!is_string($data->content) && $data->content != null) || strlen($data->content) > MediabirdContent::maxCardSize)) {
		//if given, content must be string and smaller in size than maxsize
			$validates = false;
		}

		//card must exist and user must have access rights
		if($validates) {
			$select = "card_id=$data->id AND topic_id IN (
				SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask>=".MediabirdTopicAccessConstants::allowEditingContent." AND user_id=$this->userId
			)";
			if(!$record = $this->db->getRecord(MediabirdConfig::tableName('CardContent',true),$select)) {
				$reason = MediabirdConstants::accessDenied;
				$validates = false;
			}
		}
		
		//if content given, modification date must be supplied as well to make sure this card hasn't changed in between!
		if($validates && property_exists($data,"content") && (!property_exists($data,'modified') || !is_int($data->modified))) {
			$validates = false;
		}
		
		// make sure modification date matches database value if content given
		if($validates && property_exists($data,"content") && $data->modified != $this->db->timestamp($record->modified)) {
			$reason = MediabirdConstants::invalidRevision;
			$validates = false;
		}
		
		if($validates) {
			//check check-out condition
			$minuteAgo = time()-60;

			$locked = $record->locked_by != $this->userId && $record->locked_by != 0 && $this->db->timestamp($record->locked_time) > $minuteAgo;

			if($locked) {
				$reason = MediabirdConstants::locked;
				$validates = false;
			}
		}
			
		if($validates && property_exists($data,'checkout')) {
			$validates = in_array($data->checkout,array(0,1),true);
		}
			
		if($validates && property_exists($data,'content')) {
			$cache['content'] = $data->content != null ? MediabirdUtility::purifyHTML($data->content) : null;
		}
		
		if($validates) {
			$cache['record'] = $record;
		}

		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}

		return $validates;
	}

	function update($data,$cache,&$changes) {
		$record = $cache['record'];

		$time = time();

		$contentChanged = false;

		if(array_key_exists('content',$cache) && $record->content != $cache['content']) {
			$record->content = $cache['content'];
			$record->modified = $this->db->datetime($time);
			$record->modifier = $this->userId;

			$contentChanged = true;
		}

		$checkChanged= false;
		if(property_exists($data,'checkout')) {
			$checkOut = $data->checkout == 1;

			if($checkOut) {
				$record->locked_by = $this->userId;
				$record->locked_time = $this->db->datetime(time());
			}
			else {
				$record->locked_by = 0;
			}

			$checkChanged = true;
		}

		if(($contentChanged||$checkChanged)) {
			unset($record->topic_id);
			if($this->db->updateRecord(MediabirdConfig::tableName("CardContent",true),$record)) {
				if($contentChanged) {
					//update card record as well
					$cardRecord = (object)array(
						'id'=>$record->card_id,
						'modified'=>$this->db->datetime($time),
						'modifier'=>$this->userId
					);
					
					//update record
					if($this->db->updateRecord(MediabirdConfig::tableName("Card",true),$cardRecord)) {
						$card = (object)array(
							'modified'=>$time,	
							'modifier' => $this->userId,
							'id' => intval($record->card_id)
						);
						$changes['cards'] []= $card;
					}
					
					//return content object
					$content = (object)array(
						'modified'=>$time,	
						'content' => $record->content,
						'id' => intval($record->card_id)
					);
					$changes['contents'] []= $content;
				}
				if($checkChanged) {
					$changes[$checkOut ? 'checkedOutCardIds' : 'checkedInCardIds'] []= intval($record->card_id);
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
		$fromTime = isset($data['contents']['fromTime']) ? $data['contents']['fromTime'] : 0;
		$loadedIds = isset($data['contents']['loadedIds']) ? array_values($data['contents']['loadedIds']) : array();

		$ids = isset($data['contents']['restrictIds']) ? array_values($data['contents']['restrictIds']) : array();
		$avoidIds = isset($data['contents']['avoidIds']) ? array_values($data['contents']['avoidIds']) : array();
		$parentIds = isset($data['contents']['parentIds']) ? array_values($data['contents']['parentIds']) : array();
			
		$select = "topic_id IN (
			SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask>=".MediabirdTopicAccessConstants::allowViewingCards." AND user_id=$this->userId
		)";

		if(count($ids)>0) {
			$select = "card_id IN (".join(",",$ids).") AND (".$select.")"; 
		}
		else if(count($parentIds)>0) {
			$select = "topic_id IN (".join(",",$parentIds).") AND (".$select.")";
		}
		
		if(count($avoidIds)>0) {
			$select = "card_id NOT IN (".join(",",$avoidIds).") AND ".$select; 
		}
		
		//if no loaded ids are given, save time by including modified condition into sql query
		if($fromTime > 0 && count($loadedIds)==0) {
			$select = "modified>'".$this->db->datetime($fromTime)."' AND $select";
		}

		$contents = array();
		$cards = array();
		$cardIds = array();

		if($records = $this->db->getRecords(MediabirdConfig::tableName('CardContent',true),$select)) {
			foreach($records as $record) {
				//determine card id
				$content = (object)null;
				$content->id = intval($record->card_id);
				
				//override global from time with individual settings if given
				if(isset($data['contents'][$content->id])) {
					$fromTime = $data['contents'][$content->id];
				}
				
				MediabirdUtility::arrayRemove($loadedIds,$content->id);
					
				$content->modified = $this->db->timestamp($record->modified);
					
				if($content->modified>$fromTime) {
					$content->content = $record->content;
					
					$contents []= $content;
				}
			}
		}
		
		if(count($contents)>0) {
			$results['contents'] = $contents;
		}
		if(count($loadedIds)>0) {
			$results['removedContentIds'] = $loadedIds;
		}

		return true;
	}
}

?>
