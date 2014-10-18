<?php 
/*
 * 	Copyright (C) 2010 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

/**
 * Change info representation
 * @author fabian
 */
class MediabirdChangeInfo {
	const MAX_TITLE_LENGTH = 50;
	
	/**
	 * Constructs new change info object
	 * @param string $model
	 * @param int $since
	 * @param int $userId
	 */
	public function __construct($model,$since,$userId) {
		$this->model = $model;
		$this->querySince = $since;
		$this->queryUserId = $userId;
	}
	
	/**
	 * Related model
	 * @var string
	 */
	var $model = null;
	
	/**
	 * Type of change
	 * @var string
	 */
	var $changeType = null;
	
	/**
	 * Change timestamp queried against DB
	 * @var int
	 */
	var $querySince = null;
	
	/**
	 * User id queried against DB
	 * @var int
	 */
	var $queryUserId = 0;
	
	/**
	 * Id of item
	 * @var int
	 */
	var $itemId = 0;
	
	/**
	 * Creation date of item
	 * @var string
	 */
	var $itemCreated = null;
	
	/**
	 * Modification date of item
	 * @var string
	 */
	var $itemModified = null;
	
	/**
	 * Modifier id
	 * @var int
	 */
	var $itemModifier = null;
	
	/**
	 * Title of item
	 * @var string
	 */
	var $itemTitle = null;
	
	/**
	 * Count of items
	 * @var int
	 */
	var $itemCount = 0;
	
	/**
	 * Description of item
	 * @var string
	 */
	var $itemDescription = null;
	
	/**
	 * Record this change item refers to
	 * @var object
	 */
	var $itemRecord = null;

	public static function humanizeChanges($changes,$resourceStrings) {
		$return = "";
		
		foreach($changes as $modelName=>$changeSet) {
			foreach($changeSet as $changeType => $changeInfos) {
				$itemCount = count($changeInfos);
				
				if($itemCount == 1) {
					if($changesInfos[0]->itemCount > 0) {
						$itemCount = $changesInfos[0]->itemCount;
					}
				}
				
				$return .= sprintf($resourceStrings[$changeType."_heading"],$itemCount)."\n";
				
				foreach($changeInfos as $changeInfo) {
					if($changeInfo->model==MediabirdTopic::modelName && 
						($changeInfo->changeType==MediabirdTopic::changeTypeTopicChange ||
						 $changeInfo->changeType==MediabirdTopic::changeTypeRightCreation)) {
							
						$itemTitle = $changeInfo->itemTitle;
						$itemDate = date("Y-m-d H:i",$changeInfo->itemModified);
						$itemUser = $changeInfo->itemModifier;

						$return .= sprintf($resourceStrings[$changeType."_item"],$itemTitle,$itemUser,$itemDate)."\n";
					}
					else if($changeInfo->model==MediabirdQuestion::modelName) {
						$questionRelatedChangeTypes = array(
							MediabirdQuestion::changeTypeAnsweredItemModified,
							MediabirdQuestion::changeTypeCriticalItemModified,
							MediabirdQuestion::changeTypeSolvedItemModified
						);
						if(in_array($changeInfo->changeType,$questionRelatedChangeTypes)) {
							//get title of item
							$itemTitle = $changeInfo->itemTitle;
							$itemDate = date("Y-m-d H:i",$changeInfo->itemModified);
							$itemUser = $changeInfo->itemModifier;

							
							if(strlen($itemTitle) > self::MAX_TITLE_LENGTH) {
								$itemTitle = substr($itemTitle,0,self::MAX_TITLE_LENGTH)."...";
							}
							
							$return .= sprintf($resourceStrings[$changeType."_item"],$itemTitle,$itemUser,$itemDate)."\n";
						}
					}
				}
			}
		}

		return $return;
	}
}

?>
