<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

abstract class MediabirdModel {
	/**
	 * Name of this model
	 * @var string
	 */
	var $name = "Model";
	
	
	/**
	 * Controller reference
	 * @var MediabirdController
	 */
	var $controller;
	/**
	 * Database interface
	 * @var MediabirdDbo
	 */
	var $db;

	/**
	 *
	 * @var int
	 */
	var $userId;

	/**
	 *
	 * @param MediabirdController $controller
	 * @return unknown_type
	 */
	function __construct($controller) {
		$this->db = $controller->db;
		$this->userId = $controller->userId;
		$this->controller = $controller;
	}

	abstract function validate($data,&$cache,&$reason);
	abstract function update($data,$cache,&$changes);
	abstract function load($data,&$results);

	/**
	 * Returns the change types supported by this model
	 * @return string[]
	 */
	function getAvailableChangeTypes() {
		return array();
	}
	
	/**
	 * 
	 * @param $types string[]
	 * @param $since
	 * @param $userId
	 * @return MediabirdChangeInfo[]
	 */
	function getChanges($types,$since=null,$userId=null) {
		return array();
	}

	function deleteGeneric($className,$condition,&$results=false) {
		if($records = $this->db->getRecords(MediabirdConfig::tableName($className,true),$condition,'','id')) {
			$affectedIds = array();
			
			foreach($records as $record) {
				$affectedIds []= intval($record->id);
			}
			
			$select = "id IN (".join(",",$affectedIds).")";
			
			//delete them
			if($this->db->deleteRecords(MediabirdConfig::tableName($className,true),$select)) {
				if($results!==false) {
					$results['removed'.$className.'Ids'] = $affectedIds;
				}
				return true;
			}
			else {
				return false;
			}
		}
		else {
			//no records affected, but that's okay
			return true;
		}
	}
}
?>
