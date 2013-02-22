<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdFlashcard extends MediabirdModel {
	var $flashcardProperties = array('results','answerTime','level','id');

	var $name = "Flashcard";
	
	var $allowedResults = array(0,1,2,3);
	var $allowedLevels = array(1,2,3,4);
	var $resultsCount = 5;

	function resultsFromTraining($trainingData) {
		$trainingData = intval($trainingData);
		$results = array();
		//convert integer into array
		for ($i = 0; $i < 5; $i++) {
			$results []= ($trainingData & 3*pow(4, $i))/pow(4, $i);
		}
		return $results;
	}

	function trainingFromResults($results) {
		$trainingData = 0;
		for ($i = 0; $i < sizeof($results); $i++) {
			$trainingData |= pow(4, $i)*$results[$i];
		}
		return $trainingData;
	}

	var $updateParams = array('flashcards','deletedFlashcardIds');
	//columns: results, answer_time, level_num, user_id, created, modified

	function validate($data,&$cache,&$reason) {
		$time = time();
		/* incoming:
		 * 	flashcards
		 * 		flashcard.results (array, items must be member of $allowedResults)
		 * 		flashcard.answerTime (int, must be smaller than $time)
		 * 		flashcard.level (any of $allowedLevels)
		 * 		flashcard.id (integer)
		 *  deletedFlashcardIds
		 *
		 *  generally: only owner can change or delete flashcards
		 */
			

		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->updateParams,true);
			
		$referredIds = array();
		if($validates && property_exists($data,'flashcards')) {
			//check if flashcards is an array, if it contains has at least one member, if the referred ids do not occur twice
			if(!is_array($data->flashcards) || count($data->flashcards) == 0 || !MediabirdUtility::checkUnique($data->flashcards,'id')) {
				$validates = false;
			}

			if($validates) {
				foreach ($data->flashcards as $flashcard) {
					if(!MediabirdUtility::checkKeyset($flashcard,$this->flashcardProperties,true)) {
						$validates = false;
						break;
					}

					//if no id given, all results, answerTime and level must be given
					if(!property_exists($flashcard,'id') && (!property_exists($flashcard,'results') || !property_exists($flashcard,'answerTime') || !property_exists($flashcard,'level'))) {
						$validates = false;
						break;
					}

					//if id given, it must be integer
					if(property_exists($flashcard,'id')) {
						if(!is_int($flashcard->id)) {
							$validates = false;
							break;
						}
						$referredIds []= $flashcard->id;
					}

					//type must be in $allowedLevels if given
					if(property_exists($flashcard,'level') && !in_array($flashcard->level,$this->allowedLevels,true)) {
						$validates = false;
						break;
					}

					//answer time must be equal or smaller than current time
					if(property_exists($flashcard,'answerTime') && (!is_int($flashcard->answerTime) || $flashcard->answerTime > $time)) {
						$validates = false;
						break;
					}

					//results must be an array of integers in the range $allowedResults
					if(property_exists($flashcard,'results')) {
						if( !is_array($flashcard->results) || count($flashcard->results)!=$this->resultsCount) {
							$validates = false;
							break;
						}
						foreach($flashcard->results as $result) {
							if(!in_array($result,$this->allowedResults,true)) {
								$validates = false;
								break;
							}
						}
						if(!$validates) {
							break;
						}
					}
				}
			}
		}
			
		if($validates && property_exists($data,'deletedFlashcardIds')) {
			if(!is_array($data->deletedFlashcardIds) || count($data->deletedFlashcardIds)==0 || !MediabirdUtility::checkUnique($data->deletedFlashcardIds)) {
				$validates = false;
			}

			if($validates) {
				foreach($data->deletedFlashcardIds as $deletedFlashcardId) {
					if(!is_int($deletedFlashcardId)) {
						$validates = false;
						break;
					}
					$referredIds []= $deletedFlashcardId;
				}
			}
		}
			
		if($validates && count($referredIds)>0) {
			//check if user has access to these ids
			$select = "id IN (".join(",",$referredIds).") AND user_id=$this->userId";
			if(!$records = $this->db->getRecords(MediabirdConfig::tableName("Flashcard",true),$select,'','id')) {
				$validates = false;
			}

			if($validates && count($records) != count($referredIds)) {
				$validates = false;
			}
		}
			
		if($validates) {
			$cache['flashcardRecords'] = isset($records) ? $records : array();
		}
			
		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}
			
		return $validates;
	}

	function update($data,$cache,&$changes) {
		$records = $cache['flashcardRecords'];
			
		if(property_exists($data,'flashcards')) {
			$flashcards = array();
			$time = time();
				
			foreach($data->flashcards as $flashcard) {
				if(property_exists($flashcard,'id')) {
					$found = false;
					foreach($records as $record) {
						if($record->id == $flashcard->id) {
							$found = true;
							break;
						}
					}
					if($found){
						//update record properties if they are to be changed
						if(property_exists($flashcard,'results')) {
							$record->results = $this->trainingFromResults($flashcard->results);
						}
						if(property_exists($flashcard,'level')) {
							$record->level_num = $flashcard->level;
						}
						if(property_exists($flashcard,'answerTime')) {
							$record->answer_time = $flashcard->answerTime;
						}
						//required entries
						$record->modified = $this->db->datetime($time);
							
						//update record
						if($this->db->updateRecord(MediabirdConfig::tableName("Flashcard",true),$record)) {
							$flashcard = clone $flashcard;
							$flashcard->modified = $time;
							$flashcards []= $flashcard;
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
					//initialize new record
					$record = (object)null;
					$record->results = $this->trainingFromResults($flashcard->results);
					$record->level_num = $flashcard->level;
					$record->answer_time = $flashcard->answerTime;

					//required entries
					$record->user_id = $this->userId;
					$record->modified = $record->created = $this->db->datetime($time);

					//insert record
					if($record->id = $this->db->insertRecord(MediabirdConfig::tableName("Flashcard",true),$record)) {
						$flashcard = clone $flashcard;
						$flashcard->id = intval($record->id);
						$flashcard->modified = $time;
						$flashcards []= $flashcard;
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
			}

			$changes['flashcards'] = $flashcards;
		}
			
		if(property_exists($data,'deletedFlashcardIds')) {
			$select = "id IN (".join(",",$data->deletedFlashcardIds).")";
			if(!$this->db->deleteRecords(MediabirdConfig::tableName("Flashcard",true),$select)){
				return MediabirdConstants::serverError;
			}
			$changes['deletedFlashcardIds'] = array_values($data->deletedFlashcardIds);
		}
			
		return MediabirdConstants::processed;
	}

	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['flashcards']['fromTime']) ? $data['flashcards']['fromTime'] : 0;
		$loadedIds = isset($data['flashcards']['loadedIds']) ? array_values($data['flashcards']['loadedIds']) : array();
			
		$ids = isset($data['flashcards']['restrictIds']) ? array_values($data['flashcards']['restrictIds']) : array();
			
		$flashcards = array();
			
		$select = "user_id=$this->userId";
			
		if(count($ids)>0) {
			$select = "id IN (".join(",",$ids).") AND ".$select;
		}
			
		$flashcards = array();
			
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Flashcard",true),$select)) {
			foreach($records as $record) {
				$flashcard = (object)null;
				$flashcard->id = intval($record->id);
					
				MediabirdUtility::arrayRemove($loadedIds,$flashcard->id);
					
				$modified = $this->db->timestamp($record->modified);
					
				if($modified>$fromTime){
					$flashcard->level = intval($record->level_num);
					$flashcard->answerTime = intval($record->answer_time);
					$flashcard->results = $this->resultsFromTraining($record->results);

					$flashcards []= $flashcard;
				}
			}
		}
			
		if(count($flashcards)>0) {
			$results['flashcards'] = $flashcards;
		}
		if(count($loadedIds)>0) {
			$results['removedFlashcardIds'] = $loadedIds;
		}
		return true;
	}
	
	function createDefault() {
		$record = (object)array(
			'answer_time'=>0,
			'results'=>0,
			'user_id'=>$this->userId,
			'level_num'=>1
		);
		$record->created = $record->modified = $this->db->datetime(time());
		$id = $this->db->insertRecord(MediabirdConfig::tableName("Flashcard",true),$record);
		if($id) {
			return intval($id);
		}
		else {
			return null;
		}
	}

	function delete($id) {
		$deleteFlashcardQuery = "id=$id";
		$result =
		$this->db->deleteRecords(MediabirdConfig::tableName('Flashcard',true),$deleteFlashcardQuery);
		return $result;
	}
}
?>