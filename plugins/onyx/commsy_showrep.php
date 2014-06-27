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

header("Content-Type: text/html; charset=utf-8");
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
   $show = false;
   $url = '';
   
   // via onyx url
   if ( !empty($_GET['url']) ) {
      
      // check security
      $session_item = $environment->getSessionItem();
      if ( isset($session_item)
           and $session_item->issetValue('onyx_reporter_url_array')
      	) {
      	$onyx_reporter_url_array = $session_item->getValue('onyx_reporter_url_array');
      	if ( in_array(rawurldecode($_GET['url']),$onyx_reporter_url_array) ) {
      		$show = true;
      		$url = rawurldecode($_GET['url']);
      	}
      }
   }
   
   // via file-id
   elseif ( !empty($_GET['fid']) ) {
   	
      $choice = 4;   	
      if ( !empty($_GET['choice']) ) {
         $choice = $_GET['choice'];
      }
      
      $plugin_class = $environment->getPluginClass('onyx');
      $url = $plugin_class->getReporterUrlByFileID($_GET['fid'], $choice);
      if ( !empty($url) ) {
      	$show = true;
      }

      // get url
      if ( !empty($file_item)
           and !empty($file_array)
      	) {
      	$plugin_class = $environment->getPluginClass('onyx');
      	$url = $plugin_class->getReporterUrl($file, $file_array, $choice);
      	if ( !empty($url) ) {
            $show = true;
      	}
      }
   }
   
   else {
      echo($translator->getMessage('ONYX_ERROR_URL_LOST'));
   } 

   // show
   if ( $show
        and !empty($url)
      ) {
   	echo('<div><iframe class="onyx" src="'.$url.'"></iframe></div>');
   } elseif ( !empty($_GET['fid']) ) {
      echo($translator->getMessage('ONYX_REPORTER_NO_RESULTS'));
   } else {
   	echo($translator->getMessage('ONYX_ERROR_URL_SECURITY'));
   }
    
?>
   </body>
</html>
<?php
   exit(); 
?>