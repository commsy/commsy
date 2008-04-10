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

include_once('classes/cs_index_view.php');
//include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_campus_index_view extends cs_index_view {

   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_campus_index_view ($environment, $with_modifying_actions) {
      $this->cs_index_view($environment, $with_modifying_actions);
   }

   // @segment-begin 97963  _getGetParamsAsArray()-used-in-cs_announcement_index_view.php
   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['selinstitution'] = $this->getSelectedInstitution();
      $params['seltopic'] = $this->getSelectedTopic();
      return $params;
   }
   // @segment-end 97963

}
?>