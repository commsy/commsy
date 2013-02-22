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

if ( !empty($_GET['picture'])
     and file_exists(rawurldecode($_GET['picture']))
   ) {
   header('Content-type: image/gif');
   $im = imagecreatefromgif(rawurldecode($_GET['picture']));
   if ( $im
        and function_exists('imagefilter')
        and imagefilter($im, IMG_FILTER_GRAYSCALE)
      ) {
      imagegif($im);
      imagedestroy($im);
   } else {
      readfile(rawurldecode($_GET['picture']));
   }
}
exit();
?>