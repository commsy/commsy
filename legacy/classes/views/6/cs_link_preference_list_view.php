<?PHP
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

include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');
$this->includeClass(VIEW);

/**
 *  class for preferences for rooms: list view
 */
class cs_link_preference_list_view extends cs_view {

var $_addon_list = NULL;

var $_configuration_room_list = NULL;

var $_configuration_admin_list = NULL;

var $_configuration_rubric_list = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $current_context = $this->_environment->getCurrentContextItem();
   }

    function setImportantConfigurationRoomList ($list){
       #$this->_important_configuration_room_list = $list;
    }

    function setConfigurationUsageList ($list){
       #$this->_configuration_usage_list = $list;
    }

    function setConfigurationRubricExtrasList ($list){
       #$this->_configuration_rubric_extras_list = $list;
    }

    /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item) {

      $context_item = $this->_environment->getCurrentContextItem();
      $html = '                     <td width="25%" style="vertical-align: baseline; padding-bottom:10px;">'.LF;
      if ( !$context_item->isClosed()
          or ( mb_stristr($item->getLink(),'configuration')
               and mb_stristr($item->getLink(),'archive')
             )
          or ( mb_stristr($item->getLink(),'configuration')
                and mb_stristr($item->getLink(),'preferences')
              )
       ) {
         $html .= '                        '.'<span style="font-weight:bold">'.$item->getLink().'</span>'.LF;
      } else {
         $html .= '                        '.'<span style="font-weight:bold">'.$item->getTitle().'</span>'.LF;
      }
      $html .= '                     <br/>'.LF;

      $html .= '                     <span style="width:70%; vertical-align: baseline;">'.LF;
      if ( !$context_item->isClosed()
          or ( mb_stristr($item->getLink(),'configuration')
               and mb_stristr($item->getLink(),'archive')
             )
          or ( mb_stristr($item->getLink(),'configuration')
                and mb_stristr($item->getLink(),'preferences')
              )
       ) {
         $html .= '                        '.$item->getLinkIcon().' '.$item->getDescription().LF;
      } else {
         $html .= '                        '.$item->getIcon().' '.$item->getDescription().LF;
      }
      $html .= '                     </span></td>'.LF;
      return $html;
   }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      // Actions
      $html .= LF.'<div>'.LF;
      $html .='<div>'.LF;
      $html .= '<h2 class="pagetitle" style="font-family: verdana, arial, sans-serif;">'.$this->_translator->getMessage('COMMON_COMMSY_CONFIGURE');
      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .= '<table class="configuration_table" style="margin-top:5px;" summary="Layout">'.LF;
      $html .= '<tr class="list"><td class="head" colspan="3"><span style="font-weight: bold">'.$this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').'</span></td></tr>'.LF;
      $html .= '<tr>';
      $configuration_admin_list = $this->_configuration_admin_list;
      if ( isset($configuration_admin_list)) {
         $current_item = $configuration_admin_list->getFirst();
         $count = 0;
         while ( $current_item ) {
            if ( $count == 3 ){
               $count = 0;
               $html .= '</tr><tr>'.LF;
            }
            $item_text = $this->_getItemAsHTML($current_item);
            $html .= $item_text;
            $current_item = $configuration_admin_list->getNext();
            $count++;
         }
         while ( $count < 3 ){
            $html .= '<td width="25%" style="vertical-align: baseline; padding-bottom:10px;"></td>';
            $count++;
         }
      }
      $html .= '</tr></table>'.LF;
      $html .= '<table class="configuration_table" style="margin-top:20px;" summary="Layout">'.LF;
      /*
       * Hier steht in der Version 6 die Beschreibung für die Einrichtung eines Portals, unter Version 7 die,
       * für die Einrichtung eines Raumes. Wenn beide Versionen einmal zusammengelegt werden, muss hier unterschieden
       * werden, welche Übersetzung benutzt werden soll.
       */
      $html .= '<tr class="list"><td class="head" colspan="3"><span style="font-weight: bold">'.$this->_translator->getMessage('COMMON_CONFIGURATION_PORTAL_OPTIONS'/*'COMMON_CONFIGURATION_ROOM_OPTIONS'*/).'</span></td></tr>'.LF;
      $html .= '<tr>';
      $configuration_room_list = $this->_configuration_room_list;
         if ( isset($configuration_room_list)) {
            $current_item = $configuration_room_list->getFirst();
         $count = 0;
            while ( $current_item ) {
            if ( $count == 3 ){
               $count = 0;
               $html .= '</tr><tr>'.LF;
            }
               $item_text = $this->_getItemAsHTML($current_item);
               $html .= $item_text;
               $current_item = $configuration_room_list->getNext();
            $count++;
            }
         while ( $count < 3 ){
               $html .= '<td width="25%" style="vertical-align: baseline; padding-bottom:10px;"></td>';
               $count++;
         }
         }
      $html .= '</tr></table>'.LF;
      if ( !$this->_environment->inPortal() and !$this->_environment->inServer() ){
      $html .= '<table class="configuration_table" style="margin-top:20px;" summary="Layout">'.LF;
      $html .= '<tr class="list"><td class="head" colspan="3"><span style="font-weight: bold">'.$this->_translator->getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'</span></td></tr>'.LF;
      $html .= '<tr>';
      $configuration_rubric_list = $this->_configuration_rubric_list;
         if ( isset($configuration_rubric_list)) {
            $current_item = $configuration_rubric_list->getFirst();
         $count = 0;
            while ( $current_item ) {
            if ( $count == 3 ){
               $count = 0;
               $html .= '</tr><tr>'.LF;
            }
               $item_text = $this->_getItemAsHTML($current_item);
               $html .= $item_text;
               $current_item = $configuration_rubric_list->getNext();
            $count++;
            }
         while ( $count < 3 ){
               $html .= '<td width="25%" style="vertical-align: baseline; padding-bottom:10px;"></td>';
               $count++;
         }
         }
      $html .= '</tr></table>'.LF;
      }
      if ( isset($this->_addon_list)) {
        $html .= '<table class="configuration_table" style="padding-top:15px;" summary="Layout">'.LF;
         $html .= '<tr class="list"><td class="head" colspan="3"><span style="font-weight: bold">'.$this->_translator->getMessage('COMMON_ADDITIONAL_CONFIGURATION').'</span></td></tr>'.LF;
         $html.='<tr>';
            $list = $this->_addon_list;
            if ( isset($list)) {
               $current_item = $list->getFirst();
            $count = 0;
               while ( $current_item ) {
               if ( $count == 3 ){
                  $count = 0;
                  $html .= '</tr><tr>'.LF;
               }
                  $item_text = $this->_getItemAsHTML($current_item);
                  $html .= $item_text;
                  $current_item = $list->getNext();
               $count++;
               }
            while ( $count < 3 ){
                  $html .= '<td width="25%" style="vertical-align: baseline;"></td>';
                  $count++;
            }
            }
         $html .= '</tr></table>'.LF;
      }
      $html .='</div>'.LF;
      $html .= '<!-- END OF LIST VIEW -->'.LF.LF;
      return $html;
   }

   /** set title of the list view
    * this method sets the title of the list view
    *
    * @param string  $this->_title          title of the list view
    *
    * @author CommSy Development Group
    */
    function setTitle ($value) {
       $this->_title = (string)$value;
    }

    /** get title of the list view
    * this method gets the title of the list view
    *
    * @param string  $this->_title          title of the list view
    *
    * @author CommSy Development Group
    */
    function getTitle () {
       return $this->_title;
    }

    /** set description of the list view
    * this method sets the shown description of the list view
    *
    * @param int  $this->_description          description of the shown list
    *
    * @author CommSy Development Group
    */
    function setDescription ($description) {
       $this->_description = (string)$description;
    }

    /** set no description for the list view
    * this method hides the description of the list view
    *
    *
    * @author CommSy Development Group
    */
    function setWithoutDescription () {
       $this->_with_description = FALSE;
    }


  /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function getList (){
       return $this->_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setList ($list){
       $this->_list = $list;
    }


  /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function getConfigurationRoomList (){
       return $this->_configuration_room_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setConfigurationRoomList ($list){
       $this->_configuration_room_list = $list;
    }

  /** get the content of the list view
    * this method gets the whole entries of the admin list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function getConfigurationAdminList (){
       return $this->_configuration_admin_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the admin list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setConfigurationAdminList ($list){
       $this->_configuration_admin_list = $list;
    }


  /** get the content of the list view
    * this method gets the whole entries of the rubric list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function getConfigurationRubricList (){
       return $this->_configuration_rubric_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the rubric list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setConfigurationRubricList ($list){
       $this->_configuration_rubric_list = $list;
    }

 /** get the content of the list view
    * this method gets the whole entries of the add on list view
    *
    * @param list  $this->_addonlist          content of the list view
    *
    * @author CommSy Development Group
    */
    function getAddonList (){
       return $this->_addon_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the add on list view
    *
    * @param list  $this->_addonlist          content of the list view
    *
    * @author CommSy Development Group
    */
    function setAddonList ($list){
       $this->_addon_list = $list;
    }
}
?>