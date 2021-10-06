<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

include_once('functions/text_functions.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** class for database connection to the database table "assessments"
 * this class implements a database manager for the table "assessments"
 */
class cs_assessments_manager extends cs_manager {


  var $_average_assessment_id_array = array();
  var $_item_id_array = array();
  var $_cache_on = true;


  /** constructor: cs_assessments_manager
    * the only available constructor, initial values for internal variables
    */
  function __construct ($environment) {
     cs_manager::__construct($environment);
     $this->_db_table = CS_ASSESSMENT_TYPE;
  }

  function _buildItem ($data_array) {
      return parent::_buildItem($data_array);
  }

   public function setCacheOff () {
      $this->_cache_on = false;
   }


  /** get an assessment
    *
    * @param integer item_id
    *
    * @return object link
    */
  public function getItem ($item_id) {
     $retour = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".item_id = '".encode(AS_DB,$item_id)."'";
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0])) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting one assessment item from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $retour = $this->_buildItem($result[0]);
     }
     return $retour;
  }

   /** build a new assessments item
    * this method returns a new EMTPY assessments link item
    *
    * @return object cs_assessments_item a new EMPTY assessments link item
    */
   public function getNewItem () {
      include_once('classes/cs_assessments_item.php');
      return new cs_assessments_item($this->_environment);
   }


  /** update an assessment - internal, do not use -> use method save
    * this method updates an assessment
    *
    * @param object cs_assessments_item assessments_item the assessment item
    */
  function _update ($assessments_item) {
     $current_datetime = getCurrentDateTimeInMySQL();

	 $query = '
	 	UPDATE
	 		' . $this->addDatabasePrefix($this->_db_table) . '
	 	SET
	 		assessment = "' . encode(AS_DB, $assessments_item->getAssessment()) . '"
	 	WHERE
	 		item_id = "' . encode(AS_DB, $assessments_item->getItemID()) . '"
	 ';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating assessment from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  function addAssessmentForItem($item, $assessment) {
  	 $new_item = $this->getNewItem();
	 $new_item->setContextID($this->_environment->getCurrentContextID());
	 $new_item->setCreatorID($this->_environment->getCurrentUserID());
	 $new_item->setItemLinkID($item->getItemID());
	 $new_item->setAssessment($assessment);
	 $new_item->save();
  }

  function getAssessmentForItemAverage($item) {
     if (in_array($item->getItemID(),$this->_item_id_array)){
         if (array_key_exists($item->getItemID(), $this->_average_assessment_id_array)){
            return array($this->_average_assessment_id_array[$item->getItemID()]['average_assessment'], $this->_average_assessment_id_array[$item->getItemID()]['count_assessment']);
         }else{
            return false;
         }
    }else{
	  	$query = '
	  		SELECT AVG(assessment) AS average_assessment, COUNT(item_id) AS count_assessment FROM assessments WHERE	item_link_id = "' . encode(AS_DB, $item->getItemID())  . '" AND	deletion_date IS NULL GROUP BY item_link_id';
		$result = $this->_db_connector->performQuery($query);
		if(isset($result[0])) {
			return array($result[0]['average_assessment'], $result[0]['count_assessment']);
		} else {
			return '';
		}
     }
  }


   function getAssessmentForItemAverageByIDArray ($id_array,$user_id = 0) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if ($this->_cache_on and count($id_array) > 0){
         foreach($id_array as $id){
            if (!in_array($id,$this->_item_id_array)){
               $this->_item_id_array[] = $id;
            }
         }
	  	$query = 'SELECT AVG(assessment) AS average_assessment, COUNT(item_id) AS count_assessment, item_link_id FROM assessments WHERE item_link_id IN ('.implode(",",encode(AS_DB,$id_array)).')'. ' AND deletion_date IS NULL GROUP BY item_link_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
         } else {
            $noticed = array();
            foreach ($result as $rs) {
               $temp = array();
               $temp[$rs['item_link_id']]['average_assessment'] = $rs['average_assessment'];
               $temp[$rs['item_link_id']]['count_assessment'] = $rs['count_assessment'];
               if (!in_array($temp,$this->_average_assessment_id_array)){
               $this->_average_assessment_id_array[$rs['item_link_id']]['average_assessment'] = $rs['average_assessment'];
               $this->_average_assessment_id_array[$rs['item_link_id']]['count_assessment'] = $rs['count_assessment'];
               }
            }
            return $this->_average_assessment_id_array;
         }
      }
   }



  function getAssessmentForItemDetail($item) {
  	$query = '
  		SELECT
  			count(item_link_id) as count,
  			assessment
  		FROM
  			assessments
  		WHERE
  			item_link_id = "' . encode(AS_DB, $item->getItemID())  . '" AND
			deletion_date IS NULL
		GROUP BY
			assessment
  	';
  	$result = $this->_db_connector->performQuery($query);
  	if(!empty($result)) {
  		$return = array();
  		foreach($result as $value) {
  			$return[$value['assessment']] = $value['count'];
  		}
  		return $return;
  	} else {
  		return '';
  	}
  }

  function hasCurrentUserAlreadyVoted($item) {
  	$query = '
  		SELECT
  			item_id
  		FROM
  			assessments
  		WHERE
  			creator_id = "' . encode(AS_DB, $this->_environment->getCurrentUserID()) . '" AND
  			item_link_id = "' . encode(AS_DB, $item->getItemID()) . '" AND
			deletion_date IS NULL
  	';
	$result = $this->_db_connector->performQuery($query);
	if(sizeof($result) > 0) {
		return true;
	}

	return false;
  }

  function getAssessmentForItemOwn($item) {
  	$query = '
  		SELECT
  			assessment
		FROM
			assessments
		WHERE
			item_link_id = "' . encode(AS_DB, $item->getItemID())  . '" AND
			creator_id = "' . encode(AS_DB, $this->_environment->getCurrentUserID()) . '" AND
			deletion_date IS NULL
  	';
	$result = $this->_db_connector->performQuery($query);
	if(isset($result[0]['assessment'])) {
		return $result[0]['assessment'];
	} else {
		return '';
	}
  }

  /** create an assessment - internal, do not use -> use method save
    * this method creates an assessment
    *
    * @param object cs_assessments_item
    */
  private function _create($assessments_item) {
  	 $context_id = $assessments_item->getContextID();
	 if(!isset($context_id)) {
	 	include_once('functions/error_functions.php');trigger_error('Problems creating new assessment: ContextID is not set',E_USER_ERROR);
	 } else {
	 	$query = '
	 		INSERT INTO
	 			' . $this->addDatabasePrefix('items'). '
	 		SET
	 			context_id = "' . encode(AS_DB, $context_id) . '",
	 			modification_date = "' . getCurrentDateTimeInMySQL() . '",
	 			type = "' . encode(AS_DB, $assessments_item->getItemType()) . '"
	 	';
		$result = $this->_db_connector->performQuery($query);
		if(!isset($result)) {
			include_once('functions/error_functions.php');trigger_error('Problems creating assessment from query: "'.$query.'"',E_USER_WARNING);
            $this->_create_id = NULL;
		} else {
			$this->_create_id = $result;
			$assessments_item->setItemID($this->getCreateID());
			$this->_newAssessment($assessments_item);
		}
	 }
  }

  /**
   * create a new assessment - internal, do not use -> use method save
   *
   * @param object cs_assessments_item
   */
  private function _newAssessment($assessments_item) {
  	 $current_datetime = getCurrentDateTimeInMySQL();

	 $query = '
	 	INSERT INTO
	 		' . $this->addDatabasePrefix($this->_db_table) . '
	 	SET
	 		item_id = "' . encode(AS_DB, $assessments_item->getItemID()) . '",
	 		context_id = "' . encode(AS_DB, $assessments_item->getContextID()) . '",
	 		creator_id = "' . encode(AS_DB, $assessments_item->getCreatorID()) . '",
	 		creation_date = "' . $current_datetime . '",
	 		item_link_id = "' . encode(AS_DB, $assessments_item->getItemLinkID()) . '",
	 		assessment = "' . encode(AS_DB, $assessments_item->getAssessment()) . '"
	 ';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating assessment from query: "'.$query.'"',E_USER_WARNING);
     }
     unset($assessments_item);
  }

  /** save an item
    * this method saves an item
    *
    * @param cs_assessments_item
    */
  function saveItem($item) {
  	 $item_id = $item->getItemID();
	 if(!empty($item_id)) {
	 	$this->_update($item);
	 } else {
        $creator_id = $item->getCreatorID();
        if (empty($creator_id)) {
           $user = $this->_environment->getCurrentUser();
           unset($user);
        }
        $this->_create($item);
     }
     unset($item);
  }

  function delete($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID() ?: 0;
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id = "' . encode(AS_DB, $item_id) . '"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting assessment from query: "'.$query.'"',E_USER_WARNING);
     } else {
           parent::delete($item_id);
     }
  }

  function getItemIDForOwn($item_link_id) {
  	$query = '
  		SELECT
  			item_id
		FROM
			assessments
		WHERE
			item_link_id = "' . encode(AS_DB, $item_link_id)  . '" AND
			creator_id = "' . encode(AS_DB, $this->_environment->getCurrentUserID()) . '" AND
			deletion_date IS NULL
  	';
	$result = $this->_db_connector->performQuery($query);
	if(isset($result[0]['item_id'])) {
		return $result[0]['item_id'];
	} else {
		return '';
	}
  }

  /** get all links
    * this method get all links
    *
    * @param string  type       type of the link
    * @param string  mode       one of count, select, select_with_item_type_from
    */
   public function _performQuery ($mode = 'select') {
      $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE 1';

      if ( isset($this->_room_limit) ) {
         $query .= ' AND context_id="'.encode(AS_DB,$this->_room_limit).'"';
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems with links from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }
}
?>