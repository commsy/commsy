<?php
require_once('classes/controller/cs_detail_controller.php');
require_once('functions/date_functions.php');

class cs_date_detail_controller extends cs_detail_controller {
    /**
     * constructor
     */
    public function __construct(cs_environment $environment) {
        // call parent
        parent::__construct($environment);
         
        $this->_tpl_file = 'date_detail';
    }

    /*
     * every derived class needs to implement an processTemplate function
     */
    public function processTemplate() {
        // call parent
        parent::processTemplate();
         
        // assign rubric to template
        $this->assign('room', 'rubric', CS_DATE_TYPE);
    }

    /*****************************************************************************/
    /******************************** ACTIONS ************************************/
    /*****************************************************************************/
    public function actionDetail() {

        $session = $this->_environment->getSessionItem();

        // try to set the item
        $this->setItem();
         
        $this->setupInformation();
         

        //		include_once('include/inc_delete_entry.php');

        // check for item type
        $item_manager = $this->_environment->getItemManager();
        $type = $item_manager->getItemType($_GET['iid']);
        if($type !== CS_DATE_TYPE) {
            // TODO: implement error handling
            /*
             * $params = array();
             $params['environment'] = $environment;
             $params['with_modifying_actions'] = true;
             $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
             unset($params);
             $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
             $page->add($errorbox);
             */
        } else {

            $creatorInfoStatus = array();
            if (!empty($_GET['creator_info_max'])) {
                $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
            }

            // check if item exists
            if($this->_item === null) {
                include_once('functions/error_functions.php');
                trigger_error('Item ' . $_GET['iid'] . ' does not exist!', E_USER_ERROR);
            }

            // check if item is deleted
            elseif($this->_item->isDeleted()) {
                // TODO: implement error handling
                /*
                 * $params = array();
                 $params['environment'] = $environment;
                 $params['with_modifying_actions'] = true;
                 $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
                 unset($params);
                 $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
                 $page->add($errorbox);
                 */
            }

            // check for access rights
            //            elseif(!$this->_item->maySee($current_user)) {
            // TODO: implement error handling
            /*
             * $params = array();
             $params['environment'] = $environment;
             $params['with_modifying_actions'] = true;
             $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
             unset($params);
             $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
             $page->add($errorbox);
             */
            //            } else {
             
            // Get clipboard
            if ( $session->issetValue('date_clipboard') ) {
                $clipboard_id_array = $session->getValue('date_clipboard');
            } else {
                $clipboard_id_array = array();
            }
             
            // Copy to clipboard
            if ( isset($_GET['add_to_date_clipboard'])
            and !in_array($current_item_id, $clipboard_id_array) ) {
                $clipboard_id_array[] = $current_item_id;
                $session->setValue('date_clipboard', $clipboard_id_array);
            }
             
            // set clipboard ids
            $this->setClipboardIDArray($clipboard_id_array);
             
            if (!empty($_GET['date_option'])) {
                $current_user = $environment->getCurrentUser();
                if ($_GET['date_option']=='1') {
                    $this->_item->addParticipant($current_user);
                } else if ($_GET['date_option']=='2') {
                    $this->_item->removeParticipant($current_user);
                }
            }
        }
        /*
         //TODO: is current room open?
         $context_item = $environment->getCurrentContextItem();
         $room_open = $context_item->isOpen();

         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $room_open;
         $params['creator_info_status'] = $creatorInfoStatus;
         $detail_view = $class_factory->getClass(DATE_DETAIL_VIEW,$params);
         unset($params);


         */

        // mark as read and noticed
        $this->markRead();
        $this->markNoticed();



        $context_item = $this->_environment->getCurrentContextItem();

        $current_room_modules = $context_item->getHomeConf();
        if ( !empty($current_room_modules) ){
            $room_modules = explode(',',$current_room_modules);
        } else {
            //$room_modules =  $default_room_modules;
        }
        /*
         $first = '';
         foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
         switch ($link_name[0]) {
         case 'group':
         if (empty($first)){
         $first = 'group';
         }
         break;
         case CS_TOPIC_TYPE:
         if (empty($first)){
         $first = CS_TOPIC_TYPE;
         }
         break;
         }
         }
         }
         // set up ids of linked items
         $material_ids = $dates_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids', $material_ids);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ) {
         $ids = $dates_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
         }
         if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
         $ids = $dates_item->getLinkedItemIDArray(CS_GROUP_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
         }
         if ( $context_item->withRubric(CS_INSTITUTION_TYPE) ) {
         $ids = $dates_item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_institutions_index_ids', $ids);
         }
         $rubric_connections = array();
         if ($first == CS_TOPIC_TYPE){
         $rubric_connections = array(CS_TOPIC_TYPE);
         if ($context_item->withRubric(CS_GROUP_TYPE) ){
         $rubric_connections[] = CS_GROUP_TYPE;
         }
         if ($context_item->withRubric(CS_INSTITUTION_TYPE)) {
         $rubric_connections[] = CS_INSTITUTION_TYPE;
         }
         }elseif($first == 'group'){
         $rubric_connections = array(CS_GROUP_TYPE);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ){
         $rubric_connections[] = CS_TOPIC_TYPE;
         }
         }
         elseif ($first == CS_INSTITUTION_TYPE){
         $rubric_connections = array(CS_INSTITUTION_TYPE);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ){
         $rubric_connections[] = CS_TOPIC_TYPE;
         }
         }
         $rubric_connections[] = CS_MATERIAL_TYPE;
         $detail_view->setRubricConnections($dates_item);
         */
        // annotations
        $annotations = $this->_item->getAnnotationList();
 
        /*


        // TODO: highlight search words in detail views
        $session_item = $environment->getSessionItem();
        if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
        $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
        if ( !empty($search_array['search']) ) {
        $detail_view->setSearchText($search_array['search']);
        }
        unset($search_array);
        }

        $page->add($detail_view);
        } */

        //feed template
        //pr($this->getAnnotationInformation($annotations));
        
        $timeLine1 = '';
        $timeLine2 = '';
        $translator = $this->_environment->getTranslationObject();
        if($this->_item->getStartingDay() < $this->_item->getEndingDay()) {
            //more than one day
            $timeLine1 = $translator->getMessage('DATES_AS_OF').' '.$this->_item->getStartingDayName().', '.getDateTimeInLang($this->_item->getDateTime_start());
          
            $timeLine2 = $translator->getMessage('DATES_TILL').' '.$this->_item->getEndingDayName().', '.getDateTimeInLang($this->_item->getDateTime_end()).' ('
                .getDifference(str_replace('-', '',$this->_item->getStartingDay()), str_replace('-', '',$this->_item->getEndingDay()))
                .' '.$translator->getMessage('DATES_DAYS').')';
        } elseif($this->_item->getStartingDay() > $this->_item->getEndingDay()) {
            //TODO: Error
            
        } else {
            //Same Day
            if(strlen($this->_item->getEndingTime()) > 0) {
                //from ... to ...
                $timeLine1 = $translator->getMessage('DATES_ON_DAY').' '.$this->getStartingDayStringInLang();
                $timeLine2 = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.getTimeLanguage($this->_item->getStartingTime()).' '.$translator->getMessage('DATES_OCLOCK').' '
                    .$translator->getMessage('DATES_TILL').' '.getTimeLanguage($this->_item->getEndingTime()).' '.$translator->getMessage('DATES_OCLOCK');
            } else {
                //from...
                $timeLine1 = $translator->getMessage('DATES_ON_DAY').' '.$this->getStartingDayStringInLang();
                $timeLine2 = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.getTimeLanguage($this->_item->getStartingTime());
            }
        }
        
        $this->assign('detail', 'content', $this->getDetailContent());
        $this->assign('detail', 'annotations', $this->getAnnotationInformation($annotations));
        $this->assign('detail', 'files', $this->getFileContent());
        //pr($this->getCreatorInformationAsArray($this->_item));
        //$this->assign('detail', 'lastedit', $this->getCreatorInformationAsHTML($this->_item));

        //        }
    }

    /*****************************************************************************/
    /******************************** END ACTIONS ********************************/
    /*****************************************************************************/

    protected function getAdditionalActions(&$perms) {
		$current_user = $this->_environment->getCurrentUserItem();
		$perms['date_participate'] = false;
		$perms['date_leave'] = false;
		
		// participate / leave
		if($this->_item->isParticipant($current_user)) {
			// is participant
			if($this->_with_modifying_actions) {
				// leave
				$perms['date_leave'] = true;
			} else {
				// disabled
				/*
				if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_LEAVE').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('DATE_LEAVE')).' "class="disabled">'.$image.'</a>'.LF;
				 * 
				 */
			}
		} else {
			// participate
			if($current_user->isUser() && $this->_with_modifying_actions) {
				$perms['date_participate'] = true;
			} else {
				// disabled
				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
						$image = '<img src="images/commsyicons_msie6/22x22/group_enter_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_ENTER').'"/>';
						} else {
						$image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_ENTER').'"/>';
						}
						$html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('DATE_ENTER')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}
		}
    }

    protected function setBrowseIDs() {
        $session = $this->_environment->getSessionItem();
         
        if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_date_index_ids')) {
            $this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_date_index_ids'));
        }
    }

    protected function getDetailContent() {
        $converter = $this->_environment->getTextConverter();
        $desc = $this->_item->getDescription();
        if(!empty($desc)) {
            $converter->setFileArray($this->getItemFileList());
            $desc = $converter->cleanDataFromTextArea($desc);
            //$desc = $converter->compareWithSearchText...
            $desc = $converter->text_as_html_long($desc);
            //$desc = $converter->show_images($desc, $this->_item, true);
            //$html .= $this->getScrollableContent($desc,$item,'',true);
        }
		
        return array(
				'private' => $this->_item->issetPrivatDate(),
            	'startingday' => $this->getStartingDayStringInLang(),
            	'timeline1' => $timeLine1,
                'timeline2' => $timeLine2,
                'place' => $this->_item->getPlace(),
                'files' => $this->getFileContent(),
                'member' => $this->getMember(),
                'color' => $this->_item->getColor(),
				'item_id'		=> $this->_item->getItemID(),
				'title'			=> $this->_item->getTitle(),
				'creator'		=> $this->_item->getCreatorItem()->getFullName(),
				'creation_date'	=> getDateTimeInLang($this->_item->getCreationDate()),
				'description'	=> $desc,
				'moredetails'	=> $this->getCreatorInformationAsArray($this->_item)
        );
    }

    private function convertTimeFromInput($time) {
        $converted = array();
        $original  = $time;

        // Remove spaces to prevent hassle
        $time = trim(str_replace(' ', '', $time));


        $hours = '00';
        $minutes = '00';
        $secs = '00';
        $ampm = '';
        $stct = '';
        $conforms = false;

        if ( preg_match('~^([01]?[0-9]|2[0-3])([\.:]([0-5]?[0-9]))?([\.:]([0-5]?[0-9]))?(am|pm)?((s|c)\.?t\.?)?$~iu',$time,$matches) ) {
            $hours = $matches[1];
            if ( !empty($matches[3]) ) {
                $minutes = $matches[3];
            }
            if ( !empty($matches[5]) ) {
                $secs = $matches[5];
            }
            if ( !empty($matches[3]) ) {
                $minutes = $matches[3];
            }
            if ( !empty($matches[6]) ) {
                $ampm = $matches [6];
            }
            if ( !empty($matches[7]) ) {
                $stct = $matches [7];
            }

            if ( ($hours < 12) and ($hours >= 1) and ($ampm == 'pm') ) {
                $hours += 12;
            }
            if ( ($hours >= 12) and ($hours <= 23) and ($ampm == 'am') ) {
                $hours -= 12;
            }

            if ( $stct == 'st' ) {
                $minutes = '00';
            } elseif ( $stct == 'ct' ) {
                $minutes = '15';
            }

            $conforms = true;
        }

        if ( $conforms ) {
            $converted['conforms']  = true;
            $converted['timestamp'] = str_pad($hours, 2, '0', STR_PAD_LEFT).str_pad($minutes, 2, '0', STR_PAD_LEFT).$secs;
            $converted['datetime']  = str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.$secs;
            if ( empty($stct) ) {
                $converted['display']  = '';
            } else {
                $converted['display']  = $stct;
            }
        } else {
            $converted['conforms']  = false;
            $converted['timestamp'] = '000000';
            $converted['datetime']  = '00:00:00';
            $converted['display']   = $original;
        }
        return $converted;
    }

    private function getMember() {
        // Members
        $text_converter = $this->_environment->getTextConverter();
        $translator = $this->_environment->getTranslationObject();
        $user = $this->_environment->getCurrentUser();
        $member_html = '';
        $members = $this->_item->getParticipantsItemList();
        if ( !$members->isEmpty() ) {
            $member = $members->getFirst();
            $count = $members->getCount();
            $counter = 0;
            while ($member) {
                $counter++;
                if ( $member->isUser() ){
                    $linktext = $member->getFullname();
                    $linktext = $this->_compareWithSearchText($linktext);
                    $linktext = $text_converter->text_as_html_short($linktext);
                    if ( $member->maySee($user) ) {
                        $params = array();
                        $params['iid'] = $member->getItemID();
                        $param_zip = $this->_environment->getValueOfParameter('download');
                        if ( empty($param_zip)
                        or $param_zip != 'zip'
                        ) {
                            $member_html .= ahref_curl($this->_environment->getCurrentContextID(),
                                   'user',
                                   'detail',
                            $params,
                            $linktext);
                        } else {
                            $member_html .= $linktext;
                        }
                        unset($params);
                    } else {
                        $member_html .= '<span class="disabled">'.$linktext.'</span>'.LF;
                    }
                    if ( $counter != $count) {
                        $member_html .= ', ';
                    }
                }else{
                    $link_title = chunkText($member->getFullName(),35);
                    $link_title = $this->_compareWithSearchText($link_title);
                    $link_title = $text_converter->text_as_html_short($link_title);
                    $param_zip = $this->_environment->getValueOfParameter('download');
                    if ( empty($param_zip)
                    or $param_zip != 'zip'
                    ) {
                        $member_html .= ahref_curl( $this->_environment->getCurrentContextID(),
                        $this->_environment->getCurrentModule(),
                        $this->_environment->getCurrentFunction(),
                        array(),
                        $link_title,
                        $translator->getMessage('USER_STATUS_REJECTED'),
                                      '_self',
                                      '',
                                      '',
                                      '',
                                      '',
                                      'class="disabled"',
                                      '',
                                      '',
                        true);
                    } else {
                        $member_html .= $link_title;
                    }
                    if ( $counter != $count) {
                        $member_html .= ', ';
                    }
                }
                $member = $members->getNext();
            }
//            echo $member_html;
            return $member_html;
        }
        //        $temp_array[0] = $translator->getMessage('DATE_PARTICIPANTS');
        //        $temp_array[1] = $member_html;
        //        $formal_data = array();
        //        $formal_data[] = $temp_array;
        //        if ( !empty($formal_data) ) {
        //            $html .= $this->_getFormalDataAsHTML($formal_data);
        //            $html .= BRLF;
        //        }
    }
    

   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    */
   function _compareWithSearchText($value, $bold = true) {
      if ( !empty($this->_search_array) ) {
         foreach ($this->_search_array as $search_text) {
            if ( mb_stristr($value,$search_text) ) {
               // CSS Klasse erstellen für Farbmarkierung
               include_once('functions/misc_functions.php');
               if ( getMarkerColor() == 'green') {
                  $replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
               }
               else if (getMarkerColor() == 'yellow') {
                  $replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
               }
               // $replace = '(:mainsearch_text:)$0(:mainsearch_text_end:)';
               // $replace = '*$0*';
               if ( !$bold ) {
                  if ( getMarkerColor() == 'green') {
                    $replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
                }
                else if (getMarkerColor() == 'yellow') {
                    $replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
                }

                  // $replace = '(:search:)$0(:search_end:)';
               }
               if ( stristr($value,'<!-- KFC TEXT') ) {
                   if(getMarkerColor() == 'green'){
                      $replace = '<span class="searched_text_green">$0</span>';
                   }
                   else if(getMarkerColor() == 'yellow'){
                      $replace = '<span class="searched_text_yellow">$0</span>';
                   }

                  // $replace = '<span class="bold">$0</span>';
                  if ( !$bold ) {
                    $replace = '<span class="italic" style="font-style: italic;">$0</span>';
                  }
               }
               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu',$replace,$value);
            }
         }
      }
      return $value;
   }
   
   private function getStartingDayStringInLang() {
       $translator = $this->_environment->getTranslationObject();
       return $this->_item->getStartingDayName().', '.$translator->getDateInLang($this->_item->getStartingDay());
   }
   
    private function getEndingDayStringInLang() {
       $translator = $this->_environment->getTranslationObject();
       return $this->_item->getEndingDayName().', '.$translator->getDateInLang($this->_item->getEndingDay());
   }
}