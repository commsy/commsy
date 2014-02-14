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

$this->includeClass(LIST_PLAIN_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy list view: commsys
 */
class cs_homepage_list_view extends cs_list_view_plain {

   var $_max_activity = 0;

   /**
    * int - begin of list
    */
   var $_from = NULL;

   /**
    * int - length of shown list
    */
   var $_interval = NULL;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;
   var $_count_all_shown = NULL;


    /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_homepage_list_view ($params) {
      $this->cs_plain_list_view($params);
      $manager = $this->_environment->getRoomManager();
      $max = $manager->getAllMaxActivityPoints();
      $this->_max_activity = $max;
   }

   /** set from counter of the list view
    * this method sets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    */
    function setFrom ($from) {
       $this->_from = (int)$from;
    }

   /** get from counter of the list view
    * this method gets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    */
    function getFrom (){
       return $this->_from;
    }

   /** set interval counter of the list view
    * this method sets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    */
    function setInterval ($interval) {
       $this->_interval = (int)$interval;
    }

   /** get interval counter of the list view
    * this method gets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    *
    * @author CommSy Development Group
    */
    function getInterval () {
       return $this->_interval;
    }

   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function setCountAll ($count_all) {
       $this->_count_all = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function getCountAll () {
       return $this->_count_all;
    }

   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    *
    * @author CommSy Development Group
    */
    function setCountAllShown ($count_all) {
       $this->_count_all_shown = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    *
    * @author CommSy Development Group
    */
    function getCountAllShown () {
       return $this->_count_all_shown;
    }

  /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {

      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;
      $count_all_shown = $this->_count_all_shown;
      if ( $count_all > $count_all_shown ) {
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('COMMON_NO_ENTRIES_FROM_ALL', $count_all);
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('COMMON_ONE_ENTRY_FROM_ALL', $count_all);
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('COMMON_X_ENTRIES_FROM_ALL', $count_all_shown, $count_all);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('COMMON_X_FROM_Z_FROM_ALL', $count_all_shown, $count_all);
         } else {
            if ( $from + $interval -1 <= $count_all_shown ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('COMMON_X_TO_Y_FROM_Z_FROM_ALL', $from, $to, $count_all_shown, $count_all);
         }
      } else {
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('COMMON_NO_ENTRIES');
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('COMMON_ONE_ENTRY');
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('COMMON_X_ENTRIES', $count_all_shown);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('COMMON_X_FROM_Z', $count_all_shown);
         } else {
            if ( $from + $interval -1 <= $count_all ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('COMMON_X_TO_Y_FROM_Z', $from, $to, $count_all_shown);
         }
      }
      $html ='';

      if ( !empty($description) ) {
         $html .= '<div class="disabled">'.$description.'</div>';
      }

      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $i) {
      if (1 == $i%2) {
         $color = 'white';
      } else {
         $color = 'rgb(245, 245, 245)';
      }

      $html = '';
      $html .= '   <tr style="padding: 10px;">'.LF;
      $html .= '      <td style="width: 80%; text-align: left; padding-left: 3px; padding-rigth: 3px; padding-top: 3px; background-color: '.$color.';">'.LF;
      $html .= '         '.$this->_getTitle($item).LF;
      $html .= '      </td>'.LF;
      $html .= '      <td style="width: 20%; background-color: '.$color.';">'.LF;
      $html .= '         '.$this->_getActivity($item).LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getTitle ($item) {
      $title = $this->_compareWithSearchText($item->getRoomTitle());
      $title = ahref_curl( $item->getContextID(),
                           'homepage',
                           'detail',
                           '',
                           $this->_text_as_html_short($title));
      return $title;
   }

   /** get the activity of the item
    * this method returns the item activity in the right formatted style
    *
    * @return string title
    */
   function _getActivity ($item) {
      if ( $this->_max_activity != 0 ) {
         $percentage = $item->getRoomActivity();
         if ( empty($percentage) ) {
            $percentage = 0;
         } else {
           $teiler = $this->_max_activity/20;
            $percentage = log(($percentage/$teiler)+1);
          if ($percentage < 0) {
            $percentage = 0;
          }
          $max_activity = log(($this->_max_activity/$teiler)+1);
            $percentage = round(($percentage / $max_activity) * 100,2);
         }
      } else {
         $percentage = 0;
     }
      $display_percentage = $percentage;
      $html  = '         <div class="project-gauge">'.LF;
      $html .= '            <div class="project-gauge-bar" style="width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
      $html .= '         </div>'.LF;

      return $html;
   }

   function _getListActionsAsHTML () {
     $current_context = $this->_environment->getCurrentContextItem();

      $html  = '';
      // Search / select form
      $html .= '<form action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
      $session = $this->_environment->getSession();
      if ( !$session->issetValue('cookie')
           or $session->getValue('cookie') == '0' ) {
         $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session->getSessionID()).'"/>'.LF;
      }
      $html .= '<div>'.LF;
      $html .= '<div style="float: left; text-align: left">'.$this->_translator->getMessage('COMMON_SEARCHFIELD').'<br /><input style="width: 10em;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/></div> '.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML();
     $html .= '<div><br /><input name="option" value="'.$this->_translator->getMessage('COMMON_SHOW_BUTTON').'" type="submit"/></div>'.LF;
     $html .= '</div>'.LF;
      $html .= '</form>'.LF;

      return $html;
   }

   function _getAdditionalFormFieldsasHTML () {
     $html = '';
      return $html;
   }

   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    */
   function _compareWithSearchText ($value) {
      if ( !empty($this->_search_array) ){
             foreach ($this->_search_array as $search_text) {
               if ( mb_stristr($value,$search_text) ) {
                     $value = preg_replace('~'.preg_quote($search_text,'/').'~iu','*$0*',$value);
               }
            }
         }
         return $value;
   }

   function _getTableFootAsHTML() {
      $html = '<tr class="head"><td class="head" colspan="3" style="padding-top:4px"><table style="width:100%;" summary="Layout"><tr><td>'.$this->_getIntervalLinksAsHTML().'</td><td style="width:5%;white-space:nowrap;">'.$this->_getForwardLinkAsHTML().'</td></tr></table></td></tr>'.LF;
      return $html;
   }

   function _getIntervalLinksAsHTML() {
      $params = $this->_environment->getCurrentParameterArray();
      $html = $this->_translator->getMessage('COMMON_PAGE_ENTRIES').': ';
      if ( $this->_interval == 10 ) {
         $html  .= '10';
      } else {
         $params['interval'] = 10;
         $html  .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '10', '', '', '');
      }

      if ( $this->_interval == 20 ) {
         $html .= ' | 20';
      } else {
         $params['interval'] = 20;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '20', '', '', '');
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | 50';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '50', '', '', '');
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | '.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL');
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'), '', '', '');
      }

      return $html;
   }

   function _getTableheadAsHTML() {
      include_once('functions/misc_functions.php');
      $html = '';

         $params = $this->_environment->getCurrentParameterArray();
         $html .= '   <tr class="head">'.LF;
         $html .= '      <td class="head">'.LF;
         if ( $this->getSortKey() == 'room_title' ) {
            $params['sort'] = 'room_title_rev';
            $text = $this->_translator->getMessage('COMMON_TITLE').' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
         } elseif ( $this->getSortKey() == 'room_title_rev' ) {
            $params['sort'] = 'room_title';
            $text = $this->_translator->getMessage('COMMON_TITLE').' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
         } else {
            $params['sort'] = 'room_title_rev';
            $text = $this->_translator->getMessage('COMMON_TITLE');
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $text, '', '', '').LF;
         $html .= '      </td>'.LF;

         $html .= '      <td class="head">'.LF;
         if ( $this->getSortKey() == 'activity_rev' ) {
            $params['sort'] = 'activity';
            $text = $this->_translator->getMessage('CONTEXT_ACTIVITY').' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
         } elseif ( $this->getSortKey() == 'activity' ) {
            $params['sort'] = 'activity_rev';
            $text = $this->_translator->getMessage('CONTEXT_ACTIVITY').' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
         } else {
            $params['sort'] = 'activity';
            $text = $this->_translator->getMessage('CONTEXT_ACTIVITY');
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $text, '', '', '').LF;
         $html .= '      </td>'.LF;

         $html .= '   </tr>'.LF;

      return $html;
   }

   function _getForwardLinkAsHTML () {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all_shown = $this->_count_all_shown;
      $params = $this->_environment->getCurrentParameterArray();;
      if ($interval > 0) {
         if ($count_all_shown != 0) {
            $num_pages = ceil($count_all_shown / $interval);
         } else {
            $num_pages = 1;
         }
         $act_page  = ceil(($from + $interval - 1) / $interval);
      } else {
         $num_pages = 1;
         $act_page  = 1;
      }

      // prepare browsing
      if ( $from > 1 ) {        // can I browse to the left / start?
         $browse_left = $from - $interval;
         if ($browse_left < 1) {
            $browse_left = 1;
         }
         $browse_start = 1;
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $from + $interval <= $count_all_shown ) {  // can I browse to the right / end?
         $browse_right = $from + $interval;
         $browse_end = $count_all_shown - $interval + 1;
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing icons
      $html = '';
      if ( $browse_start > 0 ) {
         $params['from'] = $browse_start;
         $image = '<img src="images/browse_start3.gif" alt="&lt;&lt;" border="0"/>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),'','').LF;
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_start_grey3.gif" alt="&lt;&lt;" border="0"/></span>'.LF;
      }
      if ( $browse_left > 0 ) {
         $params['from'] = $browse_left;
         $image = '<img src="images/browse_left3.gif" alt="&lt;" border="0"/>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),'','').LF;
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_left_grey3.gif" alt="&lt;" border="0"/></span>'.LF;
      }
      $html .= '   &nbsp;'.$act_page.' / '.$num_pages.'&nbsp;'.LF;
      if ( $browse_right > 0 ) {
         $params['from'] = $browse_right;
         $image = '<img src="images/browse_right3.gif" alt="&gt;" border="0"/>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),'','').LF;
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_right_grey3.gif" alt="&gt;" border="0"/></span>'.LF;
      }
      if ( $browse_end > 0 ) {
         $params['from'] = $browse_end;
         $image = '<img src="images/browse_end3.gif" alt="&gt;&gt;" border="0"/>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),'','').LF;
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_end_grey3.gif" alt="&gt;&gt;" border="0"/></span>'.LF;
      }

      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    */
   function _getContentAsHTML() {
     $i = 1;
      $html = '';
      if ( !$this->_list->isEmpty() ) {
         $list = $this->_list;
         $current_item = $list->getFirst();
         while ( $current_item ) {
            $item_text = $this->_getItemAsHTML($current_item, $i);
            $i++;
            $html .= $item_text;
            $current_item = $list->getNext();
         }
      } else {
         $html .= '<tr><td style="border-bottom: 0px; background-color: red">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td><td style="border-bottom: 0px; background-color: white">&nbsp;</td></tr>';
      }
      return $html;
   }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF HOMEPAGE LIST VIEW -->'.LF;
      $html .= '<a name="'.$this->_view_name.'"></a>'.LF;

      $current_context = $this->_environment->getServerItem();

     // actions
     #$html .= $this->_getActionsAsHTML();
      #$html .= $this->_getForwardLinkAsHTML();

      $html .= '<div style="width: 100%; text-align: left">'.LF;
      $html .= '<h2 style="padding-bottom:0px;margin-bottom:0px;spacing-bottom:0px;">'.$this->_translator->getMessage('HOMEPAGE_INDEX_OVERVIEW').'</h2>'.LF;
      $html .= $this->_getDescriptionAsHTML().BRLF;
      $html .= '</div>'.LF;

      $html .= '<div style="width: 100%">'.LF;
      $html .= $this->_getListActionsAsHTML();
      $html .= '</div>'.LF;

      $html .= '<table style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      $html .= $this->_getContentAsHTML();
      $html .= $this->_getTableFootAsHTML();
      $html .= '</table>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }
}
?>