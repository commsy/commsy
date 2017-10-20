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

/** class for links
 * this class implements a link object
 */
class cs_link {

  var $_link = NULL;

   /**
    * string - containing the type of the list resp. the type of the elements
    */
   var $_type = NULL;

   /**
    * array - containing the elements of the list
    */
   var $_data = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @author CommSy Development Group
    */
   function cs_link () {
      $this->_type = 'link';
   }

   /** is the type of the list = $type ?
    * this method returns a boolean expressing if type of the list is $type or not
    *
    * @param string type string to compare with type of list (_type)
    *
    * @return boolean   true - type of this list is $type
    *                   false - type of this list is not $type
    *
    * @author CommSy Development Group
    */
   function isA ($type) {
      return $this->_type == $type;
   }

   /** return the type of the object = link
    * this method returns the type of the object = link
    *
    * @return string type of the object link
    *
    * @author CommSy Development Group
    */
   function getType () {
      return $this->_type;
   }

   /** set the title of the link
    * this method sets the title of the link
    *
    * @param string title of the link
    *
    * @author CommSy Development Group
    */
   function setTitle ($value) {
      $this->_data['title'] = (string)$value;
   }

   /** return the title of the link
    * this method returns the title of the link
    *
    * @return string title of the link
    *
    * @author CommSy Development Group
    */
   function getTitle () {
      return $this->_getValue('title');
   }

   /** set the title of the link
    * this method sets the title of the link
    *
    * @param string title of the link
    *
    * @author CommSy Development Group
    */
   function setShortTitle ($value) {
      $this->_data['short_title'] = (string)$value;
   }

   /** return the short title of the link
    * this method returns the short title of the link
    *
    * @return string short title of the link
    *
    * @author CommSy Development Group
    */
   function getShortTitle () {
      if ( isset($this->_data['short_title']) and !empty($this->_data['short_title']) ){
         return $this->_getValue('short_title');
      }else{
         return $this->getTitle();
      }
   }


   /** set the description of the link
    * this method sets the description of the link
    *
    * @param string description of the link
    *
    * @author CommSy Development Group
    */
   function setDescription ($value) {
      $this->_data['desc'] = (string)$value;
   }

   /** return the description of the link
    * this method returns the description of the link
    *
    * @return string description of the link
    *
    * @author CommSy Development Group
    */
   function getDescription () {
      return $this->_getValue('desc');
   }

   /** sets the path to an icon for the item
    * @param string Path where the icon can be found
    */
   function setIconPath($icon_path) {
      $this->_data['icon_path'] = $icon_path;
   }

   /** sets the path to an icon for the item
   * @param string Path where the icon can be found
      *
      * @author CommSy Development Group
   */
   function getIconPath() {
      return $this->_getValue('icon_path');
   }

   /** sets the path to an icon for the item
    * @param string Path where the icon can be found
    */
   function setIconPathForNavigation($icon_path) {
      $this->_data['icon_path_navi'] = $icon_path;
   }

   /** sets the path to an icon for the item
    * @param string Path where the icon can be found
    */
   function getIconPathForNavigation() {
      return $this->_getValue('icon_path_navi');
   }

   /** set the context id of the link
    * this method sets the context id of the link
    *
    * @param string context id of the link
    */
   function setContextID ($value) {
      $this->_data['context_id'] = (int)$value;
   }

   /** return the context id of the link
    * this method returns the context id of the link
    *
    * @return string context id of the link
    */
   public function getContextID () {
      return $this->_getContextID();
   }

   /** return the context id of the link, INTERNAL
    * this method returns the context id of the link
    *
    * @return string context id of the link
    */
   function _getContextID () {
      return $this->_getValue('context_id');
   }

   /** set the module of the link
    * this method sets the module of the link
    *
    * @param string module of the link
    *
    * @author CommSy Development Group
    */
   function setModule ($value) {
      $this->_data['module'] = (string)$value;
   }

   /** return the module of the link, INTERNAL
    * this method returns the module of the link
    *
    * @return string module of the link
    *
    * @author CommSy Development Group
    */
   function _getModule () {
      return $this->_getValue('module');
   }

   function getModule () {
      return $this->_getModule();
   }

   /** set the function of the link
    * this method sets the function of the link
    *
    * @param string function of the link
    *
    * @author CommSy Development Group
    */
   function setFunction ($value) {
      $this->_data['function'] = (string)$value;
   }

   /** return the function of the link, INTERNAL
    * this method returns the function of the link
    *
    * @return string function of the link
    *
    * @author CommSy Development Group
    */
   function _getFunction () {
      return $this->_getValue('function');
   }

   function getFunction () {
      return $this->_getValue('function');
   }

   /** set the parameter of the link
    * this method sets the parameter of the link
    *
    * @param array parameter of the link
    *
    * @author CommSy Development Group
    */
   function setParameter ($value) {
      $this->_data['parameter'] = (array)$value;
   }

   /** return the parameter of the link, INTERNAL
    * this method returns the parameter of the link
    *
    * @return array parameter of the link
    *
    * @author CommSy Development Group
    */
   function _getParameter () {
      return $this->_getValue('parameter');
   }

   /** return the HTML-Hyperlink of the link
    * this method returns the link as HTML code
    *
    * @return string HTML-link
    *
    * @author CommSy Development Group
    */
   function getLink () {
      if (isset($this->_link)) {
        return "<a href='" . $this->_link . "'>" . $this->getTitle() . "</a>";
      }
      else {
        return ahref_curl($this->_getContextID(),$this->_getModule(),$this->_getFunction(),$this->_getParameter(),$this->getTitle());
      }
   }

   function getShortLink () {
      if (isset($this->_link)) {
        return "<a href='" . $this->_link . "'>" . $this->getShortTitle() . "</a>";
      }
      else {
        return ahref_curl($this->_getContextID(),$this->_getModule(),$this->_getFunction(),$this->_getParameter(),$this->getShortTitle());
      }
   }

   function setLink($value) {
      $this->_link = $value;
   }

    /** return the HTML-Hyperlink of the link as an icon
    * this method returns the link as HTML code as an icon
    *
    * @return string HTML-link
    *
    * @author CommSy Development Group
    */
   function getLinkIcon ($height='') {
      if (empty($height)){
         return ahref_curl($this->_getContextID(),$this->_getModule(),$this->_getFunction(),$this->_getParameter(),$this->getIcon());
      }else{
          return ahref_curl($this->_getContextID(),$this->_getModule(),$this->_getFunction(),$this->_getParameter(),$this->getIcon($height));
     }
   }

   function getIcon ($height='') {
      if (empty($height)){
         $retour = '<img style="float:left;" src="'.$this->getIconPath().'" alt="" border="0"/>';
      }else{
         $retour = '<img style="float:left; height:'.$height.'px;" src="'.$this->getIconPath().'" alt="" border="0"/>';
      }
      return $retour;
   }

   /** return a value of the link, INTERNAL
    * this method returns a value of the link
    *
    * @return string a value the link
    *
    * @author CommSy Development Group
    */
   function _getValue ($key) {
      if (!empty($this->_data[$key])) {
         $value = $this->_data[$key];
      } else {
         $value = '';
      }
      return $value;
   }


}
?>