<?php
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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



if ( !empty($_GET['iid']) ) {


   $file_manager = $environment->getFileManager();
   $file = $file_manager->getItem($_GET['iid']);
   if((file_exists('./var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/index.htm')) and empty($_GET['file'])) {
   		$filename = 'index.htm';
   } elseif((file_exists('./var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/index.html')) and empty($_GET['file'])) {
   		$filename = 'index.html';
   } else {
   		$filename = $_GET['file'];
   }
   if(file_exists('./var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/'.$filename)) {
	   	    $filecontent = file_get_contents('./var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/'.$filename);

	    	echo $filecontent;
                 exit;
	   //include('./var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/'.$filename);
   } else {
   		include_once('functions/error_functions.php');trigger_error("material_showzip: File not found!", E_USER_ERROR);
   }
   unset($iid);
} else {
   include_once('functions/error_functions.php');trigger_error("material_showzip: Have no valid Item ID", E_USER_ERROR);
}

?>