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

/** upper class of the zzz_item manager
 */
include_once('classes/cs_item_manager.php');

/** class for database connection to the database table "zzz_item"
 * this class implements a database manager for the table "zzz_item"
 */
class cs_zzz_item_manager extends cs_item_manager {
   public function __construct ($environment) {
      global $symfonyContainer;
      $this->_db_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix').'_';

      parent::__construct($environment);
   }

   public function getItem ($iid, $vid = NULL) {
      $retour = parent::getItem($iid);
      if ( !isset($retour) ) {
         $item_manager = $this->_environment->getItemManager(true);
         $retour = $item_manager->getItem($iid);
         unset($item_manager);
      }
      return $retour;
   }
}
?>