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

$this->includeClass(RUBRIC_FORM);

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_preferences_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_languages = NULL;

   var $_with_logo = NULL;

   var $_with_picture = NULL;

   var $_mod_array = array();

   var $_iid = NULL;

   var $_type = NULL;

   var $_context = NULL;

   var $_community_array = array();

   var $_usage_info_array = array();

   var $_time_array2 = array();

   var $_with_time_array2 = false;

   var $_template_array = array();

   var $_with_template_form_element = false;

   var $_with_template_form_element2 = false;

   var $_with_template_form_element3 = false;

   var $_community_template_array = array();

   var $_community_room_array = array();

   var $_shown_community_room_array = array();

   var $_session_community_room_array = array();

   var $_description_text ='';

   var $_javascript_array = array();
   /**
   * array - containing the 2 choices of the public field
   */
   var $_public_array = array();

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function setSessionCommunityRoomArray ($value) {
      $this->_session_community_room_array = (array)$value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         if ( isset($creator_ite) ) {
            $fullname = $creator_item->getFullname();
         } else {
            $current_user = $this->_environment->getCurrentUser();
            $fullname = $current_user->getFullname();
            unset($current_user);
         }
      } elseif ($this->_environment->inCommunityRoom() and !empty($this->_form_post['iid']) and $this->_form_post['iid'] != 'NEW') {
         $manager = $this->_environment->getManager(CS_PROJECT_TYPE);
         $item = $manager->getItem($this->_form_post['iid']);
         $creator_item = $item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif ( !$this->_environment->inCommunityRoom() and !empty($this->_form_post['room_id'])) {
         $manager = $this->_environment->getManager(CS_PROJECT_TYPE);
         $item = $manager->getItem($this->_form_post['room_id']);
         $creator_item = $item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $fullname = $current_user->getFullname();
      }

      $public_array = array();
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;

      if ( isset($this->_item) ) {
         $this->_iid = $this->_item->getItemID();
      } elseif (isset($this->_form_post['iid'])) {
         $this->_iid = $this->_form_post['iid'];
      } else {
         $this->_iid = 'NEW';
      }

      if ( !empty($this->_iid) and $this->_iid != 'NEW' ) {
         $this->_headline = $this->_translator->getMessage('INTERNAL_META_TITLE');
      } else {
         if ($this->_environment->getCurrentModule() == CS_PROJECT_TYPE) {
            $this->_headline = $this->_translator->getMessage('COMMON_ENTER_NEW_PROJECT');
         } elseif ($this->_environment->inServer()) {
         $this->_headline = $this->_translator->getMessage('PORTAL_ENTER_NEW');
      } else {
         $this->_headline = $this->_translator->getMessage('PORTAL_ENTER_ROOM');
      }

      if (!$this->_environment->inPortal()) {
            $new='';
            $context_item = $this->_environment->getCurrentContextItem();
            $rubric_array = $context_item->_getRubricArray(CS_PROJECT_TYPE);
            if (isset($rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS']) ){
               $genus = $rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS'];
            } else {
               $genus = $rubric_array['EN']['GENUS'];
            }
            if ($genus =='M'){
               $new = $this->_translator->getMessage('COMMON_NEW_M_BIG').' ';
            }
            elseif ($genus =='F'){
               $new =  $this->_translator->getMessage('COMMON_NEW_F_BIG').' ';
            }
            else {
               $new = $this->_translator->getMessage('COMMON_NEW_N_BIG').' ';
            }

            $this->_headline = $new.$this->_headline;
        }
      }
      $this->setHeadline($this->_headline);

      $this->_languages = $this->_environment->getAvailableLanguageArray();

      if ( isset($this->_item) ) {
         $this->_iid = $this->_item->getItemID();
      } elseif (isset($this->_form_post['iid'])) {
         $this->_iid = $this->_form_post['iid'];
      }

          if ( isset($this->_item) ) {
             $this->_type = $this->_item->getItemType();
          } elseif (isset($this->_form_post['type'])) {
             $this->_type = $this->_form_post['type'];
          }

          if (isset($this->_form_post['description_text'])) {
             $this->_description_text = $this->_form_post['description_text'];
          } elseif ( isset($this->_item) ) {
             $this->_description_text = $this->_item->getLanguage();
        if ( $this->_description_text == 'user' ) {
           $this->_description_text = 'de';
        }
          } else {
        $current_portal = $this->_environment->getCurrentPortalItem();
             if ( isset($current_portal) ) {
           $language = $current_portal->getLanguage();
           if ( $language == 'user' ) {
              $language = 'de';
           }
        } else {
           $language = 'de';
        }
        $this->_description_text = $language;
     }
      $community_room_array = array();
      // links to community room
      if ( ( $this->_type == CS_PROJECT_TYPE and $this->_environment->inProjectRoom() )
           or $this->_environment->getCurrentModule() == 'project'
         ) {
         $current_portal = $this->_environment->getCurrentPortalItem();
         $current_user = $this->_environment->getCurrentUserItem();
         $community_list = $current_portal->getCommunityList();
         $community_room_array = array();
         $temp_array['text'] = '*'.$this->_translator->getMessage('PREFERENCES_NO_COMMUNITY_ROOM');
         $temp_array['value'] = '-1';
         $community_room_array[] = $temp_array;
         $temp_array['text'] = '--------------------';
         $temp_array['value'] = 'disabled';
         $community_room_array[] = $temp_array;
              unset($temp_array);
              if ($community_list->isNotEmpty()) {
                 $community_item = $community_list->getFirst();
                 while ($community_item) {
                    $temp_array = array();
                    if ($community_item->isAssignmentOnlyOpenForRoomMembers() ){
                       if ( !$community_item->isUser($current_user)) {
                          $temp_array['text'] = $community_item->getTitle();
                          $temp_array['value'] = 'disabled';
                       }else{
                          $temp_array['text'] = $community_item->getTitle();
                          $temp_array['value'] = $community_item->getItemID();
                       }
                    }else{
                       $temp_array['text'] = $community_item->getTitle();
                       $temp_array['value'] = $community_item->getItemID();
                    }
                    $community_room_array[] = $temp_array;
                    unset($temp_array);
                    $community_item = $community_list->getNext();
                 }
              }
      }
      $this->_community_room_array = $community_room_array;
      $community_room_array = array();
      
      if (!empty($this->_session_community_room_array)) {
         foreach ( $this->_session_community_room_array as $community_room ) {
            $temp_array['text'] = $community_room['name'];
            $temp_array['value'] = $community_room['id'];
            $community_room_array[] = $temp_array;
         }
      } elseif ( isset($this->_item)
                 and $this->_item->isProjectRoom()
               ) {
      	
         $community_room_list = $this->_item->getCommunityList();
         if ($community_room_list->getCount() > 0) {
            $community_room_item = $community_room_list->getFirst();
            while ($community_room_item) {
               $temp_array['text'] = $community_room_item->getTitle();
               $temp_array['value'] = $community_room_item->getItemID();
               $community_room_array[] = $temp_array;
               $community_room_item = $community_room_list->getNext();
            }
         }
      }
      $this->_shown_community_room_array = $community_room_array;

          if ( isset($this->_item) ) {
             $this->_context = $this->_item->getRoomContext();
          } elseif (isset($this->_form_post['context'])) {
             $this->_context = $this->_form_post['context'];
          }

          if ( isset($this->_item) ) {
             $this->_with_logo = $this->_item->getLogoFilename();
          } elseif (isset($this->_form_post['with_logo'])) {
             $this->_with_logo = $this->_form_post['with_logo'];
          }
          if ( isset($this->_item) and $this->_item->isPortal()) {
             $this->_with_picture = $this->_item->getPictureFilename();
          } elseif (isset($this->_form_post['with_picture'])) {
             $this->_with_picture = $this->_form_post['with_picture'];
          }

          if (isset($this->_item)) {
             $mod_list = $this->_item->getModeratorList();
          } elseif (!empty($this->_form_post['iid']) and $this->_form_post['iid'] != 'NEW') {
             if ( isset($this->_type) and $this->_type == CS_PORTAL_TYPE ) {
                $manager = $this->_environment->getPortalManager();
             } elseif ( isset($this->_type) and $this->_type == CS_SERVER_TYPE ) {
                $manager = $this->_environment->getServerManager();
             } else {
                $manager = $this->_environment->getRoomManager();
             }
             $room = $manager->getItem($this->_form_post['iid']);
             if ( isset($room) ) {
                $mod_list = $room->getModeratorList();
             }
          }
      if ( isset($mod_list)
           and !$mod_list->isEmpty()
         ) {
         $mod_item = $mod_list->getFirst();
         while ($mod_item) {
            $temp_array = array();
            $temp_array['value'] = $mod_item->getItemID();
            $temp_array['text'] = $mod_item->getFullname();
            $this->_mod_array[] = $temp_array;
            $mod_item = $mod_list->getNext();
         }
      }

      // time pulses
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal  = $this->_environment->getCurrentPortalItem();
      if (
            ( $this->_type == CS_PROJECT_TYPE and $this->_environment->inProjectRoom() )
            or ( $this->_type == CS_PROJECT_TYPE
                 and $this->_environment->inCommunityRoom()
                 and $current_context->showTime()
               )
            or ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                 and ( $this->_environment->inCommunityRoom() or $this->_environment->inPortal() )
                 and $current_context->showTime()
               )
            or ( $this->_environment->inGroupRoom()
                 and $current_portal->showTime()
               )
         ) {
         if ( $this->_environment->inPortal() ) {
            $portal_item = $current_context;
         } else {
            $portal_item = $current_context->getContextItem();
         }
         if ($portal_item->showTime()) {
                     $current_time_title = $portal_item->getTitleOfCurrentTime();
                     if (isset($this->_item)) {
                            $time_list = $this->_item->getTimeList();
                            if ($time_list->isNotEmpty()) {
                               $time_item = $time_list->getFirst();
                               $linked_time_title = $time_item->getTitle();
                            }
                     }
                     if ( !empty($linked_time_title)
                          and $linked_time_title < $current_time_title
                            ) {
                             $start_time_title = $linked_time_title;
                     } else {
                             $start_time_title = $current_time_title;
                     }
                     $time_list = $portal_item->getTimeList();
                     if ($time_list->isNotEmpty()) {
                             $time_item = $time_list->getFirst();
                             while ($time_item) {
                                     if ($time_item->getTitle() >= $start_time_title) {
                                             $temp_array = array();
                                             $temp_array['text'] = $this->_translator->getTimeMessage($time_item->getTitle());
                                             $temp_array['value'] = $time_item->getItemID();
                                             $this->_time_array2[] = $temp_array;
                                     }
                                     $time_item = $time_list->getNext();
                             }
                     }

                         // continuous
                     $temp_array = array();
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_CONTINUOUS');
                     $temp_array['value'] = 'cont';
                     $this->_time_array2[] = $temp_array;

                     $this->_with_time_array2 = true;
                  }
          }


          // room templates 2 - select
          $current_portal = $this->_environment->getCurrentPortalItem();
          if ( ( empty($this->_type) or empty($_GET['iid']) )
               and isset($current_portal)
               and ( ( $this->_environment->inCommunityRoom()
                        and $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                     )
                     or ( $this->_environment->inPortal()
                           and $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                         )
                   )
             ) {
                 $room_manager = $this->_environment->getProjectManager();
                 $room_manager->setContextLimit($current_portal->getItemID());
                 $room_manager->setTemplateLimit();
                 if ( $this->_environment->inCommunityRoom() ) {
                    global $c_cache_cr_pr;
                    if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
                       $room_manager->setCommunityRoomLimit($this->_environment->getCurrentContextID());
                    } else {
                       /**
                        * use redundant infos in community room
                        */
                       $current_community_item = $this->_environment->getCurrentContextItem();
                       $room_manager->setIDArrayLimit($current_community_item->getInternalProjectIDArray());
                       unset($current_community_item);
                    }
                 }
                 $room_manager->select();
                 $room_list = $room_manager->get();
         $default_id = $this->_environment->getCurrentPortalItem()->getDefaultProjectTemplateID();
         if ($room_list->isNotEmpty() or $default_id != '-1' ) {
            $temp_array = array();
            $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
            $temp_array['value'] = -1;
            $this->_template_array[] = $temp_array;
            $temp_array = array();
            $temp_array['text'] = '------------------------';
            $temp_array['value'] = 'disabled';
            $this->_template_array[] = $temp_array;
            $current_user = $this->_environment->getCurrentUser();
            if ( $default_id != '-1' ) {
               $default_item = $room_manager->getItem($default_id);
               if ( isset($default_item) ) {
                  $template_availability = $default_item->getTemplateAvailability();
                  if ( $template_availability == '0' ) {
                     $temp_array['text'] = '*'.$default_item->getTitle();
                     $temp_array['value'] = $default_item->getItemID();
                     $this->_template_array[] = $temp_array;
                     $temp_array = array();
                     $temp_array['text'] = '------------------------';
                     $temp_array['value'] = 'disabled';
                     $this->_with_template_form_element2 = true;
                     $this->_template_array[] = $temp_array;
                     #$this->_javascript_array[$default_item->getItemID()] = $this->_environment->getTextConverter()->text_as_html_long($this->_environment->getTextConverter()->cleanDataFromTextArea($default_item->getTemplateDescription()));
                     $this->_javascript_array[$default_item->getItemID()] = $this->_environment->getTextConverter()->textFullHTMLFormatting($this->_environment->getTextConverter()->cleanDataFromTextArea($default_item->getTemplateDescription()));
                  }
               }
            }
            $item = $room_list->getFirst();
            while ($item) {
               $temp_array = array();
               $template_availability = $item->getTemplateAvailability();

               $community_room_member = false;
               $community_list = $item->getCommunityList();
               $user_community_list = $current_user->getRelatedCommunityList();
               if ( $community_list->isNotEmpty() and $user_community_list->isNotEmpty()) {
                  $community_item = $community_list->getFirst();
                  while ($community_item) {
                     $user_community_item = $user_community_list->getFirst();
                     while ($user_community_item) {
                         if ( $user_community_item->getItemID() == $community_item->getItemID() ){
                            $community_room_member = true;
                         }
                         $user_community_item = $user_community_list->getNext();
                     }
                     $community_item = $community_list->getNext();
                  }
               }


               if( ($template_availability == '0') OR
                   ($this->_environment->inCommunityRoom() and $template_availability == '3') OR
                   ($this->_environment->inPortal() and $template_availability == '3' and $community_room_member) OR
                   ($template_availability == '1' and $item->mayEnter($current_user)) OR
                   ($template_availability == '2' and $item->mayEnter($current_user) and ($item->isModeratorByUserID($current_user->getUserID(),$current_user->getAuthSource())))
                  ){
                  if ($item->getItemID() != $default_id or $item->getTemplateAvailability() != '0'){
                     $this->_with_template_form_element2 = true;
                     $temp_array['text'] = $item->getTitle();
                     $temp_array['value'] = $item->getItemID();
                     $this->_template_array[] = $temp_array;

                     /*
                      * Fix:   text functions causes problems with javascript and <br /> tag
                      *        anyway, they are not need for displaying the content, because
                      *        mysql holds these information already escaped
                      */
                     //$this->_javascript_array[$item->getItemID()] = $this->_environment->getTextConverter()->text_as_html_long($this->_environment->getTextConverter()->cleanDataFromTextArea($item->getTemplateDescription()));
                     $this->_javascript_array[$item->getItemID()] = nl2br($item->getTemplateDescription());
                  }

               }
               $item = $room_list->getNext();
            }
            unset($current_user);
         }
      }

      // private room
      elseif ( $this->_environment->inPrivateRoom()
               and !$this->_environment->getCurrentContextItem()->isTemplate()
             ) {
         $room_manager = $this->_environment->getPrivateRoomManager();
         $room_manager->setContextLimit($current_portal->getItemID());
         $room_manager->setTemplateLimit();
         $room_manager->select();
         $room_list = $room_manager->get();
         #$default_id = $this->_environment->getCurrentPortalItem()->getDefaultPrivateRoomTemplateID();
         $default_id = -1;
         if ( $room_list->isNotEmpty()
              or $default_id != '-1'
            ) {
            $temp_array = array();
            $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE_PRIVATEROOM');
            $temp_array['value'] = -1;
            $this->_template_array[] = $temp_array;
            $temp_array = array();
            $temp_array['text'] = '------------------------';
            $temp_array['value'] = 'disabled';
            $this->_template_array[] = $temp_array;
            $current_user = $this->_environment->getCurrentUser();
            if ( $default_id != '-1' ) {
               $default_item = $room_manager->getItem($default_id);
               if ( isset($default_item) ) {
                  $template_availability = $default_item->getTemplateAvailability();
                  if ( $template_availability == '0'
                       and $default_item->isClosed()
                     ) {
                     $temp_array['text'] = '*'.$default_item->getTitle();
                     $temp_array['value'] = $default_item->getItemID();
                     $this->_template_array[] = $temp_array;
                     $temp_array = array();
                     $temp_array['text'] = '------------------------';
                     $temp_array['value'] = 'disabled';
                     $this->_with_template_form_element = true;
                     $this->_template_array[] = $temp_array;
                     $this->_javascript_array[$default_item->getItemID()] = $default_item->getTemplateDescription();
                  }
               }
            }
            $item = $room_list->getFirst();
            while ($item) {
               $temp_array = array();
               $template_availability = $item->getTemplateAvailability();

               if ( $template_availability == '0' ) {
                  if ( $item->getItemID() != $default_id ) {
                     $temp_array['text'] = $item->getTitle();
                     $temp_array['value'] = $item->getItemID();
                     $this->_with_template_form_element = true;
                     $this->_template_array[] = $temp_array;
                     $this->_javascript_array[$item->getItemID()] = $item->getTemplateDescription();
                  }
               }
               $item = $room_list->getNext();
            }
         }
         unset($room_manager);
      }

      // room templates 3 - select
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( ( empty($this->_type) or empty($_GET['iid']) )
           and isset($current_portal)
           and $this->_environment->inPortal()
           and $this->_environment->getCurrentModule() == CS_COMMUNITY_TYPE
         ) {
         $room_manager = $this->_environment->getCommunityManager();
         $room_manager->setContextLimit($current_portal->getItemID());
         $room_manager->setTemplateLimit();
         $room_manager->select();
         $room_list = $room_manager->get();
         $default_id = $this->_environment->getCurrentPortalItem()->getDefaultProjectTemplateID();
         if ($room_list->isNotEmpty() or $default_id != '-1' ) {
            $temp_array = array();
            $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
            $temp_array['value'] = -1;
            $this->_community_template_array[] = $temp_array;
            $temp_array = array();
            $temp_array['text'] = '------------------------';
            $temp_array['value'] = 'disabled';
            $this->_community_template_array[] = $temp_array;
            $current_user = $this->_environment->getCurrentUser();
            if ( $default_id != '-1'  ){
               $default_item = $room_manager->getItem($default_id);
               if ( isset($default_item) ) {
                  $template_availability = $default_item->getCommunityTemplateAvailability();
                  if( ($template_availability == '0') and $default_item->isClosed() ){
                     $temp_array['text'] = '*'.$default_item->getTitle();
                     $temp_array['value'] = $default_item->getItemID();
                     $this->_community_template_array[] = $temp_array;
                     $temp_array = array();
                     $temp_array['text'] = '------------------------';
                     $temp_array['value'] = 'disabled';
                     $this->_with_template_form_element2 = true;
                     $this->_community_template_array[] = $temp_array;
                     $this->_javascript_array[$default_item->getItemID()] = $default_item->getTemplateDescription();
                  }
               }
            }
            $item = $room_list->getFirst();
            while ($item) {
               $temp_array = array();
               $template_availability = $item->getCommunityTemplateAvailability();
               if( ($template_availability == '0') OR
                   ($template_availability == '1' and $item->mayEnter($current_user)) OR
                   ($template_availability == '2' and $item->mayEnter($current_user) and ($item->isModeratorByUserID($current_user->getUserID(),$current_user->getAuthSource())))
                  ){
                  if ($item->getItemID() != $default_id or $item->getCommunityTemplateAvailability() != '0' ){
                     $this->_with_template_form_element3 = true;
                     $temp_array['text'] = $item->getTitle();
                     $temp_array['value'] = $item->getItemID();
                     $this->_community_template_array[] = $temp_array;
                     $this->_javascript_array[$item->getItemID()] = $item->getTemplateDescription();
                  }

               }
               $item = $room_list->getNext();
            }
            unset($current_user);
         }
      }

      if ( $this->_environment->inServer() ) {
         $this->_portal_option_array = array();
         $temp = array();
         $temp['value'] = 'overview';
         $temp['text']  = '*'.$this->_translator->getMessage('PREFERENCES_PORTAL_DEFAULT_OVERVIEW');
         $this->_portal_option_array[] = $temp;
         $server_item = $this->_environment->getCurrentContextItem();
         if ( isset($server_item) ) {
            $portal_list = $server_item->getPortalList();
            if ( isset($portal_list)
                 and $portal_list->isNotEmpty()
               ) {
               $temp = array();
               $temp['value'] = '-1';
               $temp['text']  = '-------------';
               $this->_portal_option_array[] = $temp;
               $portal_item = $portal_list->getFirst();
               while ($portal_item) {
                  $temp = array();
                  $temp['value'] = $portal_item->getItemID();
                  $temp['text']  = $portal_item->getTitle();
                  $this->_portal_option_array[] = $temp;
                  $portal_item = $portal_list->getNext();
               }
            }
         }
      }

      // check membership
      $this->_disable_code = true;
      if ( isset($this->_item) ) {
         if ($this->_item->checkNewMembersWithCode()) {
            $this->_disable_code = false;
         }
      } elseif ( !empty($this->_form_post['member_check'])
                 and $this->_form_post['member_check'] == 'withcode'
               ) {
         $this->_disable_code = false;
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $current_module = $this->_environment->getCurrentModule();
      // form fields
      $this->_form->addHidden('iid',$this->_iid);
      if ( !$this->_environment->inPrivateRoom()
           or $current_module =='myroom'
         ) {
         $this->_form->addTitleField('title','',$this->_translator->getMessage('COMMON_TITLE'),'',50,46,true);
         if ( empty($this->_iid)
              or $this->_iid == 'NEW'
              or ($this->_type == CS_PROJECT_TYPE and $this->_environment->inCommunityRoom())
            ) {
            $this->_form->addHidden('show_title','');
         } else {

            //PREFERENCES_SHOW_TITLE_OPTION
            $this->_form->addCheckbox('show_title',
                                      'yes',
                                      '',
                                      $this->_translator->getMessage('COMMON_TITLE'),
                                      $this->_translator->getMessage('PREFERENCES_SHOW_TITLE_OPTION'),
                                      ''
                                     );
         }

         if ( empty($this->_iid) or $this->_iid == 'NEW'
              or ($this->_type == CS_PROJECT_TYPE and $this->_environment->inCommunityRoom())
              or ($this->_type == CS_SERVER_TYPE) // TBD: ggf. LinkeLeiste auf Serverebene einführen
            ) {
            // do nothing
         } else {
            $this->_form->addImage('logo',
                                   '',
                                   $this->_translator->getMessage('LOGO_UPLOAD'),
                                   $this->_translator->getMessage('LOGO_UPLOAD_DESC')
                                  );

            if ( !empty($this->_with_logo) ) {
               $this->_form->combine();
               $this->_form->addCheckbox('delete_logo',
                                         '',
                                         false,
                                         '',
                                         $this->_translator->getMessage('LOGO_DELETE_OPTION'),
                                         ''
                                        );
            }
            $this->_form->addHidden('logo_hidden','');
            $this->_form->addHidden('with_logo',$this->_with_logo);
         }

         // type
         if ( ( !empty($this->_iid) and $this->_iid != 'NEW')
              or ($this->_environment->getCurrentModule() == CS_PROJECT_TYPE)
              or ($this->_environment->getCurrentModule() == CS_COMMUNITY_TYPE)
              or ($this->_environment->inServer() and $this->_iid == 'NEW')
            ) {
            $this->_form->addHidden('type',$this->_type);
         } else { // enter new room
            $radio_values = array();
            $with_project = true;
            $with_community = true;
            if ( $this->_environment->inPrivateRoom() ) {
               $current_portal_item = $this->_environment->getCurrentPortalItem();
               if ( $current_portal_item->openProjectRoomOnlyInCommunityRoom() ) {
                  $with_project = false;
               }
            }
            if ( $this->_environment->inPrivateRoom() ) {
               $current_portal_item = $this->_environment->getCurrentPortalItem();
               $current_user = $this->_environment->getCurrentUserItem();
               $portal_user = $current_user->getRelatedCommSyUserItem();
               if ( $current_portal_item->openCommunityRoomOnlyByModeration()
                    and isset($portal_user)
                    and !$portal_user->isModerator()
                  ) {
                  $with_community = false;
               }
            }
            if ( $with_project and $with_community ) {
               $radio_values[0]['text'] = $this->_translator->getMessage('ROOM_TYPE_PROJECT');
               $radio_values[0]['value'] = CS_PROJECT_TYPE;
               $radio_values[0]['extention'] = 'onclick="enable()"';
               $radio_values[1]['text'] = $this->_translator->getMessage('ROOM_TYPE_COMMUNITY');
               $radio_values[1]['value'] = CS_COMMUNITY_TYPE;
               $radio_values[1]['extention'] = 'onclick="disable()"';
               $this->_form->addRadioGroup('type',
                                           $this->_translator->getMessage('ROOM_TYPE'),
                                           $this->_translator->getMessage('ROOM_TYPE_DESC'),
                                           $radio_values,
                                           '',
                                           true,
                                           false
                                          );
               unset($radio_values);
            } elseif ( !$with_project ) {
               $this->_form->addText('type_text',$this->_translator->getMessage('ROOM_TYPE'),$this->_translator->getMessage('ROOM_TYPE_COMMUNITY'));
               $this->_form->addHidden('type',CS_COMMUNITY_TYPE);
            } elseif ( !$with_community ) {
               $this->_form->addText('type_text',$this->_translator->getMessage('ROOM_TYPE'),$this->_translator->getMessage('ROOM_TYPE_PROJECT'));
               $this->_form->addHidden('type',CS_PROJECT_TYPE);
            }
         }


         if ( $this->_environment->inPortal() and $this->_type == CS_PORTAL_TYPE ) {
            $this->_form->addImage('picture',
                                   '',
                                   $this->_translator->getMessage('PICTURE_UPLOAD'),
                                   ''
                                  );
            if ( !empty($this->_with_picture) ) {
               $this->_form->combine();
               $this->_form->addCheckbox('delete_picture',
                                         '',
                                         false,
                                         '',
                                         $this->_translator->getMessage('CONFIGURATION_PICTURE_LOGO_DELETE_OPTION'),
                                         ''
                                        );
            }
            $this->_form->addHidden('picture_hidden','');
            $this->_form->addHidden('with_picture',$this->_with_picture);
         }

         // template functions
         if ( $this->_with_template_form_element2 ) {
             $this->_form->addSelect('template_select',
                                     $this->_template_array,
                                     '',
                                     $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'),
                                     $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),
                                     0,
                                     false,
                                     false,
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     '27',
                                     false,
                                     false,
                                     '10',
                                     'onChange="cs_toggle_template()"'
                                    );
            $this->_form->combine('vertical');
            $this->_form->addText('template_select_text','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),'',false,'','','left','','id="template_extention"');
         }
         // template functions
         if ( $this->_with_template_form_element3 ) {
             $this->_form->addSelect('template_select',
                                     $this->_community_template_array,
                                     '',
                                     $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'),
                                     $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),
                                     0,
                                     false,
                                     false,
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     '27'
                                    );
            $this->_form->combine('vertical');
            $this->_form->addText('template_select_text','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),'',false,'','','left','','id="template_extention"');
         }
      }

      // template
      if ( $this->_environment->inPrivateRoom()
           and $this->_with_template_form_element
           and $this->_environment->getCurrentModule() != type2Module(CS_MYROOM_TYPE)
         ) {
         $this->_form->addSelect('template_select',
                                 $this->_template_array,
                                 '',
                                 $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'),
                                 $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),
                                 0,
                                 false,
                                 false,
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 '27',
                                 false,
                                 false,
                                 '10',
                                 'onChange="cs_toggle_template()"'
                                );
         $this->_form->combine('vertical');
         $this->_form->addText('template_select_text','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),'',false,'','','left','','id="template_extention"');
      }

      // language
      if ( ($this->_type == CS_PROJECT_TYPE
            and $this->_environment->inCommunityRoom()
            and $this->_iid != 'NEW')
           or $this->_type == CS_PORTAL_TYPE
           or $this->_type == CS_SERVER_TYPE
           or $this->_environment->inServer()
         ) {
         // do nothing
      } else {
         $languageArray = array();
         $zaehler = 0;
         $languageArray[$zaehler]['text']  = $this->_translator->getMessage('CONTEXT_LANGUAGE_USER');
         $languageArray[$zaehler]['value'] = 'user';
         $zaehler++;
         $languageArray[$zaehler]['text']  = '-------';
         $languageArray[$zaehler]['value'] = 'disabled';
         $zaehler++;
         $tmpArray = $this->_environment->getAvailableLanguageArray();
         foreach ($tmpArray as $item){
            switch ( mb_strtoupper($item, 'UTF-8') ){
               case 'DE':
                  $languageArray[$zaehler]['text']= $this->_translator->getMessage('DE');
                  break;
               case 'EN':
                  $languageArray[$zaehler]['text']= $this->_translator->getMessage('EN');
                  break;
               default:
                  // $languageArray[$zaehler]['text']= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_preferenes_form(533) ');
                  break;
            }
            $languageArray[$zaehler]['value']= $item;
            $zaehler++;
         }
         $message = $this->_translator->getMessage('CONTEXT_LANGUAGE_DESC2');
         #if ($this->_environment->getCurrentModule() == CS_PROJECT_TYPE) {
         #   $message = $this->_translator->getMessage('CONTEXT_LANGUAGE_DESC');
         #}
         $this->_form->addSelect('language',
                                 $languageArray,
                                 '',
                                 $this->_translator->getMessage('CONTEXT_LANGUAGE'),
                                 $message,
                                 0,
                                 false,
                                 true,
                                 false,
                                 '',
                                 '',
                                 '',
                                 '',
                                 '13',
                                 true
                                );
      }

      if ( !$this->_environment->inPrivateRoom() or $current_module == 'myroom' ) {

        // radio group for displaying member-check option
        if ( empty($this->_iid)
             or ($this->_iid == 'NEW' and !$this->_environment->inServer())
             or ($this->_type == CS_PROJECT_TYPE and $this->_environment->inCommunityRoom())
             or ($this->_type == CS_SERVER_TYPE)
             or ($this->_environment->inPortal())
             or ($this->_environment->inServer())
           ) {
            $this->_form->addHidden('member_check','');
        } else {

            $use_javascript = false;
            $session_item = $this->_environment->getSessionItem();
            if($session_item->issetValue('javascript')){
                if($session_item->getValue('javascript') == "1"){
                    $use_javascript = true;
                }
            }

            $radio_values = array();
            $radio_values[0]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_NEVER');
            $radio_values[0]['value'] = 'never';
            if($use_javascript){
               $radio_values[0]['extention'] = 'onclick="disable_code()"';
            }
            #if ($this->_type != CS_PORTAL_TYPE) {
            #   $radio_values[1]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_SOMETIMES_ROOM');
            #} else {
            #   $radio_values[1]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_SOMETIMES_PORTAL');
            #}
            #$radio_values[1]['value'] = 'sometimes';
            #$radio_values[1]['extention'] = 'onclick="disable_code()"';
            $radio_values[2]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_ALWAYS');
            $radio_values[2]['value'] = 'always';
            if($use_javascript){
               $radio_values[2]['extention'] = 'onclick="disable_code()"';
            }
            $radio_values[3]['text'] = $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE');
            $radio_values[3]['value'] = 'withcode';
            if($use_javascript){
               $radio_values[3]['extention'] = 'onclick="enable_code()"';
            }
            $this->_form->addRadioGroup('member_check',
                                        $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS'),
                                        $this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_DESC'),
                                        $radio_values,
                                        '',
                                        true,
                                        false
                                       );
            unset($radio_values);
            $this->_form->combine();

            $code_disabled = false;
            if($use_javascript){
               $code_disabled = $this->_disable_code;
            }
            $this->_form->addTextfield('code',$this->_translator->getMessage('PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE_VALUE'),'','','',57,'','','','','','','',$code_disabled);
        }

         // open for guests option
        if ( empty($this->_iid)
             or $this->_iid == 'NEW'
             or $this->_type != CS_COMMUNITY_TYPE
           ) {
           // do nothing
        } else {
            $radio_values = array();
            $radio_values[0]['text'] = $this->_translator->getMessage('COMMON_ON');
            $radio_values[0]['value'] = 'open';
            $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_OFF');
            $radio_values[1]['value'] = 'closed';
            $this->_form->addRadioGroup('open_for_guests',
                                        $this->_translator->getMessage('PREFERENCES_OPEN_FOR_GUESTS'),
                                        $this->_translator->getMessage('PREFERENCES_OPEN_FOR_GUESTS_DESC'),
                                        $radio_values,
                                        '',
                                        true,
                                        true
                                       );
            unset($radio_values);

            $radio_values = array();
            $radio_values[0]['text'] = $this->_translator->getMessage('COMMON_ASSIGMENT_ON');
            $radio_values[0]['value'] = 'open';
            $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_ASSIGMENT_OFF');
            $radio_values[1]['value'] = 'closed';
            $this->_form->addRadioGroup('room_assignment',
                                        $this->_translator->getMessage('PREFERENCES_ROOM_ASSIGMENT'),
                                        $this->_translator->getMessage('PREFERENCES_ASSIGMENT_OPEN_FOR_GUESTS_DESC'),
                                        $radio_values,
                                        '',
                                        true,
                                        false
                                       );
            unset($radio_values);
         }

        // show time (clock pulses in community room)
        if ( $this->_type == CS_COMMUNITY_TYPE
             and !empty($this->_iid)
             and $this->_iid != 'NEW'
           ) {
           $current_context = $this->_environment->getCurrentContextItem();
           if ($current_context->isPortal()) {
              $portal_item = $current_context;
           } else {
              $portal_item = $current_context->getContextItem();
           }
           if ($portal_item->showTime()) {
              $radio_values = array();
               $radio_values[0]['text'] = $this->_translator->getMessage('COMMON_YES');
               $radio_values[0]['value'] = '1';
               $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_NO');
               $radio_values[1]['value'] = '-1';
               $this->_form->addRadioGroup('show_time',
                                           $this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_CHOICE_TITLE'),
                                           $this->_translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_CHOICE_COMMUNITY_DESC'),
                                           $radio_values,
                                           '',
                                           true,
                                           true
                                          );
               unset($radio_values);
           }
         }

        // time
        if (isset($this->_with_time_array2) and $this->_with_time_array2) {
           $this->translatorChangeToPortal();
           $form_element_title = $this->_translator->getMessage('COMMON_TIME_NAME');
           $this->_form->addCheckboxGroup('time2',
                                          $this->_time_array2,
                                          '',
                                          $form_element_title,
                                          '',
                                          '',
                                          true,
                                          2
                                         );
           $this->translatorChangeToCurrentContext();
        }

        // community room in project room
        if ( !empty($this->_community_room_array) ) {
      $portal_item = $this->_environment->getCurrentPortalItem();
      $project_room_link_status = $portal_item->getProjectRoomLinkStatus();

      if ($project_room_link_status =='optional'){
         if ( !empty ($this->_shown_community_room_array) ) {
            $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
            $this->_form->combine();
         }
         if(count($this->_community_room_array) > 2){
            $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,false,false,'','','','',13);
            $this->_form->combine('horizontal');
            $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',180);
         }
      }else{
         if ( !empty ($this->_shown_community_room_array) ) {
            $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
            $this->_form->combine();
         }
         if(count($this->_community_room_array) > 2){
            $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,true,false,'','','','',13);
            $this->_form->combine('horizontal');
            $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',180);
         }
      }
   }

        // server: default sender email address
        if ( $this->_type == CS_SERVER_TYPE and $this->_environment->inServer() ) {
           $this->_form->addTextField('server_default_sender_address',
                                      '',
                                      $this->_translator->getMessage('SERVER_DEFAULT_SENDER_ADDRESS'),
                                      $this->_translator->getMessage('SERVER_DEFAULT_SENDER_ADDRESS_DESC'),
                                      50,
                                      30,
                                      true
                                     );
           if ( !empty($this->_portal_option_array) ) {
              $this->_form->addSelect('server_portal_option',$this->_portal_option_array,'',$this->_translator->getMessage('PREFERENCES_PORTAL_DEFAULT'),'', 1, false,false,false,'','','','',13);
           }
        }

   if ( $this->_type == CS_PORTAL_TYPE ) {
   $languageArray = array();
   $tmpArray = $this->_environment->getAvailableLanguageArray();
   $zaehler = 0;
   foreach ($tmpArray as $item){
      switch ( mb_strtoupper($item, 'UTF-8') ){
         case 'DE':
            $languageArray[$zaehler]['text']= $this->_translator->getMessage('DE');
            break;
         case 'EN':
            $languageArray[$zaehler]['text']= $this->_translator->getMessage('EN');
            break;
         case 'RU':
            $languageArray[$zaehler]['text']= $this->_translator->getMessage('RU');
            break;
         default:
            break;
      }
      $languageArray[$zaehler]['value']= $item;
      $zaehler++;
      }
      $this->_form->addSelect( 'description_text',
                               $languageArray,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_CHOOSE_LANGUAGE'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_LANGUAGE_CHOOSE_BUTTON'),
                               'option','','','13',true);

      $this->_form->combine();
      foreach ($this->_languages as $language) {
         if ( $language == $this->_description_text ) {
            $this->_form->addTextField('wellcome1_'.$language,'',$this->_translator->getMessage('COMMON_DESCRIPTION'),'',255,35);
            $this->_form->combine('horizontal');
            $this->_form->addCheckbox('wellcome1_'.$language.'_reset','1','','',mb_strtolower($this->_translator->getMessage('COMMON_RESET'), 'UTF-8').'?');
            $this->_form->combine();
            $this->_form->addTextField('wellcome2_'.$language,'',$this->_translator->getMessage('COMMON_DESCRIPTION'),'',255,35);
            $this->_form->combine('horizontal');
            $this->_form->addCheckbox('wellcome2_'.$language.'_reset','1','','',mb_strtolower($this->_translator->getMessage('COMMON_RESET'), 'UTF-8').'?');
            $this->_form->combine();
         } else {
                 $this->_form->addHidden('wellcome1_'.$language,'');
                 $this->_form->addHidden('wellcome2_'.$language,'');
         }
      }
      // description (text-area)
      foreach ($this->_languages as $language) {
         if ($language == $this->_description_text){

            if (isset ($this->_item) ){
               $html_status = $this->_item->getHtmlTextAreaStatus();
            }elseif ( $this->_environment->inCommunityRoom() ){
               $context = $this->_environment->getCurrentContextItem();
               $html_status = $context->getHtmlTextAreaStatus();
            }else{
               $portal = $this->_environment->getCurrentPortalItem();
               if ( isset($portal) ) {
                  $html_status = $portal->getHtmlTextAreaStatus();
               } else {
                  $html_status = 0;
               }
            }

            if ($html_status =='1'){
               $html_status ='2';
            }

            $this->_form->addTextArea('description_'.$language,'','','','48','15','virtual',false,false,true,$html_status);

         } else {
            $this->_form->addHidden('description_'.$language,'');
         }
      }


   }else{

        if (isset ($this->_item) ){
           $html_status = $this->_item->getHtmlTextAreaStatus();
        }elseif ( $this->_environment->inCommunityRoom() ){
            $context = $this->_environment->getCurrentContextItem();
            $html_status = $context->getHtmlTextAreaStatus();
         }else{
            $portal = $this->_environment->getCurrentPortalItem();
            if ( isset($portal) ) {
               $html_status = $portal->getHtmlTextAreaStatus();
            } else {
               $html_status = 0;
            }
         }

         if ($html_status =='1'){
            $html_status ='2';
         }

         $this->_form->addTextArea('description','',$this->_translator->getMessage('CONFIGURATION_ROOM_DESCRIPTION'),'','48','15','virtual',false,false,true,$html_status);
      }

      // portal/server: URL
      if ( $this->_environment->inServer()
           or ( $this->_environment->inPortal()
                and $this->_type == CS_PORTAL_TYPE
              )
         ) {
         $this->_form->addTextField('url','',$this->_translator->getMessage('CONFIGURATION_ROOM_URL'),'',255,35,false,'','','','left','http(s)://','',false,'/commsy.php');
      }

        // rubric connections
        if ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
             or $this->_environment->getCurrentModule() == CS_MYROOM_TYPE ) {
            $this->_setFormElementsForConnectedRubrics();
            $this->_form->addHidden('public','');
        }
      }
      // buttons
      if ( !empty($this->_iid) and $this->_iid != 'NEW' and $this->_type != CS_SERVER_TYPE ) {
                 // Projektraum
         if ($this->_environment->inProjectRoom()) {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'',$this->_translator->getMessage('ROOM_DELETE_BUTTON'));
           // aus Gemeinschaftsraum
         } elseif ($this->_environment->inCommunityRoom()) {
                // Projektraum aus Gemeinschaftsraum heraus
             if ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE ) {
                $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'');
                // im Gemeinschaftsraum
             } else {
                $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'',$this->_translator->getMessage('ROOM_DELETE_BUTTON'));
             }
           //aus Privatraum
         } elseif ($this->_environment->inPrivateRoom()) {
                 if ( $this->_environment->getCurrentModule() != CS_MYROOM_TYPE ) {
               $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','');
                 } else {
               $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
                 }
         } else {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),$this->_translator->getMessage('PORTAL_DELETE_BUTTON'));
         }
      } elseif ( $this->_type == CS_SERVER_TYPE ) {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if ( isset($this->_item) ) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['context'] = $this->_item->getRoomContext();
         $this->_values['show_title'] = $this->_item->showTitle();
         $this->_values['language'] = $this->_item->getLanguage();
         $this->_values['logo'] = $this->_item->getLogoFilename();
         $this->_values['logo_hidden'] = $this->_item->getLogoFilename();
         if ($this->_item->isCommunityRoom()){
            $this->_values['template_availability'] = $this->_item->getCommunityTemplateAvailability();
         }else{
            $this->_values['template_availability'] = $this->_item->getTemplateAvailability();
         }
         if ( $this->_item->isPortal() ) {
            $this->_values['picture'] = $this->_item->getPictureFilename();
            $this->_values['picture_hidden'] = $this->_item->getPictureFilename();
            $url = $this->_item->getURL();
            if ( isset($url) ) {
               $this->_values['url'] = $url;
            }
            unset($url);
         }
         if ($this->_item->isCommunityRoom()){
            if ($this->_item->isTemplate()) {
               $this->_values['template'] = 1;
            }
         }
         if ($this->_item->isProjectRoom()){
            $this->_values['public'] = $this->_item->getPublic();

            $community_room_array = array();
            $community_room_list = $this->_item->getCommunityList();
            if ($community_room_list->getCount() > 0) {
               $community_room_item = $community_room_list->getFirst();
               while ($community_room_item) {
                  $community_room_array[] = $community_room_item->getItemID();
                  $community_room_item = $community_room_list->getNext();
               }
            }
            if ( isset($this->_form_post['communityroomlist']) ) {
               $this->_values['communityroomlist'] = $this->_form_post['communityroomlist'];
            } else {
               $this->_values['communityroomlist'] = $community_room_array;
            }
         }
         if ( $this->_item->isServer() ) {
            $this->_values['server_default_sender_address'] = $this->_item->getDefaultSenderAddress();
            $this->_values['server_portal_option'] = $this->_item->getDefaultPortalItemID();
            $url = $this->_item->getURL();
            if ( isset($url) ) {
               $this->_values['url'] = $url;
            }
            unset($url);
         }
         if ( $this->_item->isPortal() ) {
            $description_array = $this->_item->getDescriptionWellcome1Array();
            $languages = $this->_environment->getAvailableLanguageArray();
            foreach ($languages as $language) {
               if ( isset($description_array[cs_strtoupper($language)]) ) {
                  $this->_values['wellcome1_'.$language] = $description_array[cs_strtoupper($language)];
               } else {
                  $this->_values['wellcome1_'.$language] = $this->_item->getDescriptionWellcome1ByLanguage(cs_strtoupper($language));
               }
            }
            $description_array = $this->_item->getDescriptionWellcome2Array();
            $languages = $this->_environment->getAvailableLanguageArray();
            foreach ($languages as $language) {
               if ( isset($description_array[cs_strtoupper($language)]) ) {
                  $this->_values['wellcome2_'.$language] = $description_array[cs_strtoupper($language)];
               } else {
                  $this->_values['wellcome2_'.$language] = $this->_item->getDescriptionWellcome2ByLanguage(cs_strtoupper($language));
               }
            }
            $description_array = $this->_item->getDescriptionArray();
            $languages = $this->_environment->getAvailableLanguageArray();
            foreach ($languages as $language) {
               if (!empty($description_array[cs_strtoupper($language)])) {
                  $this->_values['description_'.$language] = $description_array[cs_strtoupper($language)];
               } else {
                  $this->_values['description_'.$language] = '';
               }
            }

         }else{
            $this->_values['description'] = $this->_item->getDescription();
         }
         if ($this->_item->isCommunityRoom()) {
            $this->_values['show_title'] = $this->_item->showTitle();
            if ($this->_item->showTime()) {
               $this->_values['show_time'] = '1';
            } else {
               $this->_values['show_time'] = '-1';
            }
              }
         if ($this->_item->checkNewMembersNever()) {
            $this->_values['member_check'] = 'never';
         } elseif ($this->_item->checkNewMembersAlways()) {
            $this->_values['member_check'] = 'always';
         } elseif ($this->_item->checkNewMembersSometimes()) {
            $this->_values['member_check'] = 'sometimes';
         } elseif ($this->_item->checkNewMembersWithCode()) {
            $this->_values['member_check'] = 'withcode';
         }

         $code = $this->_item->getCheckNewMemberCode();
         if ( !empty($code) ) {
            $this->_values['code'] = $code;
         }

         if ($this->_item->isOpenForGuests()) {
            $this->_values['open_for_guests'] = 'open';
         } else {
            $this->_values['open_for_guests'] = 'closed';
         }
         if ($this->_item->isAssignmentOnlyOpenForRoomMembers()) {
            $this->_values['room_assignment'] = 'closed';

         } else {
            $this->_values['room_assignment'] = 'open';
         }
         if ($this->_type == 'project' and $this->_environment->inProjectRoom()) {
            $community_list = $this->_item->getCommunityList();
            $mark_array = array();
            if ($community_list->isNotEmpty()) {
               $community_item = $community_list->getFirst();
               while ($community_item) {
                  $mark_array[] = $community_item->getItemID();
                  $community_item = $community_list->getNext();
               }
               $this->_values[CS_COMMUNITY_TYPE] = $mark_array;
               unset($mark_array);
            }
         }

         if (
               ( $this->_type == CS_PROJECT_TYPE and $this->_environment->inProjectRoom() )
               or ( $this->_type == CS_PROJECT_TYPE and $this->_environment->inCommunityRoom() )
               or ( $this->_type == CS_GROUPROOM_TYPE and $this->_environment->inGroupRoom() )
            ) {
            $portal_item = $this->_environment->getCurrentPortalItem();
            if ( $portal_item->showTime() ) {
               $time_list = $this->_item->getTimeList();
               $mark_array = array();
               if ( $time_list->isNotEmpty() ) {
                  $time_item = $time_list->getFirst();
                  while ($time_item) {
                     $mark_array[] = $time_item->getItemID();
                     $time_item = $time_list->getNext();
                  }
                  if ($this->_item->isContinuous()) {
                     $mark_array[] = 'cont';
                  }
                  $this->_values['time2'] = $mark_array;
                  unset($mark_array);
               }
            }
         }

         if (  ( $this->_type == 'project' and $this->_environment->inCommunityRoom())
               or ( ($this->_type == 'project' or $this->_type == CS_COMMUNITY_TYPE) and $this->_environment->inPrivateRoom()) ) {
            $this->_setValuesForRubricConnections();
         }

         // usage infos
         if ($this->_type == CS_PROJECT_TYPE or $this->_type == CS_COMMUNITY_TYPE) {
            $this->_values['usage_infos'] = $this->_item->getUsageInfoArray();
         }

         $this->_values['servicelink'] = $this->_item->isServiceLinkActive();

         // templates in private rooms
         if ( $this->_item->isPrivateRoom() ) {
            $this->_values['template_select'] = $this->_item->getTemplateID();
         }

      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = '0';
         }
        if ( isset($this->_values['logo_hidden']) and !empty($this->_values['logo_hidden']) ) {
           $this->_values['logo'] = $this->_values['logo_hidden'];
        }
        if ( isset($this->_values['picture_hidden']) and !empty($this->_values['picture_hidden']) ) {
           $this->_values['picture'] = $this->_values['picture_hidden'];
        }

      } else {
         $context = $this->_environment->getCurrentContextItem();
         $this->_values['public'] ='0';
         $this->_values['context'] = $context->getRoomContext();
        if ($this->_environment->inServer()) {
           $this->_values['type'] = CS_PORTAL_TYPE;
            $this->_values['member_check'] = 'never';
        } else {
            if ( $this->_environment->getCurrentModule() == CS_COMMUNITY_TYPE ) {
              $this->_values['type'] = CS_COMMUNITY_TYPE;
            } else {
              $this->_values['type'] = CS_PROJECT_TYPE;
            }
            $this->_values['member_check'] = 'always';
        }
        $this->_values['member_check'] = 'always';
        $this->_values['show_title'] = 'yes';

        // Zuordnen des aktuellen Gemeinschatsraums in zu einem daraus neu geöffneten Raum.
        if ( $this->_environment->inCommunityRoom() and $this->_environment->getCurrentModule() == CS_PROJECT_TYPE ){
           $this->_values['communityrooms'] = $this->_environment->getCurrentContextID();
        }
        $current_portal_item = $this->_environment->getCurrentPortalItem();
        if ( isset($current_portal_item) ) {
           if ( $this->_environment->inPortal() and $this->_environment->getCurrentModule() == 'community' ){
              $this->_values['template_select'] = $this->_environment->getCurrentPortalItem()->getDefaultCommunityTemplateID();
           } else {
              $this->_values['template_select'] = $this->_environment->getCurrentPortalItem()->getDefaultProjectTemplateID();
           }
           unset($current_portal_item);
        }

         // default language of room
         $current_user = $this->_environment->getCurrentUserItem();
         $lang = $current_user->getLanguage();
         if ( empty($lang) or mb_strtoupper($lang, 'UTF-8') == 'BROWSER' ) {
            $lang = $this->_environment->getSelectedLanguage();
         }
         if ( !empty($lang) ) {
            $this->_values['language'] = mb_strtolower($lang, 'UTF-8');
         }
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      //check emails for validity
      if ( !empty($this->_form_post['server_default_sender_address'])
          and !isEmailValid($this->_form_post['server_default_sender_address'])
        ) {
         $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_VALID_ERROR');
         $this->_form->setFailure('server_default_sender_address','');
      }

      $portal_item = $this->_environment->getCurrentPortalItem();
      if (isset($portal_item) ) {
         $project_room_link_status = $portal_item->getProjectRoomLinkStatus();
         if ( isset($this->_form_post['communityrooms']) and $project_room_link_status !='optional'){
            if ( ($this->_form_post['communityrooms'] == -1 or $this->_form_post['communityrooms'] == 'disabled' or $this->_form_post['communityrooms']=='--------------------') and !isset($this->_form_post['communityroomlist']) ){
               $this->_form->setFailure('communityrooms','mandatory');
               $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_COMMUNITY_ROOM_ENTRY',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'));
            }
         }
      }

      // url: portal/server
      if ( !empty($this->_form_post['url']) ) {
         $portal_manager = $this->_environment->getPortalManager();
         $url = $this->_form_post['url'];
         $url = str_replace('http://','',$url);
         $url = str_replace('https://','',$url);
         if ( strstr($url,'?') ) {
            $url = mb_substr($url,0,strpos($url,'?'));
         }
         $url = str_replace('/commsy.php','',$url);
         $url = str_replace('/index.php','',$url);
         if ( substr($url,strlen($url)-1) == '/' ) {
            $url = substr($url,0,strlen($url)-1);
         }
         if ( !empty($url) ) {

            // check server
            $server_item = $this->_environment->getServerItem();
            $server_url = $server_item->getUrl();
            $server_id = $server_item->getItemID();
            $current_id = $this->_form_post['iid'];
            if ( $current_id != $server_id
                 and $server_url == $url
               ) {
               $this->_form->setFailure('url','');
               $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ERROR_SERVER_URL',$this->_form_post['url']);
            } else {

               // check portal
               $portal_manager->setUrlLimit($url);
               $portal_manager->select();
               $portal_list = $portal_manager->get();
               if ( !empty($portal_list)
                    and $portal_list->isNotEmpty()
                  ) {
                  $portal_item = $portal_list->getFirst();
                  $portal_id = $portal_item->getItemID();
                  $current_id = $this->_form_post['iid'];
                  if ( $portal_id != $current_id ) {
                     $this->_form->setFailure('url','');
                     $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ERROR_PORTAL_URL',$this->_form_post['url'],$portal_item->getTitle());
                  }
                  unset($portal_id);
                  unset($current_id);
                  unset($portal_item);
               }
               unset($portal_manager);
               unset($portal_list);
            }
         }
      }
   }

   function getInfoForHeaderAsHTML () {
        return '';
   }
}
?>