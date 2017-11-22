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
class cs_home_extension_view extends cs_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('HOME_EXTRA_TOOLS');
      $this->setViewName('homeextratools');
   }

   function asHTML () {
     $html  = '';

     $current_context = $this->_environment->getCurrentContextItem();
     $html  = '';
     $html .= '<div class="right_box">'.LF;
     $html .= '         <noscript>';
     $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('HOME_EXTRA_TOOLS').'</div>';
     $html .= '         </noscript>';
     $html .= '<div class="right_box_main" style="font-size:10pt; padding-top:2px;padding-bottom:3px; padding-left:0px;">'.LF;
     $current_context = $this->_environment->getCurrentContextItem();
     if ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() ) {
        global $c_pmwiki_path_url;
         $url_session_id = '';
         if ( $current_context->withWikiUseCommSyLogin() ) {
            $session_item = $this->_environment->getSessionItem();
            $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
            unset($session_item);
         }
        $title = '<span> <a href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$current_context->getWikiTitle().'</a> ('.$this->_translator->getMessage('COMMON_WIKI_LINK').')</span>';
        $link_item = new cs_link();
        $link_item->setIconPath('images/cs_config/WIKI_CONFIGURATION_IMAGE.gif');
        $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
        $html .= '<tr>'.LF;
        $html .= '<td style="width:10%;">'.LF;
        $html .= '<a href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$link_item->getIcon(30).'</a>'.LF;
        $html .= '</td>'.LF;
        $html .= '<td style="width:90%;">'.LF;
        $html .= $title.LF;
        $html .= '</td>'.LF;
        $html .= '</tr>'.LF;
        $html .= '</table>'.LF;
     }
     if ( $current_context->showHomepageLink() ) {
          $url = curl( $this->_environment->getCurrentContextID(),
                 'context',
            'forward',
            array('tool' => 'homepage'));
       $title = '<span style="white-space:nowrap;"> '.
                                   ahref_curl($this->_environment->getCurrentContextID(),'context','forward',array('tool' => 'homepage'),
                                              $this->_translator->getMessage('HOMEPAGE_HOMEPAGE'), '', 'chat', '', '',
                                              'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"').'</span>';
        $link_item = new cs_link();
        $link_item->setIconPath('images/cs_config/HOMEPAGE_CONFIGURATION_IMAGE.gif');
        $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
        $html .= '<tr>'.LF;
        $html .= '<td style="width:10%;">'.LF;
        $html .= '<span style="white-space:nowrap;"> '.
                                   ahref_curl($this->_environment->getCurrentContextID(),'context','forward',array('tool' => 'homepage'),
                                              $link_item->getIcon(30), '', 'chat', '', '',
                                              'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"').'</span>';
        $html .= '</td>'.LF;
        $html .= '<td style="width:90%;">'.LF;
        $html .= $title.LF;
        $html .= '</td>'.LF;
        $html .= '</tr>'.LF;
        $html .= '</table>'.LF;
     }
     if ( $current_context->showChatLink() ) {
        /*
           $url = curl( $this->_environment->getCurrentContextID(),
            'chat',
            'index',
            array());
        $title = '<span style="white-space:nowrap;"> '.
                                   ahref_curl($this->_environment->getCurrentContextID(),'chat','index',array(),
                                              $this->_translator->getMessage('CHAT_CHAT'), '', 'chat', '', '',
                                              'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=700, height=600\');"').'</span>';
        $link_item = new cs_link();
        $link_item->setIconPath('images/cs_config/CHAT_CONFIGURATION_IMAGE.gif');
        $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
        $html .= '<tr>'.LF;
        $html .= '<td style="width:10%;">'.LF;
        $html .= '<span style="white-space:nowrap;"> '.
                                   ahref_curl($this->_environment->getCurrentContextID(),'chat','index',array(),
                                              $link_item->getIcon(30), '', 'chat', '', '',
                                              'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=700, height=600\');"').'</span>';
        $html .= '</td>'.LF;
        $html .= '<td style="width:90%;">'.LF;
        $html .= $title.LF;
        $html .= '</td>'.LF;
        $html .= '</tr>'.LF;
        $html .= '</table>'.LF;
        */
        // new chat
        global $c_etchat_enable;
        if ( !empty($c_etchat_enable)
             and $c_etchat_enable
           ) {
           $url = curl( $this->_environment->getCurrentContextID(),
                        'context',
                        'forward',
                        array('tool' => 'etchat'));
           $current_user = $this->_environment->getCurrentUserItem();
           $link_item = new cs_link();
           if ( $current_user->isReallyGuest() ) {
              $title = '<span class="disabled" style="white-space:nowrap;">'.$this->_translator->getMessage('CHAT_CHAT').'</span>';
              // TBD: icon ausgrauen
              $link_item->setIconPath('images/cs_config/CHAT_CONFIGURATION_IMAGE.gif');
           } else {
              $title = '<span style="white-space:nowrap;"> '.
                                      ahref_curl($this->_environment->getCurrentContextID(),'context','forward',array('tool' => 'etchat'),
                                                 $this->_translator->getMessage('CHAT_CHAT'), '', 'chat', '', '',
                                                 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"').'</span>';
              $link_item->setIconPath('images/cs_config/CHAT_CONFIGURATION_IMAGE.gif');
           }
           $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
           $html .= '<tr>'.LF;
           $html .= '<td style="width:10%;">'.LF;
           $html .= '<span style="white-space:nowrap;"> '.
                                      ahref_curl($this->_environment->getCurrentContextID(),'context','forward',array('tool' => 'etchat'),
                                                 $link_item->getIcon(30), '', 'chat', '', '',
                                                 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"').'</span>';
           $html .= '</td>'.LF;
           $html .= '<td style="width:90%;">'.LF;
           $html .= $title.LF;
           $html .= '</td>'.LF;
           $html .= '</tr>'.LF;
           $html .= '</table>'.LF;
        }
     }
     $html .= '</div>'.LF;
     $html .= '</div>'.LF;
     return $html;
   }
}
?>