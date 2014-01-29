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

include_once('classes/cs_page.php');

class cs_homepage_room_list_page extends cs_page {

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_homepage_room_list_page ($environment, $with_mod_actions) {
      $this->cs_page($environment, $with_mod_actions);
   }

   function _generateViewObject () {

     // Find current browsing starting point
     if ( isset($this->_values['from']) ) {
        $from = $_GET['from'];
     }  else {
        $from = 1;
     }

     // Find current browsing interval
     if ( isset($this->_values['interval']) ) {
       $interval = $this->_values['interval'];
     } else {
       $interval = CS_LIST_INTERVAL;
     }

     // get data
     $manager = $this->_environment->getHomepageManager();
     $manager->setIndexLimit();
     $count_all = $manager->getCountAll();

     if (!empty($this->_values['search'])) {
        $manager->setSearchLimit($this->_values['search']);
     }
     if (!empty($this->_values['sort'])) {
        $manager->setOrder($this->_values['sort']);
     } else {
        $manager->setOrder('activity');
     }
     if ( $interval > 0 ) {
        $manager->setIntervalLimit($from-1,$interval);
     }
     $manager->select();
     $list = $manager->get();
     $ids = $manager->getIDArray();       // returns an array of item ids
     $count_all_shown = count($ids);

      // Prepare view object
      $params = array();
      $params['environment'] = $this->_environment;
      $params['with_modifying_actions'] = $this->_with_mod_actions;
      $this->_view_object = $this->_class_factory->getClass(HOMEPAGE_LIST_VIEW,$params);
      unset($params);
      $this->_view_object->setList($list);
      $this->_view_object->setCountAllShown($count_all_shown);
      $this->_view_object->setCountAll($count_all);
      $this->_view_object->setFrom($from);
      $this->_view_object->setInterval($interval);
      if (!empty($this->_values['search'])) {
         $this->_view_object->setSearchText($this->_values['search']);
      }
      if (!empty($this->_values['sort'])) {
         $this->_view_object->setSortKey($this->_values['sort']);
      } else {
        $this->_view_object->setSortKey('activity');
      }
   }
}
?>