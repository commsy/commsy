<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
class cs_homepage_buzzword_view extends cs_view {


   function cs_homepage_buzzword_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,
                      $with_modifying_actions);
      $this->setViewName('buzzwords');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = getMessage('COMMON_BUZZWORD_BOX');
   }


   function asHTML(){
      $current_user = $this->_environment->getCurrentUserItem();
      $session = $this->_environment->getSession();
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->setGetCountLinks();
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      $left_menue_status = $session->getValue('left_menue_status');
      if ( $left_menue_status !='disapear' ) {
        $width = '190';
      } else {
        $width = '225';
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.getMessage('COMMON_BUZZWORD_BOX').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $buzzword = $buzzword_list->getFirst();
      $params = $this->_environment->getCurrentParameterArray();
      if (!$buzzword){
         $html .= '<span class="disabled" style="font-size:10pt;">'.getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      while ($buzzword){
         $count = $buzzword->getCountLinks();
         if ($count > 0){
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span  '.$style_text.'>'.LF;
            $title .= $buzzword->getName().LF;
            $title .= '</span> ';

            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'campus_search',
                                'index',
                                $params,
                                $title,$title).LF;
         }
         $buzzword = $buzzword_list->getNext();
      }
      $html .= '<div style="width:'.$width.'px; text-align:right; padding-right:2px; padding-top:5px;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['module'] = $this->_environment->getCurrentModule();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'buzzwords','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_user);
      unset($session);
      return $html;
   }


   function getBuzzwordSizeLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=8, $maxsize=16, $tresholds=0 ) {
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

}
?>