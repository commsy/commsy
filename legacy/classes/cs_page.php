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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_page {

   var $_environment = NULL;

   public $_class_factory = NULL;

   var $_with_mod_actions = true;

   var $_view_object = NULL;

   var $_values = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    * @param boolean with_mod_actions
    */
   function __construct($environment, $with_mod_actions) {
      $this->_environment = $environment;
      $this->_class_factory = $this->_environment->getClassFactory();
      $this->_with_mod_actions = $with_mod_actions;
      $this->_values = $this->_environment->getCurrentParameterArray();
      $post_array = $this->_environment->getCurrentPostParameterArray();
      if ( !empty($post_array['activitymodus']) ) {
         $this->_values['activitymodus'] = $post_array['activitymodus'];
      }
      /*HOT-FIX: Suchbegriff über die $_GET, weil die andere Methode einen falschen Wert liefert
      TBD: Klären, warum $this->_environment->getCurrentParameterArray() nicht auf der $_GET arbeitet*/
      if (!empty($_GET['search'])) {
         $this->_values['search'] = $_GET['search'];
      }
   }

   function getViewObject () {
      $this->_generateViewObject();
      return $this->_view_object;
   }
}
?>