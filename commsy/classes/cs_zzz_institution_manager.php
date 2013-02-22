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

/** upper class of the zzz_institution_manager
 */
include_once('classes/cs_institution_manager.php');

/** class for database connection to the database table "zzz_institution"
 * this class implements a database manager for the table "zzz_institution"
 */
class cs_zzz_institution_manager extends cs_institution_manager {
   public function __construct ($environment) {
      $this->_db_prefix = $environment->getConfiguration('c_db_backup_prefix').'_';
      parent::cs_institution_manager($environment);
   }
}
?>