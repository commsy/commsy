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

			// set list actions
			$this->assign('list', 'actions', $this->getListActions());

			// set index actions
			$this->assign('index', 'actions', $this->getIndexActions());

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

      		if($sort_parameter === 'numposts') {
      			$return_array['sort_numposts_link'] = $link_parameter_text.'&sort=numposts_rev';
         		$return_array['sort_numposts'] = 'up';
      		} elseif($sort_parameter === 'numposts_rev') {
      			$return_array['sort_numposts_link'] = $link_parameter_text.'&sort=numposts';
         		$return_array['sort_numposts'] = 'down';
      		} else {
      			$return_array['sort_numposts_link'] = $link_parameter_text.'&sort=numposts';
         		$return_array['sort_numposts'] = 'none';
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

		protected function getAdditionalRestrictionTextAsHTML($restriction_array){
			return $restriction_array;
		}

		protected function _getRestrictionTextAsHTML(){
#      		$ref_user = $this->getRefUser();
#      		$ref_iid = $this->getRefIID();
	      	$html = '';
	      	$restriction_array= array();
			$environment = $this->_environment;
			$translator = $environment->getTranslationObject();
           	$params = $environment->getCurrentParameterArray();
      		if ( isset($params['seltag'])
       			or isset($params['selbuzzword'])
       			or isset($params['selgroup'])
       			or isset($params['seluser'])
      	 		or isset($params['selinstitution'])
       			or isset($params['seltopic'])
       			or isset($params['search'])
       			or isset($params['selstatus'])
       			or isset($params['selactivatingstatus'])
       			or isset($this->_activation_limit)
       			or (!empty($ref_user) and isset($params['mode']) and $params['mode'] == 'attached')
       			or (!empty($ref_iid) and isset($params['mode']) and $params['mode'] == 'attached')
       			or (isset($this->_additional_selects) && $this->_additional_selects)
       		){
/* TODO ref_user und ref_item migrieren, wenn klar ist, wofür
         	if ( !empty($ref_user) ){
            	$html_text ='<tr>'.LF;
            	$html_text .='<td>'.LF;
            	$html_text .= '<span class="infocolor">'.$this->_translator->getMessage('MODIFIED_ITEMS_LISTVIEW_SEPERATOR').': </span>';
            	$html_text .='</td>'.LF;
            	$html_text .='<td style="text-align:right;">'.LF;
            	$ref_item = $this->getRefItem();
            	$link_params = array();
            	$link_params['iid'] = $ref_user;
            	$title = ahref_curl($this->_environment->getCurrentContextID(),
                                CS_USER_TYPE,
                                'detail',
                                $link_params,
                                chunkText($this->_text_as_html_short($ref_item->getFullName()),15),
                                '',
                                '',
                                $this->getFragment()
                               );
            	unset($link_params);
            	$html_text .= '<span><a title="'.$this->_text_as_html_short($ref_item->getFullName()).'">'.$title.'</a></span>';
            	$picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            	$new_params = $params;
            	unset($new_params['ref_user']);
            	unset($new_params['mode']);
            	$html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            	$html_text .='</td>'.LF;
            	$html_text .='</tr>'.LF;
            	$html .= $html_text;
         	}
         	if ( !empty($ref_iid) and !(isset($_GET['mode']) and $_GET['mode']=='formattach')){
            	$html_text ='<tr>'.LF;
            	$html_text .='<td>'.LF;
            	$html_text .= '<span class="infocolor">'.$this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').': </span>';
            	$html_text .='</td>'.LF;
            	$html_text .='<td style="text-align:right;">'.LF;
            	$ref_item = $this->getRefItem();
            	$ref_item_type = $ref_item->getItemType();
            	if($ref_item_type == CS_USER_TYPE){
               		$link_title = $this->_text_as_html_short($ref_item->getFullName());
            	} else {
               		$link_title = $this->_text_as_html_short($ref_item->getTitle());
            	}
            	if ( $ref_item_type == CS_ANNOTATION_TYPE ) {
              		$ref_item2 = $ref_item->getLinkedItem();
              		$module = type2module($ref_item2->getItemType());
              		$link_params = array();
              		$link_params['iid'] = $ref_item2->getItemID();
              		$title = ahref_curl($this->_environment->getCurrentContextID(),
                                  $module,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $ref_item->getItemID()
                                 );
              		unset($link_params);
              		$html .= '</span>'.LF;
           		} elseif ( $ref_item_type == CS_SECTION_TYPE ) {
              		$ref_item2 = $ref_item->getLinkedItem();
              		$link_params = array();
              		$link_params['iid'] = $ref_item2->getItemID();
              		$title = ahref_curl($this->_environment->getCurrentContextID(),
                                  CS_MATERIAL_TYPE,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $ref_item->getItemID()
                                 );
              		unset($link_params);
              		$html .= '</span>'.LF;
           		} elseif ( $ref_item_type == CS_DISCARTICLE_TYPE ) {
              		$ref_item2 = $ref_item->getLinkedItem();
              		$link_params = array();
              		$link_params['iid'] = $ref_item2->getItemID();
              		$title = ahref_curl($this->_environment->getCurrentContextID(),
                                  CS_DISCUSSION_TYPE,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $ref_item->getItemID()
                                 );
              		unset($link_params);
              		$html .= '</span>'.LF;
           		} else {
              		$module = type2module($ref_item_type);
              		$link_params = array();
              		$link_params['iid'] = $ref_iid;
              		$title = ahref_curl($this->_environment->getCurrentContextID(),
                                  $module,
                                  'detail',
                                  $link_params,
                                  chunkText($link_title,20),
                                  '',
                                  '',
                                  $this->getFragment()
                                 );
              		unset($link_params);
              		$html .= '</span>'.LF;
           		}
            	$html_text .= '<span><a title="'.$ref_item->getTitle().'">'.$title.'</a></span>';
            	$picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            	$new_params = $params;
            	unset($new_params['ref_iid']);
            	unset($new_params['mode']);
            	$html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            	$html_text .='</td>'.LF;
            	$html_text .='</tr>'.LF;
            	$html .= $html_text;
         	}
 END TODO ref_user und ref_item migrieren, wenn klar ist, wofür */
	         	include_once('classes/views/cs_view.php');
	         	$parameters = array();
	   			$parameters['environment'] = $environment;
	   			$parameters['with_modifying_actions'] = 'no';
	         	$view_object = new cs_view($parameters);
	         	if ( isset($params['search']) and !empty($params['search']) ){
	            	$new_params = $params;
	            	unset($new_params['search']);
	            	unset($new_params['selrestriction']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	         		$tmp_array['type'] = 'search';
	         		$tmp_array['name'] = chunkText($view_object->_text_as_html_short(urldecode($params['search'])),12);
					$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array();
	         	}
	         	$restriction_array = $this->getAdditionalRestrictionTextAsHTML($restriction_array);
	         	if ( isset($params['selgroup']) and !empty($params['selgroup']) ){
	            	$new_params = $params;
	            	unset($new_params['selgroup']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	            	if ($params['selgroup'] == '-1'){
	        			$tmp_array['name'] = $translator->getMessage('COMMON_NOT_LINKED');
	            	}else{
	               		$group_manager = $environment->getGroupManager();
	               		$group_item = $group_manager->getItem($params['selgroup']);
	        			$tmp_array['name'] = chunkText($view_object->_text_as_html_short($group_item->getTitle()),12);
		            }
	         		$tmp_array['type'] = 'selgroup';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['seltopic']) and !empty($params['seltopic']) ){
	            	$new_params = $params;
	            	unset($new_params['seltopic']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   		$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	            	if ($params['seltopic'] == '-1'){
	        			$tmp_array['name'] = $translator->getMessage('COMMON_NOT_LINKED');
	            	}else{
	               		$topic_manager = $environment->getTopicManager();
	               		$topic_item = $topic_manager->getItem($params['seltopic']);
	        			$tmp_array['name'] = chunkText($view_object->_text_as_html_short($topic_item->getTitle()),12);
		            }
	         		$tmp_array['type'] = 'seltopic';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['selinstitution']) and !empty($params['selinstitution']) ){
	            	$new_params = $params;
	            	unset($new_params['selinstitution']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	            	if ($params['selinstitution'] == '-1'){
	        			$tmp_array['name'] = $translator->getMessage('COMMON_NOT_LINKED');
	            	}else{
	               		$institution_manager = $environment->getTopicManager();
	               		$institution_item = $institution_manager->getItem($params['selinstitution']);
	        			$tmp_array['name'] = chunkText($view_object->_text_as_html_short($institution_item->getTitle()),12);
		            }
	         		$tmp_array['type'] = 'selinstitution';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['selbuzzword']) and !empty($params['selbuzzword']) ){
	            	$new_params = $params;
	            	unset($new_params['selbuzzword']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	            	if ($params['selbuzzword'] == '-1'){
	        			$tmp_array['name'] = $translator->getMessage('COMMON_NOT_LINKED');
	            	}else{
	               		$buzzword_manager = $environment->getBuzzwordManager();
	               		$buzzword_item = $buzzword_manager->getItem($params['selbuzzword']);
	        			$tmp_array['name'] = chunkText($view_object->_text_as_html_short($buzzword_item->getTitle()),12);
		            }
	         		$tmp_array['type'] = 'selbuzzword';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['seluser']) and !empty($params['seluser']) ){
	            	$new_params = $params;
	            	unset($new_params['seluser']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	            	if ($params['seluser'] == '-1'){
	        			$tmp_array['name'] = $translator->getMessage('COMMON_NOT_LINKED');
	            	}else{
	               		$user_manager = $environment->getUserManager();
	               		$user_item = $user_manager->getItem($params['seluser']);
	        			$tmp_array['name'] = chunkText($view_object->_text_as_html_short($user_item->getTitle()),12);
		            }
	         		$tmp_array['type'] = 'seluser';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['seltag']) and !empty($params['seltag']) ){
	            	$i = 0;
	            	while ( !isset($params['seltag_'.$i]) ){
	               		$i++;
	            	}
	            	$new_params = $params;
	            	unset($new_params['seltag_'.$i]);
	            	unset($new_params['seltag']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$tmp_array = array();
	            	$tag_manager = $environment->getTagManager();
	               	$tag_item = $tag_manager->getItem($params['seltag_'.$i]);
	        		$tmp_array['name'] = chunkText($view_object->_text_as_html_short($tag_item->getTitle()),12);
	         		$tmp_array['type'] = 'seltag';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['selstatus']) and $params['selstatus'] != '-1' and $params['selstatus'] != '0' and !empty($params['selstatus']) and $environment->current_module == "todo" ){
	            	$new_params = $params;
	            	unset($new_params['selstatus']);
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		if (isset($params['selstatus']) and $params['selstatus'] == 1){
	            		$status_text = $translator->getMessage('TODO_NOT_STARTED');
	         		}elseif(isset($params['selstatus']) and $params['selstatus'] == 2){
	            		$status_text = $translator->getMessage('TODO_IN_POGRESS');
	         		}elseif(isset($params['selstatus']) and $params['selstatus'] == 3){
	            		$status_text = $translator->getMessage('TODO_DONE');
	         		}elseif(isset($params['selstatus']) and $params['selstatus'] == 4){
	            		$status_text = $translator->getMessage('TODO_NOT_DONE');
	         		}elseif(isset($params['selstatus']) and $params['selstatus'] != 0){
	            		$context_item = $environment->getCurrentContextItem();
	            		$todo_status_array = $context_item->getExtraToDoStatusArray();
	            		$status_text = '';
	            		if (isset($todo_status_array[$params['selstatus']])){
	               			$status_text = $todo_status_array[$params['selstatus']];
	            		}
	         		}else{
	            		$status_text = '';
	         		}

	       			$tmp_array['name'] = $status_text;
	         		$tmp_array['type'] = 'selstatus';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
	         	}
	         	if ( isset($params['selcolor']) and (!empty($params['selcolor']) or $environment->getCurrentModule() == 'date') and $params['selcolor'] != 2 ){
	            	$new_params = $params;
	         		$new_params['selcolor'] = 2;
	      			$link_parameter_text = '';
	      			if ( count($new_params) > 0 ) {
	         			foreach ($new_params as $key => $parameter) {
	            		   	$link_parameter_text .= '&'.$key.'='.$parameter;
	         			}
	         		}
	         		$color_text = '';
	         		switch ('#'.$params['selcolor']){
	            		case '#999999': $color_text = $translator->getMessage('DATE_COLOR_GREY');break;
	            		case '#CC0000': $color_text = $translator->getMessage('DATE_COLOR_RED');break;
	            		case '#FF6600': $color_text = $translator->getMessage('DATE_COLOR_ORANGE');break;
	            		case '#FFCC00': $color_text = $translator->getMessage('DATE_COLOR_DEFAULT_YELLOW');break;
	            		case '#FFFF66': $color_text = $translator->getMessage('DATE_COLOR_LIGHT_YELLOW');break;
	            		case '#33CC00': $color_text = $translator->getMessage('DATE_COLOR_GREEN');break;
	            		case '#00CCCC': $color_text = $translator->getMessage('DATE_COLOR_TURQUOISE');break;
	            		case '#3366FF': $color_text = $translator->getMessage('DATE_COLOR_BLUE');break;
	            		case '#6633FF': $color_text = $translator->getMessage('DATE_COLOR_DARK_BLUE');break;
	            		case '#CC33CC': $color_text = $translator->getMessage('DATE_COLOR_PURPLE');break;
	            		default: $color_text = $translator->getMessage('DATE_COLOR_UNKNOWN');
	         		}
	         		$tmp_array = array();
	        		$tmp_array['name'] = $color_text;
	         		$tmp_array['type'] = 'selcolor';
	 				$tmp_array['link_parameter'] = $link_parameter_text;
					$restriction_array[] = $tmp_array;
         		}
       		}
      		return $restriction_array;
   		}





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
      		//unset($params['seltag_array']);
      		unset($params['seltag']);

      		foreach($params as $param => $value) {
      			if(mb_substr($param, 0, 6) === 'seltag') unset($params[$param]);
      		}

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
		   $this->getPostData();
		   
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

		abstract protected function getAdditionalActions(&$perms);

		private function getIndexActions() {
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			$return = array(
				'new'		=> false
			);

			$this->getAdditionalActions($return);

			// TODO: dont forget print - which is always allowed

			$current_module = $this->_environment->getCurrentModule();

			if($current_user->isUser() && $this->_with_modifying_actions && $current_module != CS_USER_TYPE) {
				$return['new'] = true;
				/*
				 * $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         }
         if ( $current_context->isOpen()
              and $current_context->isPrivateRoom()
              and $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID())
            ) {
            // do nothing
         } else {
            $html .= '&nbsp;';
         }
         $html .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                            'edit',
                            $params,
                            $image,
                            $this->_translator->getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
				 */
			} else {
				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon_disabled"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon_disabled"/>';
         }
         $html .= '&nbsp;&nbsp;<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_NEW_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}
			return $return;
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
				if($this->_environment->getCurrentModule() === CS_DISCUSSION_TYPE) {
					$this->_list_parameter_arrray['sort'] = 'latest';
				} elseif($this->_environment->getCurrentModule() === CS_TOPIC_TYPE) {
					$this->_list_parameter_arrray['sort'] = 'title';
				} else {
					$this->_list_parameter_arrray['sort'] = 'modified';
				}
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

		abstract protected function getListContent();

		abstract protected function getAdditionalListActions();

		private function getListActions() {
			$return = array();

			// add no action
			$return[] = array('selected' => true, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_NONE, 'display' => '___COMMON_LIST_ACTION_NO___');

			// add separator
			$return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => '', 'display' => '------------------------------');

			// TODO: clipboard mode
			//$session = $this->_environment->getSessionItem();
			//if(	!isset($this->_list_parameter_arrray['clipboard_id_array']) ||
			//	!$session->issetValue($this->_environment->getCurrentModule() . '_clipboard', $this->_list_parameter_arrray['clipboard_id_array'])) {
				// clipboard is empty
				$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_MARK_AS_READ, 'display' => '___COMMON_LIST_ACTION_MARK_AS_READ___');

				$return = array_merge($return, $this->getAdditionalListActions());

				$return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => '', 'display' => '------------------------------');

				if($this->_environment->getCurrentModule() != 'user'){
   				if($this->_environment->inPrivateRoom()) {
   					$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_DELETE, 'display' => '___COMMON_LIST_ACTION_DELETE___');
   				} else {
   					$user = $this->_environment->getCurrentUserItem();
   					if($user->isModerator()) {
   						$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_DELETE, 'display' => '___COMMON_LIST_ACTION_DELETE___');
   					} else {
   						$return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => CS_LISTOPTION_DELETE, 'display' => '___COMMON_LIST_ACTION_DELETE___');
   					}
   				}
				} else {
				   $return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_EMAIL_SEND, 'display' => '___USER_LIST_ACTION_EMAIL_SEND___');
				}

				// TODO: move to cs_todo_index_controller.php
				if($this->_environment->getCurrentModule() == 'todo'){
				   $return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => '', 'display' => '------------------------------');
				   $user = $this->_environment->getCurrentUserItem();
					if($user->isModerator()) {
						$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_TODO_DONE, 'display' => '___TODO_LIST_ACTION_DONE___');
						$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_TODO_IN_PROGRESS, 'display' => '___TODO_LIST_ACTION_IN_PROGRESS___');
						$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_TODO_NOT_STARTED, 'display' => '___TODO_LIST_ACTION_NOT_STARTED___');
					} else {
						$return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => '', 'display' => '___TODO_LIST_ACTION_DONE___');
						$return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => '', 'display' => '___TODO_LIST_ACTION_IN_PROGRESS___');
						$return[] = array('selected' => false, 'disabled' => true, 'id' => '', 'value' => '', 'display' => '___TODO_LIST_ACTION_NOT_STARTED___');
					}
				}

			//} else {
			//	// clipboard is not empty
			//	$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => 1, 'display' => '___CLIPBOARD_PASTE_BUTTON___');
			//	$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => 2, 'display' => '___CLIPBOARD_DELETE_BUTTON___');
			//}

			return $return;
		}
	}