<?php
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

$this->includeClass(VIEW);
include_once('functions/date_functions.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_home_action_view extends cs_view {

var $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('actions');
      $this->_view_title = $this->_translator->getMessage('COMMON_ACTIONS');
   }

   function asHTML () {
     $html  = '';
    $current_context = $this->_environment->getCurrentContextItem();
     $current_user = $this->_environment->getCurrentUserItem();
     $html .= '<div class="right_box">'.LF;
     $html .= '         <noscript>';
     $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_ACTIONS').'</div>';
     $html .= '         </noscript>';
     $html .= '<div class="right_box_main" style="padding-top:3px; font-size:10pt;">'.LF;
     $conf = $current_context->getHomeConf();
     if ( !empty($conf) ) {
        $rubrics = explode(',', $conf);
     } else {
        $rubrics = array();
     }
     foreach ( $rubrics as $rubric ) {
        $rubric_array = explode('_', $rubric);
        if ( $rubric_array[1] != 'none' and $rubric_array[0] !='user' and $rubric_array[0] !='contact'
             and !( $rubric_array[0] =='myroom' and $this->_environment->InPrivateRoom() )
           ) {
           $params = array();
           $params['iid'] = 'NEW';

           $temp = mb_strtoupper($rubric_array[0], 'UTF-8');
           $tempMessage = "";
           switch( $temp )
           {
              case 'ANNOUNCEMENT':
                 $tempMessage = $this->_translator->getMessage('HOME_ANNOUNCEMENT_ENTER_NEW');
                 break;
              case 'DATE':
                 $tempMessage = $this->_translator->getMessage('HOME_DATE_ENTER_NEW');
                 break;
              case 'DISCUSSION':
                 $tempMessage = $this->_translator->getMessage('HOME_DISCUSSION_ENTER_NEW');
                 break;
              case 'GROUP':
                 $tempMessage = $this->_translator->getMessage('HOME_GROUP_ENTER_NEW');
                 break;
              case 'INSTITUTION':
                 $tempMessage = $this->_translator->getMessage('HOME_INSTITUTION_ENTER_NEW');
                 break;
              case 'MATERIAL':
                 $tempMessage = $this->_translator->getMessage('HOME_MATERIAL_ENTER_NEW');
                 break;
              case 'MYROOM':
                 $tempMessage = $this->_translator->getMessage('HOME_MYROOM_ENTER_NEW');
                 break;
              case 'PROJECT':
                 $tempMessage = $this->_translator->getMessage('HOME_PROJECT_ENTER_NEW');
                 break;
              case 'TODO':
                 $tempMessage = $this->_translator->getMessage('HOME_TODO_ENTER_NEW');
                 break;
              case 'TOPIC':
                 $tempMessage = $this->_translator->getMessage('HOME_TOPIC_ENTER_NEW');
                 break;
              default:
                 global $c_plugin_array;
                 if ( !empty($c_plugin_array)
                      and in_array(strtolower($temp),$c_plugin_array)
                    ) {
                    // initiate class
                    // check if there is an methode "getActionforHomeasHTML"
                    break;
                 } else {
                    $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_homepage_action_view('.__LINE__.') ');
                    break;
                 }
           }
           if ( !empty($tempMessage) ) {
              if ( $current_user->isUser()
                   and $this->_with_modifying_actions
                 ) {

                 $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),
                                          $rubric_array[0],
                                          'edit',
                                          $params,
                                          $tempMessage
                                         ).BRLF;
              } else {
                 $html .= '<span class="disabled">'.'> ';
                 $html .= $tempMessage;
                 $html .= '</span>'.BRLF;
              }
           }
           unset($params);
        }
     }
     $params = $this->_environment->getCurrentParameterArray();
     $params['mode']='print';
     $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'home','index',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).BRLF;
     $html .= '</div>'.LF;
     $html .='<div style="clear:both;">'.LF;
     $html .='</div>'.LF;
     $html .= '</div>'.LF;
     return $html;
   }

}
?>