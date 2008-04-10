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
include_once('classes/cs_view.php');

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_homepage_tag_view extends cs_view {


   function cs_homepage_tag_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,
                      $with_modifying_actions);
      $this->setViewName('tags');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = getMessage('COMMON_TAG_BOX');
   }


   function getTagSizeLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=8, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getTagColorLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=40, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function asHTML(){
      $current_user = $this->_environment->getCurrentUserItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ( $left_menue_status !='disapear' ) {
        $width = '190';
      } else {
        $width = '230';
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.getMessage('COMMON_TAG_BOX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      $params = $this->_environment->getCurrentParameterArray();
      $father_id_array = array();
      $html_text = '';
      $html_text .= $this->_getTagContentAsHTML($root_item,0);
/*      $html .= '<div style="width:'.$width.'px; text-align:right; padding-right:2px; padding-top:5px; font-size:8pt;">';
      if ( ($current_user->isUser() and $this->_with_modifying_actions)
          and ($current_context->isTagEditedByAll() or $current_user->isModerator() ) ) {
         $params = array();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'tag','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.BRLF;
      }
      $html .= '</div>'.LF;*/
      if ( empty($html_text) ){
         $html_text .= '<span class="disabled" style="font-size:10pt;">'.getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      $html .= $html_text;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      unset($current_user);
      return $html;
   }

   function _getTagContentAsHTML($item = NULL, $ebene = 0,$distance = 0) {
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $i = 0;
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            if ($ebene == 1){
               $html.= '<div style="padding-bottom:5px;">'.LF;
            }else{
               $html.= '<div style="padding-bottom:0px;">'.LF;
            }
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $id = $current_item->getItemID();
               $tag2tag_manager = $this->_environment->getTag2TagManager();
               $count = count($tag2tag_manager->getFatherItemIDArray($id));
#                  $font_size = 14 - $this->getTagSizeLogarithmic($count);
               $font_size = round(13 - (($count*0.2)+$count));
               if ($font_size < 8){
                  $font_size = 8;
               }
               $font_color = 20 + $this->getTagColorLogarithmic($count);
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               if (($ebene*15) <= 30){
                  $html .= '<div style="padding-left:'.($ebene*15).'px; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
               }else{
                  $html .= '<div style="padding-left:40px; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">';
               }
               $title = $current_item->getTitle();
               $params['seltag_'.$ebene] = $current_item->getItemID();
               if( isset($params['seltag']) ){
                  $i = $ebene+1;
                  while( isset($params['seltag_'.$i]) ){
                     unset($params['seltag_'.$i]);
                     $i++;
                  }
               }
               $params['seltag'] = 'yes';
               $html .= '<span class="disabled" style="font-size:'.$font_size.'px;">'.LF;
               $html .= '-';
               $html .= '</span>'.LF;
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             'campus_search',
                             'index',
                             $params,
                             $title,$title,'','','','','','style="color:'.$color.'"').LF;
               $html .= '</div>';
               $html .= $this->_getTagContentAsHTML($current_item, $ebene+1, $distance);
               $current_item = $list->getNext();
            }
            $html.='</div>'.LF;
         }
      }
      return $html;
   }
}
?>