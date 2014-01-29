<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_actions_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionAddToClipboard() {
			$session = $this->_environment->getSessionItem();
			$item_manager = $this->_environment->getItemManager();
			
			$itemId = $this->_data['itemId'];
			
			$copyRubricArray = $this->getUtils()->getCopyRubrics();
			
			// get type of item
			$type = $item_manager->getItemType($itemId);
			if($type === CS_LABEL_TYPE) {
				$this->setErrorReturn("104", "can't copy labels", array("item_id" => $itemId));
				
			} elseif(!in_array($type, $copyRubricArray)) {
				$this->setErrorReturn("106", "can't copy this item - not allowed", array("item_id" => $itemId));
				
			} else {
				// get current clipboard content
				$clipboardIdArray = array();
				if($session->issetValue($type . "_clipboard")) {
					$clipboardIdArray = $session->getValue($type . "_clipboard");
				}
				
				// if not already set, add id to clipboard
				if(!in_array($itemId, $clipboardIdArray)) {
					$clipboardIdArray[] = $itemId;
					$session->setValue($type . "_clipboard", $clipboardIdArray);
					
					$this->_environment->getSessionManager()->save($session);
					$this->setSuccessfullDataReturn(array());
				} else {
					$this->setErrorReturn("105", "item was already added to clipboard", array("item_id" => $itemId));
				}
			}
			
			echo $this->_return;
		}

		public function actionVersionMakeNew() {
		   $material_manager = $this->_environment->getMaterialManager();
		   $latest_version_item = $material_manager->getItem($this->_data['itemId']);
		   $old_version_item = $material_manager->getItemByVersion($this->_data['itemId'], $this->_data['versionID']);
		   $clone_item = $old_version_item->cloneCopy(true);
		   $latest_version_id = $latest_version_item->getVersionID();
		   $clone_item->setVersionID($latest_version_id+1);
		   $clone_item->save();
		   $old_version_item->delete();
		   $this->setSuccessfullDataReturn(array());
		   echo $this->_return;
		}
		
		public function actionExportToWordpress() {
		   $item_manager = $this->_environment->getItemManager();
		   $temp_item = $item_manager->getItem($this->_data['itemId']);
		   
		   $wordpress_manager = $this->_environment->getWordpressManager();
		   $wordpress_manager->exportItemToWordpress($this->_data['itemId'],$temp_item->getItemType());
		   $this->setSuccessfullDataReturn(array());
		   echo $this->_return;
		}
		
		public function actionExportToWiki() {
		   $item_manager = $this->_environment->getItemManager();
		   $temp_item = $item_manager->getItem($this->_data['itemId']);
		   
		   $wiki_manager = $this->_environment->getWikiManager();
		   $wiki_manager->exportItemToWiki($this->_data['itemId'],$temp_item->getItemType());
		   $this->setSuccessfullDataReturn(array());
		   echo $this->_return;
		}
		
		public function actionSendXHRErrorReporting() {
			$error = $this->_data["error"];
			$ioArgs = $this->_data["ioargs"];
			
			global $c_xhr_error_reporting;
			if (isset($c_xhr_error_reporting) && !empty($c_xhr_error_reporting)) {
				$currentUser = $this->_environment->getCurrentUserItem();
				$browserInfo = get_browser(null, true);
				
				// setup mail
				$receivers = implode(", ", $c_xhr_error_reporting);
				$subject = "CommSy XHR Error";
				
				$message = "
					Fehler
					========================
					Servername: " . $_SERVER['SERVER_NAME'] . "
					" . $error["message"] . "
					Beschreibung: " . $error["description"] . "
					
					
					Benutzer
					========================
					UserID: " . $currentUser->getUserID() . "
					ItemID: " . $currentUser->getItemID() . "
					Agent: " . $_SERVER["HTTP_USER_AGENT"] . "
					
					
					Aufruf
					========================
					URL: " . $ioArgs["args"]["url"] . "
					PostData: " . $ioArgs["args"]["postData"] . "
				";
				
				mail($receivers, $subject, $message);
			}
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionImportDate()
		{
			/*
			// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ( $context_item->isProjectRoom() and $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);

}  elseif ( !$current_user->isUser() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}
// Access granted
else {


   if ( isset($_GET['selection']) ){
      $date_array = $session->getValue('date_array');

      // Find out what to do
      if ( isset($_POST['option']) ) {
         $command = $_POST['option'];
      } else {
         $command = '';
      }

      // Cancel editing
      if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
         redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'index','');

      }
      // Show form and/or save item
      else {
         // Initialize the form
         $class_params= array();
         $class_params['environment'] = $environment;
         $form = $class_factory->getClass(DATE_IMPORT_SELECTION_FORM,$class_params);
         unset($class_params);
         $form->setArray($date_array[0]);
         // Load form data from postvars
         if ( !empty($_POST) ) {
            $values = $_POST;
            $form->setFormPost($values);
         }

         $form->prepareForm();
         $form->loadValues();
         if ( !empty($command) and
           isOption($command, $translator->getMessage('DATES_SELECTION_BUTTON')) ) {

            $date_manager = $environment->getDateManager();
            foreach($date_array as $dates_data){
               $dates_item = $date_manager->getNewItem();
               $dates_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $dates_item->setCreatorItem($user);
               $dates_item->setCreationDate(getCurrentDateTimeInMySQL());
               if (isset($dates_data[$_POST['title']])){
                  $dates_item->setTitle($dates_data[$_POST['title']]);
               }else{
                  $dates_item->setTitle($translator->getMessage('COMMON_TITLE'));
               }
               if (isset($dates_data[$_POST['description']])){
                  $dates_item->setDescription($dates_data[$_POST['description']]);
               }
               if (isset($_POST['mode'])){
                  $dates_item->setDateMode('1');
               }else{
                  $dates_item->setDateMode('0');
               }
               $dates_item->setPublic('1');
               if (isset($dates_data[$_POST['starttime']])){
                  $dates_item->setStartingTime($dates_data[$_POST['starttime']]);
               }
               if (isset($dates_data[$_POST['startday']])){
                  $starting_day_array = explode('.',$dates_data[$_POST['startday']]);
                  if ( isset($starting_day_array[2]) ){
                     if (mb_strlen($starting_day_array[1])==1){
                        $month = '0'.$starting_day_array[1];
                     }else{
                        $month = $starting_day_array[1];
                     }
                     if (mb_strlen($starting_day_array[2])==1){
                        $day = '0'.$starting_day_array[2];
                     }else{
                        $day = $starting_day_array[2];
                     }
                     $starting_day = $day.'-'.$month.'-'.$starting_day_array[0];
                  }else{
                     $starting_day = $dates_data[$_POST['startday']];
                  }
               }else{
                  $starting_day = $translator->getMessage('COMMON_NOTHING_ATTACHED');
               }
               $dates_item->setStartingDay($starting_day);

               if (isset($dates_data[$_POST['endday']])){
                  $ending_day_array = explode('.',$dates_data[$_POST['endday']]);
                  if ( isset($ending_day_array[2]) ){
                     if (mb_strlen($ending_day_array[1])==1){
                        $month = '0'.$ending_day_array[1];
                     }else{
                        $month = $ending_day_array[1];
                     }
                     if (mb_strlen($ending_day_array[2])==1){
                        $day = '0'.$ending_day_array[2];
                     }else{
                        $day = $ending_day_array[2];
                     }
                     $ending_day = $day.'-'.$month.'-'.$ending_day_array[0];
                  }else{
                     $ending_day = $dates_data[$_POST['endday']];
                  }
                  $dates_item->setEndingDay($ending_day);
               }
               if (isset($dates_data[$_POST['endtime']])){
                  $dates_item->setEndingTime($dates_data[$_POST['endtime']]);
               }
               if (isset($dates_data[$_POST['location']])){
                  $dates_item->setPlace($dates_data[$_POST['location']]);
               }
               $dates_item->save();
            }

            $context_item = $environment->getCurrentContextItem();
            $session->unsetValue('date_array');
            redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'index','');
         }
         // display form
         $class_params = array();
         $class_params['environment'] = $environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
         unset($class_params);
         $params['selection']= true;
         $form_view->setAction(curl($environment->getCurrentContextID(),CS_DATE_TYPE,'import',$params));
         $form_view->setForm($form);
         $page->add($form_view);

      }
   }else{

      // function for page edit
      // - to check files for virus
      if (isset($c_virus_scan) and $c_virus_scan) {
         include_once('functions/page_edit_functions.php');
      }


      // Find out what to do
      if ( isset($_POST['option']) ) {
         $command = $_POST['option'];
      } else {
         $command = '';
      }

      if ( !isset($params) ) {
         $params = array();
      }

      // Cancel editing
      if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
         redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'index',$params);

      }

      // Show form and/or save item
      else {

         // Initialize the form
         $class_params= array();
         $class_params['environment'] = $environment;
         $form = $class_factory->getClass(DATE_IMPORT_FORM,$class_params);
         unset($class_params);
         // Load form data from postvars
         if ( !empty($_POST) ) {
            if ( !empty($_FILES) ) {
               if ( !empty($_FILES['dates_upload']['tmp_name']) ) {
                  $new_temp_name = $_FILES['dates_upload']['tmp_name'].'_TEMP_'.$_FILES['dates_upload']['name'];
                  move_uploaded_file($_FILES['dates_upload']['tmp_name'],$new_temp_name);
                  $_FILES['dates_upload']['tmp_name'] = $new_temp_name;
                  $session_item = $environment->getSessionItem();
                  if ( isset($session_item) ) {
                     $current_iid = $environment->getCurrentContextID();
                     $session_item->setValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_temp_name',$new_temp_name);
                     $session_item->setValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_name',$_FILES['dates_upload']['name']);
                  }
               }
               $values = array_merge($_POST,$_FILES);
            } else {
               $values = $_POST;
            }
            $form->setFormPost($values);
         }

         $form->prepareForm();
         $form->loadValues();

         // Save item
         if ( !empty($command)
              and isOption($command, $translator->getMessage('DATES_IMPORT_BUTTON'))
            ) {

            $correct = $form->check();

            if ( $correct
                 and empty($_FILES['dates_upload']['tmp_name'])
                 and !empty($_POST['hidden_dates_upload_name'])
               ) {
               $session_item = $environment->getSessionItem();
               if ( isset($session_item) ) {
                  $current_iid = $environment->getCurrentContextID();
                  $_FILES['dates_upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_temp_name');
                  $_FILES['dates_upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_name');
                  $session_item->unsetValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_temp_name');
                  $session_item->unsetValue($environment->getCurrentContextID().'_dates_'.$current_iid.'_upload_name');
               }
            }

            if ( $correct
               and ( !isset($c_virus_scan)
               or !$c_virus_scan
               or page_edit_virusscan_isClean($_FILES['dates_upload']['tmp_name'],$_FILES['dates_upload']['name']))) {
               $data_array = file($_FILES['dates_upload']['tmp_name']);
               $dates_data_array = array();
               $separator = ',';
               if (!empty($_POST['separator'])){
                  $separator = $_POST['separator'];
               }

               for ($i = 0; $i < count($data_array); $i++){
                  /*
                   * skip empty cvs lines
                   *//*
                  if(trim(str_replace(',','',$data_array[$i])) == '') continue;

                  if ($i == 0){
                     $temp_data = str_replace('"','',$data_array[$i]);
                     $data_header_array = explode($separator,$temp_data);
                  }else{
                     $temp_data = str_replace('"','',$data_array[$i]);
                     $temp_data_array = explode($separator,$temp_data);
                     for ($j = 0; $j < count($data_header_array); $j++){
                        if ( isset($temp_data_array[$j]) ){
                           include_once('functions/text_functions.php');
                           $dates_data_array[$i-1][$data_header_array[$j]] = cs_utf8_encode($temp_data_array[$j]);
                        }
                     }
                  }
               }
               $session->setvalue('date_array', $dates_data_array);
               $params['selection']= true;
               redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'import',$params);
            }
         }

         // display form
         $class_params = array();
         $class_params['environment'] = $environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
         unset($class_params);
         $form_view->setAction(curl($environment->getCurrentContextID(),CS_DATE_TYPE,'import',''));
         $form_view->setForm($form);
         $page->add($form_view);
      }
   }
}
			 */
		}
		
		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}