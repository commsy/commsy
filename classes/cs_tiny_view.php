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

include_once('classes/cs_view.php');
include_once('functions/text_functions.php');

/**
 *  generic upper class for CommSy list-views
 */
class cs_tiny_view extends cs_view {

   /**
    * string - containing the title of the tiny view
    */
   var $_title;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;

   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;

   /**
    * string - containing the description of the tiny view
    */
   var $_description;

   /**
    * array - containing the actions of the tiny view
    */
   var $_actions;

   var $_two_lines;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_tiny_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,
                      $with_modifying_actions);
      $this->_title = '';
      $this->_actions = array();
      $this->_two_lines = true;
      $this->setOneLine();
   }

   /** set title of the view
    * this method sets the title of the view
    *
    * @param string  $this->_title          title of the view
    *
    * @author CommSy Development Group
    */
    function setTitle ($value) {
       $this->_title = (string)$value;
    }

   /** set description of the view
    * this method sets the shown description of the view
    *
    * @param int  $this->_description          description
    *
    * @author CommSy Development Group
    */
    function setDescription ($description) {
       $this->_description = (string)$description;
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

    function setOneLine () {
       $this->_two_lines = false;
    }

    function setTwoLines () {
       $this->_two_lines = true;
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
    function getList (){
       return $this->_list;
    }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF TINY VIEW -->'.LF;
      $html .= '<table class="list" style="width:100%" summary="Layout">'.LF;
      $html .= '   <tr><td class="head">'.LF;
      $html .= '<span class="head">';
      $html .= $this->_title;
      $html .= '</span>';
      $desc = $this->_getDescriptionAsHTML();
      if ( !$this->_two_lines and !empty($desc) ) {
         $html .= ' <span class="desc" style="font-weight:bold;">('.$desc.')</span>'.LF;
      }
      $html .= '   </td><td class="head">'.LF;
      $html .= $this->_getActionsAsHTML();
      $html .= '   </td></tr>'.LF;
      if ( $this->_two_lines and !empty($desc) ) {
         $html .= '   <tr><td colspan="2">'.LF;
         $html .= $desc;
         $html .= '   </td></tr>'.LF;
      }
      $html .= '</table>'.BRLF;
      $html .= '<!-- END OF TINY VIEW -->'.LF.LF;
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
      $html = '';
      $first = true;
      if ( isset($this->_actions) ) {
         foreach($this->_actions as $key => $value) {
            if ( $first ) {
               $first = false;
            } else {
               $html .= '| ';
            }
            $html .= $value."\n";
         }
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
	  if (empty($this->_description)) {
         $all = $this->getCountAll();
         return '<span class="desc">'.$this->_translator->getMessage('COMMON_TINY_VIEW_DESCRIPTION',$all).'</span>';
	  } else {
		 return $this->_description;
	  }
   }

}
?>