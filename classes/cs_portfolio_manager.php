<?PHP
// $Id$
//
// Release $Name$
//

include_once('functions/text_functions.php');

class cs_portfolio_manager extends cs_manager {

  var $_user_limit = NULL;
  var $_delete_limit = true;
  var $_db_table = CS_PORTFOLIO_TYPE;
  var $_sort_order = NULL;

  private $_translator = null;

  function cs_announcement_manager ($environment) {
     $this->cs_manager($environment);
     $this->_db_table = CS_PORTFOLIO_TYPE;
     $this->_translator = $environment->getTranslationObject();
  }

  function resetLimits () {
     parent::resetLimits();
     $this->_user_limit = NULL;
     $this->_delete_limit = true;
     $this->_sort_order = NULL;
  }


   function setUserLimit ($limit) {
      $this->_user_limit = (int)$limit;
   }

   function setDeleteLimit ($limit) {
      $this->_delete_limit = (int)$limit;
   }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   function _performQuery ($mode = 'select') {
      if ($mode == 'count') {
         $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
      } elseif ($mode == 'id_array') {
          $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
      } elseif ($mode == 'distinct') {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
      } else {
         $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
      }
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE 1';
      if ( isset($this->_user_limit) ) {
         $query .= ' AND user_id_id = "'.encode(AS_DB,$this->_user_limit).'"';
      }
      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
      }
      if ( isset($this->_sort_order) ) {
         if ( $this->_sort_order == 'modified' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
         } elseif ( $this->_sort_order == 'modified_rev' ) {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
         }
      } else {
         $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
      }
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting portfolio.',E_USER_WARNING);
      } else {
         return $result;
      }
   }


   function getItem ($item_id) {
      $portfolio_item = NULL;
      if ( !empty($item_id) ) {
         if ( !empty($this->_cache_object[$item_id]) ) {
            return $this->_cache_object[$item_id];
         } elseif ( array_key_exists($item_id,$this->_cached_items) ) {
            return $this->_buildItem($this->_cached_items[$item_id]);
         } else {
            $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".item_id = '".encode(AS_DB,$item_id)."'";
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting one portfolio item.',E_USER_WARNING);
            } elseif ( !empty($result[0]) ) {
               if ( $this->_cache_on ) {
                  $this->_cached_items[$result[0]['item_id']] = $result[0];
               }
               $portfolio_item = $this->_buildItem($result[0]);
               unset($result);
            } else {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting announcement item ['.$item_id.'].',E_USER_WARNING);
            }
         }
      }
      return $portfolio_item;
   }



   function getItemList($id_array) {
      return $this->_getItemList(CS_PORTFOLIO_TYPE, $id_array);
   }

    function getNewItem () {
      include_once('classes/cs_portfolio_item.php');
      return new cs_portfolio_item($this->_environment);
   }


  function _update ($portfolio_item) {
     parent::_update($portfolio_item);
     $modification_date = getCurrentDateTimeInMySQL();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'modification_date="'.$modification_date.'",'.
              'title="'.encode(AS_DB,$portfolio_item->getTitle()).'",'.
              'description="'.encode(AS_DB,$portfolio_item->getDescription()).'",'.
              'user_id="'.encode(AS_DB,$portfolio_item->getUserID()).'",'.
              'rows="'.encode(AS_DB,$portfolio_item->getRows()).'",'.
              'columns="'.encode(AS_DB,$portfolio_item->getColumns()).'",'.
              ' WHERE item_id="'.encode(AS_DB,$portfolio_item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating portfolio.',E_USER_WARNING);
     } else {
        unset($result);
     }
     unset($portfolio_item);
  }

  function _create ($portfolio_item) {
     $modification_date = getCurrentDateTimeInMySQL();
     $portal_id = $this->_environment->getCurrentPortalID();
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$portal_id).'",'.
              'modification_date="'.$modification_date.'",'.
              'type="portfolio"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating portfolio.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $portfolio_item->setItemID($this->getCreateID());
        $this->_newPortfolio($portfolio_item);
        unset($result);
     }
     unset($portfolio_item);
  }

  function _newPortfolio ($portfolio_item) {
     $user = $portfolio_item->getCreatorItem();
     $modification_date = getCurrentDateTimeInMySQL();
     $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'item_id="'.encode(AS_DB,$portfolio_item->getItemID()).'",'.
              'user_id="'.encode(AS_DB,$portfolio_item->getUserID()).'",'.
              'modification_date="'.$modification_date.'",'.
              'title="'.encode(AS_DB,$portfolio_item->getTitle()).'",'.
              'description="'.encode(AS_DB,$portfolio_item->getDescription()).'",'.
              'rows="'.encode(AS_DB,$portfolio_item->getRows()).'",'.
              'columns="'.encode(AS_DB,$portfolio_item->getColumns()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating portfolio.',E_USER_WARNING);
     } else {
        unset($result);
     }
     unset($portfolio_item);
  }

  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
              'deletion_date="'.$current_datetime.'",'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting portfolio.',E_USER_WARNING);
     } else {
         parent::delete($item_id);
     }
  }


/***********NEW FUNCTIONS****************/

	function getTagsForTableCell($item_id,$column,$row){
		$tag_array = array();
		return $tag_array;
	}

	function getAnnotationsForTableCell($item_id,$column,$row){
		$annotation_array = array();
		return $annotation_array;
	}

/****************************************/

}
?>