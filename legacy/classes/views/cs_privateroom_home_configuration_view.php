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

$this->includeClass(VIEW);
include_once('functions/date_functions.php');
include_once('classes/cs_link.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_privateroom_home_configuration_view extends cs_view {

var  $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('HOME_EXTRA_TOOLS');
      $this->setViewName('configuration');
   }

   function asHTML () {
     $current_context = $this->_environment->getCurrentContextItem();
     $html  = '';
     $html .= '<div id="'.get_class($this).'">'.LF;
     $html .= '<table>'.LF;
     if (( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() ) or ( $current_context->showChatLink() )){
        if ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() ) {
           global $c_pmwiki_path_url;
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/pmwiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/pmwiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
           }
           $title = $this->_translator->getMessage('COMMON_WIKI_LINK').': '.$current_context->getWikiTitle();
           $url_session_id = '';
           if ( $current_context->withWikiUseCommSyLogin() ) {
              $session_item = $this->_environment->getSessionItem();
              $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
              unset($session_item);
           }
           $html .= '<tr>'.LF;
           $html .= '<td>'.'<a title="'.$title.'" href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a></td>'.LF;
           $html .= '<td>(<a href="http://localhost/commsy/htdocs/commsy.php?cid='.$current_context->getItemID().'&amp;mod=configuration&amp;fct=wiki">'.$this->_translator->getMessage('COMMON_CONFIGURATION').'</a>)</td>'.LF;
           $html .= '</tr>'.LF;
        }
      }

      if (( $current_context->showWordpressLink() and $current_context->existWordpress() and $current_context->issetWordpressHomeLink() )){
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->showWordpressLink() and $current_context->existWordpress() and $current_context->issetWordpressHomeLink() ) {
         	$wordpress_path_url = $context_item->getWordpressUrl();
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/wordpress.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/wordpress.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'"/>';
            }
            $title = $this->_translator->getMessage('COMMON_WORDPRESS_LINK').': '.$current_context->getWikiTitle();
            $url_session_id = '';
            if ( $current_context->withWordpressUseCommSyLogin() ) {
               $session_item = $this->_environment->getSessionItem();
               $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
               unset($session_item);
            }
            $html .= '<tr>'.LF;
            $html .= '<td>'.'<a title="'.$title.'" href="'.$wordpress_path_url.'/'.$current_context->getContextID().'_'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a></td>'.LF;
            $html .= '<td>(<a href="http://localhost/commsy/htdocs/commsy.php?cid='.$current_context->getItemID().'&amp;mod=configuration&amp;fct=wordpress">'.$this->_translator->getMessage('COMMON_CONFIGURATION').'</a>)</td>';
            $html .= '</tr>'.LF;
         }
      }
      $html .= '</table>'.LF;

     $html .= '</div>';
     return $html;
   }

   #function getPreferencesAsHTML(){
   #   $html = '&nbsp;';
   #   return $html;
   #}
}
?>