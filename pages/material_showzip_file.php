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
if ( !empty($_GET['iid']) and !empty($_GET['file']) ) {

   $file_manager = $environment->getFileManager();
   $file = $file_manager->getItem($_GET['iid']);
   $_GET['file'] = str_replace(" ", "%20", $_GET['file']);
   $_GET['file'] = str_replace("ß", "%DF", $_GET['file']);
   $_GET['file'] = str_replace("ü", "%FC", $_GET['file']);
   $_GET['file'] = str_replace("ä", "%E4", $_GET['file']);
   $location = './var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/'.$_GET['file'];

   if ( file_exists($location) ) {
      $extension = strtolower(substr(strrchr($_GET['file'],"."),1));
      if ( $extension != 'html' and $extension != 'htm' ) {
         $mimetype = $file_manager->getMime($_GET['file']);
         header('Content-type: '.$mimetype.'');
         if ( $mimetype != 'image/gif' AND
              $mimetype != 'image/jpeg' AND
              $mimetype != 'image/png' AND
              $mimetype != 'text/css' AND
              $mimetype != 'application/x-javascript'
            ) {
            header('Content-Disposition: attachment; filename="'.$_GET['file'].'"');
         }
         readfile($location);
         exit();
      } else {
      	  $filecontent = file_get_contents($location);
         echo $filecontent;
         exit();
      }
   }
}
?>