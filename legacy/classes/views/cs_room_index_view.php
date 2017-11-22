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

$this->includeClass(INDEX_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_room_index_view extends cs_index_view {

   var $_selected_group = NULL;
   var $_available_groups = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_index_view::__construct($params);
   }

   function setSelectedGroup ($group_id) {
      $this->_selected_group = (int)$group_id;
   }

   function getSelectedGroup () {
      return $this->_selected_group;
   }

   function setAvailableGroups ($group_list) {
      $this->_available_groups = $group_list;
   }

   function getAvailableGroups () {
      return $this->_available_groups;
   }

   function setSelectedTopic ($topic_id) {
      $this->_selected_topic = (int)$topic_id;
   }

   function getSelectedTopic () {
      return $this->_selected_topic;
   }

   function setAvailableTopics ($topic_list) {
      $this->_available_topics = $topic_list;
   }

   function getAvailableTopics () {
      return $this->_available_topics;
   }

   function setSelectedInsitution ($institution_id) {
      $this->_selected_institution = (int)$institution_id;
   }

   function getSelectedInsitution () {
      return $this->_selected_institution;
   }

   function setAvailableInsitutions ($institution_list) {
      $this->_available_institutions = $institution_list;
   }

   function getAvailableInsitutions () {
      return $this->_available_institutions;
   }

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['selgroup'] = $this->getSelectedGroup();
      $params['seltopic'] = $this->getSelectedTopic();
     if ($this->_environment->inCommunityRoom()) {
         $params['selinstitution'] = $this->getSelectedInstitution();
     }
      return $params;
   }


}
?>