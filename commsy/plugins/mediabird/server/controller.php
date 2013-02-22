<?php
/*
 * 	Copyright (C) 2008-2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

/**
 * Handles client session requests
 * @author fabian
 */
class MediabirdController {
	/**
	 * List of supported model class names
	 * @var string[]
	 */
	var $supportedModelNames = array('User','Topic','Content','TagColor','Markers','Files','Question','Link','Flashcard','Check');
	
	
	
	/**
	 * Database object
	 * @var MediabirdDbo
	 */
	var $db;

	/**
	 * Id of current user
	 * @var int
	 */
	var $userId;

	/**
	 * Auth interface
	 * @var MediabirdAuthManager
	 */
	var $auth;

	/**
	 * User model
	 * @var MediabirdUser
	 */
	var $User;

	/**
	 * Topic model
	 * @var MediabirdTopic
	 */
	var $Topic;

	/**
	 * Card model
	 * @var MediabirdContent
	 */
	var $Content;

	/**
	 * Tag color model
	 * @var MediabirdTagColor
	 */
	var $TagColor;

	/**
	 * Markers model
	 * @var MediabirdMarkers
	 */
	var $Markers;

	/**
	 * Question model, used for discussions
	 * @var MediabirdQuestion
	 */
	var $Question;

	/**
	 * Link model, used for twitter, youtube, wikipedia and generic links
	 * @var MediabirdLink
	 */
	var $Link;

	/**
	 * Flash card model, used for storing flash card results
	 * @var MediabirdFlashcard
	 */
	var $Flashcard;

	/**
	 * File model, used to authorize PDF files, check access and render the associated PNG files
	 * @var MediabirdFiles
	 */
	var $Files;

	/**
	 * Check model, used to maintain cehecks
	 * @var MediabirdCheck
	 */
	var $Check;

	/**
	 * @param MediabirdDbo $db Database object
	 * @param MediabirdAuthManager $auth Auth interface to identify the current user
	 */
	function __construct($db,$auth) {
		$this->db = $db;
		$this->auth = $auth;
		$this->userId = $auth->userId;

		$this->User = new MediabirdUser($this);
		$this->Topic = new MediabirdTopic($this);
		$this->Content = new MediabirdContent($this);
		$this->TagColor = new MediabirdTagColor($this);
		$this->Markers = new MediabirdMarkers($this);
		$this->Files = new MediabirdFiles($this);
		$this->Question = new MediabirdQuestion($this);
		$this->Link = new MediabirdLink($this);
		$this->Flashcard = new MediabirdFlashcard($this);
		$this->Check = new MediabirdCheck($this);

	}

	/**
	 * Valid actions
	 * @var string[]
	 */
	var $validActions = array('init','load','sess','del','up','keep','auth','render','feedback','copy','backtrace');

	/**
	 * Dispatches a session request from the client
	 * @param $action Command that is to be performed
	 * @param $args Arguments for the given command
	 * @return stdClass Object that is supposed to be sent back to the client
	 */
	function dispatch($action, $args) {
		if(in_array($action,$this->validActions) && method_exists($this,$action)) {
			unset($args['action']);
			
			foreach($args as $key=>$arg) {
				$args[$key] = MediabirdUtility::getArgNoSlashes($args[$key]);
			}

			return $this->$action((object)$args);
		}
	}

	/**
	 * keep alive handler
	 */
	function keep() {
		return array('r'=>MediabirdConstants::processed);
	}
	
	function init($args) {
		//load topic list, tag list, file list and user list
		$results = array();

		$okay =
		$this->Topic->load(null,$results) &&
		$this->TagColor->load(null,$results) &&
		$this->Files->load(null,$results) &&
		$this->User->load(null,$results) &&
		$this->User->loadExternalUsers($results);

		//send back time stamp
		$results['t']=time();

		if(!$okay) {
			$results = array();
		}

		//send back state
		$results['r'] = $okay ? MediabirdConstants::processed : MediabirdConstants::serverError;

		return $results;
	}

	function load($args) {
		//this is what can be loaded
		$validClasses = array('Topic','Content','Markers','Files','User','Question','Link', 'Flashcard','Check');

		if(!MediabirdUtility::checkKeyset($args,$validClasses,true)) {
			return false;
		}

		//all must be json-strings
		foreach($args as $key => $value) {
			if(!is_string($value)) {
				return false;
			}

			$value = json_decode($value);

			if(!MediabirdUtility::checkKeyset($value,array('fromTime','loadedIds','restrictIds','parentIds'),true)) {
				return false;
			}

			$arg = array();

			//convert fromTime
			if(property_exists($value,'fromTime')) {
				if(!is_int($value->fromTime)) {
					return false;
				}
				$arg['fromTime'] = $value->fromTime;
			}

			//convert loadedIds
			if(property_exists($value,'loadedIds')) {
				if(!is_array($value->loadedIds)) {
					return false;
				}

				foreach($value->loadedIds as $id) {
					if(!is_int($id)) {
						return false;
					}
				}

				$arg['loadedIds'] = $value->loadedIds;
			}

			//convert loadedIds
			if(property_exists($value,'parentIds')) {
				if(!is_array($value->parentIds)) {
					return false;
				}

				foreach($value->parentIds as $id) {
					if(!is_int($id)) {
						return false;
					}
				}

				$arg['parentIds'] = $value->parentIds;
			}

			//convert loadedIds
			if(property_exists($value,'restrictIds')) {
				if(!is_array($value->restrictIds)) {
					return false;
				}

				foreach($value->restrictIds as $id) {
					if(!is_int($id)) {
						return false;
					}
				}

				$arg['restrictIds'] = $value->restrictIds;
			}

			$plural = strtolower($key);
			if(substr($plural,strlen($plural)-1)!="s") {
				$plural .= "s";
			}

			$args->$key = array(
				$plural=>$arg
			);
		}

		$results = array();

		$okay = true;
		foreach($args as $key => $value) {
			$okay = $okay && $this->$key->load($value,$results);
		}

		//send back time stamp
		$results['t']=time();

		if(!$okay) {
			$results = array();
		}

		//send back state
		$results['r'] = $okay ? MediabirdConstants::processed : MediabirdConstants::serverError;

		return $results;
	}

	function sess($args) {
		//this is what can be loaded
		$validArgs = array('options');

		if(!MediabirdUtility::checkKeyset($args,$validArgs)) {
			return false;
		}

		//validate input
		$cache = array();
		$reason = MediabirdConstants::invalidData;
		
		//decode options
		$options = json_decode($args->options);
		
		$validates = 
			is_object($options) && //options must be an object
			property_exists($options,'topicId') == property_exists($options,'topicTime'); //topic time and id must be given at same time if any given
			
		
		if($validates && property_exists($options,'topicId')) {
			if(!is_int($options->topicId) || !is_int($options->topicTime)) {
				$validates = false;
			}
			
			if($validates) {
				$topicId = $options->topicId;	
				$topicTime = $options->topicTime;
				
				unset($options->topicId);
				unset($options->topicTime);
			}
		}
		
		if($validates && property_exists($options,'cardTime')) {
			if(!is_int($options->cardTime)) {
				$validates = false;
			}
			
			if($validates) {
				$cardTime = $options->cardTime;
				unset($options->cardTime);
			}
		}
		
		//validate remaining object (may only contain cardId, checkOut and checkInId)
		$validates = $validates && $this->User->validate($options,$cache,$reason);
		
		//check if topic id is valid
		if($validates && property_exists($options,'topicId') && !in_array($options->topicId,$cache['state']['validTopicIds'])) {
			$validates = false;
		}
		
		if(!$validates) {
			$results['r'] = $reason;
			return $results;
		}
		
		
		//now process the validated data!
		
		//this array holds all results
		$results = array();
		
		//refresh current card info
		$okay = $this->User->update($options,$cache,$results) == MediabirdConstants::processed;
			
		if($okay && isset($cardTime) && !isset($topicId)) {
			$okay = $this->Content->load(array(
				'contents'=>array(
					'restrictIds'=>array($options->cardId),
					'fromTime'=>$cardTime
				)
			),$results);
			
			//load markers that belong to this card
			$okay = $okay && $this->Markers->load(array(
				'markers'=>array(
					'parentIds'=>array($options->cardId),
					'fromTime'=>$cardTime-1,
					'returnAllIds'=>true
				)
			),$results);
		}
		
		//refresh topic
		if($okay && isset($topicId)) {
			//refresh topic
			$okay = $this->Topic->load(array(
				'topics'=>array(
					'restrictIds'=>array($topicId),
					'fromTime'=>$topicTime,
					'includeCards'=>true
				)
			),$results);
			
			//refresh related contents
			$okay = $okay && $this->Content->load(array(
				'contents'=>array(
					'parentIds'=>array($topicId),
					'fromTime'=>$topicTime
				)
			),$results);
			
			//refresh related questions
			$okay = $okay && $this->Question->load(array(
				'questions'=>array(
					'fromTopic'=>$topicId,
					'fromTime'=>$topicTime-1
				)
			),$results);
	
			//refresh related checks
			$okay = $okay && $this->Check->load(array(
				'checks'=>array(
					'fromTopic'=>$topicId,
					'fromTime'=>$topicTime-1
				)
			),$results);
	
			
			//load markers that belong to this card
			$okay = $okay && $this->Markers->load(array(
				'markers'=>array(
					'fromTopic'=>$topicId,
					'fromTime'=>$topicTime-1,
					'returnAllIds'=>true
				)
			),$results);
		}

		//load user infos
		if($okay && isset($topicTime)) {
			$okay = $this->User->load(array(
				'users'=>array(
					'states'=>true,
					//'fromTime'=>$topicTime,
					'fromTopic'=>$topicId,
					'avoidIds'=>array($this->userId)
				)
			),$results);
		}

		if($okay) {
			//send back time stamp
			$results['t']=time();
		
			$results['r'] = MediabirdConstants::processed;
		}
		else {
			$results = array(
				'r' => MediabirdConstants::serverError
			);
		}
		
		return $results;
	}
	
	function up($args) {
		//this is what can be updated
		$validClasses = array(
			'Topic'=>$this->Topic->updateParams,
			'TagColor'=>$this->TagColor->updateParams,
			'Content'=>$this->Content->updateParams,
			'Markers'=>array_merge($this->Markers->updateParams,array('relations')),
			'Settings'=>$this->User->settingParams,

		//relatable objects:
			'Question'=>$this->Question->updateParams,
			'Link'=>$this->Link->updateParams,
			'Flashcard'=>$this->Flashcard->updateParams,
			'Check'=>$this->Check->updateParams
		);

		if(!MediabirdUtility::checkKeyset($args,array_keys($validClasses),true)) {
			return false;
		}

		$results = array();
		$cache = array();
		$data = (object)null;

		$validates = true;
		//validate args
		foreach($args as $key => $value) {
			if(!is_string($value)) {
				return false;
			}

			$value = $data->$key = json_decode($value);

			if(!MediabirdUtility::checkKeyset($value,$validClasses[$key],true)) {
				return false;
			}

			if($key=='Settings') {
				$this->User->updateSettings($value,$results);
				unset($data->$key);
				continue;
			}
			
			//validate it
			$reason = null;
			if(!$validates = ($validates && $this->$key->validate($value,$cache[$key],$reason))) {
				//fixme: check if 'r' is equal to invalidRevision and send back {some data} if the case
				$results['r'] = $reason;
				break;
			}
		}

		$okay = $validates;
		if($validates) {
			//process changes
			foreach($data as $key => $value) {
				//process it
				$okay = $okay && $this->$key->update($value,$cache[$key],$results) == MediabirdConstants::processed;
			}
		}

		if($okay) {
			$results['r'] = MediabirdConstants::processed;
		}
		else if(!$okay && $validates) {
			$results['r'] = MediabirdConstants::serverError;
		}

		return $results;
	}
	
	function copy($args) {
		//this is what can be updated
		$validClasses = array(
			'Files'=>$this->Files->copyParams
		);

		if(!MediabirdUtility::checkKeyset($args,array_keys($validClasses),true)) {
			return false;
		}

		$results = array();
		$cache = array();
		$data = (object)null;

		$validates = true;
		//validate args
		foreach($args as $key => $value) {
			if(!is_string($value)) {
				return false;
			}

			$value = $data->$key = json_decode($value);

			if(!MediabirdUtility::checkKeyset($value,$validClasses[$key],true)) {
				return false;
			}

			//validate it
			$reason = null;
			if(!$validates = ($validates && $this->$key->validateCopy($value,$cache[$key],$reason))) {
				//fixme: check if 'r' is equal to invalidRevision and send back {some data} if the case
				$results['r'] = $reason;
				break;
			}
		}

		$okay = $validates;
		if($validates) {
			//process changes
			foreach($data as $key => $value) {
				//process it
				$okay = $okay && $this->$key->copy($value,$cache[$key],$results) == MediabirdConstants::processed;
			}
		}

		if($okay) {
			$results['r'] = MediabirdConstants::processed;
		}
		else if(!$okay && $validates) {
			$results['r'] = MediabirdConstants::serverError;
		}

		return $results;
	}
	
	function del($args) {
		//this is what can be updated
		$validClasses = array(
			'Topic',
			'Files'
		);

		$validates = true;
		
		//only allow one type of record to be deleted at the same time
		if(!MediabirdUtility::checkKeyset($args,array_keys($validClasses),true) || count($args) > 1) {
			$validates = false;
		}

		$data = (object)null;
		
		//validate args
		foreach($args as $key => $value) {
			if(!is_string($value)) {
				$validates = false;
			}

			if($validates) {
				$value = $data->$key = json_decode($value);

				if(!$value || !is_array($value)) {
					$validates = false;
				}

				if($validates) {
					foreach($value as $val) {
						if(!is_int($val)) {
							$validates = false;
							break;
						}
					}
				}
			}
		}

		$results = array();
		
		if($validates) {
			//process changes
			foreach($data as $key => $value) {
				//process it
				$okay = $this->$key->delete($value,$results) == MediabirdConstants::processed;
			}
			
			if($okay) {
				$results['r'] = MediabirdConstants::processed;
			}
			else if(!$okay && $validates) {
				$results['r'] = MediabirdConstants::serverError;
			}
		}
		else {
			$results['r'] = MediabirdConstants::invalidData;
		}

		return $results;
	}
	
	function backtrace($args) {
		//this is what can be loaded
		$validClasses = array('Question','Link', 'Flashcard');

		if(!MediabirdUtility::checkKeyset($args,$validClasses,true)) {
			return false;
		}

		$results = array();

		$okay = true;
		foreach($args as $key => $value) {
			if(!is_string($value)) {
				return false;
			}

			$value = json_decode($value);
			
			$okay = $okay && $this->Markers->findMarkers($value,strtolower($key),$results);
		}
		
		if($okay) {
			$results['r'] = MediabirdConstants::processed;
		}

		return $results;
	}

	function auth($args) {
		if(!MediabirdUtility::checkKeyset($args,array('id','password'))) {
			return false;
		}

		$args->id = intval($args->id);

		if($this->Files->checkFileAuth($args->id)) {
			return array(
				'r'=>MediabirdConstants::processed
			);
		}

		$results = array();
		$result = $this->Files->authorizeFile($args->id,$args->password,$results);

		$results['r'] = $result;
		
		return $results;
	}

	function render($args) {
		if(!MediabirdUtility::checkKeyset($args,array('eq'))) {
			return false;
		}

		$results = array();
		$result = $this->Files->renderEquation($args->eq,$results);

		$results['r'] = $result;
		
		return $results;
	}
	
	function feedback($args) {
		if(!MediabirdUtility::checkKeyset($args,array('desc'))) {
			return false;
		}
		
		$description = $args->desc;

		$results = array();
		
		$body = "User with id $this->userId has suggested the following feature:\n".$description;
		$body = wordwrap($body, 70);

		if (!MediabirdConfig::$disable_mail) {
			$oldReporting = error_reporting(0);
			if (method_exists($this->auth, 'sendMail') && $this->auth->sendMail(-1, "Mediabird Feedback", $body)) {
				$result = MediabirdConstants::processed;
			}
			else {
				$result = MediabirdConstants::serverError;
			}
			error_reporting($oldReporting);
		}
		else {
			error_log("Feature suggested by user $this->userId: $description .");
			$result = MediabirdConstants::processed;
		}
		
		$results['r'] = $result;
		
		return $results;
	}

	function getAvailableModels() {
		return array_values($this->supportedModelNames);
	}
	
	/**
	 * Get supported change types of the specified models
	 * @param $models string[]
	 * @return array
	 * Returns associative array with model names as keys and change type array as values
	 */
	function getSupportedChangeTypes($modelNames) {
		$changeTypes = array();
		
		foreach($modelNames as $modelName) {
			//get instance of model
			if(in_array($modelName,$this->supportedModelNames)) {
				$model = $this->$modelName;
				
				$changeTypes[$modelName] = $model->getAvailableChangeTypes();
			}
		}
		
		return $changeTypes;
	}
	
	
	function collectChanges($modelChangeTypes,$from=null,$forUserId=null) {
		if(count($modelChangeTypes)==0) {
			return array();
		}
		
		if($forUserId==null) {
			$forUserId = $this->userId;
		}
		if($from==null) {
			$from=1;
		}
		
		$changes = array();
		
		foreach($modelChangeTypes as $modelName => $changeTypes) {
			//get instance of model
			if(in_array($modelName,$this->supportedModelNames)) {
				$model = $this->$modelName;
				
				$changes[$modelName] = $model->getChanges($changeTypes,$from,$forUserId);
			}
		}
		
		return $changes;
	}

}
?>
