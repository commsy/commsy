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
include_once('functions/curl_functions.php');

/**
 *  generic upper class for CommSy list-views
 */
class cs_list_view extends cs_view {

   /**
    * string - a name that can uniquely identify this view if multiple lists are displayed on one page
    */
   var $_name = NULL;


   /**
    * int - begin of list
    */
   var $_from = NULL;

   /**
    * int - length of shown list
    */
   var $_interval = NULL;

   /**
    * string - description of shown list
    */
   var $_description = NULL;

   /**
    * boolean - with description
    */
   var $_with_description = TRUE;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;

   /**
    * string - the current sort key
    */
   var $_sort_key;

   /**
    * array - with sort keys
    */
   var $_sort_keys;

   /**
    * boolean - with navigation
    */
   var $_with_navigation = true;

   /**
    * boolean - should the list have a sort_box
    */
   var $_has_sort_box = false;

   /**
    * string - with search_text as keys
    */
   var $_search_text = NULL;

   /**
    * boolean - should the list have a search_box
    */
   var $_has_search_box = false;

   /**
    * string - containing the title of the list view
    */
   var $_title = NULL;

    /**
    * string - containing the action title of the list view
    */
   var $_action_title =NULL;

   /**
    * array - containing the actions of the list view
    */
   var $_actions;

   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;

   /**
    * boolean - should the list have the check-boxes or not
    */
   var $_has_check_boxes = false;

   /**
    * boolean - should the list have content or not
    */
   var $_has_content = true;

   var $_with_form = false;
   /**
    * list - containing the button texts
    */
   var $_button_list = NULL;

   /**
    * string - containing the view name of the list view for the group detail page
    */
   var $_view_name;

   /**
    * string - containing the link name of the list view for the group detail page
    */
   var $_link_name;

   /**
    * int - containing the itemID of the list view for the group detail page
    */
   var $_link_id = NULL;

   /**
    * array - containing extra options
    */
   var $_extra_options = array();

   /**
    * string - containing a ahref mark i.e. "http://www.commsy.net/index.html#fragment"
    */
   var $_fragment;

   /**
    * cs_list- contains hidden fields of the form
    */
   var $_hidden_list = NULL;

   /**
    * cs_list- contains delete buttons of the form
    */
   var $_delete_button_list = NULL;

   /**
   * String - contains name of stylesheet to use for table in method asHTML()
   */
   var $_stylesheet_name = NULL;


   var $_assigned_list_of = NULL;


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      $viewname = 'list_view';
      if ( !empty($params['viewname']) ) {
         $viewname = $params['viewname'];
      }
      $this->_name = $viewname;
      $this->_view_name = $viewname;
      $this->_link_name = $viewname;
      $this->link_id ='';
      $this->_assigned_list_of = NULL;
      cs_view::__construct($params);
      $this->_sort_keys = array();
      $this->_sort_key = '';
      $this->_button_list = new cs_list();
      $this->_delete_button_list =  new cs_list();
      $this->_hidden_list = new cs_list();
      $this->_actions = array();
      $this->_search_text = '';
      $this->_fragment = '';
      $this->_stylesheet_name = 'view';
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

    /** set stylesheet_name of the list view
    * this method sets the stylesheet_name of the list view
    *
    * @param string  $this->_stylesheet_name          Name of the Stylesheet used
    *
    * @author CommSy Development Group
    */
    function setStylesheetName ($value) {
       $this->_stylesheet_name = (string)$value;
    }

    /** get stylesheet_name of the list view
    * this method gets the stylesheet_name of the list view
    *
    * @param string  $this->_stylesheet_name          Name of the Stylesheet used
    *
    * @author CommSy Development Group
    */
    function _getStylesheetName() {
       return $this->_stylesheet_name;
    }

    /** get from counter of the list view
    * this method gets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    *
    * @author CommSy Development Group
    */
    function getFrom (){
       return $this->_from;
    }

    /** set from counter of the list view
    * this method sets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    *
    * @author CommSy Development Group
    */
    function setFrom ($from) {
       $this->_from = (int)$from;
    }

    /** set view name for the group detail page
    * this method sets explicit view name (e.g. group detail) for the group detail page
    *
    * @param string  $view_name
    *
    * @author CommSy Development Group
    */
    function setViewName($view_name){
       $this->_view_name = (string)$view_name;
    }

    /** set link name for the group detail page
    * this method sets view name (e.g. material_group_index) for the group detail page
    *
    * @param string  $view_name
    *
    * @author CommSy Development Group
    */
    function setLinkName($link_name){
       $this->_link_name = (string)$link_name;
    }

    /** set item id of the group detail page
    * this method sets item id for the group detail page for displaying the materials
    *
    * @param int  $item_iid
    *
    * @author CommSy Development Group
    */
    function setLinkID($item_iid){
       $this->_link_id = 'iid='.(string)$item_iid.'&';
    }

    /** get interval counter of the list view
    * this method gets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    *
    * @author CommSy Development Group
    */
    function getInterval () {
       return $this->_interval;
    }

    /** set interval counter of the list view
    * this method sets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    *
    * @author CommSy Development Group
    */
    function setInterval ($interval) {
       $this->_interval = (int)$interval;
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


    /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function getCountAll () {
       return $this->_count_all;
    }

    /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function setCountAll ($count_all) {
       $this->_count_all = (int)$count_all;
    }

    /** set the navigation of the list view
    *
    *
    * @author CommSy Development Group
    */
    function setWithNavigation () {
       return $this->_with_navigation = true;
    }

    /** unset the navigation of the list view
    *
    *
    * @author CommSy Development Group
    */
    function setWithOutNavigation () {
       return $this->_with_navigation = false;
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

    /** the list view has a sort box
    * this method gives the list view a sort box
    *
    * @param boolean  $this->_has_sort_box
    *
    * @author CommSy Development Group
    */
    function setHasSortBox (){
       $this->setWithForm();
       $this->_has_sort_box = true;
    }

    function setWithForm(){
       $this->_with_form=true;
    }

    /** the list view has a search box
    * this method gives the list view a search box
    *
    * @param boolean  $this->_has_search_box
    *
    * @author CommSy Development Group
    */
    function setHasSearchBox (){
       $this->setWithForm();
       $this->_has_search_box = true;
    }

    /** set the criterias of the sort box
    * this method sets the sort criterias of the list
    *
    * @param string  key
    * @param string  description
    *
    * @author CommSy Development Group
    */
    function addSortKey ($key, $description){
         $this->_sort_keys[$key] = $description;
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
    }

    /** set the value of the sort box
    * this method sets the sort key of the list
    *
    * @param string  $this->_sort_key
    *
    * @author CommSy Development Group
    */
    function setSortKey ($sort_key) {
       $this->_sort_key = $sort_key;
    }

    /** set the title of the list actions
    * this method sets the title of the list actions
    *
    * @param string  $this->_action_title
    *
    * @author CommSy Development Group
    */
    function setActionTitle($action_title){
       $this->_action_title = (string)$action_title;
    }

    /** set the actions of the list
    * this method sets the actions of the list
    *
    * @param array  $this->_action_array
    *
    * @author CommSy Development Group
    */
    function addAction($action){
       $this->_actions[] = $action;
    }

    /** set no content in short view
    *
    * @param string  $this->_has_content
    *
    * @author CommSy Development Group
    */
    function setWithOutContent() {
       $this->_has_content = false;
    }

    /** set content in short view
    *
    * @param string  $this->_has_content
    *
    * @author CommSy Development Group
    */
    function setWithContent() {
       $this->_has_content = true;
    }

    /** set content in short view
    *
    * @param string  $this->_has_content
    */
    function setEntryOfAssignedList ($assigned_array) {
       $this->_assigned_list_of = $assigned_array;
    }

    function hasContent(){
       return $this->_has_content;
    }

    /** add a button to the list-view
    * this method adds a button to the list-view
    *
    * @param string  button
    *
    * @author CommSy Development Group
    */
    function addButton($buttonvalue,
                       $buttonname) {
       $button = array();
       $button['value']= $buttonvalue;
       $button['name']= $buttonname;
       $this->_button_list->add($button);
    }

    /** add a delete button to the list-view
    * this method adds a delete button to the list-view
    * delete buttons are aligned right (normal buttons are aligned left)
    *
    * @param string  button
    *
    * @author CommSy Development Group
    */
    function addDeleteButton($buttonvalue,
                             $buttonname) {
       $button = array();
       $button['value']= $buttonvalue;
       $button['name']= $buttonname;
       $this->_delete_button_list->add($button);
    }


    /** add a hidden-field to the list-view
    * this method adds a hidden-field to the list-view
    *
    * @param string  name the name of the hidden field
    * @param string  value the value of the hidden field
    *
    * @author CommSy Development Group
    */
    function addHidden($name,$value){
       $hidden = array();
       $hidden['value']= $value;
       $hidden['name']= $name;
       $this->_hidden_list->add($hidden);
    }

    /** the list view has check boxes
    * this method gives the list view the check boxes
    *
    * @param boolean  $this->_has_check_box
    *
    * @author CommSy Development Group
    */
    function setCheckBoxes(){
       $this->setWithForm();
       $this->_has_check_boxes = true;
    }

    /**
    * TBD
    */
    function setExtraOptionArray ($array) {
       $this->_extra_options = (array)$array;
    }

    function getExtraOptionArray () {
       return $this->_extra_options;
    }

    /**
    * TBD
    */
    function getExtraOptionsAsForm () {
       $retour = '';
       foreach ($this->_extra_options as $key => $value) {
          $retour .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
       }
       return $retour;
    }

    /**
    * TBD // can deleted (TBD)
    */
    function getExtraOptionsAsGetParams () {
       $retour = '';
       $first = true;
       foreach ($this->_extra_options as $key => $value) {
          if ($first) {
             $first = false;
          } else {
             $retour .= '&';
          }
          $retour .= $key.'='.$value;
       }
       return $retour;
    }

    /**
    * TBD
    */
    function setFragment ($value) {
       $this->_fragment = (string)$value;
    }

    /**
    * TBD
    */
    function getFragment () {
       return (string)$this->_fragment;
    }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = "\n".'<!-- BEGIN OF LIST VIEW -->'."\n";
      $html .= '<a name="'.$this->_view_name.'"></a>'."\n";
      if ( $this->_with_form ) {
         $params = array();
         $params['viewname'] = $this->_name;
         $html .= '      <form action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,$params).'" method="post" enctype="multipart/form-data" name="'.$this->_name.'_check_boxes">'."\n";
         unset($params);
      }
      $html .= '<table class="'.$this->_getStylesheetName().'" summary="Layout">'."\n";

      // Header line (Title, Description, etc.)
      $html .= '   <tr>'."\n";
      $html .= '      <td class="'.$this->_getStylesheetName().'_title">'."\n";
      $html .= $this->_getTitleAsHTML();
      $html .= $this->_getDescriptionAsHTML();
      $html .= '      </td>'."\n";
      if ($this->_with_navigation){
         $html .= '      <td class="'.$this->_getStylesheetName().'_browsing_icons_top">'."\n";
/////////////////////////
         if ( $this->_has_check_boxes ){
            $html .=$this->_getBrowsingButtonsAsHTML();
         }else {
            $html .= $this->_getBrowsingIconsAsHTML();
         }
////////////////////////
      } else{
         $html .= '      <td class="'.$this->_getStylesheetName().'_actions">'."\n";
         $html .=  $this->_getActionsAsHTML();
      }
      $html .= '      </td>'."\n";
      $html .= '   </tr>'."\n";

      // Content, if any
      if ( $this->hasContent() ){
         $html .= '   <tr>'."\n";
         if ($this->_with_navigation){
             $html .= '      <td class="list_view_content" rowspan="2">'."\n";
         }else{
             $html .= '      <td class="list_view_content" colspan="2">'."\n";
         }
         $html .= $this->_getContentAsHTML();
         $html .= '      </td>'."\n";
         if ($this->_with_navigation){
            $html .= '      <td class="'.$this->_getStylesheetName().'_navigation" >'."\n";
            $html .= $this->_getNavigationAsHTML();
            $html .= '      </td>'."\n";
         }
         $html .= '   </tr>'."\n";
         if ($this->_with_navigation){
            $html .= '   <tr>'."\n";
#            $html .= '      <td class="'.$this->_getStylesheetName().'_browsing_icons_bottom">'."\n";



/////////////////////////
            if ( $this->_has_check_boxes ){
               $html .= '      <td class="'.$this->_getStylesheetName().'_browsing_icons_bottom" rowspan="2">'."\n";
               $html .=$this->_getBrowsingButtonsAsHTML();
            }else {
               $html .= '      <td class="'.$this->_getStylesheetName().'_browsing_icons_bottom">'."\n";
               $html .= $this->_getBrowsingIconsAsHTML();
            }

/////////////////////////
            $html .= '      </td>'."\n";
            $html .= '   </tr>'."\n";
         }

///////////////////////////////
         if ( isset($this->_button_list) and !$this->_button_list->isEmpty()) {
            $html .= '   <tr>'."\n";
            $html .= '      <td class="'.$this->_getStylesheetName().'_form" width=80%>'."\n";
            $button = $this->_button_list->getFirst();
            while ($button) {
               $html .= '<input type="submit" value="'.$button['value'].'" name="'.$button['name'].'">'."\n";
               $button = $this->_button_list->getNext();
            }
            if (isset($this->_delete_button_list) and !$this->_delete_button_list->isEmpty()) {
               $html .= '      </td>'."\n";
               $html .= '      <td align="right" class="'.$this->_getStylesheetName().'_form">'."\n";
               $delete_button = $this->_delete_button_list->getFirst();
               while ($delete_button) {
                  $html .= '<input type="submit" value="'.$delete_button['value'].'" name="'.$delete_button['name'].'">'."\n";
                  $delete_button = $this->_delete_button_list->getNext();
               }
            }
            $html .= '      </td>'."\n";
            $html .= '   </tr>'."\n";
         }

         if ( isset($this->_hidden_list) and !$this->_hidden_list->isEmpty()) {
            $hidden = $this->_hidden_list->getFirst();
            while ($hidden) {
               $html .= '<input type="hidden"  name="'.$hidden['name'].'" value="'.$hidden['value'].'">'."\n";
               $hidden = $this->_hidden_list->getNext();
            }
         }
      }
///////////////////////////////////////
      $html .= '</table>'."\n";
//////////////////////////
      if ( $this->_with_form ){
         $html .= '      </form>'."\n"; // extra hinter das td, damit die Leerzeile unter den Buttons verschwindet
      }
//////////////////////////
      $html .= '<!-- END OF LIST VIEW -->'."\n\n";
      return $html;
   }

   /** get the title of the list view as HTML
    * this method returns the title in HTML-Code
    *
    * @return string $this->_title as HMTL
    *
    * @author CommSy Development Group
    */
   function _getTitleAsHTML() {
      if ( !empty($this->_title) ) {
         $html = '         <b>'.$this->_title.'</b>'."\n";
      } else {
         $html = '';
      }
      return $html;
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {

      $description = NULL;

      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;

      if ($this->_with_description) {
         if ( isset($this->_description) ) {
            $description = $this->_description;
         } elseif ($this->_has_content) {
            // determine a meaningful description
            if ( $count_all == 0 ) {
               $description = $this->_translator->getMessage('COMMON_NO_ENTRIES');
            } elseif ( $count_all == 1 ) {
               $description = $this->_translator->getMessage('COMMON_ONE_ENTRY');
            } elseif ( $count_all <= $interval ) {
               $description = $this->_translator->getMessage('COMMON_X_ENTRIES', $count_all);
            } elseif ( $from == $count_all){
               $description = $this->_translator->getMessage('COMMON_X_FROM_Z', $count_all);
            } else {
               if ( $from + $interval -1 <= $count_all ) {
                  $to = $from + $interval - 1;
               } else {
                  $to = $count_all;
               }
               $description = $this->_translator->getMessage('COMMON_X_TO_Y_FROM_Z', $from, $to, $count_all);
            }
         }
      }
      if (isset($description)) {
         $html = '         <span class="small">('.$description.')</span>'."\n";
      } else {
         $html = '';
      }
      return $html;
   }

   /** get the browsing icons of the list view as HTML
    * this method returns the browsing icons in HTML-Code
    *
    * @return string browsing icons as HMTL
    *
    * @author CommSy Development Group
    */
   function _getBrowsingIconsAsHTML() {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;

      // prepare browsing
      if ( $from > 1 ) {        // can I browse to the left / start?
         $browse_left = $from - $interval;
         if ($browse_left < 1) {
            $browse_left = 1;
         }
         $browse_start = 1;
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $from + $interval <= $count_all ) {  // can I browse to the right / end?
         $browse_right = $from + $interval;
         $browse_end = $count_all - $interval + 1;
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing icons
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $params['viewname'] = $this->_view_name;
      $params = array_merge($params,$this->getExtraOptionArray());
      if ( $browse_start > 0 ) {
         $image = '<img src="images/browse_start3.gif" alt="&lt;&lt;" border="0" height="16">';
         $params[$this->_link_id.$this->_link_name.'_from'] = $browse_start;
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),'',$this->getFragment());
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_start_grey3.gif" alt="&lt;&lt;" border="0" height="16"></span>';
      }
      if ( $browse_left > 0 ) {
         $image = '<img src="images/browse_left3.gif" alt="&lt;" border="0" height="16">';
         $params[$this->_link_id.$this->_link_name.'_from'] = $browse_left;
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),'',$this->getFragment());
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_left_grey3.gif" alt="&lt;" border="0" height="16"></span>';
      }
      if ( $browse_right > 0 ) {
         $image = '<img src="images/browse_right3.gif" alt="&gt;" border="0" height="16">';
         $params[$this->_link_id.$this->_link_name.'_from'] = $browse_right;
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),'',$this->getFragment());
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_right_grey3.gif" alt="&gt;" border="0" height="16"></span>';
      }
      if ( $browse_end > 0 ) {
         $image = '<img src="images/browse_end3.gif" alt="&gt;&gt;" border="0" height="16">';
         $params[$this->_link_id.$this->_link_name.'_from'] = $browse_end;
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),'',$this->getFragment());
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_end_grey3.gif" alt="&gt;&gt;" border="0" height="16"></span>';
      }
      unset($params);
      return $html;
   }

   /** get the browsing buttons of the list view as HTML
    * this method returns the browsing buttons in HTML-Code
    *
    * @return string browsing buttons as HMTL
    *
    * @author CommSy Development Group
    */
   function _getBrowsingButtonsAsHTML() {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;

      // prepare browsing
      if ( $from > 1 ) {        // can I browse to the left / start?
         $browse_left = $from - $interval;
         if ($browse_left < 1) {
            $browse_left = 1;
         }
         $browse_start = 1;
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $from + $interval <= $count_all ) {  // can I browse to the right / end?
         $browse_right = $from + $interval;
         $browse_end = $count_all - $interval + 1;
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing icons
      $html = '';
      if ( $browse_start > 0 ) {
         $html .= '<input type="image" name="'.$this->_name.'_start" src="images/browse_start3.gif" border="0" height="16">';
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_start_grey3.gif" TITLE="&lt;&lt;" border="0" height="16"></span>';
      }
      $html .= '<input type="hidden" name="browse_start" value="'.$browse_start.'">'."\n";
      if ( $browse_left > 0 ) {
         $html .= '<input type="image" name="'.$this->_name.'_left" src="images/browse_left3.gif"  border="0" height="16">';
      } else {
         $html .= '         <span class="disabled"><img  src="images/browse_left_grey3.gif" TITLE="&lt;" border="0" height="16"></span>';
      }
      $html .= '<input type="hidden" name="browse_right" value="'.$browse_right.'">'."\n";
      if ( $browse_right > 0 ) {
         $html .= '<input type="image" name="'.$this->_name.'_right" src="images/browse_right3.gif" border="0" height="16">';
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_right_grey3.gif" TITLE="&gt;" border="0" height="16"></span>';
      }
      $html .= '<input type="hidden" name="browse_left" value="'.$browse_left.'">'."\n";
      if ( $browse_end > 0 ) {
         $html .= '<input type="image" name="'.$this->_name.'_end" src="images/browse_end3.gif" border="0" height="16">';
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_end_grey3.gif" TITLE="&gt;&gt;" border="0" height="16"></span>';
      }
      $html .= '<input type="hidden" name="browse_end" value="'.$browse_end.'">'."\n";
      return $html;
   }


   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getContentAsHTML() {

      $html ='';
      if ( $this->hasContent() ) {
         $list = $this->_list;
         if ( isset($list)) {
            $current_item = $list->getFirst();
            if ( empty($current_item) ){
               if (isset($this->_assigned_list_of)) {
                  $params = array();
                  $params['iid'] = $this->_assigned_list_of['iid'];
                  $link_text = ahref_curl( $this->_environment->getCurrentContextID(),
                                           $this->_assigned_list_of['type'],
                                           'detail',
                                           $params,
                                           $this->_assigned_list_of['title']);
                  unset($params);
                  $text = $this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').'&nbsp;'.$link_text;
                  $html .= '                  <table class="list_view_item" summary="Layout">'."\n";
                  $html .= '                     <tr><td width="100%" class="list_view_seperator">'."\n";
                  $html .= '                        '.$text."\n";
                  $html .= '                     </td>'."\n";
                  $html .= '                  </table>'."\n";
                  $html .= '         <table class="list_view_item" summary="Layout">'."\n";
                  $html .= '            <tr><td class="list_view_entry">'."\n";
                  $html .= $this->_translator->getMessage('COMMON_NO_CURRENT_CONTENT');
                  $html .= '            </td></tr>'."\n";
                  $html .= '         </table>'."\n";
                } else {
                  if ($this->_getStylesheetName()=='clipboard'){
                     $html  = '         <table class="clipboard_view_item" summary="Layout">'."\n";
                     $html .= '            <tr><td class="clipboard_view_entry">'."\n";
                  } else {
                     $html  = '         <table class="list_view_item" summary="Layout">'."\n";
                     $html .= '            <tr><td class="list_view_entry">'."\n";
                  }
                  $html .= $this->_translator->getMessage('COMMON_NO_CURRENT_CONTENT');
                  $html .= '            </td></tr>'."\n";
                  $html .= '         </table>'."\n";
               }
            } else {
               $html = '';
               if (isset($this->_assigned_list_of)){
                  $params = array();
                  $params['iid'] = $this->_assigned_list_of['iid'];
                  $link_text = ahref_curl( $this->_environment->getCurrentContextID(),
                                           $this->_assigned_list_of['type'],
                                           'detail',
                                           $params,
                                           $this->_assigned_list_of['title']);
                  unset($params);
                  $text = $this->_translator->getMessage('ASSIGNED_ITEMS_LISTVIEW_SEPERATOR').'&nbsp;'.$link_text;
                $html .= '                  <table class="list_view_item" summary="Layout">'."\n";
               $html .= '                     <tr><td width="100%" class="list_view_seperator">'."\n";
               $html .= '                        '.$text."\n";
               $html .= '                     </td>'."\n";
               $html .= '                  </table>'."\n";
         }
               while ( $current_item ) {
                  $item_text = $this->_getItemAsHTML($current_item);
                  $html .= $item_text;
                  $current_item = $list->getNext();
               }
            }
         }
      }
      return $html;
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item) {
   //must be overwritten
   }

   /** get the modificator-name of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modificator of an item in a standardized
    * manner.
    *
    * @return string modificator_fullname
    *
    * @author CommSy Development Group
    */
   function _getModificator($item){
      $modificator = $item->getModificatorItem();
      $fullname = $modificator->getFullName();
      $fullname = $this->_compareWithSearchText($fullname);
      $fullname = '<span class="list_view_description">'.
                   $this->_translator->getMessage('COMMON_ENTERED_BY').
                   ':</span> '.
                   $fullname;
      return $fullname;
   }

   /** get the modification-date of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modification date of an item in a
    * standardized manner.
    *
    * @return string modification_date
    *
    * @author CommSy Development Group
    */
   function _getModificationDate($item){
      if ( $item->getCreationDate() <> $item->getModificationDate() ) {
         $mod_date = $this->_translator->getDateInLang($item->getModificationDate());
      } else {
         $mod_date = $this->_translator->getDateInLang($item->getCreationDate());
      }
      $mod_date = $this->_compareWithSearchText($mod_date);
      $mod_date = '<span class="list_view_description">'.
                   $this->_translator->getMessage('COMMON_MODIFIED_LIST').
                   ':</span>&nbsp;'.
                   $this->_text_as_html_short($mod_date);
      return $mod_date;
   }

   /** get the whole navigation of the list view as HTML
    * this method returns the navigation in HTML-Code
    *
    * @return string navigation as HMTL
    *
    * @author CommSy Development Group
    */
   function _getNavigationAsHTML() {
      $action_text = $this->_getActionsAsHTML();
      $html = $action_text;
      if ( $this->_has_sort_box ){
         $sort_box_text = $this->_getSortBoxAsHTML();
         $html .= $sort_box_text;
      }
      if ( $this->_has_search_box ){
         $search_box_text = $this->_getSearchBoxAsHTML();
         $html .= $search_box_text;
      }
      $text = CS_WITDH_CONSTANT;
      $html .= '<br />'.$text;
      return $html;
   }

    /** get the action links of the list view as HTML
    * this method returns the action links in HTML-Code
    *
    * @return string actions as HMTL
    *
    * @author CommSy Development Group
    */
   function _getActionsAsHTML(){
      $html='';
      if ( isset($this->_action_title) ){
         $html = '<b>'.$this->_action_title.'</b><br />';
      }
      if ( isset($this->_actions) ){
         foreach($this->_actions as $key => $value){
            if ($this->_with_navigation){
                $html .= '- '.$value.'<br />'."\n";
            }else{
                $html .= $value.' '."\n";
            }
         }
      }
      if (!empty($html)) {
         $html .= '<br />'."\n";
      }
      return $html;
   }

   /** get the sort box of the list view as HTML
    * this method returns the sort box in HTML-Code
    *
    * @return string sort box as HMTL
    *
    * @author CommSy Development Group
    */
   function _getSortBoxAsHTML(){
      if ( isset($this->_sort_keys) ) {
         $html = '         <b>'.$this->_translator->getMessage('COMMON_SORT_BOX').'</b><br />';

         $html .= '            &nbsp;<select name="'.$this->_link_name.'_sortby" size="1"> '."\n";

         asort($this->_sort_keys); // sort array
         foreach($this->_sort_keys as $key => $value){
            $html .= '               <option value="'.$key.'"';
            if ( $key == $this->_sort_key ) {
               $html .= ' selected';
            }
            $html .= '>'.$value.'</option>'."\n";
         }
         $html .= '            </select> '."\n";
         $html .= '            <input type="submit" name="'.$this->_link_name.'_sortbox" value="'.$this->_translator->getMessage('COMMON_SORT_BUTTON').'">'."\n";
         $html .= $this->getExtraOptionsAsForm();
      } else {
         $html  = '';
      }
      return $html;
   }

   /** get the search box of the list view as HTML
    * this method returns the search box in HTML-Code
    *
    * @return string search box as HMTL
    *
    * @author CommSy Development Group
    */
   function _getSearchBoxAsHTML(){
      $this->setWithForm();
      if ($this->_has_sort_box){
         $html = '<br /><br />';
      }else{
         $html='';
      }
      if ( !isset($this->_search_text) ) {
         $value = '';
      } else {
         $value = $this->_search_text;
      }
      $html .= '         <b>'.$this->_translator->getMessage('COMMON_SEARCH_BOX').'</b><br />';
      $html .= '            &nbsp;<input type="text" name="'.$this->_link_name.'_search" value="'.$value.'" maxlength="255" size="10">'."\n";
      $html .= '            <input type="submit" name="'.$this->_link_name.'_searchbox" value="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'">'."\n";
      if (!empty($value)) {
         $params = array();
         $params = $this->getExtraOptionArray();
         $params[$this->_link_id.$this->_link_name.'_reset'] = true;
         $reset_link = '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $this->_translator->getMessage('COMMON_RESET_SEARCH'), $this->_translator->getMessage('COMMON_RESET_SEARCH_DESC',$value),'',$this->getFragment());
         unset($params);
      } else {
         $reset_link = '&nbsp;<span class="disabled">'.$this->_translator->getMessage('COMMON_RESET_SEARCH').'</span>';
      }
      $html .= '<br />'.$reset_link;
      $html .= $this->getExtraOptionsAsForm();
      return $html;
   }

   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    *
    * @author CommSy Development Group
    */
   function _compareWithSearchText($value){

      if ( !empty($this->_search_text) ){
          $search_text = htmlspecialchars($this->_search_text, ENT_NOQUOTES, 'UTF-8');
         if ( mb_stristr($value,$search_text) ) {

            if ( $search_text == $this->_search_text) {
               $value = preg_replace('~((?!&.{0,5})('.preg_quote($search_text,'/').')(?!.{0,5};))~iu',"<b>\\2</b>",$value);
            } else {
               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu',"<b>\\0</b>",$value);
            }

         }
      }
      return $value;
   }

   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    *
    * @author CommSy Development Group
    */
   function _getChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $reader_manager = $this->_environment->getReaderManager();
         $reader = $reader_manager->getLatestReader($item->getItemID());
         if ( empty($reader) ) {
            $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
      } else {
         $info_text = '';
      }
      return $info_text;
   }

   /** return a text indicating the modification state of an item
    * this method returns a string like [new] or [modified] depending
    * on the read state of the current user.
    *
    * @param  object item       a CommSy item (cs_item)
    *
    * @return string value
    *
    * @author CommSy Development Group
    */
   function _getAnnotationChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $reader_manager = $this->_environment->getReaderManager();
       $annotation_list = $item->getAnnotationList();
       $anno_item = $annotation_list->getFirst();
       $new = false;
       $changed = false;
       $date = "0000-00-00 00:00:00";
       while ( $anno_item ) {
            $reader = $reader_manager->getLatestReader($anno_item->getItemID());
            if ( empty($reader) ) {
             if ($date < $anno_item->getModificationDate() ) {
                   $new = true;
                   $changed = false;
                   $date = $anno_item->getModificationDate();
             }
            } elseif ( $reader['read_date'] < $anno_item->getModificationDate() ) {
             if ($date < $anno_item->getModificationDate() ) {
                   $new = false;
                   $changed = true;
                   $date = $anno_item->getModificationDate();
             }
            }
            $anno_item = $annotation_list->getNext();
       }
       if ( $new ) {
               $info_text =' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
       } else if ( $changed ) {
               $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED_ANNOTATION').']</span>';
       } else {
            $info_text = '';
       }
      } else {
         $info_text = '';
      }
      return $info_text;
   }
}


?>
