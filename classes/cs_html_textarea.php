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
      $value = '<!-- KFC TEXT -->'.$value.'<!-- KFC TEXT -->';
      // this is for migration of texts not insert with FCKeditor
      $value = str_replace("\n\n",'<br/><br/>',$value);

      // now the fckeditor object
      include_once($c_fckeditor_file_path.'/fckeditor.php') ;
      $oFCKeditor = new FCKeditor($name);
      $oFCKeditor->BasePath = $c_fckeditor_url_path.'/';
      $oFCKeditor->Config["CustomConfigurationsPath"] = $c_commsy_url_path.'/javascript/CommSyFCKEditorConfig.js';
      $oFCKeditor->Value    = $value;

      // TBD
      // das wird doch eh unten überschrieben
      // kann das nicht weg?
      // ij 06.03.2009
      if ( $no_discussion ) {
         if ( empty($vsize) ) {
            $oFCKeditor->Width   = '504px';
         } else {
            $oFCKeditor->Width   = (string)round($vsize*8.5,0).'px';
         }
      }
      // TBD

      global $environment;
      $current_browser = strtolower($environment->getCurrentBrowser());
      $current_browser_version = $environment->getCurrentBrowserVersion();
      $context_item = $environment->getCurrentContextItem();
      if ( !$context_item->isDesign7()
           and $current_browser == 'msie'
           and ( strstr($current_browser_version,'5.')
                 or strstr($current_browser_version,'6.')
                 or strstr($current_browser_version,'7.')
               )
         ) {
         $oFCKeditor->Width = '504px';
      } else {
         $oFCKeditor->Width = '98%';
      }
      $oFCKeditor->Height   = round($hsize*13.5,0);
      $oFCKeditor->TabIndex = $tabindex;
      if ( $html_status == '2' ) {
         $oFCKeditor->ToolbarSet = 'MinCommSy';
      } elseif ($html_status == 'homepage') {
         $oFCKeditor->ToolbarSet = 'homepage';
      } else {
         $oFCKeditor->ToolbarSet = 'CommSy';
      }
      $retour = $oFCKeditor->CreateHtml() ;
      return LF.$retour.LF;
   }
}
?>