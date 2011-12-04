<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_list_controller extends cs_room_controller {
		private $_entries_per_page = 20;
		protected $_list_parameter_arrray = array();
		protected $_page_html_fragment_array = array();
		protected $_browsing_icons_parameter_array = array();
		protected $_perspective_rubric_array = array();

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// init variables
			/*
			$this->getViewMode() = 'browse';
			$this->_filter = array();
			$this->_paging = array(
				'offset'	=> 0,
				'limit'		=> 20
			);
			*/
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();

			/*
			// set paging information
			$paging = array(
				'num_pages'		=> ceil($this->_num_entries / $this->_paging['limit']),
				'actual_page'	=> floor($this->_paging['offset'] / $this->_paging['limit']) + 1,
				'from'			=> $this->_paging['offset'] + 1,
				'to'			=> $this->_paging['offset'] + $this->_paging['limit']
			);
			$this->assign('list', 'paging', $paging);
			$this->assign('list', 'num_entries', $this->_num_entries);
			*/
		}

		protected function getViewMode(){
			$mode = 'browse';
			if ( isset($_GET['mode']) ) {
            	return $_GET['mode'];
			} elseif ( isset($_POST['mode']) ) {
   				return $_POST['mode'];
			} else {
   				unset($this->_list_parameter_arrray['ref_iid']);
   				unset($this->_list_parameter_arrray['ref_user']);
			}
		}

		protected function performOptions() {
			// get parameter array
			$parameter_array = $this->_environment->getCurrentParameterArray();

			//pr($parameter_array); exit;
			// paging
			if(isset($parameter_array['from'])) {
				$this->_paging['offset'] = $parameter_array['from'];
			}
		}

		protected function getListEntriesParameterArray(){
			$environment = $this->_environment;
           	$params = $environment->getCurrentParameterArray();
      		if (isset($params['interval']) and !empty($params['interval'])){
      			$interval_parameter = $params['interval'];
      		}elseif(isset($params['interval']) and empty($params['interval'])){
      			$interval_parameter = 'all';
      		}else{
      			$interval_parameter = '';
      		}
      		$link_parameter_text = '';
      		if ( count($params) > 0 ) {
         		foreach ($params as $key => $parameter) {
         			if ($key != 'interval'){
            			$link_parameter_text .= '&'.$key.'='.$parameter;
         			}
         		}
         	}
         	$return_array = array();
      		if ( $interval_parameter == '20' or empty($interval_parameter)) {
         		$return_array['20'] = 'disabled';
      		}else{
         		$return_array['20'] = $link_parameter_text.'&interval=20';
      		}
      		if ( $interval_parameter == '50' ) {
         		$return_array['50'] = 'disabled';
      		}else{
         		$return_array['50'] = $link_parameter_text.'&interval=50';
      		}
      		if ( $interval_parameter == 'all' ) {
         		$return_array['all'] = 'disabled';
      		}else{
         		$return_array['all'] = $link_parameter_text.'&interval=0';
      		}
      		return $return_array;
		}

		protected function getSortingParameterArray(){
			$environment = $this->_environment;
           	$params = $environment->getCurrentParameterArray();
      		if (isset($params['sort']) and !empty($params['sort'])){
      			$sort_parameter = $params['sort'];
      		}else{
      			$sort_parameter = '';
      		}
      		unset($params['sort']);
      		$link_parameter_text = '';
      		if ( count($params) > 0 ) {
         		foreach ($params as $key => $parameter) {
            		if ($key != 'from'){
            		   $link_parameter_text .= '&'.$key.'='.$parameter;
            		}
         		}
         	}

      		if ( $sort_parameter == 'title') {
         		$return_array['sort_title_link'] = $link_parameter_text.'&sort=title_rev';
         		$return_array['sort_title'] = 'up';
      		}elseif ( $sort_parameter == 'title_rev'){
         		$return_array['sort_title_link'] = $link_parameter_text.'&sort=title';
         		$return_array['sort_title'] = 'down';
      		}else{
         		$return_array['sort_title_link'] = $link_parameter_text.'&sort=title';
         		$return_array['sort_title'] = 'none';
      		}
      		if ( $sort_parameter == 'modificator') {
         		$return_array['sort_modificator_link'] = $link_parameter_text.'&sort=modificator_rev';
         		$return_array['sort_modificator'] = 'up';
      		}elseif ($sort_parameter == 'modificator_rev'){
         		$return_array['sort_modificator_link'] = $link_parameter_text.'&sort=modificator';
         		$return_array['sort_modificator'] = 'down';
      		}else{
         		$return_array['sort_modificator_link'] = $link_parameter_text.'&sort=modificator';
         		$return_array['sort_modificator'] = 'none';
      		}
      		if ( $sort_parameter == 'assessment') {
         		$return_array['sort_assessment_link'] = $link_parameter_text.'&sort=assessment_rev';
         		$return_array['sort_assessment'] = 'up';
      		}elseif ($sort_parameter == 'assessment_rev'){
         		$return_array['sort_assessment_link'] = $link_parameter_text.'&sort=assessment';
         		$return_array['sort_assessment'] = 'down';
      		}else{
         		$return_array['sort_assessment_link'] = $link_parameter_text.'&sort=assessment';
         		$return_array['sort_assessment'] = 'none';
      		}
      		if ( $sort_parameter == 'modified' or empty($sort_parameter)) {
         		$return_array['sort_modified_link'] = $link_parameter_text.'&sort=modified_rev';
         		$return_array['sort_modified'] = 'up';
      		}elseif($sort_parameter == 'modified_rev'){
         		$return_array['sort_modified_link'] = $link_parameter_text.'&sort=modified';
         		$return_array['sort_modified'] = 'down';
      		}else{
         		$return_array['sort_modified_link'] = $link_parameter_text.'&sort=modified';
         		$return_array['sort_modified'] = 'none';
      		}
			return $return_array;
		}

/**************Begin*********************************/
/*List Restriction (Tags, Buzzwords, SelectionBoxes)*/
/**************Begin*********************************/

		protected function getRestrictionBuzzwordLinkParameters(){
			$restriction_array = array();
			$environment = $this->_environment;
      		$link_parameter_text = '';
      		$params = $environment->getCurrentParameterArray();
      		unset($params['from']);
      		unset($params['selbuzzword']);
       		foreach ($params as $key => $parameter) {
           		$link_parameter_text .= '&'.$key.'='.$parameter;
       		}
      		return $link_parameter_text;
		}

		protected function getRestrictionTagLinkParameters(){
			$restriction_array = array();
			$environment = $this->_environment;
      		$link_parameter_text = '';
      		$params = $environment->getCurrentParameterArray();
      		unset($params['from']);
      		unset($params['seltag_array']);
      		unset($params['seltag']);
       		foreach ($params as $key => $parameter) {
           		$link_parameter_text .= '&'.$key.'='.$parameter;
       		}
      		return $link_parameter_text;
		}

/**************End***********************************/
/*List Restriction (Tags, Buzzwords, SelectionBoxes)*/
/**************End***********************************/

   		function getBrowsingIconsParameterArray($from = 0, $interval = 0, $count_all_shown = 0){
			$environment = $this->_environment;
           	$params = $environment->getCurrentParameterArray();
      		$link_parameter_text = '';
      		if ( count($params) > 0 ) {
         		foreach ($params as $key => $parameter) {
            		if ($key != 'from'){
            			$link_parameter_text .= '&'.$key.'='.$parameter;
            		}
         		}
         	}
     		if ($interval > 0) {
         		if ($count_all_shown != 0) {
            		$num_pages = ceil($count_all_shown / $interval);
         		} else {
            		$num_pages = 1;
         		}
         		$act_page  = ceil(($from + $interval - 1) / $interval);
      		} else {
         		$num_pages = 1;
         		$act_page  = 1;
      		}
		    // prepare browsing
      		if ( $from > 1 ) {        // can I browse to the left / start?
         		$browse_left = $from - $interval;
         		if ($browse_left < 1) {
            		$browse_left = 1;
         		}
         		$browse_start = 1;
      		} else {
         		$browse_left = 0;      // 0 means: do not browse
         		$browse_start = 0;     // 0 means: do not browse
      		}
      		if ( $from + $interval <= $count_all_shown ) {  // can I browse to the right / end?
         		$browse_right = $from + $interval;
         		$browse_end = $count_all_shown - $interval + 1;
      		} else {
         		$browse_right = 0;     // 0 means: do not browse
         		$browse_end = 0;       // 0 means: do not browse
      		}

      		// Set return array values
      		$return_array = array();
      		if ( $browse_start > 0) {
         		$return_array['browse_start'] = $link_parameter_text.'&from='.$browse_start;
      		}else{
      			$return_array['browse_start'] = 'disabled';
      		}
      		if ( $browse_left > 0 ) {
         		$return_array['browse_left'] = $link_parameter_text.'&from='.$browse_left;
      		}else{
      			$return_array['browse_left'] = 'disabled';
      		}
       		if ( $browse_right > 0) {
         		$return_array['browse_right'] = $link_parameter_text.'&from='.$browse_right;
      		}else{
      			$return_array['browse_right'] = 'disabled';
      		}
      		if ( $browse_end > 0 ) {
         		$return_array['browse_end'] = $link_parameter_text.'&from='.$browse_end;
      		}else{
      			$return_array['browse_end'] = 'disabled';
      		}
      		if ($interval > 0) {
         		if ($count_all_shown != 0) {
            		$num_pages = ceil($count_all_shown / $interval);
         		} else {
            		$num_pages = 1;
         		}
         		$act_page  = ceil(($from + $interval - 1) / $interval);
      		} else {
         		$num_pages = 1;
         		$act_page  = 1;
      		}
      		$return_array['actual_page_number'] = $act_page;
      		$return_array['page_numbers'] = $num_pages;
      		return $return_array;
  		}

   		protected function getCountEntriesText($from = 0, $interval = 0, $count_all = 0, $count_all_shown = 0) {
			$environment = $this->_environment;
			$translator = $environment->getTranslationObject();
            $description = '';
        	if ( $count_all_shown == 0 ) {
            	$description = $translator->getMessage('COMMON_NO_ENTRIES');
        	} elseif ( $count_all_shown == 1 ) {
            	$description = $translator->getMessage('COMMON_ONE_ENTRY');
        	} elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            	$description = $translator->getMessage('COMMON_X_ENTRIES', $count_all_shown);
         	} elseif ( $from == $count_all_shown){
            	$description = $translator->getMessage('COMMON_X_FROM_Z', $count_all_shown);
         	} else {
            	if ( $from + $interval -1 <= $count_all ) {
               		$to = $from + $interval - 1;
            	} else {
               		$to = $count_all_shown;
            	}
            	$description = $translator->getMessage('COMMON_X_TO_Y_FROM_Z',
                                                          $from,
                                                          $to,
                                                          $count_all_shown
                                                         );
         	}
      		return $description;
   		}


		protected function performListOption($rubric){
			$environment = $this->_environment;
			$session = $environment->getSessionItem();
			$translator = $environment->getTranslationObject();

			// Find current option
			if ( isset($_POST['option']) ) {
   				$option = $_POST['option'];
			} elseif ( isset($_GET['option']) ) {
   				$option = $_GET['option'];
			} else {
   				$option = '';
			}

			// Find out what to do
			if ( isset($_POST['delete_option']) ) {
   				$delete_command = $_POST['delete_option'];
			}elseif ( isset($_GET['delete_option']) ) {
   				$delete_command = $_GET['delete_option'];
			} else {
   				$delete_command = '';
			}

			// LIST ACTIONS
			// initiate selected array of IDs
			$selected_ids = array();
			$mode = $this->getViewMode();
			if ($mode == '') {
   				$session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
			}elseif ($mode == 'list_actions') {
   				if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_selected_ids')) {
      				$selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_selected_ids');
   				}
			}

	      	// Update attached items from cookie (requires JavaScript in browser)
      		if ( isset($_COOKIE['attach']) ) {
         		foreach ( $_COOKIE['attach'] as $key => $val ) {
            		setcookie ('attach['.$key.']', '', time()-3600);
            		if ( $val == '1' ) {
               			if ( !in_array($key, $selected_ids) ) {
                  			$selected_ids[] = $key;
               			}
            		} else {
               			$idx = array_search($key, $selected_ids);
               			if ( $idx !== false ) {
                  			unset($selected_ids[$idx]);
               			}
            		}
         		}
      		}

	      	// Update attached items from form post (works always)
    	  	if ( isset($_POST['attach']) ) {
        	 	foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            		if ( array_key_exists($shown_key, $_POST['attach']) ) {
               			if ( !in_array($shown_key, $selected_ids) ) {
                  			$selected_ids[] = $shown_key;
               			}
	            	} else {
    	           		$idx = array_search($shown_key, $selected_ids);
        	       		if ( $idx !== false ) {
            	      		unset($selected_ids[$idx]);
               			}
            		}
         		}
      		}

			// Cancel editing
			if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   				$params = $environment->getCurrentParameterArray();
   				redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
			}

			// Delete item
			elseif ( isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
   				if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_deleted_ids')) {
      				$selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids');
                }
   				$manager = $environment->getManager($rubric);
   				foreach ($selected_ids as $id) {
      				$item = $manager->getItem($id);
      				$item->delete();
   				}
   				$session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_deleted_ids');
   				$params = $environment->getCurrentParameterArray();
   				unset($params['mode']);
   				unset($params['select']);
   				$selected_ids = array();
   				redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
			}
   			if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        			and !isset($_GET['show_copies'])
        			and $_POST['index_view_action'] != '-1'
        			and !empty($selected_ids)
      		) {
      		// prepare action process
      		switch ($_POST['index_view_action']) {
         		case 1:
            		$action = 'ENTRY_MARK_AS_READ';
            		$error = false;
            		$rubric_manager = $environment->getManager($rubric);
            		$noticed_manager = $environment->getNoticedManager();
            		foreach ($selected_ids as $id) {
               			$item = $rubric_manager->getItem($id);
               			$version_id = $item->getVersionID();
               			$noticed_manager->markNoticed($id, $version_id );
               			$annotation_list =$item->getAnnotationList();
               			if ( !empty($annotation_list) ){
                  			$annotation_item = $annotation_list->getFirst();
                  			while($annotation_item){
                     			$noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                     			$annotation_item = $annotation_list->getNext();
                  			}
               			}
            		}
            		break;
         		case 2:
            		$action = 'ENTRY_COPY';
            		// Copy to clipboard
            		foreach ($selected_ids as $id) {
               			if ( !in_array($id, $this->_list_parameter_arrray['clipboard_id_array']) ) {
                  			$this->_list_parameter_arrray['clipboard_id_array'][] = $id;
               			}
            		}
            		$session->setValue($rubric.'_clipboard', $this->_list_parameter_arrray['clipboard_id_array']);
            		break;
         		case 3:
            		$user = $environment->getCurrentUserItem();
            		if( $user->isModerator() ){
                 		$session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               			$params = $environment->getCurrentParameterArray();
               			$params['mode'] = 'list_actions';
//Reimplementierung notwendig
#               			$page->addDeleteBox(curl($environment->getCurrentContextID(),$rubric,'index',$params),'index',$selected_ids);
//               			unset($params);

            		}
            		break;
         		case 'download':
            		include_once('include/inc_rubric_download.php');
            		break;
         		default:
            		if ( !empty($_POST['index_view_action'])
                 			and ( $environment->isPlugin($_POST['index_view_action'])
                       		or $environment->isPlugin(substr($_POST['index_view_action'],0,strpos($_POST['index_view_action'],'_')))
                     	)) {
               			$plugin = '';
               			if ( $environment->isPlugin($_POST['index_view_action']) ) {
                  			$plugin = $_POST['index_view_action'];
               			} else {
                  			$plugin = substr($_POST['index_view_action'],0,strpos($_POST['index_view_action'],'_'));
               			}
               			plugin_hook_plugin($plugin,'performListAction',$_POST);
            		} else {
               			$params = $environment->getCurrentParameterArray();
               			unset($params['mode']);
               			redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
            		}
      			}
      			if ($_POST['index_view_action'] != '3'){
         			$selected_ids = array();
         			$session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      			}
      		}
		}

		protected function initFilter() {
			// get parameter array
			$parameter_array = $this->_environment->getCurrentParameterArray();

			if(isset($parameter_array['ref_iid']))
				$this->filter['ref_iid'] = $parameter_array['ref_iid'];
			elseif(isset($_POST['ref_iid']))
				$this->filter['ref_id'] = $_POST['ref_iid'];

			if(isset($parameter_array['ref_user']))
				$this->filter['ref_user'] = $parameter_array['ref_user'];
			elseif(isset($_POST['ref_user']))
				$this->filter['ref_user'] = $_POST['ref_user'];
		}

		protected function initListParameters($rubric) {
			$environment = $this->_environment;
			$session = $environment->getSessionItem();
			if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   				$index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   				$params['interval'] = $index_search_parameter_array['interval'];
   				$params['sort'] = $index_search_parameter_array['sort'];
				$params['selbuzzword'] = $index_search_parameter_array['selbuzzword'];
   				$params['seltag_array'] = $index_search_parameter_array['seltag_array'];
   				$params['interval'] = $index_search_parameter_array['interval'];
   				$params['sel_activating_status'] = $index_search_parameter_array['sel_activating_status'];
   				$sel_array = $index_search_parameter_array['sel_array'];
   				foreach($sel_array as $key => $value){
      				$params['sel'.$key] = $value;
   				}
   				$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   				$session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   				redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
			}



			// Find clipboard id array
			if ( $session->issetValue('announcement_clipboard') ) {
   				$this->_list_parameter_arrray['clipboard_id_array']= $session->getValue('announcement_clipboard');
			} else {
   				$this->_list_parameter_arrray['clipboard_id_array'] = array();
			}


			// Handle attaching
			if ( $this->getViewMode() == 'formattach' or $this->getViewMode() == 'detailattach' ) {
   				$attach_type = $rubric;
   				include('pages/index_attach_inc.php');
			}

			// Find current browsing starting point
			if ( isset($_GET['from']) ) {
   				$this->_list_parameter_arrray['from'] = $_GET['from'];
			}  else {
   				$this->_list_parameter_arrray['from'] = 1;
			}

			// Find current browsing interval
			// The browsing interval is applied to all rubrics
			$context_item = $environment->getCurrentContextItem();
			if ( isset($_GET['interval']) ) {
   				$this->_list_parameter_arrray['interval'] = $_GET['interval'];
#			}
#			elseif ( $session->issetValue('interval') ) {
#   				$this->_list_parameter_arrray['interval'] = $session->getValue('interval');
			} else{
   				$this->_list_parameter_arrray['interval'] = $context_item->getListLength();
			}

			if ( isset($_GET['sort']) ) {
   				$this->_list_parameter_arrray['sort'] = $_GET['sort'];
			}  else {
   				$this->_list_parameter_arrray['sort'] = 'modified';
			}

			if ( isset($_GET['selgroup']) ) {
   				$this->_list_parameter_arrray['selgroup'] = $_GET['selgroup'];
			}
			if ( isset($_GET['seltopic']) ) {
   				$this->_list_parameter_arrray['seltopic'] = $_GET['seltopic'];
			}
			if ( isset($_GET['selinstitution']) ) {
   				$this->_list_parameter_arrray['selinstitution'] = $_GET['selinstitution'];
			}

			if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   				$this->_list_parameter_arrray['search'] = '';
   				$this->_list_parameter_arrray['selinstitution'] = '';
   				$this->_list_parameter_arrray['seltopic'] = '';
   				$this->_list_parameter_arrray['last_selected_tag'] = '';
   				$this->_list_parameter_arrray['$seltag_array'] = array();
   				$this->_list_parameter_arrray['sel_activating_status'] = '';
			} else {
   				$this->_list_parameter_arrray['sel_activating_status'] = '';

   				// Find current search text
   				if ( isset($_GET['search']) and ($_GET['search'] != $translator->getMessage('COMMON_SEARCH_IN_ROOM') || $_GET['search'] != $translator->getMessage('COMMON_SEARCH_IN_RUBRIC'))) {
      				$this->_list_parameter_arrray['search'] = $_GET['search'];
   				}  else {
      				$this->_list_parameter_arrray['search'] = '';
   				}

   				// Find current sel_activating_status selection
   				if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
      				$this->_list_parameter_arrray['sel_activating_status'] = $_GET['selactivatingstatus'];
   				} else {
      				$this->_list_parameter_arrray['sel_activating_status'] = 2;
   				}

				// Find current buzzword selection
   				if ( isset($_GET['selbuzzword']) and $_GET['selbuzzword'] !='-2') {
      				$this->_list_parameter_arrray['selbuzzword'] = $_GET['selbuzzword'];
   				} else {
      				$this->_list_parameter_arrray['selbuzzword'] = 0;
   				}

   				// Find current tag selection
    			$last_selected_tag = '';
				if ( isset($_GET['seltag']) and $_GET['seltag'] =='yes') {
      				$i = 0;
      				while ( !isset($_GET['seltag_'.$i]) ){
         				$i++;
      				}
      				$seltag_array[] = $_GET['seltag_'.$i];
      				$j = 0;
      				while(isset($_GET['seltag_'.$i]) and $_GET['seltag_'.$i] !='-2'){
         				if (!empty($_GET['seltag_'.$i])){
            				$seltag_array[$i] = $_GET['seltag_'.$i];
            				$j++;
         				}
         				$i++;
      				}
      				$this->_list_parameter_arrray['last_selected_tag'] = $seltag_array[$j-1];
   				}else{
      				$this->_list_parameter_arrray['last_selected_tag'] = '';
      				$this->_list_parameter_arrray['seltag_array'] = array();
   				}

	   			$context_item = $environment->getCurrentContextItem();
   				$current_room_modules = $context_item->getHomeConf();
   				if ( !empty($current_room_modules) ){
      				$room_modules = explode(',',$current_room_modules);
   				} else {
      				$room_modules =  $default_room_modules;
   				}
				$sel_array = array();
   				foreach ( $room_modules as $module ) {
      				$link_name = explode('_', $module);
      				if ( $link_name[1] != 'none' ) {
         				if ($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) {
            				// Find current institution selection
            				$string = 'sel'.$link_name[0];
            				if ( isset($_GET[$string]) and $_GET[$string] !='-2') {
               					$sel_array[$link_name[0]] = $_GET[$string];
	            			} else {
    	           				$sel_array[$link_name[0]] = 0;
        	    			}
         				}
      				}
   				}
   				foreach($sel_array as $rubric => $value){
   					$params = $environment->getCurrentParameterArray();
   					$sel_name = 'sel'.$rubric;
   					unset($params[$sel_name]);
      				$link_parameter_text = '';
      				$hidden_array = array();
      				if ( count($params) > 0 ) {
         				foreach ($params as $key => $parameter) {
        					$link_parameter_text .= '&'.$key.'='.$parameter;
         					$tmp_hidden_array = array();
         					$tmp_hidden_array['name'] = $key;
         					$tmp_hidden_array['value'] = $parameter;
         					$hidden_array[] = $tmp_hidden_array;
         				}
         			}
   					$params = $environment->getCurrentParameterArray();
   					$label_manager = $environment->getManager($rubric);
   					$label_manager->setContextLimit($environment->getCurrentContextID());
   					$label_manager->select();
   					$rubric_list = $label_manager->get();
   					$temp_rubric_list = clone $rubric_list;
   					$label_item = $temp_rubric_list->getFirst();
   					$tmp2_array = array();
   					while ($label_item){
   						$tmp_array = array();
   						$tmp_array['id'] = $label_item->getItemID();
   						$tmp_array['name'] = $label_item->getTitle();
   						$tmp_array['selected'] = 'no';
   						if (isset($params['selgroup']) and $params['selgroup'] == $label_item->getItemID()
   							or (isset($params['seltopic']) and $params['seltopic'] == $label_item->getItemID())
   							or (isset($params['selinstitution']) and $params['selinstitution'] == $label_item->getItemID())
   						){
   						   $tmp_array['selected'] = $label_item->getItemID();
   						}
   						$tmp2_array[] = $tmp_array;
   						$label_item = $temp_rubric_list->getNext();
   					}
   					$tmp3_array = array();
   					$tmp3_array['items'] = $tmp2_array;
   					$tmp3_array['action'] = 'commsy.php?cid='.$environment->getCurrentContextID().'&mod='.$environment->getCurrentModule().'&fct='.$environment->getCurrentFunction().'&'.$link_parameter_text;
   					$tmp3_array['hidden'] = $hidden_array;
   					$tmp3_array['tag'] = strtoupper($rubric);
   					$tmp3_array['name'] = $rubric;
   					$this->_perspective_rubric_array[] = $tmp3_array;
   					unset($rubric_list);
				}
			}
		}

		abstract function getListContent();
	}