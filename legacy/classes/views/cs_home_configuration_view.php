<?php
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

$this->includeClass(VIEW);
include_once('functions/date_functions.php');
include_once('classes/cs_link.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_home_configuration_view extends cs_view {

var  $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_CONFIGURATION');
      $this->setViewName('preferences');
   }

   function asHTML () {
     $html  = '';

     $current_context = $this->_environment->getCurrentContextItem();
     $html  = '';
     $html .= '<div class="right_box">'.LF;
     $html .= '         <noscript>';
     $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>';
     $html .= '         </noscript>';
     $html .= '<div class="right_box_main" style="font-size:10pt; padding-top:2px;padding-bottom:3px; padding-left:0px;">'.LF;
     $link_item = new cs_link();
     $link_item->setDescription($this->_translator->getMessage('HOME_ROOM_MEMBER_ADMIN_DESC'));
     $link_item->setIconPath('images/cs_config/CONFIGURATION_OVERVIEW.gif');
     $link_item->setTitle($this->_translator->getMessage('COMMON_COMMSY_CONFIGURE_HOME'));
     $link_item->setContextID($this->_environment->getCurrentContextID());
     $link_item->setModule('configuration');
     $link_item->setFunction('index');
     $params = array();
     $link_item->setParameter($params);
     unset($params);
     $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
     $html .= '<tr>'.LF;
     $html .= '<td style="width:10%;">'.LF;
     $html .= $link_item->getLinkIcon(30).LF;
     $html .= '</td>'.LF;
     $html .= '<td style="width:90%;">'.LF;
     $html .= $link_item->getLink(30).LF;
     $html .= '</td>'.LF;
     $html .= '</tr>'.LF;
     $html .= '</table>'.LF;

    if ( $this->_with_modifying_actions ) {

        // tasks
        $manager = $this->_environment->getTaskManager();
        $manager->resetLimits();
        $manager->setContextLimit($this->_environment->getCurrentContextID());
        $manager->setStatusLimit('REQUEST');
        $manager->select();
        $tasks = $manager->get();
        $task = $tasks->getFirst();
        $show_user_config = false;
        $count_new_accounts = 0;
        while($task){
           $mode = $task->getTitle();
           $task = $tasks->getNext();
           if ($mode == 'TASK_USER_REQUEST'){
              $count_new_accounts ++;
              $show_user_config = true;
           }
        }

       if ( !$this->_environment->inPrivateRoom() ) {
        $link_item = new cs_link();
          $link_item->setDescription($this->_translator->getMessage('HOME_ROOM_MEMBER_ADMIN_DESC'));
        $link_item->setIconPath('images/cs_config/ROOM_MEMBER_ADMIN.gif');
        $link_item->setTitle($this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts));
        $link_item->setContextID($this->_environment->getCurrentContextID());
        $link_item->setModule('account');
        $link_item->setFunction('index');
        $params = array();
        $params['selstatus']='1';
        $link_item->setParameter($params);
        unset($params);
        $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
        $html .= '<tr>'.LF;
        $html .= '<td style="width:10%;">'.LF;
        if ( !$show_user_config ){
           $html .= $link_item->getIcon(30).LF;
        }else{
           $html .= $link_item->getLinkIcon(30).LF;
        }
        $html .= '</td>'.LF;
        if ( !$show_user_config){
           $html .= '<td style="width:90%; font-weight:normal">'.LF;
           $html .= '<span class="disabled">'.$link_item->getTitle().'</span>'.LF;
        }else{
           $html .= '<td style="width:90%; font-weight:bold;">'.LF;
           $html .= $link_item->getLink().LF;
        }
        $html .= '</td>'.LF;
        $html .= '</tr>'.LF;
        $html .= '</table>'.LF;
       }

        if ( $this->_environment->inCommunityRoom()
            and $current_context->withRubric(CS_MATERIAL_TYPE)
         ) {
             // tasks
           $manager = $this->_environment->getTaskManager();
           $manager->resetLimits();
           $manager->setContextLimit($this->_environment->getCurrentContextID());
           $manager->setStatusLimit('REQUEST');
           $manager->select();
           $tasks = $manager->get();
           $task = $tasks->getFirst();
           $show_materials_config = false;
           $count_new_materials = 0;
           while($task){
              $mode = $task->getTitle();
              $task = $tasks->getNext();
              if ($mode == 'TASK_REQUEST_MATERIAL_WORLDPUBLIC' or $mode == 'TASK_CANCEL_MATERIAL_WORLDPUBLIC'){
                 $count_new_materials ++;
                 $show_materials_config = true;
              }
           }
           // material
           $link_item = new cs_link();
           $link_item->setTitle($this->_translator->getMessage('HOME_MATERIAL_ADMIN_TINY_HEADER',$count_new_materials));
           $link_item->setDescription($this->_translator->getMessage('HOME_MATERIAL_ADMIN_TINY_DESCRIPTION'));
           $link_item->setIconPath('images/cs_config/MATERIAL_ADMIN_TINY_DESCRIPTION.gif');
           $link_item->setContextID($this->_environment->getCurrentContextID());
           $link_item->setModule('material_admin');
           $link_item->setFunction('index');
           $params= array();
           $params['selstatus']='1';
           $link_item->setParameter($params);
           $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
           $html .= '<tr>'.LF;
           $html .= '<td style="width:10%;">'.LF;
           if ( !$show_materials_config ){
              $html .= $link_item->getIcon(30).LF;
           }else{
              $html .= $link_item->getLinkIcon(30).LF;
           }
           $html .= '</td>'.LF;
           if ( !$show_materials_config){
              $html .= '<td style="width:90%; font-weight:normal">'.LF;
              $html .= '<span class="disabled">'.$link_item->getTitle().'</span>'.LF;
           }else{
              $html .= '<td style="width:90%; font-weight:bold;">'.LF;
              $html .= $link_item->getLink().LF;
           }
           $html .= '</td>'.LF;
           $html .= '</tr>'.LF;
           $html .= '</table>'.LF;
        }
    }
    $html .= '</div>'.LF;
     $html .= '</div>'.LF;
     return $html;
   }
}
?>