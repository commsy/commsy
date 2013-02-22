<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

if ( !empty($plugin_module)
     and !empty($plugin_function)
   ) {
   $file = 'plugins/'.$plugin_module.'/commsy_'.$plugin_function.'.php';
   if ( file_exists($file) ) {
      include_once($file);
   } else {
      include_once('functions/error_functions.php');
      trigger_error('plugin page does not exits',E_USER_ERROR);
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('plugin name or plugin fuction is empty',E_USER_WARNING);
}
?>