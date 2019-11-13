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
		$current_context = $this->_environment->getCurrentContextItem();

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
                $current_user = $this->_environment->getCurrentUser();
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

		$first = '';
		foreach($room_modules as $module) {
			list($name, $view) = explode('_', $module);

			if($view !== 'none') {
				switch($name) {
					case 'group':
						if(empty($first)) {
							$first = 'group';
						}
						break;
					case CS_TOPIC_TYPE:
						if(empty($first)) {
							$first = CS_TOPIC_TYPE;
						}
						break;
				}
			}
		}

		// set up ids of linked items
		if ( isset($this->_item) ) {
		   $material_ids = $this->_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
		   $session->setValue('cid' . $this->_environment->getCurrentContextID() . '_material_index_ids', $material_ids);
		}

		if($current_context->withRubric(CS_TOPIC_TYPE) and isset($this->_item) ) {
			$ids = $this->_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
			$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_topics_index_ids', $ids);
		}

		if($current_context->withRubric(CS_GROUP_TYPE) and isset($this->_item) ) {
			$ids = $this->_item->getLinkedItemIDArray(CS_GROUP_TYPE);
			$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_group_index_ids', $ids);
		}

		$rubric_connections = array();
		if($first === CS_TOPIC_TYPE) {
			$rubric_connections = array(CS_TOPIC_TYPE);
			if($current_context->withRubric(CS_GROUP_TYPE)) {
				$rubic_connections[] = CS_GROUP_TYPE;
			}
		} elseif($first == 'group') {
			$rubric_connections = array(CS_GROUP_TYPE);
			if($current_context->withRubric(CS_TOPIC_TYPE)) {
				$rubric_connections[] = CS_TOPIC_TYPE;
			}
		}
		$rubric_connections[] = CS_MATERIAL_TYPE;

		// TODO: seems to be senseless??? why creating $rubric_connections array and set item here
		// before migration: $detail_view->setRubricConnections($dates_item);

		$this->setRubricConnections($this->_item);

		// annotations
		// get annotations
		if ( isset($this->_item) ) {
		   $annotations = $this->_item->getAnnotationList();
		}

		// assign annotations
		$this->assign('detail', 'annotations', $this->getAnnotationInformation($annotations));


		// assign to template
        $this->assign('detail', 'content', $this->getDetailContent());
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
		$translator = $this->_environment->getTranslationObject();

		// description
        $desc = $this->_item->getDescription();
        if(!empty($desc)) {

            $converter->setFileArray($this->getItemFileList());
      		if ( $this->_with_old_text_formating ) {
      			$desc = $converter->textFullHTMLFormatting($desc);
      		} else {
               //$desc = $converter->cleanDataFromTextArea($desc);
               //$desc = $converter->compareWithSearchText...
               //$desc = $converter->text_as_html_long($desc);
               //$desc = $converter->show_images($desc, $this->_item, true);
			      $desc = $converter->textFullHTMLFormatting($desc);
      		}
        }

		// set up style of days and times
		// time
		$parse_time_start = convertTimeFromInput($this->_item->getStartingTime());
		$conforms = $parse_time_start['conforms'];
		if($conforms === true) {
			$start_time_print = getTimeLanguage($parse_time_start['datetime']);
		} else {
			// TODO: compareWithSearchText
			$start_time_print = $converter->text_as_html_short($this->_item->getStartingTime());
		}

		$parse_time_end = convertTimeFromInput($this->_item->getEndingTime());
		$conforms = $parse_time_end['conforms'];
		if($conforms === true) {
			$end_time_print = getTimeLanguage($parse_time_end['datetime']);
		} else {
			// TODO: compareWithSearchText
			$end_time_print = $converter->text_as_html_short($this->_item->getEndingTime());
		}
		// day
		$parse_day_start = convertDateFromInput($this->_item->getStartingDay(), $this->_environment->getSelectedLanguage());
		$conforms = $parse_day_start['conforms'];
		if($conforms === true) {
			$start_day_print = $this->_item->getStartingDayName() . ', ' . $translator->getDateInLang($parse_day_start['datetime']);
		} else {
			// TODO: compareWithSearchText
			$start_day_print = $converter->text_as_html_short($this->_item->getStartingDay());
		}

		$parse_day_end = convertDateFromInput($this->_item->getEndingDay(), $this->_environment->getSelectedLanguage());
		$conforms = $parse_day_end['conforms'];
		if($conforms === true) {
			$end_day_print = $this->_item->getEndingDayName() . ', ' . $translator->getDateInLang($parse_day_end['datetime']);
		} else {
			// TODO: compareWithSearchText
			$end_day_print = $converter->text_as_html_short($this->_item->getEndingDay());
		}

		// formate dates and times for displaying
		$date_print = '';
		$time_print = '';

		if($end_day_print !== '') {
			// with ending day
			$date_print = $translator->getMessage('DATES_AS_OF') . ' ' . $start_day_print . ' ' . $translator->getMessage('DATES_TILL') . ' ' . $end_day_print;
			if($parse_day_start['conforms'] && $parse_day_end['conforms']) {
				// start and end are dates, not string <- ???
				$date_print .= ' (' . getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']) . ' ' . $translator->getMessage('DATES_DAYS') . ')';
			}

			if($start_time_print !== '' && $end_time_print === '') {
				// only start time given
				$time_print = $translator->getMessage('DATES_AS_OF_LOWER') . ' ' . $start_time_print;

				if($parse_time_start['conforms'] === true) {
					$time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}
			} elseif($start_time_print === '' && $end_time_print !== '') {
				// only end time given
				$time_print = $translator->getMessage('DATES_TILL') . ' ' . $end_time_print;

				if($parse_time_end['conforms'] === true) {
					$time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}
			} elseif($start_time_print !== '' && $end_time_print !== '') {
				// all times given
				if($parse_time_end['conforms'] === true) {
					$end_time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}

				if($parse_time_start['conforms'] === true) {
					$start_time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}

				$date_print =	$translator->getMessage('DATES_AS_OF') . ' ' . $start_day_print . ', ' . $start_time_print . '<br/>' .
								$translator->getMessage('DATES_TILL') . ' ' . $end_day_print . ', ' . $end_time_print;

				if($parse_day_start['conforms'] && $parse_day_end['conforms']) {
					$date_print .= ' (' . getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']) . ' ' . $translator->getMessage('DATES_DAYS') . ')';
				}
			}
		} else {
			// without ending day
			$date_print = $translator->getMessage('DATES_ON_DAY') . ' ' . $start_day_print;

			if($start_time_print !== '' && $end_time_print == '') {
				// starting time given
				$time_print = $translator->getMessage('DATES_AS_OF_LOWER') . ' ' . $start_time_print;

				if($parse_time_start['conforms'] === true) {
					$time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}
			} elseif($start_time_print === '' && $end_time_print !== '') {
				// end time given
				$time_print = $translator->getMessage('DATES_TILL') . ' ' . $end_time_print;

				if($parse_time_end['conforms'] === true) {
					$time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}
			} elseif($start_time_print !== '' && $end_time_print !== '') {
				// all times given
				if($parse_time_end['conforms'] === true) {
					$end_time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}

				if($parse_time_start['conforms'] === true) {
					$start_time_print .= ' ' . $translator->getMessage('DATES_OCLOCK');
				}

				$time_print = $translator->getMessage('DATES_FROM_TIME_LOWER') . ' ' . $start_time_print . ' ' . $translator->getMessage('DATES_TILL') . ' ' . $end_time_print;
			}
		}

		if($parse_day_start['timestamp'] === $parse_day_end['timestamp'] && $parse_day_start['conforms'] && $parse_day_end['conforms']) {
			$date_print = $translator->getMessage('DATES_ON_DAY') . ' ' . $start_day_print;

			if($start_time_print !== '' && $end_time_print === '') {
				// starting time given
				$time_print = $translator->getMessage('DATES_AS_OF_LOWER') . ' ' . $start_time_print;
			} elseif($start_time_print === '' && $end_time_print !== '') {
				// endtime given
				$time_print = $translator->getMessage('DATES_TILL') . ' ' . $end_time_print;
			} elseif($start_time_print !== '' && $end_time_print !== '') {
				// all times given
				$time_print = $translator->getMessage('DATES_FROM_TIME_LOWER') . ' ' . $start_time_print . ' ' . $translator->getMessage('DATES_TILL') . ' ' . $end_time_print;
			}
		}

		// date and time
		$datetime = $date_print;
		if($time_print !== '') {
			$datetime .= BRLF . $time_print;
		}

		// place
		$place = $this->_item->getPlace();
		if(!empty($place)) {
			// TODO: compareWithSearchText
			$place = $place;
		}

		// color
		$color = $this->_item->getColor();
		if(!empty($color)) {
			$color = $converter->text_as_html_short($color);
		}
       
		 $formal = array();
	    if ($this->_item->isNotActivated()){
	        $activating_date = $this->_item->getActivatingDate();
	        $text = '';
	        if (strstr($activating_date,'9999-00-00')){
	           $activating_text = $translator->getMessage('COMMON_NOT_ACTIVATED');
	        }else{
	           $activating_text = $translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($this->_item->getActivatingDate());
	        }
			$temp_array = array();
			$temp_array[] = $translator->getMessage('COMMON_RIGHTS');
			$temp_array[] = $activating_text;
			$formal[] = $temp_array;
	    }
	    $temp_array = array();


        return array(
				'formal'			=> $formal,
				'privat'			=> $this->_item->issetPrivatDate(),
				'datetime'			=> $datetime,
				'place'				=> $place,
				'color'				=> $color,
                'files'				=> $this->getFileContent(),
                'member'			=> $this->getMember(),
				'item_id'			=> $this->_item->getItemID(),
				'title'				=> $this->_item->getTitle(),
				'description'		=> $desc,
				'moredetails'		=> $this->getCreatorInformationAsArray($this->_item)
        );
    }

    private function getMember() {
		$return = array();
		$current_user = $this->_environment->getCurrentUser();
		$converter = $this->_environment->getTextConverter();

		$members = $this->_item->getParticipantsItemList();
		if(!$members->isEmpty()) {
			$member = $members->getFirst();
			$count = $members->getCount();
			$counter = 0;

			while($member) {
				$member_array = array();

				$linktext = $member->getFullname();
				// TODO: compareWithSearchText
				$linktext = $converter->text_as_html_short($linktext);
				$member_array['linktext'] = $linktext;

				$param_zip = $this->_environment->getValueOfParameter('download');

				if($member->isUser()) {
					$member_array['is_user'] = true;

					if($member->maySee($current_user)) {
						$member_array['visible'] = true;
						$member_array['as_link'] = false;

						if(empty($param_zip) || $param_zip !== 'zip') {
							$member_array['as_link'] = true;
							$member_array['item_id'] = $member->getItemID();
						}
					} else {
						// disabled
						$member_array['visible'] = false;
					}
				} else {
					$member_array['is_user'] = false;
					$member_array['as_link'] = false;

					if(empty($param_zip) || $param_zip !== 'zip') {
						$member_array['as_link'] = true;
						$member_array['item_id'] = $member->getItemID();
					}
				}

				$return[] = $member_array;

				$member = $members->getNext();
			}
		}

		return $return;
    }
}