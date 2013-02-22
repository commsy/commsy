<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdLink extends MediabirdModel {

	var $allowedTypes = array(0,1,2,3,4); //link, email, youtube, twitter, ...
	var $maxUrlLength = 3000;
	var $maxTitleLength = 80;
	var $linkProperties = array('url','title','type','id');

	
	var $name = "Link";
	
	var $updateParams = array('links','deletedLinkIds');
	
	//columns: title, url, type_num, user_id, created, modified

	
	function validate($data,&$cache,&$reason) {
		/* incoming:
		 * 	links
		 * 		link.url (string, maxlength $maxUrlLength)
		 * 		link.title (string, maxlength $maxTitleLength)
		 * 		link.type (any of $allowedTypes)
		 * 		link.id (integer)
		 *  deletedLinkIds
		 *
		 *  generally: only owner can change or delete links
		 */
			

		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->updateParams,true);
			
		$referredIds = array();
		if($validates && property_exists($data,'links')) {
			//check if links is an array, if it contains has at least one member, if the referred ids do not occur twice
			if(!is_array($data->links) || count($data->links) == 0 || !MediabirdUtility::checkUnique($data->links,'id')) {
				$validates = false;
			}

			if($validates) {
				foreach ($data->links as $link) {
					if(!MediabirdUtility::checkKeyset($link,$this->linkProperties,true)) {
						$validates = false;
						break;
					}

					//if no id given, all url, title and type must be given
					if(!property_exists($link,'id') && (!property_exists($link,'url') || !property_exists($link,'type') || !property_exists($link,'title'))) {
						$validates = false;
						break;
					}

					//if id given, it must be integer
					if(property_exists($link,'id')) {
						if(!is_int($link->id)) {
							$validates = false;
							break;
						}
						$referredIds []= $link->id;
					}

					//type must be in $allowedTypes if given
					if(property_exists($link,'type') && !in_array($link->type,$this->allowedTypes,true)) {
						$validates = false;
						break;
					}

					//title must be smaller than max size if given
					if(property_exists($link,'title') && (!is_string($link->title) || strlen($link->title) > $this->maxTitleLength)) {
						$validates = false;
						break;
					}

					//url must be smaller than max size if given
					if(property_exists($link,'url') && (!is_string($link->url) || strlen($link->url) > $this->maxUrlLength)) {
						$validates = false;
						break;
					}

					if(property_exists($link,'url')) {
						//fixme: validate url scheme here
					}
				}
			}
		}
			
		if($validates && property_exists($data,'deletedLinkIds')) {
			if(!is_array($data->deletedLinkIds) || count($data->deletedLinkIds)==0 || !MediabirdUtility::checkUnique($data->deletedLinkIds)) {
				$validates = false;
			}

			if($validates) {
				foreach($data->deletedLinkIds as $deletedLinkId) {
					if(!is_int($deletedLinkId)) {
						$validates = false;
						break;
					}
					$referredIds []= $deletedLinkId;
				}
			}
		}
			
		if($validates && count($referredIds)>0) {
			//check if user has access to these ids
			$select = "id IN (".join(",",$referredIds).") AND user_id=$this->userId";
			if(!$records = $this->db->getRecords(MediabirdConfig::tableName("Link",true),$select,'','id,user_id')) {
				$validates = false;
			}

			if($validates && count($records) != count($referredIds)) {
				$validates = false;
			}
		}
			
		if($validates) {
			$cache['linkRecords'] = isset($records) ? $records : array();
		}
			
		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}
			
		return $validates;
	}

	function update($data,$cache,&$changes) {
		$records = $cache['linkRecords'];
			
			
		if(property_exists($data,'links')) {
			$links = array();
			$time = time();
				
			foreach($data->links as $link) {
				if(property_exists($link,'id')) {
					$found = false;
					foreach($records as $record) {
						if($record->id == $link->id) {
							$found = true;
							break;
						}
					}
					if($found){
						//update record properties if they are to be changed
						if(property_exists($link,'title')) {
							$record->title = $link->title;
						}
						if(property_exists($link,'url')) {
							$record->url = $link->url;
						}
						if(property_exists($link,'type')) {
							$record->type_num = $link->type;
						}
						//required entries
						$record->modified = $this->db->datetime($time);
						$record->modifier = $this->userId;
							
						//update record
						if($this->db->updateRecord(MediabirdConfig::tableName("Link",true),$record)) {
							$link = clone $link;
							$link->modified = $time;
							$link->modifier = $record->modifier;
							$link->userId = intval($record->user_id);
							$links []= $link;
						}
						else {
							return MediabirdConstants::serverError;
						}
					}
				}
				else {
					//initialize new record
					$record = (object)null;
					$record->title = $link->title;
					$record->url = $link->url;
					$record->type_num = $link->type;

					//required entries
					$record->user_id = $this->userId;
					$record->modified = $record->created = $this->db->datetime($time);
					$record->modifier = $this->userId;

					//insert record
					if($record->id = $this->db->insertRecord(MediabirdConfig::tableName("Link",true),$record)) {
						$link = clone $link;
						$link->id = intval($record->id);
						$link->modified = $time;
						$link->userId = $record->user_id;
						$link->modifier = $record->modifier;
						$links []= $link;
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
			}

			$changes['links'] = $links;
		}
			
		if(property_exists($data,'deletedLinkIds')) {
			$select = "id IN (".join(",",$data->deletedLinkIds).")";
			if(!$this->db->deleteRecords(MediabirdConfig::tableName("Link",true),$select)){
				return MediabirdConstants::serverError;
			}
			$changes['deletedLinkIds'] = array_values($data->deletedLinkIds);
		}
			
		return MediabirdConstants::processed;
	}

	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['links']['fromTime']) ? $data['links']['fromTime'] : 0;
		$loadedIds = isset($data['links']['loadedIds']) ? array_values($data['links']['loadedIds']) : array();
			
		$ids = isset($data['links']['restrictIds']) ? array_values($data['links']['restrictIds']) : array();
			
		$links = array();
			
		$select = "(user_id=$this->userId OR id IN (
				SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='link' AND marker_id IN (
					SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$this->userId OR shared=1) AND topic_id IN (
						SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
					)
				)
			))";
			
		if(count($ids)>0) {
			$select = "id IN (".join(",",$ids).") AND ".$select;
		}
			
		$links = array();
			
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Link",true),$select)) {
			foreach($records as $record) {
				$link = (object)null;
				$link->id = intval($record->id);
					
				MediabirdUtility::arrayRemove($loadedIds,$link->id);
					
				$link->modified = $this->db->timestamp($record->modified);
					
				if($link->modified > $fromTime){
					$link->title = $record->title;
					$link->url = $record->url;
					$link->type = intval($record->type_num);
					$link->userId = intval($record->user_id);
					$link->modifier = intval($record->modifier);
					
					//in case database was upgraded, it won't feature a valid modifier entry
					if($link->modifier==0) {
						$link->modifier = $link->userId;
					}
					
					$links []= $link;
				}
			}
		}
			
			
		$results['links'] = $links;
		if(count($loadedIds)>0) {
			$results['removedLinkIds'] = $loadedIds;
		}
		return true;
	}

	function delete($id) {
		$deleteLinkQuery = "id=$id";
		$result =
		$this->db->deleteRecords(MediabirdConfig::tableName('Link',true),$deleteLinkQuery);
		return $result;
	}
		
}
?>