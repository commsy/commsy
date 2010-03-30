<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Dr. Iver Jackewitz
//
// This file is part of the CKEditor plugin for CommSy.
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

include_once('classes/cs_plugin.php');
class class_ckeditor extends cs_plugin {

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_identifier = 'ckeditor';
      $this->_title      = 'CKEditor';
      $this->_image_path = 'plugins/'.$this->getIdentifier();
      $this->_translator->addMessageDatFolder('plugins/'.$this->getIdentifier().'/messages');
   }

   public function getDescription () {
      return $this->_translator->getMessage('CKEDITOR_DESCRIPTION');
   }

   public function getHomepage () {
      return 'http://www.ckeditor.com';
   }

   public function getInfosForHeaderAsHTML () {
      $retour  = '';
      $retour .= '   <script type="text/javascript" src="plugins/'.$this->getIdentifier().'/ckeditor.js"></script>';
      $retour .= '   <link rel="stylesheet" href="plugins/'.$this->getIdentifier().'/commsy_css.php?cid='.$this->_environment->getCurrentContextID().'" />';
      return $retour;
   }

   public function isConfigurableInPortal () {
      return true;
   }

   public function isConfigurableInRoom ( $room_type = '' ) {
      $retour = true;
      return $retour;
   }

   public function getTextAreaAsHTML ($form_element) {
      $current_context = $this->_environment->getCurrentContextItem();
      $color = $current_context->getColorArray();
      unset($current_context);
      if ( !empty($color['content_background']) ) {
         $background_color = $color['content_background'];
         if ( $background_color[0] == '#' ) {
            $background_color = substr($background_color,1);
         }
         $r = hexdec($background_color[0].$background_color[1]);
         $g = hexdec($background_color[2].$background_color[3]);
         $b = hexdec($background_color[4].$background_color[5]);
         $hsv = $this->_RGB_TO_HSV($r,$g,$b);
         if ( $hsv['V'] > 0.9 ) {
            $hsv['V'] = 0.9;
            $rgb = $this->_HSV_TO_RGB($hsv['H'],$hsv['S'],$hsv['V']);
            $color['content_background'] = '#'.dechex($rgb['R']).dechex($rgb['G']).dechex($rgb['B']);
         }
      } else {
         $color['content_background'] = '#eeeeee';
      }
      $retour = '';
      $retour = '<textarea style="width:98%" name="'.$form_element['name'].'"';
      $retour .= ' rows="'.$form_element['hsize'].'"';
      $retour .= ' tabindex="'.$form_element['tabindex'].'"';
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $retour .= ' disabled="disabled"';
      }
      $retour .= ' id="'.$form_element['name'].'_'.$form_element['tabindex'].'"';
      $retour .= '>'.LF;
      if ( !empty($form_element['value_for_output_html_security']) ) {
         $retour .= $form_element['value_for_output_html_security'];
      } elseif ( !empty($form_element['value_for_output_html']) ) {
         $retour .= $form_element['value_for_output_html'];
      } elseif ( !empty($form_element['value_for_output']) ) {
         $retour .= $form_element['value_for_output'];
      }
      $retour .= LF.'</textarea>'.LF;
      $retour .= '<script type="text/javascript">'.LF;
      $retour .= '   CKEDITOR.replace( \''.$form_element['name'].'_'.$form_element['tabindex'].'\' ,
                  {
                     language : \''.$this->_environment->getSelectedLanguage().'\',
                     skin : \'kama\',
                     uiColor: \''.$color['content_background'].'\',
                     startupFocus: false,
                     resize_enabled: false,
                     toolbar :
                     [
                        [ \'Cut\', \'Copy\', \'Paste\', \'PasteFromWord\', \'-\', \'Undo\', \'Redo\', \'-\', \'Bold\', \'Italic\', \'Underline\', \'Strike\', \'Subscript\', \'Superscript\', \'-\', \'NumberedList\', \'BulletedList\', \'Outdent\', \'Indent\', \'Blockquote\', \'-\', \'TextColor\', \'BGColor\', \'-\', \'RemoveFormat\']
                        ,\'/\',
                        [ \'Format\', \'Font\', \'FontSize\', \'-\', \'JustifyLeft\', \'JustifyCenter\', \'JustifyRight\', \'JustifyBlock\', \'-\', \'Link\', \'Unlink\', \'-\', \'Table\', \'HorizontalRule\', \'Smiley\', \'-\', \'Maximize\', \'About\', \'-\', \'CommSyImages\',\'CommSyFiles\',]
                     ]
                  });'.LF;
      $retour .= '</script>'.LF;
      $retour .= LF;
      unset($color);
      return $retour;
   }

   // RGB Values:Number 0-255
   // HSV Results:Number 0-1
   private function _RGB_TO_HSV ($R, $G, $B) {
      $HSL = array();

      $var_R = ($R / 255);
      $var_G = ($G / 255);
      $var_B = ($B / 255);

      $var_Min = min($var_R, $var_G, $var_B);
      $var_Max = max($var_R, $var_G, $var_B);
      $del_Max = $var_Max - $var_Min;
      $max = $var_Max;

      $V = $var_Max;

      if ($del_Max == 0) {
         $H = 0;
         $S = 0;
      } else {
         $S = $del_Max / $var_Max;

         $del_R = ( ( ( $max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
         $del_G = ( ( ( $max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
         $del_B = ( ( ( $max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

         if ($var_R == $var_Max) $H = $del_B - $del_G;
         else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
         else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

         if ($H<0) $H++;
         if ($H>1) $H--;
      }

      $HSL['H'] = $H;
      $HSL['S'] = $S;
      $HSL['V'] = $V;

      return $HSL;
   }

   // HSV Values:Number 0-1
   // RGB Results:Number 0-255
   private function _HSV_TO_RGB ($H, $S, $V) {
      $RGB = array();

      if ($S == 0) {
         $R = $G = $B = $V * 255;
      } else {
         $var_H = $H * 6;
         $var_i = floor( $var_H );
         $var_1 = $V * ( 1 - $S );
         $var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) );
         $var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) );

         if ($var_i == 0) { $var_R = $V ; $var_G = $var_3 ; $var_B = $var_1 ; }
         else if ($var_i == 1) { $var_R = $var_2 ; $var_G = $V ; $var_B = $var_1 ; }
         else if ($var_i == 2) { $var_R = $var_1 ; $var_G = $V ; $var_B = $var_3 ; }
         else if ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2 ; $var_B = $V ; }
         else if ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1 ; $var_B = $V ; }
         else { $var_R = $V ; $var_G = $var_1 ; $var_B = $var_2 ; }

         $R = $var_R * 255;
         $G = $var_G * 255;
         $B = $var_B * 255;
      }

      $RGB['R'] = $R;
      $RGB['G'] = $G;
      $RGB['B'] = $B;

      return $RGB;
   }
}
?>