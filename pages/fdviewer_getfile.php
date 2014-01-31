<?php
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

$disc_manager = $environment->getDiscManager();
if (!empty($_GET['file']) and $disc_manager->existsFile($_GET['file'])) {
   header('Content-type: application/x-shockwave-flash');
   header('Pragma: no-cache');
   header('Expires: 0');
   readfile($disc_manager->getFilePath().$_GET['file']);
} else if(!empty($_GET['file']) and withUmlaut($_GET['file'])) {
     $filename = rawurlencode($_GET['file']);
      if (file_exists($disc_manager->_getFilePath().$filename)) {
       header('Content-type: application/x-shockwave-flash');
       header('Pragma: no-cache');
       header('Expires: 0');
       readfile($disc_manager->getFilePath().$filename);
     }
  }
exit();
?>