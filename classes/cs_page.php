<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

   var $_with_mod_actions = true;

   var $_view_object = NULL;

   var $_values = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    * @param boolean with_mod_actions
    */
   function cs_page ($environment, $with_mod_actions) {
      $this->_environment = $environment;
      $this->_with_mod_actions = $with_mod_actions;
      $this->_values = $this->_environment->getCurrentParameterArray();
      /*HOT-FIX: Suchbegriff �ber die $_GET, weil die andere Methode einen falschen Wert liefert
      TBD: Kl�ren, warum $this->_environment->getCurrentParameterArray() nicht auf der $_GET arbeitet*/
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