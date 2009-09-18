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

      $retour = '';
      $retour = '<textarea style="width:98%" name="'.$form_element['name'].'"';
      $retour .= ' rows="'.$form_element['hsize'].'"';
      $retour .= ' tabindex="'.$form_element['tabindex'].'"';
      if (isset($form_element['is_disabled']) and $form_element['is_disabled']) {
         $retour .= ' disabled="disabled"';
      }
      $retour .= ' id="'.$form_element['name'].'_'.$form_element['tabindex'].'"';
      $retour .= '>';
      $retour .= $form_element['value_for_output'];
      $retour .= '</textarea>'.LF;
      $retour .= '<script type="text/javascript">'.LF;
      $retour .= '   CKEDITOR.replace( \''.$form_element['name'].'_'.$form_element['tabindex'].'\' ,
                  {
                     language : \''.$this->_environment->getSelectedLanguage().'\',
                     skin : \'kama\',
                     uiColor: \''.$color['content_background'].'\',
                     toolbar :
                     [
                        [ \'SelectAll\', \'Cut\', \'Copy\', \'Paste\', \'PasteFromWord\', \'-\', \'Undo\', \'Redo\', \'-\', \'Find\', \'Replace\', \'-\', \'Bold\', \'Italic\', \'Underline\', \'Strike\', \'Subscript\', \'Superscript\', \'-\', \'NumberedList\', \'BulletedList\', \'Outdent\', \'Indent\', \'Blockquote\', \'-\', \'RemoveFormat\', \'-\', \'Maximize\'],
                        [ \'Format\', \'Font\', \'FontSize\', \'-\', \'JustifyLeft\', \'JustifyCenter\', \'JustifyRight\', \'JustifyBlock\', \'-\', \'Link\', \'Unlink\', \'-\', \'Table\', \'HorizontalRule\', \'Smiley\', \'-\', \'TextColor\', \'BGColor\' ]
                     ]

                  });'.LF;
      $retour .= '</script>'.LF;
      $retour .= LF;
      unset($color);
      return $retour;
   }
}
?>