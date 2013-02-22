<?PHP
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

/** upper class of the detail view
 */
$this->includeClass(PAGE_VIEW);

/** language_functions are needed for language specific display
 */
include_once('functions/language_functions.php');

/** curl_functions are needed for actions
 */
include_once('functions/curl_functions.php');

/** date_functions are needed for language specific display
 */
include_once('functions/date_functions.php');

/** misc_functions are needed for display the commsy version
 */
include_once('functions/misc_functions.php');
include_once('functions/text_functions.php');

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_page_help_view extends cs_page_view {

   /**
    * string - containing the parameter of the page
    */
   var $_current_parameter = '';

   var $_form_tags =false;

   var $_form_action= '';

   /**
    * array - containing the hyperlinks for the page
    */
   var $_links = array();

   var $_space_between_views=true;

   var $_blank_page = false;

   var $_blank_page_content ='';

   /**
    * boolean - containing the flag for displaying the CommSy header
    * standard = true
    */
   var $_with_commsy_header = true;

   /**
    * boolean - containing the flag for displaying a personal area for root (e.g. page commsy overview)
    * standard = false
    */
   var $_with_root_personal_area = false;

   /**
    * boolean - containing the flag for displaying a navigation bar for root (e.g. page commsy overview)
    * standard = false
    */
   var $_with_root_navigation_links = false;


   var $_bold_rubric = '';

   var $_shown_as_printable = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_page_help_view ($params) {
      $this->cs_page_view($params);
   }

   function setBlankPage () {
      $this->_blank_page = true;
   }

   function setBlankPageContent ($content) {
      $this->_blank_page_content = $content;
   }

   function unsetBlankPage () {
      $this->_blank_page = false;
   }

   function setSpace () {
      $this->_space_between_views = true;
   }

   function unsetSpace () {
      $this->_space_between_views = false;
   }

   function setContextID ($value) {
      $this->_context_id = (int)($value);
   }

   function setBoldRubric($value){
      $this->_bold_rubric = $value;
   }

   function setPrintableView(){
      $this->_shown_as_printable = true;
   }
   function isPrintableView(){
      return $this->_shown_as_printable;
   }

   /** so page will be displayed without the CommSy header
    * this method skip a flag, so that the CommSy header will not be shown
    *
    * @author CommSy Development Group
    */
   function withoutCommSyHeader () {
      $this->_with_commsy_header = false;
   }

   /** so page will be displayed without the personal area
    */
   function setWithoutPersonalArea () {
      $this->_with_personal_area = false;
   }

   /** so page will be displayed with the personal area for root user
    */
   function setWithRootPersonalArea () {
      $this->_with_root_personal_area = true;
   }

   /** so page will be displayed without the navigation links
    * this method skip a flag, so that the navigation links will not be shown
    */
   function setWithoutNavigationLinks () {
      $this->_with_navigation_links = false;
   }

   /** so page will be displayed with the navigation bar for root user
    */
   function setWithRootNavigationLinks () {
      $this->_with_root_navigation_links = true;
   }

   function addFormTags($action){
      $this->_form_tags = true;
      $this->_form_action = $action;
   }

   /** add an action to the page
    * this method adds an action (hyperlink) to the page view
    *
    * @param string  title        title of the action
    * @param string  explanantion explanation of the action
    * @param string  module       module of the action
    * @param string  function     function in module of the action
    * @param string  parameter    get parameter of the action
    *
    * @author CommSy Development Group
    */
   function addAction ($title, $explanation = '', $module = '', $function = '', $parameter = '') {
      $action['title'] = $title;
      $action['module'] = $module;
      $action['function'] = $function;
      $action['parameter'] = $parameter;
      $action['explanation'] = $explanation;
      $this->_links[] = $action;
   }



   /** get the linkbar as HTML
    * this method returns the linkbar as HTML - internal, do not use
    *
    * @return string linkbar as HTML
    *
    * @author CommSy Development Group
    */
   function _getLinkRowAsHTML () {

      $html  = LF.'<!-- BEGIN TABS -->'."\n";
      $session = $this->_environment->getSession();
      $width = 'width:560px;';
      $html .= '<div class="tabs_frame" style="'.$width.'">'.LF;
      $html .= '<div class="tabs">'.LF;
      $html .= '<div style="float:left; margin:0px; padding-left:13px;">'.LF;
      $html .= '<span class="navlist">'.$this->_translator->getMessage('COMMON_HELP').'</span>';
      $html .= '  '."\n";
      $html .= '</div>'."\n";
      $html .= '<div style="margin:0px; padding:0px;">'."\n";
      $html .= '<span class="navlist">&nbsp;</span>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '</div>'."\n";
      $html .= '<!-- END TABS -->'."\n";

      return $html;
   }





   function asHTML () {
      $html = '';
      $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      // Header
      $html .= $this->_getHTMLHeadAsHTML();

      // Body
      if ( !$this->_blank_page ) {
         $html .= '<body';
         if ($this->_focus_onload) {
            $html .= ' onload="window.focus();setfocus();';
            $html .= ' "';
         }
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right);
         $view = reset($views);
         while ($view) {
            $html .= $view->getInfoForBodyAsHTML();
            $view = next($views);
         }
         $html .= '>'.LF;
         $html .= '<div style="width:560px; padding:0px; margin:0px;">'.LF;
         $html .= LF.'<table style="border-collapse:collapse; padding:0px; margin-top:5px; width:100%;" summary="Layout">'.LF;

         // Page Header
         $session = $this->_environment->getSession();
         $html .='<tr>'.LF;
   $html .= '<td style="padding-left:5px; padding-right:0px; padding-top:0px; margin:0px; vertical-align: top; ">'.LF;
         $html .= $this->_getLinkRowAsHTML();

         // Content
         $html .= '<div style="border-left: 2px solid #C3C3C3; border-right: 2px solid #C3C3C3; padding:0px 0px; margin:0px;">'.LF;
         $html .= '<div class="content">'.LF;

         // Full Screen Views
         $first = true;
         $html .= '<div class="content_fader">';
         $html .= LF.'<div class="main">'.LF;
         if ( !empty($this->_views) ) {
            foreach ($this->_views as $view) {
               if ($first){
                  $first = false;
                  $html .= $view->asHTML();
               }else{
                  $html .= $view->asHTML();
               }
            }
         }
         if ($this->_environment->getCurrentModule()!='home'){
            $html .='</div>';
         }
         $html .= '<div class="top_of_page">'.LF;
         $html .= '<div style="float:right;">'.LF;

         $month_array = array($this->_translator->getMessage('DATES_JANUARY_LONG'),
          $this->_translator->getMessage('DATES_FEBRUARY_LONG'),
          $this->_translator->getMessage('DATES_MARCH_LONG'),
          $this->_translator->getMessage('DATES_APRIL_LONG'),
          $this->_translator->getMessage('DATES_MAY_LONG'),
          $this->_translator->getMessage('DATES_JUNE_LONG'),
          $this->_translator->getMessage('DATES_JULY_LONG'),
          $this->_translator->getMessage('DATES_AUGUST_LONG'),
          $this->_translator->getMessage('DATES_SEPTEMBER_LONG'),
          $this->_translator->getMessage('DATES_OCTOBER_LONG'),
          $this->_translator->getMessage('DATES_NOVEMBER_LONG'),
          $this->_translator->getMessage('DATES_DECEMBER_LONG'));
         $date = date("Y-m-d");
         $date_array = explode('-',$date);
         $current_time = localtime();
         $month = $current_time[4];
         $year = $current_time[5]+1900;
         $text = $date_array[2].'. '.$month_array[$month].' '.$date_array[0];
         $text .=', ';
         $text .= $current_time[2].':'.$current_time[1];
         $html .= '<span>'.$text.'</span>'.LF;
         $html .= '</div>'.LF;
         $html .= '<div>'.LF;
         $html .= '<a href="#top">'.'<img src="images/browse_left2.gif" alt="&lt;" border="0"/></a>&nbsp;<a href="#top">'.$this->_translator->getMessage('COMMON_TOP_OF_PAGE').'</a>';
         $html .= '</div>'.LF;

         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '<div class="frame_bottom">'.LF;
         $html .= '<div class="content_bottom">'.LF;
         $html .= '</div>'."\n";
         $html .= '</div>'."\n";

         $html .= '</td></tr></table>';
         $html .= '</div>'."\n";
         $html .= '</body>'."\n";
         $html .= '</html>'."\n";
      }
      return $html;
   }

}
?>