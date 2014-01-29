<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdTagColor extends MediabirdModel {
	var $name = "TagColor";
	
	var $tagProperties = array('color','showText','tagId');
	var $allowedShowStates = array(0,1);
	
	function validateHexColor($colorStr) {
		$color = strtolower(preg_replace("/[^0-9abcdef]/i","",$colorStr));
		return (strlen($color)==6 && strlen($colorStr)==6);
	}

	var $updateParams = array('tagColors');
	
	function validate($data,&$cache,&$reason) {
		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->updateParams) && is_array($data->tagColors) && count($data->tagColors)>0;
		
		$referredTagIds = array();
		
		if($validates) {
			foreach($data->tagColors as $tagColor) {
				//check if only valid keys are given
				if(!MediabirdUtility::checkKeyset($tagColor,$this->tagProperties,true)) {
					$validates = false;
					break;
				}
				
				//check if id and at least color or showText are given
				if(!property_exists($tagColor,'tagId') || (!property_exists($tagColor,'color') && !property_exists($tagColor,'showText'))) {
					$validates = false;
					break;
				}
				
				//tag id must be given as int
				if(!is_int($tagColor->tagId)) {
					$validates = false;
					break;
				}
				
				if(!in_array($tagColor->tagId,$referredTagIds,true)) {
					$referredTagIds []= $tagColor->tagId;
				}
				else {
					//tagId should not be given twice
					$validates = false;
					break;
				}
				
				//validate color
				if(property_exists($tagColor,'color') && !$this->validateHexColor($tagColor->color)) {
					$validates = false;
					break;
				}
				
				//validate showText
				if(property_exists($tagColor,'showText') && !in_array($tagColor->showText,$this->allowedShowStates,true)) {
					$validates = false;
					break;
				}
			}
			
			if($validates) {
				$select = "id IN (".join(",",$referredTagIds).")";
				if($this->db->countRecords(MediabirdConfig::tableName("Tag",true),$select)!=count($referredTagIds)) {
					$validates = false;
				}
				else {
					$cache ['referredTagIds'] = $referredTagIds;
				}
			}
		}
		
		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}
			
		return $validates;
	}
	
	function update($data,$cache,&$changes) {
		$referredTagIds = $cache['referredTagIds'];
		
		$select = "user_id=$this->userId AND tag_id IN (".join(",",$referredTagIds).")";
		$tagColorRecords = $this->db->getRecords(MediabirdConfig::tableName("TagColor",true),$select);
		$tagColorRecords = $tagColorRecords ? $tagColorRecords : array();
		
		$tagColors = array();
		
		foreach($data->tagColors as $tagColor) {
			//clone this object such that it can be used as return value
			$tagColor = clone $tagColor;
			//avoid updated due to case mismatch
			if(property_exists($tagColor,'color')) {
				$tagColor->color = strtoupper($tagColor->color);
			}
			
			//search for matching record
			$found = false;
			foreach($tagColorRecords as $tagColorRecord) {
				if($tagColorRecord->tag_id == $tagColor->tagId) {
					$found = true;
					break;
				}
			}
			
			if($found) {
				//update record
				$updatedRecord = (object)null;
				$updatedRecord->id = $tagColorRecord->id;
				
				$changed = false;
				if(property_exists($tagColor,'color') && $tagColor->color != $tagColorRecord->color) {
					$updatedRecord->color = $tagColor->color;
					$changed = true;
				}
				if(property_exists($tagColor,'showText') && $tagColor->showText != $tagColorRecord->display_text) {
					$updatedRecord->display_text = $tagColor->showText;
					$changed = true;
				}
				
				if($changed) {
					if($this->db->updateRecord(MediabirdConfig::tableName("TagColor",true),$updatedRecord)) {
						//tagcolor has been cloned above
						$tagColor->userColor = $tagColor->color;
						unset($tagColor->color);
						$tagColors []= $tagColor;
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
			}
			else {
				//create new record
					
				$tagColorRecord = (object)null;
				$tagColorRecord->user_id = $this->userId;
				$tagColorRecord->tag_id = $tagColor->tagId;
				$tagColorRecord->color = property_exists($tagColor,'color') ? $tagColor->color : MediabirdConstants::$tagColors[0];
				$tagColorRecord->display_text = property_exists($tagColor,'showText') ? $tagColor->showText : 0;
				
				if($tagColorRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("TagColor",true),$tagColorRecord)) {
					//tagColor has been cloned above
					unset($tagColor->color);
					$tagColor->userColor = $tagColorRecord->color;
					$tagColor->showText = intval($tagColorRecord->display_text);
					$tagColors []= $tagColor;
				}
			}
		}
		
			
		$changes['tagColors'] = $tagColors;
			
		return MediabirdConstants::processed;
	}

	function load($data,&$results) {
		$tagColors = array();
		//get existing tag color records
		$select = "user_id=$this->userId";
		if($tagColorRecords = $this->db->getRecords(MediabirdConfig::tableName('TagColor',true),$select)) {
			foreach($tagColorRecords as $tagColorRecord) {
				$tagColor = (object)null;
				$tagColor->tagId = intval($tagColorRecord->tag_id);
				$tagColor->userColor = strtoupper($tagColorRecord->color);
				$tagColor->showText = intval($tagColorRecord->display_text);

				array_push($tagColors,$tagColor);
			}
		}
		
		$results['tagColors'] = $tagColors;
		return true;
	}
}
?>
