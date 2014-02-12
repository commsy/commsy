<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2011 Dr. Iver Jackewitz
//
// This file is part of the onyx plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.
?>
<html>
   <head>
   <style type="text/css">
   <!--
      iframe.onyx {
         width: 100%;
         height: 100%;
         border: 0px;
      }
   -->
   </style>
   </head>
   <body>
<?php
   if ( !empty($_GET['iid']) ) {
      $onyx_class = $environment->getPluginClass('onyx');
      $player_url_run = '';
      
      // via file-id
      if ( is_numeric($_GET['iid']) ) {
         $player_url_run = $onyx_class->getPlayerRunUrlByFileID($_GET['iid'],$_GET['params']);
      }
      
      // old style
      else {
         $player_url_run = $onyx_class->getPlayerRunUrl().'?id='.$_GET['iid'];
      }
      if ( !empty($player_url_run) ) {
         echo('<div><iframe class="onyx" src="'.$player_url_run.'"></iframe></div>');
      } else {
      	echo($translator->getMessage('ONYX_ERROR_URL_LOST_PLAYER'));
      }
   } else {
      echo($translator->getMessage('ONYX_ERROR_ID_LOST'));
   } 
?>
   </body>
</html>
<?php
   exit(); 
?>