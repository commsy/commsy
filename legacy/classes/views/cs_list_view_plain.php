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

$this->includeClass(VIEW);
include_once('classes/cs_list.php');

/**
 *  generic upper class for CommSy plain list-views
 */
class cs_list_view_plain extends cs_view {

   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;

   /**
    * string - with search_text as keys
    */
   var $_search_text = NULL;
   var $_search_array = array();

   /**
    * string - containing the view name of the list view
    */
   var $_view_name;

  /**
   * string - contains name of stylesheet to use for table in method asHTML()
   */
   var $_stylesheet_name = NULL;

   /**
    * array - containing the actions of the list view
    */
   var $_actions = NULL;

   /**
    * string - the current sort key
    */
   var $_sort_key = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_plain_list_view ($params) {
      $this->_view_name = 'list_view_plain';
      if ( !empty($params['viewname']) ) {
         $this->_view_name = $params['viewname'];
      }
      cs_view::__construct($params);
      $this->_stylesheet_name = 'list_view_plain';
   }

    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    *
    * @author CommSy Development Group
    */
    function getSearchText (){
       return $this->_search_text;
    }

    /** set the value of the search box
    * this method sets the search value of the list
    *
    * @param string  $this->_search_text
    *
    * @author CommSy Development Group
    */

   function setSearchText ($search_text){
      $this->_search_text = $search_text;
      $literal_array = array();
      $search_array = array();

      //find all occurances of quoted text and store them in an array
      preg_match_all('~("(.+?)")~u',$search_text,$literal_array);
      //delete this occurances from the original string
      $search_text = preg_replace('~("(.+?)")~u','',$search_text);

      $search_text = preg_replace('~-(\w+)~u','',$search_text);

      //clean up the resulting array from quots
      $literal_array = str_replace('"','',$literal_array[2]);
      //clean up rest of $limit and get an array with entrys
      $search_text = str_replace('  ',' ',$search_text);
      $search_text = trim($search_text);
      $split_array = explode(' ',$search_text);

      //check which array contains search limits and act accordingly
      if ($split_array[0] != '' AND count($literal_array) > 0) {
         $search_array = array_merge($split_array,$literal_array);
      } else {
         if ($split_array[0] != '') {
            $search_array = $split_array;
         } else {
            $search_array = $literal_array;
         }
      }
      $this->_search_array = $search_array;
   }

   /** get the content of the list view
    * this method gets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function getList () {
       return $this->_list;
    }

    /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function setList ($list) {
       $this->_list = $list;
    }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF PLAIN LIST VIEW -->'.LF;
      $html .= '<a name="'.$this->_view_name.'"></a>'.LF;

      if ($this->_environment->inPortal()) {
         $current_context = $this->_environment->getCurrentPortalItem();
      } else {
         $current_context = $this->_environment->getServerItem();
      }

      $html .='<table style="width:100%;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="width:75%; padding-top:5px; vertical-align:bottom">'.LF;
      $html .='<div>'.LF;
      $html .='<div style="float:right;text-align:right;">'.LF;
      $html .= '<span class="portal_description">'.$this->_getIntervalLinksFirstLineAsHTML().'</span>'.LF;
      $html .= '<span class="portal_description">'.$this->_getIntervalLinksSecondLineAsHTML().'</span>'.BRLF;
      $html .= $this->_getDescriptionAsHTML().LF;
      $html .='</div>'.LF;
      $html .='<div>'.LF;
      if ($this->_environment->inServer()) {
         $html .= '<span class="portal_section_title">'.$this->_translator->getMessage('SERVER_PORTAL_OVERVIEW').'</span>'.LF;
      } else {
         $html .= '<span class="portal_section_title">'.$this->_translator->getMessage('PORTAL_ROOM_OVERVIEW').'</span>'.LF;
      }
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</td>'.LF;
      $html .='<td colspan="2" style="width:25%; padding-top:5px; vertical-align:bottom; text-align:right;">'.LF;
#	   $html .= '<span class="portal_description">'.$this->_translator->getMessage('COMMON_LIST_VIEW').': </span>'.LF;
#	   $html .= '<span class="portal_description" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_ROOMS').'</span>'.BRLF;
      $html .= '<span class="portal_forward_links">'.$this->_getForwardLinkAsHTML().'</span>'.BRLF;
      // actions
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="border-top:1px solid black; padding-top:10px; vertical-align:top; ">'.LF;


      $html .= '<table style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      $html .= $this->_getContentAsHTML();
      $html .= '</table>'.LF;
      $html .='</td>'.LF;

      $html .='<td>&nbsp;'.LF;
      $html .='</td>'.LF;
      $html .='<td style="width:23%; border-top:1px solid black; vertical-align:top; padding-top:10px;">'.LF;
      $html .='<div style="margin-bottom:10px;">'.LF;
      $html .= $this->_getListActionsAsHTML();
      $html .='</div>'.LF;
      $html .='<div style="margin-bottom:10px;">'.LF;
      $html .= $this->_getListSelectionsAsHTML();
      $html .='</div>'.LF;
      $user = $this->_environment->getCurrentUser();
      if ( $user->isModerator() ){
         $html .='<div style="margin-bottom:10px;">'.LF;
         $html .= $this->_getConfigurationBoxAsHTML();
         $html .='</div>'.LF;
      }
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= '</table>'.LF;
     $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }

   function _getTableFootAsHTML(){
      return '';
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    */
   function _getContentAsHTML() {

      $html = '';
      if ( !$this->_list->isEmpty() ) {
         $list = $this->_list;
         $current_item = $list->getFirst();
         $html = '';
         while ( $current_item ) {
            $item_text = $this->_getItemAsHTML($current_item);
            $html .= $item_text;
            $current_item = $list->getNext();
         }
      }
      return $html;
   }


   function _getListActionsAsHTML () {
      $html  = '';
      // Search / select form
      $html .= '<form action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
#      if ( $this->hasCheckboxes() ) {
#         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
#         $html .= '   <input type="hidden" name="mode" value="'.$this->_text_as_form($this->_has_checkboxes).'"/>'.LF;
#      }
#      if ( $this->isAttachedList() ) {
#         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
#         $html .= '   <input type="hidden" name="mode" value="attached"/>'.LF;
#      }
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
#      $html .= '   <input type="hidden" name="from" value="1"/>'.LF;
#      $html .= '   <input type="hidden" name="interval" value="'.$this->_text_as_form($this->getInterval()).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
#      if ($this->_module==CS_DATE_TYPE and isset($this->_month) ){
#         $html .= '   <input type="hidden" name="month" value="'.$this->_text_as_form($this->_month).'"/>'.LF;
#         $html .= '   <input type="hidden" name="year" value="'.$this->_text_as_form($this->_year).'"/>'.LF;
#      }
      $session = $this->_environment->getSession();
      if ( !$session->issetValue('cookie')
           or $session->getValue('cookie') == '0' ) {
         $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session->getSessionID()).'"/>'.LF;
      }
      $html .= '<div>'.LF;
      $html .= '<div style="float: left; text-align: left">'.$this->_translator->getMessage('COMMON_SEARCHFIELD').'<br /><input style="width: 8.8em;" name="search" type="text" size="15" value="'.$this->_text_as_form($this->getSearchText()).'"/></div> '.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML();
      $html .= '<div><br /><input name="option" value="'.$this->_translator->getMessage('COMMON_SHOW_BUTTON').'" type="submit"/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</form>'.LF;

      return $html;
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML($item) {
      // must be overwritten
   }

   function _getTableheadAsHTML () {
   }

    /** set the value of the sort box
    * this method sets the sort key of the list
    *
    * @param string  $this->_sort_key
    *
    * @author CommSy Development Group
    */
    function setSortKey ($sort_key) {
       $this->_sort_key = (string)$sort_key;
    }

    function getSortKey () {
       return $this->_sort_key;
    }


   /** get the action links of the list view as HTML
    * this method returns the action links in HTML-Code
    *
    * @return string actions as HMTL
    *
    * @author CommSy Development Group
    */
   function _getActionsAsHTML () {
      $html  = '';
      if ( isset( $this->_actions ) ) {
         $html .= '<div class="action" style="padding-top: 0px;">'.LF;
         foreach( $this->_actions as $key => $value ) {
            $html .= '   '.$value.''.LF;
         }
         $html .= '</div>'.LF;
      }
      return $html;
   }

   /** set the actions of the list
    * this method sets the actions of the list
    *
    * @param array  $this->_action_array
    */
    function addAction($action){
       $this->_actions[] = $action;
    }
}
?>