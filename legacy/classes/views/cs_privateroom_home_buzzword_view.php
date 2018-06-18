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

/** upper class of the form view
 */
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_buzzword_view extends cs_view {

var $_buzzword_list = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('buzzwords');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_BUZZWORD_BOX');
      $current_user = $this->_environment->getCurrentUserItem();
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->setGetCountLinks();
      $buzzword_manager->select();
      $this->_buzzword_list = $buzzword_manager->get();
      unset($current_user);
   }


   function asHTML(){
      $buzzword_list = $this->_buzzword_list;
      $html  = '';
      $html .= '<div id="'.get_class($this).'">'.LF;
      $html .= '<div style="font-size:8pt; width:100%">'.LF;
      $buzzword = $buzzword_list->getFirst();
      $params = $this->_environment->getCurrentParameterArray();
      $buzzword = $buzzword_list->getFirst();
      if (!$buzzword){
         $html .= '<span class="disabled" style="font-size:10pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      while ($buzzword){
         $count = $buzzword->getCountLinks();
         if ($count > 0 or true){
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            if (!empty($this->_selbuzzword) and $this->_selbuzzword == $buzzword->getItemID()){
               $style_text .= ' color:#000000;';
               $style_text .= ' font-weight:bold;';
            }else{
               $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            }
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span id="buzzword_'.$buzzword->getItemID().'" class="droppable_buzzword" '.$style_text.'>'.LF;
            $title .= $this->_text_as_html_short($buzzword->getName()).LF;
            $title .= '</span> ';

            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'entry',
                                'index',
                                $params,
                                $title,
                                $buzzword->getName()).LF;
         }
         $buzzword = $buzzword_list->getNext();
      }

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }


   function getBuzzwordSizeLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=10, $maxsize=20, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getBuzzwordColorLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=30, $maxsize=70, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getPreferencesAsHTML(){
      $buzzword_list = $this->_buzzword_list;
      $html = '<input type="text" id="portlet_buzzword_new" size="40" />';
      $html .= '<input type="submit" id="portlet_buzzword_new_button" value="'.$this->_translator->getMessage('BUZZWORDS_NEW_BUTTON').'" />';
      $html .= '<br/><br/>'.LF;

      #$html .= '<div id="portlet_buzzword_combine">';
      $html .= '<select id="portal_buzzword_combine_first" size="1" tabindex="15">'.LF;
      $buzzword = $buzzword_list->getFirst();
      while($buzzword){
         $html .= '<option value="'.$buzzword->getItemID().'">'.$buzzword->getName().'</option>'.LF;
         $buzzword = $buzzword_list->getNext();
      }
      $html .= '</select>'.LF;
      $html .= '<select id="portal_buzzword_combine_second" size="1" tabindex="15">'.LF;
      $buzzword = $buzzword_list->getFirst();
      while($buzzword){
         $html .= '<option value="'.$buzzword->getItemID().'">'.$buzzword->getName().'</option>'.LF;
         $buzzword = $buzzword_list->getNext();
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="portlet_buzzword_combine_button" value="'.$this->_translator->getMessage('BUZZWORDS_COMBINE_BUTTON').'"/>';
      $html .= '</div><br/>';

      $length = $buzzword_list->getCount();
      if ($length > 7){
         $html .= '<div id="portlet_buzzword_preferences_list" style="height:120px; overflow-y: scroll;">';
      }else{
         $html .= '<div id="portlet_buzzword_preferences_list">';
      }
      $buzzword = $buzzword_list->getFirst();
      while($buzzword){
         $html .= '<div>';
         $html .= '<input type="text" class="portlet_buzzword_textfield" id="portlet_buzzword_'.$buzzword->getItemID().'" value="'.$buzzword->getName().'" size="40"/>&nbsp;';
         $html .= '<input type="submit" class="portlet_buzzword_change_button" id="'.$buzzword->getItemID().'" value="'.$this->_translator->getMessage('BUZZWORDS_CHANGE_BUTTON').'"/>&nbsp;';
         $html .= '<input type="submit" class="portlet_buzzword_delete_button" id="'.$buzzword->getItemID().'" value="'.$this->_translator->getMessage('COMMON_DELETE_BUTTON').'"/>';
         $html .= '</div>';
         $buzzword = $buzzword_list->getNext();
      }
      #$html .= '</div>';
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var buzzword_cid = "'.$this->_environment->getCurrentContextID().'";'.LF;
      $html .= 'var buzzword_message = "'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
      return $html;
   }
}
?>