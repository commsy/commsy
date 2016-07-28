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

class cs_html_textarea {

   function getAsHTML ($name,$value='',$hsize='',$html_status=1,$tabindex='',$vsize='',$no_discussion = true) {

      // some configurations
      global $c_commsy_url_path;
      if ( mb_strlen($c_commsy_url_path) > 1 and mb_substr($c_commsy_url_path,mb_strlen($c_commsy_url_path)-1) == '/') {
         $c_commsy_url_path = mb_substr($c_commsy_url_path,0,mb_strlen($c_commsy_url_path)-1);
      }
      global $c_fckeditor_url_path;
      if ( mb_strlen($c_fckeditor_url_path) > 1 and mb_substr($c_fckeditor_url_path,mb_strlen($c_fckeditor_url_path)-1) == '/') {
         $c_fckeditor_url_path = mb_substr($c_fckeditor_url_path,0,mb_strlen($c_fckeditor_url_path)-1);
      }
      global $c_fckeditor_file_path;
      if ( mb_strlen($c_fckeditor_file_path) > 1 and mb_substr($c_fckeditor_file_path,mb_strlen($c_fckeditor_file_path)-1) == '/') {
         $c_fckeditor_file_path = mb_substr($c_fckeditor_file_path,0,mb_strlen($c_fckeditor_file_path)-1);
      }

      // value translations
      $value = str_replace('<!-- KFC TEXT -->','',$value);
      $temp_text = $value;

      // security KFC
      $values = array();
      preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u',$value,$values);
      if ( !empty($values[1]) ) {
         $hash = $values[1];
         $temp_text = str_replace('<!-- KFC TEXT '.$hash.' -->','',$value);
      } elseif ( !strstr($value,'<!-- KFC TEXT') ) {
         include_once('functions/security_functions.php');
         $value = '<!-- KFC TEXT '.getSecurityHash($value).' -->'.$value.'<!-- KFC TEXT '.getSecurityHash($value).' -->';
      }

      // this is for migration of texts not insert with FCKeditor
      $value = str_replace("\n\n",'<br/><br/>',$value);

      // now the fckeditor object
      include_once($c_fckeditor_file_path.'/fckeditor.php') ;
      $oFCKeditor = new FCKeditor($name);
      $oFCKeditor->BasePath = $c_fckeditor_url_path.'/';
      $oFCKeditor->Config["CustomConfigurationsPath"] = $c_commsy_url_path.'/javascript/CommSyFCKEditorConfig.js';
      $oFCKeditor->Value    = $value;

      global $environment;
      $current_browser = strtolower($environment->getCurrentBrowser());
      $current_browser_version = $environment->getCurrentBrowserVersion();
      $context_item = $environment->getCurrentContextItem();
      $oFCKeditor->Width = '98%';
      $oFCKeditor->Height   = round($hsize*13.5,0);
      $oFCKeditor->TabIndex = $tabindex;
      if ( $html_status == '2' ) {
         $oFCKeditor->ToolbarSet = 'MinCommSy';
      } elseif ($html_status == 'homepage') {
         $oFCKeditor->ToolbarSet = 'homepage';
      } else {
         $oFCKeditor->ToolbarSet = 'CommSy';
      }
      $retour = $oFCKeditor->CreateHtml().LF;

      // security KFC
      $hidden_value = str_replace('"','COMMSY_QUOT',$temp_text);
      $hidden_value = str_replace('&','COMMSY_AMPERSEND',$hidden_value);
      $retour .= '<input type="hidden" name="'.$name.'_fck_hidden" value="'.$hidden_value.'" />';

      return LF.$retour.LF;
   }
}
?>