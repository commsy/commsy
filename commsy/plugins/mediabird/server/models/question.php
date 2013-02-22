<?php
/*
 * 	Copyright (C) 2009-2010 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdQuestion extends MediabirdModel {
	const modelName = "Question";
	
	const changeTypeCriticalItemModified = "question_critical_modified";
	const changeTypeAnsweredItemModified = "question_answered_modified";
	const changeTypeSolvedItemModified = "question_solved_modified";
	
	const itemTypeQuestion = 0; //actually flash card
	const itemTypeAnnotation = 1;
	const itemTypeDefinition = 2;
	const itemTypeIssue = 3; //actually question
	const itemTypeRelatedQuestion = 4; //actually repetition mark
	const itemTypeRelatedTerm = 5;
	const itemTypeFeedback = 6;
	const itemTypeFeedbackSolved = 7;
	const itemTypeIssueForce = 8;
	const itemTypeIssueSolved = 9;
	//just a reserved constant, unspecific so far
	const itemTypeReserved = 10;
		
	var $name = self::modelName;
	
	var $maxQuestionSize = 1536;
	var $maxAnswerSize = 1536;
	
	
	var $allowedQuestionModes = array(
		self::itemTypeQuestion,
		self::itemTypeAnnotation,
		self::itemTypeDefinition,
		self::itemTypeIssue,
		self::itemTypeRelatedQuestion,
		self::itemTypeRelatedTerm,
		self::itemTypeFeedback,
		self::itemTypeFeedbackSolved,
		self::itemTypeIssueForce,
		self::itemTypeIssueSolved,
		self::itemTypeReserved
	);
	
	//fixme: on client: delete dependent objects as well when updating associated answers

	var $questionProperties = array('id','question','mode');
	var $answerProperties = array('id','answer');

	var $updateParams = array(
		'question',
		'answers',
		'deletedAnswerIds',
		'votedAnswerIds',
		'unvotedAnswerIds',
		'starAnswerId'
	);

	function validate($data,&$cache,&$reason) {
			
		$validates = 
			is_object($data) &&
			MediabirdUtility::checkKeyset($data,$this->updateParams,true) && 
			property_exists($data,'question') && 
			is_object($data->question) && 
			MediabirdUtility::checkKeyset($data->question,$this->questionProperties,true);
			  	 
	  	//validate question
	  	if($validates) {
	  		$question = $data->question;
	  		//question id must be integer
	  		if(property_exists($question,'id') && !is_int($question->id)) {
	  			$validates = false;
	  		}

	  		//if question id is given, check that user is owner or has access
	  		if($validates && property_exists($question,'id')) {
	  			//check permission
	  			$select = "id=$question->id AND (user_id=$this->userId OR id IN (
					SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='question' AND marker_id IN (
						SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE shared=1 AND topic_id IN (
							SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
						)
					)
				))";
	  			if($questionRecord = $this->db->getRecord(MediabirdConfig::tableName("Question",true),$select)) {
	  				$cache['questionRecord']=$questionRecord;
	  			}
	  			else {
	  				$reason = MediabirdConstants::accessDenied;
	  				$validates = false;
	  			}
	  		}

	  		//at least id or {question,mode} must be given
	  		if($validates && !property_exists($question,'id') && (!property_exists($question,'question') || !property_exists($question,'mode'))){
	  			$validates = false;
	  		}

	  		//check if question mode is valid
	  		if($validates && property_exists($question,'mode')) {
	  			if(!in_array($question->mode,$this->allowedQuestionModes,true)) {
	  				$validates = false;
	  			}
	  		}

	  		//check if question is string, no larger than max size
	  		if($validates && property_exists($question,'question')) {
	  			if($question->question !== null && (!is_string($question->question) || strlen($question->question)>$this->maxQuestionSize)) {
	  				$validates = false;
	  			}
	  			 
	  			//if id given, user must be owner of question record
	  			if(property_exists($question,'id') && $questionRecord->user_id!=$this->userId) {
	  				$reason = MediabirdConstants::accessDenied;
	  				$validates = false;
	  			}
	  		}
	  	}
	  	 
	  	//votes and stars may only be given if question already exists (so there is a chance that the answers exist as well)
	  	if($validates && !isset($questionRecord) && (property_exists($data,'votedAnswerIds')||property_exists($data,'unvotedAnswerIds')||property_exists($data,'starAnswerId'))) {
	  		$validates = false;
	  	}
	  	 
	  	$referredAnswerIds = array();
	  	 
	  	//check vote answer ids
	  	if($validates && property_exists($data,'votedAnswerIds')) {
	  		if(!is_array($data->votedAnswerIds) || count($data->votedAnswerIds)==0 || !MediabirdUtility::checkUnique($data->votedAnswerIds)) {
	  			$validates = false;
	  		}
	  		if($validates) {
	  			foreach($data->votedAnswerIds as $votedAnswerId) {
	  				if(!is_int($votedAnswerId)) {
	  					$validates = false;
	  					break;
	  				}

	  				$referredAnswerIds []= $votedAnswerId;
	  			}
	  		}
	  	}
	  	 
	  	//check unvote answer ids
	  	if($validates && property_exists($data,'unvotedAnswerIds')) {
	  		if(!is_array($data->unvotedAnswerIds) || count($data->unvotedAnswerIds)==0 || !MediabirdUtility::checkUnique($data->unvotedAnswerIds)) {
	  			$validates = false;
	  		}
	  		if($validates) {
	  			foreach($data->unvotedAnswerIds as $unvotedAnswerId) {
	  				if(!is_int($unvotedAnswerId)) {
	  					$validates = false;
	  					break;
	  				}

	  				//unvoted answers must currently be voted for
	  				$unvotedAnswerIds []= $unvotedAnswerId;

	  				//unvoted answers must not be in the list of voted answers
	  				if(in_array($unvotedAnswerId,$referredAnswerIds)) {
	  					$validates = false;
	  					break;
	  				}
	  			}
	  		}

	  		//check if unvoted answers are currently voted for
	  		if($validates) {
	  			$select = "answer_id IN (".join(",",$unvotedAnswerIds).") AND user_id=$this->userId";
	  			if($this->db->countRecords(MediabirdConfig::tableName("Vote",true),$select)!=count($unvotedAnswerIds)) {
	  				$reason = MediabirdConstants::accessDenied;
	  				$validates = false;
	  			}
	  		}
	  	}
	  	 
	  	//check star answer id
	  	if($validates && property_exists($data,'starAnswerId')) {
	  		if(!is_int($data->starAnswerId)) {
	  			$validates = false;
	  		}
	  		else {
	  			if($data->starAnswerId !=0 && array_search($data->starAnswerId,$referredAnswerIds)===false) {
	  				$referredAnswerIds []= $data->starAnswerId;
	  			}
	  		}
	  	}
	  	
	  	//save changed answer ids
	  	$updatedAnswerIds = array();
	  	 
	  	//validate answers
	  	if($validates && property_exists($data,'answers')) {
	  		if(!is_array($data->answers) || count($data->answers)==0 || !MediabirdUtility::checkUnique($data->answers,'id')) {
	  			$validates = false;
	  		}

	  		if($validates) {
	  			foreach($data->answers as $answer) {
	  				if(!is_object($answer) || !MediabirdUtility::checkKeyset($answer,$this->answerProperties,true)) {
	  					$validates = false;
	  					break;
	  				}

	  				//answer id must be integer
	  				if(property_exists($answer,'id') && !is_int($answer->id)) {
	  					$validates = false;
	  					break;
	  				}

	  				//if answer id given, question must exist and an answer be given
	  				if(property_exists($answer,'id') && (!property_exists($answer,'answer') || !isset($questionRecord))) {
	  					$validates = false;
	  					break;
	  				}

	  				//either id or answer must be given
	  				if(!property_exists($answer,'id') && !property_exists($answer,'answer')){
	  					$validates = false;
	  					break;
	  				}

	  				//check if question is string, no larger than max size and if id given, user must be owner of question record
	  				if(property_exists($answer,'answer')) {
	  					if(!is_string($answer->answer) || strlen($answer->answer)>$this->maxAnswerSize) {
	  						$validates = false;
	  						break;
	  					}
	  				}

	  				//if question id is given, check that user is owner
	  				if(property_exists($answer,'id')) {
	  					$updatedAnswerIds []= $answer->id;
	  				}
	  			}
	  			 
	  			if($validates && count($updatedAnswerIds)>0) {
	  				//only owner may edit question
	  				$select = "id IN (".join(",",$updatedAnswerIds).") AND user_id=$this->userId AND question_id=$questionRecord->id";
	  				$answerRecords = $this->db->getRecords(MediabirdConfig::tableName("Answer",true),$select);
	  				if($answerRecords && count($answerRecords) == count($updatedAnswerIds)) {
	  					$cache['answerRecords'] = $answerRecords;
	  				}
	  				else {
	  					$reason = MediabirdConstants::accessDenied;
	  					$validates = false;
	  				}
	  			}
	  		}
	  	}
	  	 
	  	//remove checked answers from list of answers that are to be checked
	  	if($validates && count($referredAnswerIds)>0 && count($updatedAnswerIds)>0) {
	  		foreach($updatedAnswerIds as $answerId) {
	  			$key = array_search($answerId,$referredAnswerIds);
	  			if($key!==false) {
	  				array_splice($referredAnswerIds,$key,1);
	  			}
	  		}
	  	}
	  	 
	  	//validate deleted answers array
	  	if($validates && property_exists($data,'deletedAnswerIds')) {
	  		//answers may not be deleted if there is no question record
	  		if(!isset($questionRecord)) {
	  			$validates = false;
	  		}
	  		if($validates && !is_array($data->deletedAnswerIds) || count($data->deletedAnswerIds)==0 || !MediabirdUtility::checkUnique($data->deletedAnswerIds)) {
	  			$validates = false;
	  		}

	  		//all ids must be int
	  		if($validates) {
	  			foreach($data->deletedAnswerIds as $deletedAnswerId) {
	  				if(!is_int($deletedAnswerId)) {
	  					$validates = false;
	  					break;
	  				}

	  				//if answer that is to be deleted is being referred to by votes, starring or updated answers, validation fails
	  				if($validates && (array_search($deletedAnswerId,$referredAnswerIds)!==false || array_search($deletedAnswerId,$updatedAnswerIds)!==false)) {
	  					$validates = false;
	  					break;
	  				}
	  			}
	  		}
	  	}
	  	 
	  	//include unvoted answers in check
	  	if($validates && isset($unvotedAnswerIds)) {
	  		$referredAnswerIds = array_merge($referredAnswerIds,$unvotedAnswerIds);
	  	}
	  	 
	  	//check if voted, unvoted or starred answers are accessible
	  	if($validates && count($referredAnswerIds)>0) {
	  		//only answer must be related to given question (since that is owned by or shared with current user)
	  		$select = "id IN (".join(",",$referredAnswerIds).") AND question_id=$questionRecord->id";
	  		if($this->db->countRecords(MediabirdConfig::tableName("Answer",true),$select) != count($referredAnswerIds)) {
	  			$reason = MediabirdConstants::accessDenied;
	  			$validates = false;
	  		}
	  	}
	  	 
	  	//check if to be deleted answers really can be deleted
	  	if($validates && property_exists($data,'deletedAnswerIds')) {
	  		//only owners may delete answers, answer must belong to this question
	  		//question_id <=> parent field
	  		$select = "id IN (".join(",",$data->deletedAnswerIds).") AND user_id=$this->userId AND question_id=$questionRecord->id";
	  		if($this->db->countRecords(MediabirdConfig::tableName("Answer",true),$select)!=count($data->deletedAnswerIds)) {
	  			$reason = MediabirdConstants::accessDenied;
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

		$question = $data->question;
		if(property_exists($question,'id')) {
			$questionRecord = $cache['questionRecord'];

			if(	property_exists($question,'question') ||
				property_exists($question,'mode') ||
				property_exists($data,'answers') ||
				property_exists($data,'deletedAnswerIds')
				) {
				if(property_exists($question,'question')){
					$questionRecord->question = MediabirdUtility::purifyHTML($question->question);
				}
				if(property_exists($question,'mode')) {
					$questionRecord->question_mode = $question->mode;
				}
				$questionRecord->modified = $this->db->datetime($time);
				$questionRecord->modifier = $this->userId;
					
				if($this->db->updateRecord(MediabirdConfig::tableName("Question",true),$questionRecord)) {
					$question = clone $question;
					$question->question = $questionRecord->question;
					$question->modifier = $questionRecord->modifier;
					$question->modified = $time;
				}
				else {
					return MediabirdConstants::serverError;
				}
			}
			else {
				$question = (object)array(
					'id'=>intval($questionRecord->id)
				);
			}
		}
		else {
			$questionRecord = (object)null;
			//data given by user
			$questionRecord->question = MediabirdUtility::purifyHTML($question->question);
			$questionRecord->question_mode = $question->mode;

			//default data
			$questionRecord->user_id = $this->userId;
			$questionRecord->created = $questionRecord->modified = $this->db->datetime($time);
			$questionRecord->modifier = $this->userId;
				
			//insert new record
			if($questionRecord->id=$this->db->insertRecord(MediabirdConfig::tableName("Question",true),$questionRecord)) {
				$question = (object) array(
						'id'=>intval($questionRecord->id),
						'question'=>$questionRecord->question,
						'mode'=>$questionRecord->question_mode,
						'userId'=>$questionRecord->user_id,
						'modified'=>$time,
						'modifier'=>$questionRecord->modifier
				);
			}
			else {
				return MediabirdConstants::serverError;
			}
		}
		
		$changes['questions'] = array($question);
		
		//create and store questions
		if(property_exists($data,'answers')) {
			$answerRecords = isset($cache['answerRecords']) ? $cache['answerRecords'] : array();
			$question->answers = array();
			
			foreach($data->answers as $answer) {
				if(property_exists($answer,'id')) {
					//update record
					$found = false;
					foreach($answerRecords as $answerRecord) {
						if($answerRecord->id==$answer->id) {
							$found = true;
							break;
						}
					}
					if($found) {
						$answerRecord->answer = MediabirdUtility::purifyHTML($answer->answer);
						$answerRecord->modified = $this->db->datetime($time);
						if($this->db->updateRecord(MediabirdConfig::tableName("Answer",true),$answerRecord)) {
							$answer = clone $answer;
							$answer->answer = $answerRecord->answer;
							$answer->modified = $time;
							$changes['answers'] []= $answer;
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
					$answerRecord = (object)null;
					$answerRecord->answer = MediabirdUtility::purifyHTML($answer->answer);
					$answerRecord->question_id = $question->id;
					$answerRecord->user_id = $this->userId;
					$answerRecord->created = $answerRecord->modified = $this->db->datetime($time);
					if($answerRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Answer",true),$answerRecord)) {
						$answer = (object) array(
								'id'=>intval($answerRecord->id),
								'answer'=>$answerRecord->answer,
								'userId'=>$answerRecord->user_id,
								'modified'=>$time
						);
						$question->answers []= $answer;
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
			}
		}
			
		//create new votes
		if(property_exists($data,'votedAnswerIds')) {
			$votedAnswerIds = array_values($data->votedAnswerIds);

			$question->votes = array();
			
			//retrieve possible votes
			$select = "answer_id IN (".join(",",$votedAnswerIds).") AND user_id=$this->userId";

			$voteRecords = $this->db->getRecords(MediabirdConfig::tableName("Vote",true),$select);
			$voteRecords = $voteRecords ? $voteRecords : array();

			//do not vote if already voted for!
			foreach($voteRecords as $voteRecord) {
				$key = array_search(intval($voteRecord->id),$votedAnswerIds);
				if($key!==false) {
					array_splice($votedAnswerIds,$key,1);
				}
			}

			foreach($votedAnswerIds as $votedAnswerId) {
				$voteRecord = (object)null;
				$voteRecord->answer_id = $votedAnswerId;
				$voteRecord->user_id = $this->userId;
				$voteRecord->created = $voteRecord->modified = $this->db->datetime($time);
					
				if($voteRecord->id=$this->db->insertRecord(MediabirdConfig::tableName("Vote",true),$voteRecord)) {
					$vote = (object)array(
						'id'=>intval($voteRecord->id),
						'modified'=>$time,
						'userId'=>$voteRecord->user_id,
						'answerId'=>$voteRecord->answer_id
					);
					$question->votes []= $vote;
				}
				else {
					return MediabirdConstants::serverError;
				}
			}
		}
			
		//delete old votes
		if(property_exists($data,'unvotedAnswerIds')) {
			$unvotedAnswerIds = $data->unvotedAnswerIds;
			$select = "answer_id IN (".join(",",$unvotedAnswerIds).") AND user_id=$this->userId";
			if($this->db->deleteRecords(MediabirdConfig::tableName("Vote",true),$select)) {
				$changes['unvotedAnswerIds'] = array_values($unvotedAnswerIds);
			}
		}
			
		//update star
		if(property_exists($data,'starAnswerId')) {
			$starAnswerId = $data->starAnswerId;
			
			$select = "user_id=$this->userId AND question_id=$question->id";
			if($starRecord=$this->db->getRecord(MediabirdConfig::tableName("Star",true),$select)) {
				if($starAnswerId!=0) {
					//update star record
					$starRecord->answer_id = $starAnswerId;
					$starRecord->modified = $this->db->datetime($time);
					if($this->db->updateRecord(MediabirdConfig::tableName("Star",true),$starRecord)) {
						$star = (object) array(
							'id'=>intval($starRecord->id),
							'userId'=>$this->userId,
							'answerId'=>$starAnswerId,
							'modified'=>$time
						);
						$question->stars = array($star);
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
				else {
					//delete star
					$select = "id=$starRecord->id";
					if($this->db->deleteRecords(MediabirdConfig::tableName("Star",true),$select)) {
						$changes['removedStarIds'] = array(intval($starRecord->id));
					}
					else {
						return MediabirdConstants::serverError;
					}
				}
			}
			else if($starAnswerId!=0) {
				//create new star record
				$starRecord = (object)null;
				$starRecord->answer_id = $starAnswerId;
				$starRecord->user_id = $this->userId;
				$starRecord->question_id = $question->id;
				$starRecord->created = $starRecord->modified = $this->db->datetime($time);
				if($starRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("Star",true),$starRecord)) {
					$star = (object) array(
							'id'=>intval($starRecord->id),
							'userId'=>$this->userId,
							'answerId'=>$starAnswerId,
							'modified'=>$time
					);
					$question->stars = array($star);
				}
				else {
					return MediabirdConstants::serverError;
				}
			}
		}
			
			
		//delete answers that are to be deleted
		if(property_exists($data,'deletedAnswerIds')) {
			$deletedAnswerIds = $data->deletedAnswerIds;

			//delete votes, stars
			$selectStars = $selectVotes = "answer_id IN (".join(",",$deletedAnswerIds).")";

			$this->db->deleteRecords(MediabirdConfig::tableName("Vote",true),$selectVotes);
			$this->db->deleteRecords(MediabirdConfig::tableName("Star",true),$selectStars);

			//delete answers
			$select = "id IN (".join(",",$deletedAnswerIds).")";
			$this->db->deleteRecords(MediabirdConfig::tableName("Answer",true),$select);

			$changes['removedAnswerIds'] = array_values($deletedAnswerIds);
		}
		
		return MediabirdConstants::processed;
	}
	
	function checkDependencies($question,$relations) {
		foreach($relations as $relation) {
			if($relation->type=="flashcard") {
				return false; //everything okay
			}
		}
		//no flashcard was defined, check if required
		
		//question=0, definition=2, relatedQuestion=4, relatedTerm=5
		$select = "id=$question->id AND question_mode IN (0,2,4,5)"; 
		
		$requireFlashcard = false;
		
		//check if this question requires flashcard
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Question",true),$select,'','id,question_mode')) {
			foreach($records as $record) {
				$mode = intval($record->question_mode);

				//question and definition require at least one answer to be trainable
				if($mode == 0 || $mode == 2) {
					$select = "question_id=$record->id";
					$answerCount = $this->db->countRecords(MediabirdConfig::tableName("Answer",true),$select);

					if($answerCount > 0) {
						$requireFlashcard = true;
					}
				}
				else {
					$requireFlashcard = true;
				}
			}
		}
	
		if($requireFlashcard) {
			//create new flashcard
			
			if($flashcardId = $this->controller->Flashcard->createDefault()) {
				//return dummy relation
				
				$relation = (object)array(
					'type'=>'flashcard',
					'relatedId'=>$flashcardId,
					'shared'=>false
				);
				
				return array($relation);
			}
		}
		
		return false;
	}

	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['questions']['fromTime']) ? $data['questions']['fromTime'] : 0;
		$fromTopic = isset($data['questions']['fromTopic']) ? $data['questions']['fromTopic'] : 0;
			
		$questionLoadedIds = isset($data['questions']['loadedIds']) ? array_values($data['questions']['loadedIds']) : array();
		$answerLoadedIds = isset($data['answers']['loadedIds']) ? array_values($data['answers']['loadedIds']) : array();
		$voteLoadedIds = isset($data['votes']['loadedIds']) ? array_values($data['votes']['loadedIds']) : array();
		$starLoadedIds = isset($data['stars']['loadedIds']) ? array_values($data['stars']['loadedIds']) : array();
			
		$ids = isset($data['questions']['restrictIds']) ? array_values($data['questions']['restrictIds']) : array();
			
		if($fromTopic == 0) {
			$select = "(user_id=$this->userId OR id IN (
				SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='question' AND marker_id IN (
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
				SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='question' AND marker_id IN (
					SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$this->userId OR shared=1) AND topic_id=$fromTopic
				)
			)";	
		}
			
		$questions = array();
			
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Question",true),$select)) {
			foreach($records as $record) {
				$hasChanges = false; //defines if this question should be returned or not
				
				$question = (object)null;
				$question->id = intval($record->id);
				
				MediabirdUtility::arrayRemove($questionLoadedIds,$question->id);
				
				$question->modified = $this->db->timestamp($record->modified);
					
				//this more complicated approach is required,
				//because question records won't be updated even
				//if answers, votes or stars change!
				//as a result, we have to go through all related records
				//even if the question is up to date!
				if($question->modified > $fromTime) {
					//send question and modification date back if newer than fromTime
					$question->question = $record->question;
					$question->mode = intval($record->question_mode);
					$question->userId = intval($record->user_id);
					$question->modifier = intval($record->modifier);
					
					//in case database was upgraded, it won't feature a valid modifier entry
					if($question->modifier==0) {
						$question->modifier = $question->userId;
					}
					
					$hasChanges = true;
				}
					
				//load answers
				$select = "question_id=$question->id";
				if($answerRecords = $this->db->getRecords(MediabirdConfig::tableName("Answer",true),$select)) {
					$question->answers = array();
					$question->votes = array();
					foreach($answerRecords as $answerRecord) {
						$answer = (object) array(
								'id'=>intval($answerRecord->id),
								'answer'=>$answerRecord->answer,
								'userId'=>intval($answerRecord->user_id),
								'modified'=>$this->db->timestamp($answerRecord->modified)
						);
							
						MediabirdUtility::arrayRemove($answerLoadedIds,$answer->id);
							
						if($answer->modified > $fromTime) {
							$question->answers []= $answer;
							$hasChanges = true;
						}
							
						//load votes
						$select = "answer_id=$answerRecord->id";
						if($voteRecords = $this->db->getRecords(MediabirdConfig::tableName("Vote",true),$select)) {
							foreach($voteRecords as $voteRecord) {
								$vote = (object)array(
									'id'=>intval($voteRecord->id),
									'modified'=>$this->db->timestamp($voteRecord->modified),
									'userId'=>intval($voteRecord->user_id),
									'answerId'=>intval($voteRecord->answer_id)
								);
								MediabirdUtility::arrayRemove($voteLoadedIds,$vote->id);
									
								if($vote->modified > $fromTime) {
									$question->votes []= $vote;
									$hasChanges = true;
								}
							}
						}
					}
				}
					
				//load star for current user
				$select = "question_id=$question->id AND user_id=$this->userId";
				if($starRecord = $this->db->getRecord(MediabirdConfig::tableName("Star",true),$select)) {
					$star = (object) array(
							'id'=>intval($starRecord->id),
							'userId'=>intval($starRecord->user_id),
							'answerId'=>intval($starRecord->answer_id),
							'modified'=>$this->db->timestamp($starRecord->modified)
					);

					MediabirdUtility::arrayRemove($starLoadedIds,$star->id);

					if($star->modified > $fromTime) {
						$question->stars = array($star);
						$hasChanges = true;
					}
				}
				
				if($hasChanges) {
					$questions []= $question;
				}
			}
		}
			
		if(count($questions)>0) {
			$results['questions'] = $questions;
		}
		if(count($questionLoadedIds)>0) {
			$results['removedQuestionIds'] = $questionLoadedIds;
		}
		if(count($voteLoadedIds)>0) {
			$results['removedVoteIds'] = $voteLoadedIds;
		}
		if(count($starLoadedIds)>0) {
			$results['removedStarIds'] = $starLoadedIds;
		}
		if(count($answerLoadedIds)>0) {
			$results['removedAnswerIds'] = $answerLoadedIds;
		}
		return true;
	}

	function delete($id) {
		//also delete answers,votes,stars
		$select = "question_id=$id";
			
		//find answers
		if($answerRecords = $this->db->getRecords(MediabirdConfig::tableName("Answer",true),$select,'','id')) {
			$deletedAnswerIds = array();
			foreach($answerRecords as $answerRecord) {
				$deletedAnswerIds []= intval($answerRecord->id);
			}

			//delete votes, stars
			$selectStars = $selectVotes = "answer_id IN (".join(",",$deletedAnswerIds).")";

			$this->db->deleteRecords(MediabirdConfig::tableName("Vote",true),$selectVotes);
			$this->db->deleteRecords(MediabirdConfig::tableName("Star",true),$selectStars);

			//delete answer
			$select = "id IN (".join(",",$deletedAnswerIds).")";
			$this->db->deleteRecords(MediabirdConfig::tableName("Answer",true),$select);

		}
			
		//delete question
		$select = "id=$id";
		$this->db->deleteRecords(MediabirdConfig::tableName("Question",true),$select);
	}
	
	/**
	 * Returns the change types supported by this model
	 * @return string[]
	 */
	function getAvailableChangeTypes() {
		return array(
			self::changeTypeCriticalItemModified,
			self::changeTypeAnsweredItemModified,
			self::changeTypeSolvedItemModified
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
			if(	$type==self::changeTypeCriticalItemModified ||
				$type==self::changeTypeAnsweredItemModified ||
				$type==self::changeTypeSolvedItemModified) {
				
				$itemTypes = array();
					
				//determine what item types are to be matched
				if($type == self::changeTypeCriticalItemModified) {
					$itemTypes = array(
						self::itemTypeIssue,
						self::itemTypeIssueForce,
						self::itemTypeFeedback
					);
				}
				else if($type == self::changeTypeAnsweredItemModified) {
					$itemTypes = array(
						self::itemTypeIssue,
						self::itemTypeIssueForce,
						self::itemTypeFeedback
					);
				}
				else if($type == self::changeTypeSolvedItemModified) {
					$itemTypes = array(
						self::itemTypeIssueSolved,
						self::itemTypeFeedbackSolved
					);
				}
					
				//create select clause
				$select = "
					modified>'".$this->db->datetime($since)."' AND 
					question_mode IN (".join(",",$itemTypes).") AND 
					(
						user_id=$userId OR 
						id IN (
							SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='question' AND marker_id IN (
								SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE (user_id=$userId OR shared=1) AND topic_id IN (
									SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
								)
							)
						)
					)
				";
				
				$sort = 'modified DESC';
				
				//retrieve matching records from db
				$questionRecords = $this->db->getRecords(
					MediabirdConfig::tableName('Question',true),
					$select,
					$sort
				);
				
				//check if list is to be filtered by answer count
				if(	$type == self::changeTypeCriticalItemModified ||
					$type == self::changeTypeAnsweredItemModified) {
					$questionIds = array();
					
					//collect question ids
					if($questionRecords) {
						foreach($questionRecords as $questionRecord) {
							$questionIds []= $questionRecord->id;
						}
					}
					
					//get answers that match
					if(count($questionIds)>0) {
						$fields = "id,question_id,user_id";
						$select = "question_id IN (".join(",",$questionIds).")";
						
						$answerRecords = $this->db->getRecords(MediabirdConfig::tableName("Answer",true),$select,$sort,$fields);
					}
					else {
						$answerRecords = null;
					}
					
					if(!$answerRecords && $type == self::changeTypeAnsweredItemModified) {
						continue;
					}
					
					//collect valid questions
					$validQuestionRecords = array();
					
					foreach($questionRecords as $questionRecord) {
						//get question id
						$questionId = $questionRecord->id;
						
						//search if there is an answer record referring to that question
						$answerFound = false;
						foreach($answerRecords as $answerRecord) {
							if($answerRecord->question_id == $questionId) {
								$answerFound = true;
								//save reference to answer record
								$questionRecord->answerRecord = $answerRecord;
								break;
							}
						}
						
						if($type == self::changeTypeCriticalItemModified && !$answerFound) {
							$validQuestionRecords []= $questionRecord;
						}
						else if($type == self::changeTypeAnsweredItemModified && $answerFound) {
							$validQuestionRecords []= $questionRecord;
						}
					}
					
					$questionRecords = $validQuestionRecords;
				}
				
				//create change info for each new record
				foreach($questionRecords as $record) {
					$changeInfo = new MediabirdChangeInfo($this->name,$since,$userId);
					
					$changeInfo->itemId = $record->id;
					
					$changeInfo->itemCreated = $this->db->timestamp($record->created);
					$changeInfo->itemModified = $this->db->timestamp($record->modified);
					
					
					if(property_exists($record,"answerRecord")) {
						$changeInfo->itemModifier = intval($record->answerRecord->user_id);
					}
					else {
						$changeInfo->itemModifier = intval($record->user_id);
					}
					
					$changeInfo->changeType = $type; 
					
					$changeInfo->itemTitle = strip_tags($record->question);
					
					$changeInfo->itemRecord = $record;
				
					$changes[$type] []= $changeInfo;
				}
			}
		}
		
		return $changes;
	}
}

?>
