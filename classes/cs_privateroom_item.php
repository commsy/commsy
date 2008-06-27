<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** upper class of the community item
 */
include_once('classes/cs_room_item.php');

/** class for a community
 * this class implements a community item
 */
class cs_privateroom_item extends cs_room_item {

   var $_user_item = NULL;

   /**
    * Constructor
    */
   function cs_privateroom_item ($environment) {
      $this->cs_context_item($environment);
      $this->_type = CS_PRIVATEROOM_TYPE;
      $this->_default_rubrics_array[0] = CS_MYROOM_TYPE;
      $this->_default_rubrics_array[1] = CS_MATERIAL_TYPE;
      $this->_default_rubrics_array[2] = CS_DATE_TYPE;
      $this->_default_rubrics_array[3] = CS_TOPIC_TYPE;
      $this->_default_rubrics_array[4] = CS_USER_TYPE;
      $this->_default_home_conf_array[CS_MYROOM_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
   }

   function isPrivateRoom () {
      return true;
   }

   /** get projects of a project
    * this method returns a list of projects which are linked to the project
    *
    * @return object cs_list a list of projects (cs_project_item)
    *
    * @author CommSy Development Group
    */
   function getProjectList () {
      return $this->getLinkedItemList(CS_MYROOM_TYPE);
   }

   /** get time spread for items on home
    * this method returns the time spread for items on the home of the project project
    *
    * @return integer the time spread
    *
    * @author CommSy Development Group
    */
   function getTimeSpread () {
      $retour = '7';
      if ($this->_issetExtra('TIMESPREAD')) {
         $retour = $this->_getExtra('TIMESPREAD');
         if ($retour != '1' and $retour != '7' and $retour != '30'){
            $retour ='7';
         }
      }
      return $retour;
   }

   /** set time spread for items on home
    * this method sets the time spread for items on the home of the project project
    *
    * @param integer value the time spread
    *
    * @author CommSy Development Group
    */
   function setTimeSpread ($value) {
      $this->_addExtra('TIMESPREAD',(int)$value);
   }


   /** get home status for home page
    * this method returns the display status of the home page
    *
    * @return string the home status
    *
    * @author CommSy Development Group
    */
   function getHomeStatus () {
      $retour = 'normal';
      if ($this->_issetExtra('HOMESTATUS')) {
         $retour = $this->_getExtra('HOMESTATUS');
      }
      return $retour;
   }

   /** set home status for home page
    * this method sets the the display status of the home page
    *
    * @param string value the home status
    *
    * @author CommSy Development Group
    */
   function setHomeStatus ($value) {
      $this->_addExtra('HOMESTATUS',$value);
   }


  /** set projects of a project item by item id and version id
   * this method sets a list of project item_ids and version_ids which are linked to the project
   *
   * @param array of project ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setProjectListByID ($value) {
      $project_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $project_array[] = $tmp_data;
      }
      $this->_setValue(CS_MYROOM_TYPE, $project_array, FALSE);
   }

   /** save private room
    * this method save the private room
    */
   function save() {
      $item_id = $this->getItemID();
      $manager = $this->_environment->getPrivateRoomManager();

     if ( empty($item_id) ) {
        $this->setContinuous();
        $this->setServiceLinkActive();
     }

      $this->_save($manager);

      if ( empty($item_id) ) {
         // create first moderator
         $current_user = $this->getCreatorItem();
         if ( !isset($current_user) or empty($current_user) ) {
            $current_user = $this->_environment->getCurrentUserItem();
         }
         $new_room_user = $current_user->cloneData();
         $new_room_user->setContextID($this->getItemID());
         $new_room_user->makeModerator();
         $new_room_user->makeContactPerson();
         $new_room_user->setVisibleToLoggedIn();
         $new_room_user->save();
         $new_room_user->setCreatorID2ItemID();
      }
      $this->_save($manager);
      if ( empty($item_id) ) {
         $this->initTagRootItem();
      }
   }

   /** delete private room
    * this method deletes the private room
    */
   function delete() {
      $manager = $this->_environment->getPrivateRoomManager();
      $this->_delete($manager);
   }

   function undelete () {
      $manager = $this->_environment->getPrivateRoomManager();
      $this->_undelete($manager);
   }

   function setRoomContext ($value) {
   }

   /** is newsletter active ?
    * can be switched at room configuration
    *
    * true = newletter is active
    * false = newsletter is not active, default
    *
    * @return boolean
    */
   function isPrivateRoomNewsletterActive () {
      $retour = false;
      if ( $this->_issetExtra('PRIVATEROOMNEWSLETTER') ) {
         $active = $this->_getExtra('PRIVATEROOMNEWSLETTER');
         if ($active != 'none') {
            $retour = true;
         }
      }
      return $retour;
   }

   /** set activity of the newsletter, INTERNAL
    *
    */
   function setPrivateRoomNewsletterActivity ($value) {
      $this->_addExtra('PRIVATEROOMNEWSLETTER',$value);
   }

   /** set newsletter active
    */
   function getPrivateRoomNewsletterActivity () {
      $retour = 'none';
      if ( $this->_issetExtra('PRIVATEROOMNEWSLETTER') ) {
         $retour = $this->_getExtra('PRIVATEROOMNEWSLETTER');
      }
      return $retour;
   }

   /** send email newsletter
    * this cron job sends an email newsletter to all users, who wants the newsletter
    * the newsletter describes the activity in the last seven days
    *
    * return array result of cron job
    */
   function _sendPrivateRoomNewsletter () {
      $retour = array();
      $retour['title'] = 'privateroom newsletter';
      $retour['description'] = 'send activity newsletter to private room user';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      // get user in room
      $user = $this->getOwnerUserItem();

      if ( isset($user)
           and $this->isPrivateRoomNewsletterActive()
           and $this->isPrivateroom()
         ) {
         $file = $_SERVER['PHP_SELF'];
         $file = str_replace('cron','commsy',$file);
         $curl_text = 'http://'.$_SERVER['HTTP_HOST'].$file.'?cid=';

         $mail_array = array();
         $mail_array[] = $user->getEmail();
         // get activity informations for room and send mail
         if ( !empty($mail_array) ) {

            // email
            $id = $user->getItemID();

            $portal = $this->getContextItem();
            $room_manager = $this->_environment->getRoomManager();
            $list = $room_manager->_getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$portal->getItemID());
            $list2 = new cs_list();
            if ( !$list->isEmpty() ) {
               $item = $list->getFirst();
               while ( $item ) {
                  if ( $item->isPrivateRoom()
                       or !$item->isShownInPrivateRoomHomeByItemID($id)
                       or !$item->isOpen()
                     ) {
                  // do nothing
                  } else {
                     $list2->add($item);
                  }
                  unset($item);
                  $item = $list->getNext();
               }
               unset($item);
               unset($list);
            }

            $translator = $this->_environment->getTranslationObject();
            $translator->setRubricTranslationArray($this->getRubricTranslationArray());
            $mail_sequence = $this->getPrivateRoomNewsletterActivity();

            $body = '';
            $item  = $list2->getFirst();
            while ($item) {

               $conf = $item->getHomeConf();
               if ( !empty($conf) ) {
                  $rubrics = explode(',', $conf);
               } else {
                  $rubrics = array();
               }
               $count = count($rubrics);
               $check_managers = array();
               $check_rubrics = array();
               foreach ( $rubrics as $rubric ) {
                  list($rubric_name, $rubric_status) = explode('_', $rubric);
                  if ( $rubric_status != 'none' ) {
                     $check_managers[] = $rubric_name;
                     if ( $rubric_name == 'discussion' ) {
                        $check_managers[] = 'discarticle';
                     }
                     if ( $rubric_name == 'material' ) {
                        $check_managers[] = 'section';
                     }
                  }
               }
               $check_managers[] = 'annotation';

               $title = '<a href="'.$curl_text.$item->getItemID().'&amp;mod=home&amp;fct=index">'.$item->getTitle().'</a>';
               $body_title = BR.BR.$title.''.LF;
               $total_count = 0;
               if ($mail_sequence =='daily'){
                  $count_total = $item->getPageImpressions(1);
                  $active = $item->getActiveMembers(1);
               } else {
                  $count_total = $item->getPageImpressions(7);
                  $active = $item->getActiveMembers(7);
               }
               if ( $count_total == 1 ) {
                  $body_title .= '('.$count_total.'&nbsp;'.$translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS_SINGULAR').'; ';
               } else {
                  $body_title .= '('.$count_total.'&nbsp;'.$translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS').'; ';
               }
               $body_title .= $translator->getMessage('ACTIVITY_ACTIVE_MEMBERS').': ';
               $body_title .= $active.'):'.BRLF;
               $body2 ='';

               for ( $i =0; $i<$count; $i++){
                  $rubric_array = explode('_', $rubrics[$i]);
                  if ( $rubric_array[1] != 'none' ) {

                     $rubric_manager = $this->_environment->getManager($rubric_array[0]);
                     $rubric_manager->reset();
                     $rubric_manager->setContextLimit($item->getItemID());
                     if ( $mail_sequence =='daily' ) {
                        $rubric_manager->setAgeLimit(1);
                     } else {
                        $rubric_manager->setAgeLimit(7);
                     }
                     if ( $rubric_manager instanceof cs_dates_manager ) {
                        $rubric_manager->setDateModeLimit(2);
                     }
                     if ( $rubric_manager instanceof cs_user_manager ) {
                        $rubric_manager->setUserLimit();
                     }
                     $rubric_manager->select();
                     $rubric_list = $rubric_manager->get();        // returns a cs_list of announcement_items
                     $ids = $rubric_manager->getIDs();
                     $rubric_item = $rubric_list->getFirst();
                     $user_manager = $this->_environment->getUserManager();
                     $user_manager->resetLimits();
                     $user_manager->setUserIDLimit($user->getUserID());
                     $user_manager->_room_limit = $item->getItemID();
                     $user_manager->select();
                     $user_list = $user_manager->get();
                     $ref_user = $user_list->getFirst();
                     $temp_body ='';
                     $count_entries = 0;
                     while($rubric_item){
                        $noticed_manager = $this->_environment->getNoticedManager();
                        $noticed = $noticed_manager->getLatestNoticedForUserByID($rubric_item->getItemID(),$ref_user->getItemID());
                        if ( empty($noticed) ) {
                           $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_NEW').']</span>';
                        } elseif ( $noticed['read_date'] < $rubric_item->getModificationDate() ) {
                           $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_CHANGED').']</span>';
                        } else {
                           $info_text = '';
                        }
                        if (!empty($info_text)){
                           $count_entries++;
                           $params = array();
                           $params['iid'] = $rubric_item->getItemID();
                           $title ='';
                           if ($rubric_item->isA(CS_USER_TYPE)){
                              $title .= $rubric_item->getFullname();
                           } else {
                              $title .= $rubric_item->getTitle();
                           }
                           if ( $rubric_item->isA(CS_LABEL_TYPE) ) {
                              $mod = $rubric_item->getLabelType();
                           } else {
                              $mod = $rubric_item->getType();
                           }
                           $ahref_curl = '<a href="'.$curl_text.$item->getItemID().'&amp;mod='.$mod.'&amp;fct=detail&amp;iid='.$params['iid'].'">'.$title.'</a>';

                           $temp_body .= BR.'&nbsp;&nbsp;- '.$ahref_curl;
                        }
                        $rubric_item = $rubric_list->getNext();

                     }
                     $tempMessage = '';
                     switch ( strtoupper($rubric_array[0]) ){
                        case 'ANNOUNCEMENT':
                           $tempMessage = $translator->getMessage('ANNOUNCEMENT_INDEX');
                           break;
                        case 'DATE':
                           $tempMessage = $translator->getMessage('DATES_INDEX');
                           break;
                        case 'DISCUSSION':
                           $tempMessage = $translator->getMessage('DISCUSSION_INDEX');
                           break;
                        case 'GROUP':
                           $tempMessage = $translator->getMessage('GROUP_INDEX');
                           break;
                        case 'INSTITUTION':
                           $tempMessage = $translator->getMessage('INSTITUTION_INDEX');
                           break;
                        case 'MATERIAL':
                           $tempMessage = $translator->getMessage('MATERIAL_INDEX');
                           break;
                        case 'MYROOM':
                           $tempMessage = $translator->getMessage('MYROOM_INDEX');
                           break;
                        case 'PROJECT':
                           $tempMessage = $translator->getMessage('PROJECT_INDEX');
                           break;
                        case 'TODO':
                           $tempMessage = $translator->getMessage('TODO_INDEX');
                           break;
                        case 'TOPIC':
                           $tempMessage = $translator->getMessage('TOPIC_INDEX');
                           break;
                        case 'USER':
                           $tempMessage = $translator->getMessage('USER_INDEX');
                           break;
                        default:
                           $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_privateroom_item(456) ');
                           break;
                     }
                     if ( $count_entries == 1 ) {
                        $ahref_curl = '<a href="'.$curl_text.$item->getItemID().'&amp;mod='.$rubric_array[0].'&amp;fct=index">'.$tempMessage.'</a>';
                        $body2 .= '&nbsp;&nbsp;'.$ahref_curl;
                        $body2 .= ' <span style="font-size:8pt;">('.$count_entries.' '.$translator->getMessage('NEWSLETTER_NEW_SINGLE_ENTRY').')</span>';
                     }elseif($count_entries > 1){
                        $ahref_curl = '<a href="'.$curl_text.$item->getItemID().'&amp;mod='.$rubric_array[0].'&amp;fct=index">'.$tempMessage.'</a>';
                        $body2 .= '&nbsp;&nbsp;'.$ahref_curl;
                        $body2 .= ' <span style="font-size:8pt;">('.$count_entries.' '.$translator->getMessage('NEWSLETTER_NEW_ENTRIES').')</span>';
                     }
                     if (!empty($body2) and !empty($temp_body)){
                        $body2 .= $temp_body.BRLF.LF;
                     }
                  }
                  $j = $i+1;
               }
               $item = $list2->getNext();
               if (!empty($body2)){
                  $body  .= $body_title;
                  $body2 .= BRLF;
                  $body  .= $body2;
               }else{
                  $body  .= $body_title;
                  $body2 .= '&nbsp;&nbsp;'.$translator->getMessage('COMMON_NO_NEW_ENTRIES').BRLF;
                  $body  .= $body2;
               }
            }
            if (empty($body)){
               $translator->getMessage('COMMON_NO_NEW_ENTRIES').LF;
            }
            $body .= LF;
            $portal = $this->getContextItem();
            $portal_title = '';
            if (isset($portal)){
               $portal_title = $portal->getTitle();
            }
            if ($mail_sequence == 'daily'){
               $body = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_HEADER_DAILY',$portal_title).LF.LF.$body;
            }else{
               $body = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_HEADER_WEEKLY',$portal_title).LF.LF.$body;
            }

            $body .= BRLF.BR.'-----------------------------'.BRLF.LF.$translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_FOOTER');

            $from = $translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_title);
            $to = implode($mail_array,',');
            if ($mail_sequence == 'daily'){
               $subject = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_DAILY').': '.$portal_title;
            }else{
               $subject = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_WEEKLY').': '.$portal_title;
            }

            // send email
            include_once('classes/cs_mail.php');
            $mail = new cs_mail();
            $mail->set_to($to);
            $mail->set_from_name($from);
            $server_item = $this->_environment->getServerItem();
            $default_sender_address = $server_item->getDefaultSenderAddress();
            if (!empty($default_sender_address)) {
               $mail->set_from_email($default_sender_address);
            } else {
               $mail->set_from_email('@');
            }
            $mail->set_subject($subject);
            $mail->set_message($body);
            $mail->setSendAsHTML();
            if ( $mail->send() ) {
               $retour['success'] = true;
               $retour['success_text'] = 'send newsletter to '.$to;
            }
            unset($mail);
            unset($body);
            unset($subject);
            unset($default_sender_address);
            unset($from);
            unset($to);
            unset($user_list);
            unset($ref_user);
            unset($translator);
            unset($room_manager);
            unset($list);
            unset($list2);
            unset($portal);
            unset($user_manager);
            unset($file);
            unset($curl_text);
            unset($mail_array);
            unset($rubric_manager);
            unset($noticed_manager);
            unset($rubric_list);
            unset($rubric_item);
         } else {
            $retour['success'] = true;
            $retour['success_text'] = 'no user in room want the newsletter';
         }
      }
      return $retour;
   }


   ###################################################
   # time methods
   ###################################################

   function _getShowTime () {
      $retour = '';
      if ($this->_issetExtra('TIME_SHOW')) {
         $retour = $this->_getExtra('TIME_SHOW');
      }
      return $retour;
   }

   function showTime () {
      $retour = true;
      $value = $this->_getShowTime();
      if ($value == -1) {
        $retour = false;
      }
      return $retour;
   }

   function setShowTime () {
      $this->_addExtra('TIME_SHOW',1);
   }

   function setNotShowTime () {
      $this->_addExtra('TIME_SHOW',-1);
   }

   function getUsageInfoTextForRubric($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
    $link = ahref_curl($this->_environment->getCurrentContextID(),
                       'help',
                       'context',
                       array('module'  =>$this->_environment->getCurrentModule(),
                             'function'=>$this->_environment->getCurrentFunction(),
                             'context' =>'HELP_COMMON_FORMAT'),
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $temp = strtoupper($rubric).'_'.strtoupper($funct);
         $tempMessage = "";
         switch( $temp )
         {
            case 'HOME_INDEX':             // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_HOME_INDEX',$link);
                break;
            case 'HOME_USAGEINFO':         // siehe "Übersicht der Einstellungsoptionen" / "Nutzungshinweise bearbeiten"
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_HOME_USAGEINFO',$link);
                break;
            case 'MYROOM_INDEX':           // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MYROOM_INDEX',$link);
                break;
            case 'MATERIAL_INDEX':         // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MATERIAL_INDEX',$link);
                break;
            case 'DATE_INDEX':             // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_DATE_INDEX',$link);
                break;
            case 'TOPIC_INDEX':            // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_TOPIC_INDEX',$link);
                break;
            case 'DATE_CLIPBOARD_INDEX':   // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_DATE_CLIPBOARD_INDEX',$link);
                break;
            case 'MATERIAL_CLIPBOARD_INDEX':   // getestet
                $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MATERIAL_CLIPBOARD_INDEX',$link);
                break;
            default:                       // getestet
                $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR' . " cs_privateroom_item (616)");
                break;
         }
         $retour = $tempMessage;
         // if ($retour =='USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.strtoupper($rubric).'_'.strtoupper($funct) or $retour =='tbd'){
         if ($retour =='USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.$temp or $retour =='tbd'){
            $retour = getMessage('USAGE_INFO_COMING_SOON');
         }
      }
      return $retour;
   }

   function getUsageInfoTextForRubricInForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $link = ahref_curl($this->_environment->getCurrentContextID(),
                            'help',
                            'context',
                            array('module'  =>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context' =>'HELP_COMMON_FORMAT'),
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
        $temp = strtoupper($rubric);
         $tempMessage = "";
         switch( $temp )
         {
            case 'DATE':                  // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_DATE_INDEX',$link);
               break;
            case 'HOME':                  // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_HOME_INDEX',$link);
               break;
            case 'MATERIAL':              // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MATERIAL_INDEX',$link);
               break;
            case 'MYROOM':                // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MYROOM_INDEX',$link);
               break;
            case 'TOPIC':                 // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_TOPIC_INDEX',$link);
               break;
            case 'USER':                  // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_USER_INDEX',$link);
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'." cs_privateroom_item _INDEX");
               break;
         }
         $retour = $tempMessage;
         // if ($retour =='USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.strtoupper($rubric).'_INDEX' or $retour =='tbd'){
         if ($retour =='USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.$temp.'_INDEX' or $retour == 'tbd'){
            $retour = getMessage('USAGE_INFO_COMING_SOON');
         }
      }
      return $retour;
   }

   function setUsageInfoTextForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_TEXT',$value_array);
   }

   function setUsageInfoTextForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
   }


   function getUsageInfoTextForRubricForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $link = ahref_curl($this->_environment->getCurrentContextID(),
                            'help',
                            'context',
                            array('module'=>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context'=>'HELP_COMMON_FORMAT'),
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $temp = strtoupper($rubric).'_'.strtoupper($funct);
         $tempMessage = "";
         switch( $temp )
         {
            case 'ANNOTATION_EDIT':       // getestet: pers. Raum/Meine Räume/<1 Raum>/Neue Anmerkung erstellen
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_ANNOTATION_EDIT_FORM',$link);
               break;
            case 'BUZZWORDS_EDIT':        // getestet: pers. Raum, "Materialien" / Materialarten bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_BUZZWORDS_EDIT_FORM',$link);
               break;
            case 'CONFIGURATION_COLOR':   // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_COLOR_FORM',$link);
               break;
            case 'CONFIGURATION_DATES':   // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_DATES_FORM',$link);
               break;
            case 'CONFIGURATION_HOME':    // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_HOME_FORM',$link);
               break;
            case 'CONFIGURATION_PREFERENCES': // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_PREFERENCES_FORM',$link);
               break;
            case 'CONFIGURATION_PRIVATEROOM_NEWSLETTER': // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_PRIVATEROOM_NEWSLETTER_FORM',$link);
               break;
            case 'CONFIGURATION_RUBRIC':  // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_RUBRIC_FORM',$link);
               break;
            case 'CONFIGURATION_PATH':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_PATH_FORM',$link);
               break;
             case 'CONFIGURATION_TAGS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_TAGS_FORM',$link);
               break;
             case 'CONFIGURATION_LISTVIEWS':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PROJECTROOM_FOR_CONFIGURATION_LISTVIEWS_FORM',$link);
               break;
            case 'CONFIGURATION_SERVICE': // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_SERVICE_FORM',$link);
               break;
            case 'CONFIGURATION_USAGEINFO': // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_USAGEINFO_FORM',$link);
               break;
            case 'CONFIGURATION_INFORMATIONBOX':    // getestet
               $tempMessage      = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_INFORMATIOBOX_FORM',$link);
               break;
            case 'DATE_EDIT':             // getestet: pers. Raum, "Termine" / Bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'DATE_IMPORT':           // getestet: pers. Raum, "Termine" / Termine importieren
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_DATE_IMPORT_FORM',$link);
               break;
            case 'LABELS_EDIT':           // getestet: pers. Raum, "Materialien" / Materialarten bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_LABELS_EDIT_FORM',$link);
               break;
            case 'MATERIAL_EDIT':         // getestet: pers. Raum, "Materialien" / Neuen Eintrag erstellen
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'SECTION_EDIT':            // getestet: pers. Raum, "Themen" / Bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_SECTION_EDIT_FORM',$link);
               break;
            case 'TOPIC_EDIT':            // getestet: pers. Raum, "Themen" / Bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER_EDIT':             // getestet: pers. Raum, "Mein Profil" / Bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_USER_EDIT_FORM',$link);
               break;
            case 'USER_PREFERENCES':      // getestet: pers. Raum, "Mein Profil" / Einstellungen
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_USER_PREFERENCES_FORM',$link);
               break;
            case 'CONFIGURATION_HTMLTEXTAREA':      // getestet: pers. Raum, "Mein Profil" / Einstellungen
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_CONFIGURATION_HTMLTEXTAREA_FORM',$link);
               break;
            case 'TAG_EDIT':            // getestet: pers. Raum, "Themen" / Bearbeiten
               $tempMessage = getMessage('USAGE_INFO_TEXT_ROOM_TAG_EDIT_FORM',$link);
               break;
            case 'LANGUAGE_UNUSED':      //
               $tempMessage      = getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM');
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR' . " cs_privateroom_item(825) ");
               break;
         }
         $retour = $tempMessage;
         if ( $retour == 'USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.$temp.'_FORM'
              or $retour == 'USAGE_INFO_TEXT_PROJECTROOM_FOR_'.$temp.'_FORM'
              or $retour =='tbd'
            ){
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
      }
      return $retour;
   }

   function getUsageInfoTextForRubricFormInForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $link = ahref_curl($this->_environment->getCurrentContextID(),
                            'help',
                            'context',
                            array('module'=>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context'=>'HELP_COMMON_FORMAT'),
                            getMessage('COMMON_HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"','','').LF;
         $temp = strtoupper($rubric);
         $tempMessage = "";
         switch( $temp )
         {
            case 'DATE':                  // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATE_FOR_DATE_EDIT_FORM',$link);
               break;
            case 'MATERIAL':              // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MATERIAL_EDIT_FORM',$link);
               break;
            case 'MYROOM':                // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_MYROOM_EDIT_FORM',$link);
               break;
            case 'TOPIC':                 // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATE_FOR_TOPIC_EDIT_FORM',$link);
               break;
            case 'USER':                  // getestet
               $tempMessage = getMessage('USAGE_INFO_TEXT_PRIVATEROOM_FOR_USER_EDIT_FORM',$link);
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_privateroom_item _EDIT_FORM');
               break;
         }
         $retour = $tempMessage;
         // if ($retour =='USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.strtoupper($rubric).'_EDIT_FORM' or $retour == 'tbd'){
         if ($retour =='USAGE_INFO_TEXT_PRIVATEROOM_FOR_'.$temp.'_EDIT_FORM' or $retour == 'tbd'){
            $retour = getMessage('USAGE_INFO_FORM_COMMING_SOON');
         }
      }
      return $retour;
   }

   function getOwnerUserItem () {
      if ( !isset($this->_user_item) ) {
         $moderator_list = $this->getModeratorList();
         if ( $moderator_list->getCount() == 1 ) {
            $this->_user_item = $moderator_list->getFirst();
         }
      }
      return $this->_user_item;
   }


   function _cronWeekly () {
      // you can link weekly cron jobs here like this
      // $cron_array[] = $this->_sendEmailNewsLetter();
      $cron_array = array();

      ################ BEGIN ###################
      # email newsletter
      ##########################################

      if ( $this->isPrivateRoomNewsletterActive() and $this->isOpen() ) {
         $period = $this->getPrivateRoomNewsletterActivity();
         if ($period=='weekly'){
            $cron_array[] = $this->_sendPrivateRoomNewsletter();
         }
         unset($period);
      }

      ##########################################
      # email newsletter
      ################# END ####################

      return $cron_array;
   }


   function _cronDaily () {
      // you can link daily cron jobs here like this
      // $cron_array[] = $this->_sendEmailNewsLetter();
      $cron_array = array();

      $father_cron_array = parent::_cronDaily();
      $cron_array = array_merge($father_cron_array,$cron_array);

      ################ BEGIN ###################
      # email newsletter
      ##########################################

      if ( $this->isPrivateRoomNewsletterActive() and $this->isOpen() ) {
         $period = $this->getPrivateRoomNewsletterActivity();
         if ($period=='daily'){
            $cron_array[] = $this->_sendPrivateRoomNewsletter();
         }
         unset($period);
      }

      ##########################################
      # email newsletter
      ################# END ####################

      return $cron_array;
   }

   /** get shown option
    *
    * @return boolean if room is shown on home
    */
   function isShownInPrivateRoomHome ($user_id) {
      return false;
   }
}
?>