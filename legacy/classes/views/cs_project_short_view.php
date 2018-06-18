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

$this->includeClass(CONTEXT_SHORT_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_project_short_view extends cs_context_short_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_context_short_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_PROJECT_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_PROJECT_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
      $this->_room_type = CS_PROJECT_TYPE;
      $manager = $this->_environment->getProjectManager();
      if ($this->_environment->inCommunityRoom()) {
         $manager->setContextLimit($this->_environment->getCurrentPortalID());
      }
      global $c_cache_cr_pr;
      if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
         $this->_max_activity = $manager->getMaxActivityPointsInCommunityRoom($this->_environment->getCurrentContextID());
      } else {
         $current_context_item = $this->_environment->getCurrentContextItem();
         $this->_max_activity = $manager->getMaxActivityPointsInCommunityRoomInternal($current_context_item->getInternalProjectIDArray());
         unset($current_context_item);
      }
   }
}
?>