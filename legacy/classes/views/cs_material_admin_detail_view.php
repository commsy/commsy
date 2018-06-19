<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

$this->includeClass(MATERIAL_DETAIL_VIEW);
include_once('functions/curl_functions.php');
include_once('classes/cs_link.php');

/**
 * class for CommSy detail view: material public
 */
class cs_material_admin_detail_view extends cs_material_detail_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_material_detail_view::__construct($params);
   }

   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= $this->_getPrintAction($item,$current_user);
      if ( $item->getWorldPublic() != 2 ) {
         $params = array();
         $params['id'] = $item->getItemID();
         $params['material_mode'] = 'public';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/material_admin_public.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_MAKE_PUBLIC').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/material_admin_public.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_MAKE_PUBLIC').'"/>';
         }
         $html .= '&nbsp;&nbsp;&nbsp;'.ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'material_admin',
                                    'index',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('MATERIAL_MAKE_PUBLIC')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/material_admin_public_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_MAKE_PUBLIC').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/material_admin_public_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_MAKE_PUBLIC').'"/>';
         }
         $html .= '&nbsp;&nbsp;&nbsp;<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('MATERIAL_MAKE_PUBLIC')).' "class="disabled">'.$image.'</a>'.LF;
      }
      $params = array();
      $params['id'] = $item->getItemID();
      $params['material_mode'] = 'not_public';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/material_admin_not_public.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_MAKE_NOT_PUBLIC').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/material_admin_not_public.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_MAKE_NOT_PUBLIC').'"/>';
      }
      $html .= '&nbsp;&nbsp;&nbsp;'.ahref_curl(  $this->_environment->getCurrentContextID(),
                                 'material_admin',
                                 'index',
                                 $params,
                                 $image,
                                 $this->_translator->getMessage('MATERIAL_MAKE_NOT_PUBLIC')).LF;
      unset($params);
      return $html;
   }

     function _getConfigurationOptionsAsHTML(){
         $html = '<div id="netnavigation1">'.LF;
         $html .= '<div class="netnavigation" >'.LF;
         $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>';
         $html .= $this->_getConfigurationBoxAsHTML($this->_environment->getCurrentFunction());

         $title_string ='"'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'"';
         $title_string .=',"'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"';
         if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
            $title_string .=',"'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"';
         }
         $show_entry ='-1';
         if ($this->_environment->getCurrentFunction() == 'mail' or
             $this->_environment->getCurrentFunction() == 'agb' or
             $this->_environment->getCurrentFunction() == 'usageinfo' or
             $this->_environment->getCurrentFunction() == 'news' or
             $this->_environment->getCurrentFunction() == 'extra' or
             $this->_environment->getCurrentModule() == 'account' or
             $this->_environment->getCurrentModule() == 'material_admin' or
             $this->_environment->getCurrentFunction() == 'statistic'
            ){
            $show_entry = '0';
         }elseif ($this->_environment->getCurrentFunction() == 'preferences' or
             $this->_environment->getCurrentFunction() == 'portalhome' or
             $this->_environment->getCurrentFunction() == 'portalupload' or
             $this->_environment->getCurrentFunction() == 'rubric' or
             $this->_environment->getCurrentFunction() == 'defaults' or
             $this->_environment->getCurrentFunction() == 'home' or
             $this->_environment->getCurrentFunction() == 'color' or
             $this->_environment->getCurrentFunction() == 'listviews' or
             $this->_environment->getCurrentFunction() == 'tags' or
             $this->_environment->getCurrentFunction() == 'time' or
             $this->_environment->getCurrentFunction() == 'room_opening' or
             $this->_environment->getCurrentFunction() == 'ims' or
             $this->_environment->getCurrentFunction() == 'privateroom_newsletter' or
             $this->_environment->getCurrentFunction() == 'authentication' or
             $this->_environment->getCurrentFunction() == 'language' or
             $this->_environment->getCurrentFunction() == 'backup'
            ){
            $show_entry = '1';
         }elseif ($this->_environment->getCurrentFunction() == 'dates' or
             $this->_environment->getCurrentFunction() == 'discussion' or
             $this->_environment->getCurrentFunction() == 'path' or
             $this->_environment->getCurrentFunction() == 'tags' or
             $this->_environment->getCurrentFunction() == 'grouproom'
            ){
            $show_entry = '2';
         }else{
            $show_entry = '3';
         }
         $title_string .=',"'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION_TITLE').'"';
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initDhtmlNetnavigation("netnavigation",Array('.$title_string.'),'.$show_entry.',"1");'.LF;
         $html .= '</script>'.LF;
         return $html;
     }

     function _getConfigurationBoxAsHTML($act_fct){
      $html = '';
      $room = $this->_environment->getCurrentContextItem();
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
      $html .= '<div class="netnavigation_panel_top">     '.LF;
      $html .= '<div style="padding-top:3px; padding-bottom:3px; padding-left:0px; padding-right:0px;"><ul style="list-style-type: none; font-size:8pt; padding-top:0px; margin-bottom:0px; padding-left:0px;">'.LF;
      $html .= '<li>'.LF;
      $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
      $html .= $link_item->getLinkIcon(25).LF;
      $html .= '</div><div style="padding-top:5px; text-align:left;">'.LF;
      $html .= $link_item->getLink(30).LF;
      $html .= '</div></div>'.LF;
      $html .='</li>'.LF;
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getAdminConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getRoomConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;


      if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
      $html .= '<div class="netnavigation_panel">     '.LF;
      $html .= '<noscript>';
      $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'</div>';
      $html .= '</noscript>';
      $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;
      $list = $this->getRubricConfigurationList();
      $element = $list->getFirst();
      while ($element){
         $html .= '<li>'.LF;
         $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
         if ( $element->getFunction() == $this->_environment->getCurrentFunction()
            or !$this->_with_modifying_actions ) {
            $html .= $element->getIcon(25).LF;
         } else {
            $html .= $element->getLinkIcon(25).LF;
         }
         $html .= '</div><div style="padding-top:5px;">'.LF;
         if ( !$this->_with_modifying_actions ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } elseif ( $element->getModule() == $this->_environment->getCurrentModule()
                 and $element->getFunction() == 'index'
                 and $this->_environment->getCurrentFunction() == 'status'
                ) {
            $html .= $element->getShortLink().LF;
         } elseif ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
            $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
         } else {
            $html .= $element->getShortLink().LF;
         }
         $html .= '</div></div>'.LF;
         $html .='</li>'.LF;
         $element = $list->getNext();
      }
      $html .= '</ul>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      }

      $addonlist = $this->getAddOnConfigurationList();
      $element = $addonlist->getFirst();
      if ($element){
         $html .= '<div class="netnavigation_panel">     '.LF;
         $html .= '<noscript>';
         $html .= '<div class="netnavigation_title">'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION_TITLE').'</div>';
         $html .= '</noscript>';
         $html .= '<div><ul style="list-style-type: none; font-size:8pt; padding-left:0px;">'.LF;

         while ($element){
            $html .= '<li>'.LF;
            $html .= '<div style="min-height:30px; width:100%;"><div style="float:left; width:30px;">'.LF;
            if ( $element->getFunction() == $this->_environment->getCurrentFunction() or !$this->_with_modifying_actions ){
               $html .= $element->getIcon(25).LF;
            } else {
               $html .= $element->getLinkIcon(25).LF;
            }
            $html .= '</div><div style="padding-top:5px;">'.LF;
            if ( $element->getFunction() == $this->_environment->getCurrentFunction() ) {
               $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
            } elseif ( !$this->_with_modifying_actions ){
               $html .= '<span class="disabled">'.$element->getShortTitle().'</span>'.LF;
            } else {
               $html .= $element->getShortLink().LF;
            }
            $html .= '</div></div>'.LF;
            $html .='</li>'.LF;
            $element = $addonlist->getNext();
         }
         $html .= '</ul>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div>'.LF;
      return $html;
   }



   function getRoomConfigurationList () {
      $room_link_list = '';
      include_once('include/inc_configuration_room_links.php');
      return $room_link_list;
   }

   function getAdminConfigurationList () {
      $admin_link_list = '';
      include_once('include/inc_configuration_admin_links.php');
      return $admin_link_list;
   }

   function getRubricConfigurationList () {
      $rubric_link_list = '';
      include_once('include/inc_configuration_rubric_links.php');
      return $rubric_link_list;
   }

   function getAddOnConfigurationList () {
        $addon_link_list = '';
      // addon configuration options
      include_once('include/inc_configuration_links_addon.php');
      return $addon_link_list;
   }



   function asHTML () {
      $item = $this->getItem();
      $html  = LF.'<!-- BEGIN OF DETAIL VIEW -->'.LF;
      $html .='<div style="width:100%;">'.LF;
      $rubric = $this->_environment->getCurrentModule();
      $current_context = $this->_environment->getCurrentContextItem();
      $detail_box_conf = $current_context->getDetailBoxConf();

      $html .= $this->_getDetailPageHeaderAsHTML();

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $this->_right_box_config['size_string'] = '';
         $current_context = $this->_environment->getCurrentContextItem();
         $html .='<div style="float:right; width:27%; margin-top:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div>'.LF;
         $html .='<div style="width:250px;">'.LF;
         $html .='<div id="commsy_panels">'.LF;

         if(!isset($this->_browse_ids) or count($this->_browse_ids) ==0){
             $this->_browse_ids[] = $this->_item->getItemID();
         }
         $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
         $html .= $this->_getForwardBoxAsHTML($item);
         $html .='</div>'.LF;
         $separator = '';

         $html .=  $this->_getConfigurationOverviewAsHTML();

         $html .='<div>&nbsp;'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
      }
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px; ';
      }else{
         $width= '';
      }

      if ( (isset($_GET['mode']) and $_GET['mode']=='print') ){
         $html .='<div class="infoborder" style="width:100%; margin-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='<div class="infoborder_display_content"  style="'.$width.'margin-top:5px; vertical-align:bottom;">'.LF;
      }
      $html .='<div id="detail_headline">'.LF;
      $html .= '<div style="padding:3px 5px 4px 5px;">'.LF;
      if($rubric == CS_DISCUSSION_TYPE){
         $html .= '<h2 class="contenttitle">'.$this->_getTitleAsHTML();
      }elseif ($rubric != CS_USER_TYPE and $rubric != 'account'){
         $html .= '<h2 class="contenttitle">'.$this->_text_as_html_short($item->getTitle());
      }elseif ($rubric == 'account' ){
         $html .= '<h2 class="contenttitle">'.$item->getFullName();
      }else{
        $html .= '<h2 class="contenttitle">'.$item->getFullName();
      }
      $html .= '</h2>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .='<div id="detail_content" style="'.$width.'">'.LF;


      $formal_data1 = array();
      if ($item->isNotActivated()){
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('COMMON_RIGHTS');

         $activating_date = $item->getActivatingDate();
         if (strstr($activating_date,'9999-00-00')){
            $title = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
         }else{
            $title = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
         }
         $temp_array[] = $title;
         $formal_data1[] = $temp_array;
      }
      if ($this->_environment->getCurrentModule() == CS_DATE_TYPE and $item->issetPrivatDate()){
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('COMMON_PRIVATE_DATE');
         $title = $this->_translator->getMessage('COMMON_NOT_ACCESSIBLE');
         $temp_array[] = $title;
         $formal_data1[] = $temp_array;
      }
      if (!empty($formal_data1)){
         $html .= $this->_getFormalDataAsHTML($formal_data1);
      }

      $html .= $this->_getContentAsHTML();
      $html .='<div class="infoborder" style="margin-top:5px; padding-top:10px; vertical-align:top;">';
      $mode = 'short';
      if (in_array($item->getItemID(),$this->_openCreatorInfo)) {
         $mode = 'long';
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= $this->_getCreatorInformationAsHTML($item, 3,$mode).LF;
      }
      if ($this->_environment->getCurrentModule() != 'user' or !$this->_environment->inPrivateRoom() ){
############SQL-Statements reduzieren
         $html .= $this->_getSubItemsAsHTML($item);
      }

      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF DETAIL VIEW -->'.LF.LF;
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= '<script type="text/javascript">'.LF;
         $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ( $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE and $current_browser == 'msie' and !(strstr($current_browser_version,'7.') or strstr($current_browser_version,'8.'))){
            $html .= 'preInitCommSyPanels(Array('.$this->_right_box_config['title_string'].'),Array('.$this->_right_box_config['desc_string'].'),Array('.$this->_right_box_config['config_string'].'), Array(),Array('.$this->_right_box_config['size_string'].'));'.LF;
         }else{
            $html .= 'initCommSyPanels(Array('.$this->_right_box_config['title_string'].'),Array('.$this->_right_box_config['desc_string'].'),Array('.$this->_right_box_config['config_string'].'), Array(),Array('.$this->_right_box_config['size_string'].'),Array(),null,null);'.LF;
         }
         $html .= '</script>'.LF;
      }
      return $html;
   }

     function _getConfigurationOverviewAsHTML(){
        $html='';
        $room = $this->_environment->getCurrentContextItem();
        $html .='<div class="commsy_no_panel" style="margin-bottom:1px; padding:0px;">'.LF;
        $html .= '<div class="right_box">'.LF;
        $array = $this->_environment->getCurrentParameterArray();
        $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE_LINKS').'</div>';
        $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_COMMSY_CONFIGURE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;
        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/room_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/room_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'room_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/rubric_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/rubric_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'rubric_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/structure_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/structure_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'structure_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/account_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/account_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'account_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;

        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/account.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_PAGETITLE_ACCOUNT').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/account.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_PAGETITLE_ACCOUNT').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_PAGETITLE_ACCOUNT')).LF;
        $context_item = $this->_environment->getCurrentContextItem();
        if ( $context_item->isCommunityRoom()
           and $context_item->isOpenForGuests()
           and $context_item->withRubric(CS_MATERIAL_TYPE)
        ) {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/material_admin.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/material_admin.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'material_admin',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION')).LF;
        }
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/informationbox.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_INFORMATION_BOX').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/informationbox.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_INFORMATION_BOX').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'informationbox',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_INFORMATION_BOX')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/usage_info_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/usage_info_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'usageinfo',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE')).LF;
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/mail_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/mail_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'mail',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;

        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt; white-space:nowrap;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ADDON_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        $context_item = $this->_environment->getCurrentContextItem();
        if ( $context_item->withWikiFunctions() and !$context_item->isServer()  and !$context_item->isGrouproom()) {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/pmwiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('WIKI_CONFIGURATION_LINK').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/pmwiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('WIKI_CONFIGURATION_LINK').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'wiki',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('WIKI_CONFIGURATION_LINK') and !$context_item->isGrouproom()).LF;
        }
        if ( $context_item->withChatLink() and !$context_item->isPortal() ) {
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/etchat.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CONFIGURATION_LINK').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/etchat.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CONFIGURATION_LINK').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'chat',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CHAT_CONFIGURATION_LINK')).LF;
        }
        if ( !$context_item->isGrouproom()){
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/config/template_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/config/template_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE').'"/>';
           }
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'template_options',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE')).LF;
        }
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/config/rubric_extras.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/config/rubric_extras.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
        }
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'rubric_extras',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;


        $html .= '</div>'.LF;
        $html .='</div>'.LF;
        $html .= '</div>'.LF;
        return $html;
     }


   /**
   * Overwritten method from cs_material_detail_view:
   *
   * No more 'edit'-strings (or links) are displayed here for the sections
   *
   */
   function _getSubActionsAsHTML($item){
      return '';
   }
}
?>