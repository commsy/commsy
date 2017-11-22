<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** for internal data-structure
 */
include ('classes/cs_array_list.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_form {

        /**
         * array_list containing the elements of the form, will be initialized in constructor
         */
        var $_formElements;

        /**
        * array - containing strings of error messages
        */
        var $_error_array = array();

        /*
         * Translation Object
         */
        private $_translator = null;

        /** constructor: cs_form
         * the only available constructor
         *
         * @author CommSy Development Group
         */
        function __construct() {
           $this->_formElements = new cs_array_list();

           global $environment;
           $this->_translator = $environment->getTranslationObject();
        }

   function setCurrentColor($color){
      $this->_current_color = $color;
   }

   function setCurrentRubric($rubric){
      $this->_current_rubric = $rubric;
   }

   function setColorArray($color_array){
      $this->_color_array = $color_array;
   }


        /** adds a textarea to the form
         *
         * this method adds a textarea to the form
         *
         * @param string  $name          name of the form field
         * @param string  $value         the default content of the field
         * @param string  $nameText      the text left of the text field
         * @param string  $exampleText   the text with the example right of the textarea
         * @param integer $vsize         number of rows of the textarea
         * @param integer $hsize         size of the textarea
         * @param string  $wrap          specify the text wrapping mode
         * @param boolean $isMandatory   set true if the field is mandatory
         * @param string  $isDisabled      maybe disable this element: standard = false
         */
        function addTextarea( $name,
                              $value,
                              $nameText,
                              $exampleText,
                              $vsize = 65,
                              $hsize = 20,
                              $wrap = 'virtual',
                              $isMandatory = false,
                              $isDisabled = false,
                              $with_html_area = true,
                              $with_html_area_status = 1,
                              $full_width = true,
                              $help_text = true ) {

                if (empty($vsize)) {
                        $vsize = 66;
                }
                if (empty($hsize)) {
                        $hsize = 20;
                }
                 if ( mb_strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') == true ) {
                     $vsize = $vsize + 6;
                 }
                 if ( mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Mozilla') == true
                      and mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Linux') == true ) {
                     $vsize = $vsize + 6;
                 }
                 if ( mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Firefox') == true
                      and mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Linux') == true ) {
                     $vsize = $vsize + 6;
                 }

                $element['type'] = 'textarea';
                $element['name'] = $name;
                $element['value'] = mb_eregi_replace('<br[[:space:]]*/?[[:space:]]*>', "\n", $value);
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['vsize'] = $vsize;
                $element['hsize'] = $hsize;
                $element['wrap'] = $wrap;
                $element['mandatory'] = $isMandatory;
                $element['is_disabled'] = $isDisabled;
                $element['with_html_area'] = $with_html_area;
                $element['with_html_area_status'] = $with_html_area_status;
                $element['full_width'] = $full_width;
                $element['help_text'] = $help_text;

                $this -> _formElements -> addElement($element);
        }

   /** adds a textfield to the form
    *
    * this method add a single line text field to the form
    *
    * @param string  $name          name of the form field
    * @param string  $value         the default content of the field
    * @param string  $nameText      the text left of the text field
    * @param string  $exampleText   the text with the example right of the text field
    * @param integer $maxlength     max length of the entered text
    * @param integer $size          size of the text field of the source entry
    * @param boolen  $isMandatory   true if the field is mandatory
    * @param string  $button_text   text for a optional button
    * @param string  $button_name   name for a optional button
    * @param string  $columnbackgroundcolor
    * @param string  $align         align for form element: standard = left
    * @param string  $before_form_text         ???
    * @param integer $width                     width in EMs
    *
    * @author CommSy Development Group
    */
   function addTextfield( $name,
                          $value,
                          $nameText,
                          $exampleText,
                          $maxlength = 255,
                          $size = 50,
                          $isMandatory = false,
                          $button_text = '',
                          $button_name = '',
                          $columnbackgroundcolor = '',
                          $align = 'left',
                          $before_form_text = '',
                          $width='',
                          $disabled=false,
                          $after_form_text = '',
                          $font_size = 10,
                          $drop_down = false,
                          $show_drop_down = false ) {

      if ( empty($maxlength) ) {
        $maxlength = 255;
      }
      if ( empty($size) ) {
        $size = 50;
      }
      if ( !empty($width) ) {
        $element['width'] = $width;
      }
      $element['type'] = 'textfield';
      $element['name'] = $name;
      $element['value'] = $value;
      $element['label'] = $nameText;
      $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
      $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
      $element['example'] = $exampleText;
      $element['maxlength'] = $maxlength;
      $element['size'] = $size;
      $element['font_size'] = $font_size;
      $element['mandatory'] = $isMandatory;
      if ( !empty($button_text) and !empty($button_name) ) {
        $element['button_text'] = $button_text;
        $element['button_name'] = $button_name;
      }
      if ( !empty($columnbackgroundcolor) ) {
        $element['columnbackgroundcolor'] = $columnbackgroundcolor;
      }
      if ( !empty($align) ) {
        $element['text-align'] = $align;
      }
      if ( !empty($before_form_text) ) {
         $element['before_form_text'] = $before_form_text;
      }
      if ( !empty($after_form_text) ) {
         $element['after_form_text'] = $after_form_text;
      }
      $element['is_disabled'] = $disabled;
      $element['drop_down'] = $drop_down;
      $element['show_drop_down'] = $show_drop_down;

      $this -> _formElements -> addElement($element);
   }

   /** adds a titlefield to the form
    *
    * this method add a single line text field to the form
    *
    * @param string  $name          name of the form field
    * @param string  $value         the default content of the field
    * @param string  $nameText      the text left of the text field
    * @param string  $exampleText   the text with the example right of the text field
    * @param integer $maxlength     max length of the entered text
    * @param integer $size          size of the text field of the source entry
    * @param boolen  $isMandatory   true if the field is mandatory
    * @param string  $button_text   text for a optional button
    * @param string  $button_name   name for a optional button
    * @param string  $columnbackgroundcolor
    * @param string  $align         align for form element: standard = left
    * @param string  $before_form_text         ???
    * @param integer $width                     width in EMs
    *
    * @author CommSy Development Group
    */
   function addTitlefield( $name,
                           $value,
                           $nameText,
                           $exampleText,
                           $maxlength = 255,
                           $size = 50,
                           $isMandatory = false,
                           $button_text = '',
                           $button_name = '',
                           $columnbackgroundcolor = '',
                           $align = 'left',
                           $before_form_text = '',
                           $width='',
                           $display= true ) {

      if ( empty($maxlength) ) {
           $maxlength = 255;
      }
      if ( empty($size) ) {
              $size = 50;
         if ( mb_strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') == true ) {
            $size = $size - 6;
         }
      }
      if ( mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Mozilla') == true
           and mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Linux') == true ) {
            $size = $size - 7;
      }
      if ( mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Firefox') == true
           and mb_stristr($_SERVER['HTTP_USER_AGENT'], 'Linux') == true ) {
           $size = $size - 4;
      }

      if ( !empty($width) ) {
                        $element['width'] = $width;
      }
      $element['type'] = 'titlefield';
      $element['name'] = $name;
      $element['value'] = $value;
      $element['label'] = $nameText;
      $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
      $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
      $element['example'] = $exampleText;
      $element['maxlength'] = $maxlength;
      if ( mb_strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') == true ) {
          $size = $size - 6;
      }
      $element['size'] = $size;
      $element['mandatory'] = $isMandatory;
      $element['display'] = $display;
      if ( !empty($button_text) and !empty($button_name) ) {
        $element['button_text'] = $button_text;
        $element['button_name'] = $button_name;
      }
      if ( !empty($columnbackgroundcolor) ) {
        $element['columnbackgroundcolor'] = $columnbackgroundcolor;
      }
      if ( !empty($align) ) {
        $element['text-align'] = $align;
      }
      if ( !empty($before_form_text) ) {
         $element['before_form_text'] = $before_form_text;
      }

      $this -> _formElements -> addElement($element);
   }

        /** adds a password-field to the form
         * this method add a single line password field to the form
         *
         * @param string  $name          name of the form field
         * @param string  $value         the default content of the field
         * @param string  $nameText      the text left of the text field
         * @param string  $exampleText   the text with the example right of the text field
         * @param integer $size          size of the text field of the source entry
         * @param boolean $isMandatory   true if the field is mandatory
         * @param integer $width                        width in EMs
         *
         * @author CommSy Development Group
         */
        function addPassword( $name,
                              $value,
                              $nameText,
                              $exampleText,
                              $maxlength = 15,
                              $size = 50,
                              $isMandatory = false,
                              $width = '' ) {

                if ( !empty($width) ) {
                        $element['width'] = $width;
                }

                $element['type'] = 'password';
                $element['name'] = $name;
                $element['value'] = $value;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['maxlength'] = $maxlength;
                $element['size'] = $size;
                $element['mandatory'] = $isMandatory;

                $this -> _formElements -> addElement($element);
       }

        /** adds a select-field to the form
         * this method add a select-field (selectbox) to the form
         *
         * @param string  $name             the name of the form field
         * @param array   $optionArray      a two dimensional array with all the options
         *                                  [n][text] = text to be shown
         *                                  [n][value] = value to be transmit
         *                                  [value] is optional, if [value] is empty [text] will be transmitted
         * @param array   $selected         an array with the preselected options: text or value
         * @param string  $nameText         the text left of the select field
         * @param string  $exampleText      the text with the example on the right
         * @param integer $size             the vertical size
         * @param boolean $isMultiple       true if multiple selections are possible
         * @param boolean $isMandatory      true if a selections is mandatory
         * @param string  $descriptionText  Text describing the select field
         *
         * @author CommSy Development Group
         */
        function addSelect( $name,
                            $optionArray,
                            $selected,
                            $nameText,
                            $exampleText,
                            $size = 0,
                            $isMultiple = false,
                            $isMandatory = false,
                            $event = false,
                            $button_text = '',
                            $button_name = '',
                            $descriptionText = '',
                            $before_form_text = '',
                            $width = '',
                            $noscript = false,
                            $isDisabled = false,
                            $font_size = '10',
                            $extension = '' ) {
                if (empty($selected)) {
                        $selected = array();
                }
                if (empty($size)) {
                        $size = 0;
                }
                if ($width !='') {
                   $element['width'] = $width;
                }
                $element['font_size'] = $font_size;
                $element['type'] = 'select';
                $element['name'] = $name;
                $element['options'] = (array) $optionArray;
                $element['selected'] = (array) $selected;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['size'] = $size;
                $element['multiple'] = $isMultiple;
                $element['exampleText'] = $exampleText;
                $element['mandatory'] = $isMandatory;
                $element['event'] = $event;
                $element['noscript'] = $noscript;
                $element['is_disabled'] = $isDisabled;
                $element['extention'] = $extension;
                if (!empty($descriptionText)) {
                        $element['descriptionText'] = $descriptionText;
                }

                if (!empty($button_text) and !empty($button_name)) {
                        $element['button_text'] = $button_text;
                        $element['button_name'] = $button_name;
                }
                if (!empty($before_form_text)) {
                        $element['before_form_text'] = $before_form_text;
                }
                $this -> _formElements -> addElement($element);
        }

        /** adds a select-field group to the form
         * this method add a group of select-fields (selectboxes) to the form
         *
         * @param string  $name         the name of the form field
         * @param array   $optionArrays this array contains arrays of the following kind:
         *                              a two dimensional array with all the options
         *                              [n][text] = text to be shown
         *                              [n][value] = value to be transmit
         *                              [value] is optional, if [value] is empty [text] will be transmitted
         * @param array   $selectedArrays     this array contains arrays with the preselected options: text or value
         * @param string  $nameText     the text left of the select field
         * @param string  $exampleText  the text with the example on the right
         * @param integer $size         the vertical size
         *
         * @author CommSy Development Group
         */
        function addSelectGroup( $name,
                                 $nameArray,
                                 $optionArrays,
                                 $selectedArrays,
                                 $nameText,
                                 $exampleText,
                                 $size = 0,
                                 $horizontal = false,
                                 $event = false ) {

                if (empty($selected)) {
                        $selected = array();
                }
                if (empty($size)) {
                        $size = 0;
                }
                $element['type'] = 'selectgroup';
                $element['name'] = $name;
                $element['names4select'] = (array) $nameArray;
                $element['options'] = (array) $optionArrays;
                $element['selected'] = (array) $selectedArrays;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['size'] = $size;
                $element['exampleText'] = $exampleText;
                $element['event'] = $event;
                $element['mandatory'] = false;

                $this -> _formElements -> addElement($element);
        }

        function addListSelect( $name,
                                $optionArray,
                                $selected,
                                $nameText,
                                $exampleText,
                                $size = 0,
                                $isMultiple = false,
                                $isMandatory = false ) {

                if (empty($selected)) {
                        $selected = array();
                }
                if (empty($size)) {
                        $size = 0;
                }
                $element['type'] = 'select';
                $element['name'] = $name;
                $element['options'] = (array) $optionArray;
                $element['selected'] = (array) $selected;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['size'] = $size;
                $element['multiple'] = $isMultiple;
                $element['exampleText'] = $exampleText;

                $this -> _formElements -> addElement($element);
        }

        /** add hidden-field to the form
         * this method add a hidden-field to the form
         *
         * @param string $name   the name of the form field
         * @param string $value  the default content of the field
         *
         * @author CommSy Development Group
         */
        function addHidden($name, $value) {

                $element['type'] = 'hidden';
                $element['name'] = $name;
                $element['value'] = $value;
                $element['mandatory'] = false;

                $this -> _formElements -> addElement($element);
        }

        /** add a checkbox to the form
         * this method add a checkbox to the form
         *
         * @param string  $name          the internal name for the check box
         * @param string  $value         the default content of the field
         * @param boolean $isChecked     is true in case of a pre checked box
         * @param string  $nameText      the text before the check box
         * @param string  $valueText     the text directly at the check box
         * @param string  $exampleText   the text with the example right of the check box/title
         * @param boolean $isMandatory   true if a selections is mandatory
         * @param boolean $isDisabled    true if a selections is disabled: standard = false
         * @param string  $extention     extention, i.e. onChange: standard = ''
         */
        function addCheckbox( $name,
                              $value,
                              $isChecked,
                              $nameText,
                              $valueText,
                              $exampleText = '',
                              $isMandatory = false,
                              $isDisabled = false,
                              $extention = '',
                              $before_form_text = '',
                              $drop_down = false,
                              $show_drop_down = false
                               ) {

                $element['type']  = 'checkbox';
                $element['name']  = $name;
                $element['value'] = $value;
                $element['ischecked'] = $isChecked;
                $element['label'] = $nameText;
                $element['text']  = $valueText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example']     = $exampleText;
                $element['mandatory']   = $isMandatory;
                $element['is_disabled'] = $isDisabled;
                $element['extention']   = $extention;
                $element['before_form_text'] = $before_form_text;
                $element['drop_down'] = $drop_down;
                $element['show_drop_down'] = $show_drop_down;

                $this -> _formElements -> addElement($element);
        }

        /** add a NetNavigationContent to the form
         * this method add a group of checkboxes to the form
         *
         * @param string  $name         the internal name for the check box
         * @param array   values        array of options
         *                              [n][text] text to display of the n Element
         *                              [n][value] value to transmit of the n Element
         * @param array   $selected     an array with the preselected options
         * @param string  $nameText     the text directly behind the check box
         * @param string  $exampleText  the text with the example right of the check box/title
         * @param boolean $isMandatory  true if a selections is mandatory
         * @param string  $link_text  text for a optional link
         *
         * @author CommSy Development Group
         */
        function addNetNavigationContent( $name,
                                          $values,
                                          $selected,
                                          $nameText,
                                          $isMandatory = false,
                                          $horizontal = false,
                                          $link_text = true,
                                          $anchor='' ) {
                $element['type'] = 'netnavigation';
                $element['name'] = $name;
                $element['value'] = $values;
                if (!empty($selected)) {
                   $element['selected'] = $selected;
                } else {
                   $element['selected'] = array();
                }
                $element['label'] = $nameText;
                $element['example'] = '';
                $element['mandatory'] = $isMandatory;
                $element['horizontal'] = $horizontal;
                $element['link_text'] = $link_text;
                      $element['anchor'] = $anchor;
                $this -> _formElements -> addElement($element);
        }



        /** add a checkbox-group to the form
         * this method add a group of checkboxes to the form
         *
         * @param string  $name         the internal name for the check box
         * @param array   values        array of options
         *                              [n][text] text to display of the n Element
         *                              [n][value] value to transmit of the n Element
         * @param array   $selected     an array with the preselected options
         * @param string  $nameText     the text directly behind the check box
         * @param string  $exampleText  the text with the example right of the check box/title
         * @param boolean $isMandatory  true if a selections is mandatory
         * @param string  $button_text  text for a optional button
         * @param string  $button_name  name for a optional button
         */
        function addCheckboxGroup( $name,
                                   $values,
                                   $selected,
                                   $nameText,
                                   $exampleText = '',
                                   $isMandatory = false,
                                   $horizontal = false,
                                   $columns = 0,
                                   $button_text = '',
                                   $button_name = '',
                                   $anchor='',
                                   $chunk_text='',
                                   $up_and_down = false,
                                   $no_html_decode = false,
                                   $with_dhtml = false,
                                   $font_size = 10,
                                   $isDisabled = false ) {
                $element['type'] = 'checkboxgroup';
                $element['name'] = $name;
                $element['font_size'] = $font_size;
                $element['value'] = $values;
                if (!empty($selected)) {
                        $element['selected'] = $selected;
                } else {
                        $element['selected'] = array();
                }
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['mandatory'] = $isMandatory;
                $element['horizontal'] = $horizontal;
                if (!empty($button_text) and !empty($button_name)) {
                        $element['button_text'] = $button_text;
                        $element['button_name'] = $button_name;
                }
                if (!empty($columns)) {
                        $element['columns'] = $columns;
                }
                $element['anchor'] = $anchor;
                $element['chunk_text'] = $chunk_text;
                $element['up_and_down'] = $up_and_down;
                $element['no_html_decode'] = $no_html_decode;
                $element['with_dhtml'] = $with_dhtml;
                $element['is_disabled'] = $isDisabled;

                $this -> _formElements -> addElement($element);
        }

        /** add a filefield to the form
         * this method add a filefield to the form
         *
         * @param string  $name          the name of the file field
         * @param string  $nameText      the text left of the file field
         * @param string  $exampleText   the text with the example on the right
         * @param string  $size          the size of the field
         * @param boolean $isMandatory   switch, determines if the field is mandatory
         * @param string  $button_text   text for a optional button
         * @param string  $button_name   name for a optional button
         */
        function addFilefield( $name,
                               $nameText,
                               $exampleText,
                               $size = 30,
                               $isMandatory = false,
                               $button_text = '',
                               $button_name = '',
                               $multi_upload = false ) {

                $element['type'] = 'file';
                $element['name'] = $name;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['size'] = $size;
                $element['mandatory'] = $isMandatory;
                if (!empty($button_text) and !empty($button_name)) {
                        $element['button_text'] = $button_text;
                        $element['button_name'] = $button_name;
                }
                $element['multi_upload'] = $multi_upload;

                $this -> _formElements -> addElement($element);
        }

        /** add a radio-group to the form
         * this method add group of yes/no buttons to the form
         *
         * @param string  $name          the name of the radiobutton field
         * @param string  $nameText      the text left of the element
         * @param string  $exampleText   the text with the example on the right
         * @param array   $values        array of options
         *                               [n][text] text to display of the n element
         *                               [n][value] value to transmit of the n element
         * @param string  $checkedValue  determines which option is preselected
         * @param boolean $isMandatory   determines if the field is mandatory
         * @param boolean $vertical      boolean switch for vertical or horizontal
          */
        function addRadioGroup( $name,
                                $nameText,
                                $exampleText,
                                $values,
                                $checkedValue = '',
                                $isMandatory = false,
                                $horizontal = false,
                                $button_text = '',
                                $button_name = '',
                                $disabled = false,
                                $extention = '',
                                $drop_down = false,
                                $show_drop_down = false) {

                $element['type']  = 'radio';
                $element['name']  = $name;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText      = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example']    = $exampleText;
                $element['value']      = $values;
                $element['checked']    = $checkedValue;
                $element['mandatory']  = $isMandatory;
                $element['horizontal'] = $horizontal;
                if (!empty($button_text) and !empty($button_name)) {
                   $element['button_text'] = $button_text;
                   $element['button_name'] = $button_name;
                }
                $element['is_disabled'] = $disabled;
                $element['extention']   = $extention;
                $element['drop_down'] = $drop_down;
                $element['show_drop_down'] = $show_drop_down;

                $this -> _formElements -> addElement($element);
        }

        /** add a DateTimeField to the form
         * this method add a range field containing two textfields the form
         *
         * @param string  name                   the name of the radiobutton field
         * @param array   values                 array of strings
         *                                       [0] first value
         *                                       [1] second value
         * @param string  firstName              the name of the first field
         * @param string  secondName             the name of the second field
         * @param integer firstFieldSize         an integer for the length of the first field
         * @param integer secondFieldSize        an integer for the length of the second field
         * @param string  nameText               the name left of the range field
         * @param string  firstLabel             the text left of the first field
         * @param string  secondLabel            the text left of the second field
         * @param string  exampleText            the example text on the right
         * @param boolean isFirstMandatory       true, if the first field is a mandatory
         * @param boolean isSecondMandatory      true, if the second field is a mandatory
         * @param integer firstFieldMaxLength    max length of the entered text in first field
         * @param integer second FieldMaxLength  max length of the entered text in second field
         *
         * @author CommSy Development Group
         */
        function addDateTimeField( $name,
                                   $values,
                                   $firstName,
                                   $secondName,
                                   $firstFieldSize,
                                   $secondFieldSize,
                                   $nameText,
                                   $firstLabel,
                                   $secondLabel,
                                   $exampleText,
                                   $isFirstMandatory,
                                   $isSecondMandatory,
                                   $firstFieldMaxLength = 255,
                                   $secondFieldMaxLength = 255,
                                   $horizontal = true,
                                   $align = 'left',
                                   $secondFieldType = '',
                                   $withWhiteSpace = true,
                                   $showOnlyDate = false) {

                if (empty($values)) {
                        $values = array();
                }
                $element['type'] = 'datetime';
                $element['name'] = $name;
                $element['value'] = $values;
                $element['firstName'] = $firstName;
                $element['secondName'] = $secondName;
                $element['firstFieldSize'] = $firstFieldSize;
                $element['secondFieldSize'] = $secondFieldSize;
                $element['label'] = $nameText;
                $element['firstLabel'] = $firstLabel;
                $element['secondLabel'] = $secondLabel;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['isFirstMandatory'] = $isFirstMandatory;
                $element['isSecondMandatory'] = $isSecondMandatory;
                $element['mandatory'] = $isFirstMandatory OR $isSecondMandatory;
                $element['firstFieldMaxLength'] = $firstFieldMaxLength;
                $element['secondFieldMaxLength'] = $secondFieldMaxLength;
                $element['horizontal'] = $horizontal;
                $element['withWhiteSpace'] = $withWhiteSpace;
                if (!empty($align)) {
                        $element['text-align'] = $align;
                }
                if (!empty($secondFieldType)) {
                        $element['second_field_type'] = $secondFieldType;
                }
                $element['showOnlyDate'] = $showOnlyDate;
                $this -> _formElements -> addElement($element);
        }


        /** add an empty line to the form
         * this method add an empty line to the form
         *
         * @param string $name name of the element for internal storage
         *
         * @author CommSy Development Group
         */
        function addEmptyline( $line_right = false ) {

                $element['type'] = 'emptyline';
                $element['name'] = 'emptyline';
                $element['mandatory'] = false;
                $element['line_right'] = $line_right;

                $this -> _formElements -> addElement($element);
        }

        /** add a buttonbar to the form
         * this method adds the button bar to the form
         * It will add the cancel and save and, if necessary, the delete
         * button to the internal array
         *
         * @param string $name              name of the element for internal storage
         * @param string $labelSave         the label for the save button
         * @param string $labelCancel       the label for the cancel button
         * @param string $lableDelete       the label for an optional delete button
         * @param string $nameText          the text left of the file field
         * @param string $exampleText       the text with the example on the right
         * @param string $labelSecondSave   the label for the second save button
         * @param string $isDisabled        maybe disable this element: standard = false
         */
                function addButtonBar( $name,
                               $labelSave,
                               $labelCancel = '',
                               $labelDelete = '',
                               $nameText = '',
                               $exampleText = '',
                               $labelSecondSave = '',
                               $isDisabled = false,
                               $firstWidth='',
                               $secondWidth='',
                               $style='',
                               $javascript='',
                               $idSave='',
                               $idCancel='',
                               $idDelete='') {
                $element['type'] = 'buttonbar';
                $element['name'] = $name;
                $element['labelSave']       = $labelSave;
                $element['labelSecondSave'] = $labelSecondSave;
                $element['labelCancel'] = $labelCancel;
                $element['labelDelete'] = $labelDelete;
                $element['label'] = $nameText;
                $temp_exampleText = mb_eregi_replace('^/', '<i>',  $exampleText);
                $exampleText      = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example']     = $exampleText;
                $element['is_disabled'] = $isDisabled;
                $element['mandatory']   = false;
                $element['firstWidth']  = $firstWidth;
                $element['secondWidth'] = $secondWidth;
                $element['style']       = $style;
                $element['javascript']       = $javascript;
                $element['idSave']      = $idSave;
                $element['idCancel']    = $idCancel;
                $element['idDelete']    = $idDelete;

                $this -> _formElements -> addElement($element);
        }

        /** add a button to the form
         * this method adds a button to the form in one line
         *
         * @param string $name              name of the element for internal storage and for button
         * @param string $labelSave         the label for the save button
         * @param string $labelCancel       the label for the cancel button
         * @param string $lableDelete       the label for an optional delete button
         * @param string $nameText          the text left of the file field
         * @param string $exampleText       the text with the example on the right
         */
        function addButton( $name,
                            $button_text,
                            $nameText = '',
                            $exampleText = '',
                            $width = '',
                            $isDisabled = false,
                            $text_after = '',
                            $anchor = '',
                            $font_size = 10,
                            $javascript = '',
                            $id = '') {
                $element['type'] = 'button';
                $element['name'] = $name;
                $element['button_text'] = $button_text;
                $element['label']   = $nameText;
                $temp_exampleText   = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText        = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                if ( !empty($width) ) {
                     $element['width'] = $width;
                }
                $element['is_disabled'] = $isDisabled;
                $element['mandatory'] = false;
                $element['font_size'] = $font_size;
                if ( !empty($text_after) ) {
                     $element['text_after'] = $text_after;
                } else {
                     $element['text_after'] = '';
                }
                $element['javascript']  = $javascript;
                $element['element_id'] = $id;
                $this -> _formElements -> addElement($element);
        }

        /** add a headline to the form
         * this method adds a headline to the form
         *
         * @param  string $name               name of the element for internal storage
         * @param  string $headerText         a string with the text for the header
         * @param  string $headerDescription  a string for describe the headerText
         * @param  string $headerRight     a string shown an the right hand
         */
        function addHeadline( $name,
                              $headerText,
                              $headerDescription = '',
                              $headerRight = '',
                              $size =2 ) {

                $element['type'] = 'headline';
                $element['name'] = $name;
                $element['label'] = $headerText;
                $element['description'] = $headerDescription;
                $element['right'] = $headerRight;
                $element['mandatory'] = false;
                $element['size'] = $size;

                $this -> _formElements -> addElement($element);
        }

        function addTitleText($name, $headerText) {

                $element['type']  = 'titletext';
                $element['name']  = $name;
                $element['label'] = $headerText;
                $element['mandatory'] = false;
                $this -> _formElements -> addElement($element);
        }

   function addWarning ($name, $text) {
      $element['type'] = 'warning';
      $element['name'] = $name;
      $element['text'] = $text;
      $element['mandatory'] = false;
      $this->_formElements->addElement($element);
   }

        /** add a headline to the form
         * this method adds a headline to the form
         *
         * @param  string $name               name of the element for internal storage
         * @param  string $headerText         a string with the text for the header
         * @param  string $headerDescription  a string for describe the headerText
         * @param  string $headerRight     a string shown an the right hand
         *
         * @author CommSy Development Group
         */
        function addSubHeadline( $name,
                                 $headerText,
                                 $headerDescription = '',
                                 $headerRight = '',
                                 $size =2 ) {

                $element['type']  = 'subheadline';
                $element['name']  = $name;
                $element['label'] = $headerText;
                $element['description'] = $headerDescription;
                $element['right']       = $headerRight;
                $element['mandatory']   = false;
                $element['size']        = $size;

                $this -> _formElements -> addElement($element);
        }

        /** add a headline to the form
         * this method adds a headline to the form
         *
         * @param  string $name               name of the element for internal storage
         * @param  string $headerText         a string with the text for the header
         * @param  string $headerDescription  a string for describe the headerText
         * @param  string $headerRight     a string shown an the right hand
         */
        function addTextline($name, $text) {

                $element['type'] = 'textline';
                $element['name'] = $name;
                $element['description'] = $text;
                $element['mandatory'] = false;

                $this -> _formElements -> addElement($element);
        }

       function addExplanation($name, $text) {

                $element['type'] = 'explanation';
                $element['name'] = $name;
                $element['description'] = $text;
                $element['mandatory'] = false;

                $this -> _formElements -> addElement($element);
        }

   /** add an anchor to the form
    * this method adds an anchor to the form
    *
    * @param  string $name   name of the element
    */
   function addAnchor($name) {
      $element['type'] = 'anchor';
      $element['name'] = $name;
      $this->_formElements->addElement($element);
   }

        /** add a text to the form
         * this method add a static text to the form. the text can be bold
         *
         * @param string  $name        name of the element for internal storage
         * @param string  $label       the text left of the text
         * @param string  $text        the text itself
         * @param string  $exampleText the text with the example on the right
         * @param boolean $isbold      boolean switch, standard: false
         *
         * @author CommSy Development Group
         */
        function addText( $name,
                          $label,
                          $text,
                          $exampleText = '',
                          $isbold = false,
                          $button_text = '',
                          $button_name = '',
                          $align = 'left',
                          $anchor='',
                          $extention = '',
                          $drop_down = false,
                          $show_drop_down = false,
                          $exampleText = '') {

                $element['type']  = 'text';
                $element['name']  = $name;
                $element['label'] = $label;
                $element['value'] = $text;
                $temp_exampleText = mb_eregi_replace('^/', '<i>', $exampleText);
                $exampleText      = mb_eregi_replace('/$', '</i>', $temp_exampleText);
                $element['example'] = $exampleText;
                $element['isbold']  = $isbold;
                $element['extention']  = $extention;
                $element['mandatory'] = false;
                if (!empty($button_text) and !empty($button_name)) {
                        $element['button_text'] = $button_text;
                        $element['button_name'] = $button_name;
                }
                if (!empty($align)) {
                        $element['text-align'] = $align;
                }
                $element['anchor'] = $anchor;
                $element['drop_down'] = $drop_down;
                $element['show_drop_down'] = $show_drop_down;
                $element['example'] = $exampleText;
                $this -> _formElements -> addElement($element);
        }

        /** add a color table to the form
         * this method add a color table to the form
         *
         * @param string $name name of the element for internal storage
         *
         * @author CommSy Development Group
         */
        function addColorTable($name) {

                $element['type'] = 'color_table';
                $element['name'] = $name;
                $element['mandatory'] = false;

                $this -> _formElements -> addElement($element);
        }

  /** add an image to the form
   * this method adds an image to the form
   *
   * @param string  $name        name of the element for internal storage
   * @param string  $filename    name of the image file
   * @param string  $label       the text left of the image
   * @param string  $example     the example text on the right
   * @param integer $context_id  context_id of image if different to current context_id
   */
   function addImage ( $name,
                       $filename='',
                       $label='',
                       $example='',
                       $context_id='',
                       $is_mandatory = false,
                       $width = '') {
      $element['type']       = 'image';
      $element['name']       = $name;
      $element['filename']   = $filename;
      $element['label']      = $label;
      $element['example']    = $example;
      $element['context_id'] = $context_id;
      $element['mandatory']  = $is_mandatory;
      $element['width']  = $width;

      $this -> _formElements -> addElement($element);
   }

   function addImageButton ( $name,
                             $label='',
                             $src='',
                             $alt='',
                             $border='',
                             $width='',
                             $height='') {
      $element['type']       = 'imagebutton';
      $element['name']       = $name;
      $element['src']        = $src;
      $element['alt']        = $alt;
      $element['border']     = $border;
      $element['height']     = $height;
      $element['width']      = $width;

      $this -> _formElements -> addElement($element);
   }

   function addRoomLogo ( $name,
                       $filename='',
                       $label='',
                       $example='',
                       $context_id='',
                       $is_mandatory = false,
                       $width = '') {
      $element['type']       = 'room_logo';
      $element['name']       = $name;
      $element['filename']   = $filename;
      $element['label']      = $label;
      $element['example']    = $example;
      $element['context_id'] = $context_id;
      $element['mandatory']  = $is_mandatory;

      $this -> _formElements -> addElement($element);
   }

        /** combines the last form field with the next one
          * this method combines the last form field with the next one
          *
          * @author CommSy Development Group
          */
        function combine( $direction = 'vertical', $direct = 'false' ) {
                $last_element = $this -> _formElements -> getLastAndDropIt();
                $last_element['combine'] = $direction;

                if ($direct == 'true') {
                        $last_element['combine_direct'] = 'true';
                }
                $this -> _formElements -> addElement($last_element);
        }

        /** combines the last form field with the next one
          * this method combines the last form field with the next one
          *
          * @author CommSy Development Group
          */
        function add_without_line() {
                $last_element = $this -> _formElements -> getLastAndDropIt();
                $last_element['without_line'] = 'true';
                $this -> _formElements -> addElement($last_element);
        }

  /** load values
   * loads a set of given values into the form-fields
   *
   * @param  array $array   array of values with index used for field name
   */
   function loadValues( $array ) {
      foreach ($array as $key => $value) {
         if (!is_integer($key)) {
            // ONLY REPLACE VALUES USING ASSOCIATIVE ARRAYS NOT NUMBERS!!!
            $elements = $this->_formElements->getElements($key);
            foreach ($elements as $row) {
               if (!empty($row) and !empty($value)) {
                  if ($row['type'] == 'textarea') {
                     $row['value'] = $value;
                  } elseif ($row['type'] == 'select') {
                     if (is_array($value)) {
                        $row['selected'] = $value;
                     } else {
                        $tmp_array = array();
                        $tmp_array[] = $value;
                        $row['selected'] = $tmp_array;
                     }
                  } elseif ($row['type'] == 'selectgroup') {
                     if (is_array($value)) {
                        $row['selected'] = $value;
                     } else {
                        $tmp_array = array();
                        $tmp_array[] = $value;
                        $row['selected'] = $tmp_array;
                     }
                  } elseif ($row['type'] == 'checkboxgroup' or $row['type'] == 'netnavigation') {
                     if (is_array($value)) {
                        $row['selected'] = $value;
                     } else {
                        $tmp_array = array();
                        $tmp_array[] = $value;
                        $row['selected'] = $tmp_array;
                     }
                  } elseif ($row['type'] == 'checkbox') {
                     $row['ischecked'] = $value;
                  } elseif ($row['type'] == 'radio') {
                     $row['checked'] = $value;
                  } elseif ($row['type'] == 'text') {
                     $row['value'] = $value;
                  } elseif ($row['type'] == 'datetime') {
                     $row['value'] = $value;
                  } elseif ($row['type'] == 'image') {
                     $row['filename'] = $value;
                  }elseif ($row['type'] == 'room_logo') {
                     $row['filename'] = $value;
                  }elseif ($row['type'] == 'titletext') {
                     $row['label'] = $value;
                  } else {
                     $row['value'] = $value;
                  }
                  $this->_formElements->replaceElement($row);
               } else {
                 // did not find field
               }
            }
         }
      }
   }

   public function checkValues () {
      $result = true;
      $this->_formElements->resetCursor();
      while ( $this->_formElements->isCurrentValid() ) {
         $current = $this->_formElements->getCurrent();

         // radio
         if ($current['type'] == 'radio') {
            if ( !empty($current['checked']) ) {
               $value_from_form = $current['checked'];
               $found = false;
               foreach ($current['value'] as $value) {
                  if ( $value_from_form == $value['value'] ) {
                     $found = true;
                     break;
                  }
               }
               if ( !$found ) {
                  $current['failure'] = true;
                  $current['failuretype'] = 'value';
                  if (empty($current['failuretext'])) {
                     $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_MANIPULATION', $current['label']);
                  }
                  $this->_formElements->replaceElement($current);
                  $result = false;
               }
            }
         }

         // select box
         elseif ($current['type'] == 'select') {
            if ( !empty($current['selected']) ) {
               $found_array = array();
               foreach ( $current['selected'] as $selected ) {
                  $value_from_form = $selected;
                  foreach ($current['options'] as $value) {
                     if ( is_numeric($value_from_form)
                          or $value_from_form == $value['value']
                        ) {
                        $found_array[] = $value_from_form;
                        break;
                     }
                  }
               }
               if ( count($found_array) != count($current['selected']) ) {
                  $current['failure'] = true;
                  $current['failuretype'] = 'value';
                  if (empty($current['failuretext'])) {
                     $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_MANIPULATION', $current['label']);
                  }
                  $this->_formElements->replaceElement($current);
                  $result = false;
               }
            }
         }

         // checkbox group
         elseif ($current['type'] == 'checkboxgroup') {
            if ( !empty($current['selected']) ) {
               $found_array = array();
               foreach ( $current['selected'] as $selected ) {
                  $value_from_form = $selected;
                  foreach ($current['value'] as $value) {
                     if ( is_numeric($value_from_form)
                          or $value_from_form == trim($value['value']) //trim file space
                        ) {
                        $found_array[] = $value_from_form;
                        break;
                     }
                  }
               }
               if ( count($found_array) != count($current['selected']) ) {
                  $current['failure'] = true;
                  $current['failuretype'] = 'value';
                  if (empty($current['failuretext'])) {
                     $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_MANIPULATION', $current['label']);
                  }
                  $this->_formElements->replaceElement($current);
                  $result = false;
               }
            }
         }

         // checkbox
         elseif ($current['type'] == 'checkbox') {
            if ( !empty($current['ischecked']) ) {
               if ( $current['ischecked'] != $current['value'] ) {
                  $current['failure'] = true;
                  $current['failuretype'] = 'value';
                  if (empty($current['failuretext'])) {
                     $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_MANIPULATION', $current['label']);
                  }
                  $this->_formElements->replaceElement($current);
                  $result = false;
               }
            }
         }
         $this->_formElements->moveNext();
      }
      return $result;
   }

        /** check mandatory fields
         * checks a given form with values whether the mandatory fields are filled
         *
         * @return boolean true, if all mandatory fields are filled
         *                 false, if not
         */
        function checkMandatory() {
           $result = true;
           $this -> _error_array = array();
           $this -> _formElements -> resetCursor();
           while ($this -> _formElements -> isCurrentValid()) {
              $current = $this -> _formElements -> getCurrent();

              if ( isset($current['mandatory']) and $current['mandatory']) {
                 if ($current['type'] == 'radio') {
                    if (empty($current['checked'])) {
                       $current['failure'] = true;
                       $current['failuretype'] = 'mandatory';
                       if (empty($current['failuretext'])) {
                          $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $current['label']);
                       }
                       $this -> _formElements -> replaceElement($current);
                       $result = false;
                    }
                 }
                 elseif ($current['type'] == 'select') {
                    if (empty($current['selected'])) {
                       $current['failure'] = true;
                       $current['failuretype'] = 'mandatory';
                       if (empty($current['failuretext'])) {
                          $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $current['label']);
                       }
                       $this -> _formElements -> replaceElement($current);
                       $result = false;
                    }
                 }
                 elseif ($current['type'] == 'checkboxgroup') {
                    if (empty($current['selected'])) {
                       $current['failure'] = true;
                       $current['failuretype'] = 'mandatory';
                       if (empty($current['failuretext'])) {
                          $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $current['label']);
                       }
                       $this -> _formElements -> replaceElement($current);
                       $result = false;
                    }
                 }
                 elseif ($current['type'] == 'checkbox') {
                    if (empty($current['ischecked'])) {
                       $current['failure'] = true;
                       $current['failuretype'] = 'mandatory';
                       if (empty($current['failuretext'])) {
                          $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_SELECT', $current['label']);
                       }
                       $this -> _formElements -> replaceElement($current);
                       $result = false;
                    }
                 }
                 elseif ($current['type'] == 'datetime') {
                    $current['failure_element'] = array();
                    if (empty($current['value'])) {
                       $current['failure'] = true;
                       $current['failuretype'] = 'mandatory';
                       if (empty($current['failuretext'])) {
                          $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_FIELD',$current['label']);
                       }
                       $current['failure_element'][] = 0;
                       $current['failure_element'][] = 1;
                       $this -> _formElements -> replaceElement($current);
                       $result = false;
                    } else {
                       if ($current['isFirstMandatory'] and empty($current['value'][0])) {
                          $current['failure'] = true;
                          $current['failuretype'] = 'mandatory';
                          if (empty($current['failuretext'])) {
                             $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_FIELD',$current['label']);
                          }
                          $current['failure_element'][] = 0;
                          $this -> _formElements -> replaceElement($current);
                          $result = false;
                       }
                       if ($current['isSecondMandatory'] and empty($current['value'][1])) {
                          $current['failure'] = true;
                          $current['failuretype'] = 'mandatory';
                          if (empty($current['failuretext'])) {
                             $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_FIELD',$current['label']);
                          }
                          $current['failure_element'][] = 1;
                          $this -> _formElements -> replaceElement($current);
                          $result = false;
                       }
                    }
                 } else {
                    if (!empty($current['value'])) {
                       $current['value'] = trim($current['value']);
                    }
                    if (empty($current['value'])) {
                       $current['failure'] = true;
                       $current['failuretype'] = 'mandatory';
                       if (empty($current['failuretext'])) {
                          if ( isset($current['label']) ){
                             $current['failuretext'] = $this->_translator->getMessage('COMMON_ERROR_FIELD',$current['label']);
                          }
                       }
                       $this -> _formElements -> replaceElement($current);
                       $result = false;
                    }
                 }
              }
              $this -> _formElements -> moveNext();
           }
           return $result;
        }

        /** check HTML
         * checks a given form with values whether there are forbidden HTML-tags
         *
         * @return boolean true, if there is no HMTL
         *                 false, if there is HTML
         *
         * @author CommSy Development Group
         */
        function checkHtml() {
           $result = true;

           $this -> _formElements -> resetCursor();

           while ($this -> _formElements -> isCurrentValid()) {
              $current = $this -> _formElements -> getCurrent();
              if (!empty($current['value']) and !is_array($current['value'])) {
                 if (mb_eregi('<.+>', $current['value'])) {
                     // remove html-tags
                     // $current['value'] = trim(eregi_replace('<.+>','',$current['value']));
                     $current['failure'] = true;
                     $current['failuretype'] = 'html';
                     $this -> _formElements -> replaceElement($current);
                     $result = false;
                 }
              }
              elseif (!empty($current['value']) and $current['type'] == 'datetime') {
                 if (mb_eregi('<.+>', $current['value'][0]) or mb_eregi('<.+>', $current['value'][1])) {
                    // remove html-tags
                    // $current['value'] = trim(eregi_replace('<.+>','',$current['value']));
                    $current['failure'] = true;
                    $current['failuretype'] = 'html';
                    $this -> _formElements -> replaceElement($current);
                    $result = false;
                 }
              }
              $this -> _formElements -> moveNext();
           }
           return $result;
        }

        /** set mark a field with "failure"
         * mark an element with failure and failure type
         *
         * @param string name name of the field of the form
         * @param string type type of the failure
         *
         * @author CommSy Development Group
         */
        function setFailure( $name, $type = '', $position = '' ) {
           if (empty($type)) {
                   $type = 'self_define';
           }
           $this -> _formElements -> resetCursor();
           $current_element = $this -> _formElements -> getElement($name);
           $current_element['failure'] = true;
           $current_element['failuretype'] = $type;
           if (!empty($position)) {
                   $current_element['failure_element'][] = $position -1;
           }
           $this -> _formElements -> replaceElement($current_element);
        }

        /** get all form elements
         * this method returns all form elements as an array
         *
         * @return array an array list of all form elements
         *
         * @author CommSy Development Group
         */
        function getFormElements() { // weg TBD
           return $this -> _formElements;
        }

        function addFilelist( $name,
                              $labelDelete,
                              $nameText,
                              $exampleText,
                              $isMandatory = false,
                              $button_text = '',
                              $button_name = '' ) {

           $element['name']  = $name;
           $element['type']  = 'filelist';
           $element['label'] = $nameText;
           $temp_exampleText = mb_eregi_replace('^/', '<i>',  $exampleText);
           $exampleText      = mb_eregi_replace('/$', '</i>', $temp_exampleText);
           $element['example'] = $exampleText;
           $element['field']   = '&nbsp;';
           $element['mandatory'] = $isMandatory;
           if (!empty($button_text) and !empty($button_name)) {
              $element['button_text'] = $button_text;
              $element['button_name'] = $button_name;
           }
           $element['mandatory'] = false;

           $this -> _formElements -> addElement($element);
        }



        function addBuzzwordBox($selected_array, $all_buzzword_array, $isMandatory = false){
           $element['name']  = 'buzzwordlist';
           $element['type']  = 'buzzword_box';
           $element['selected_array']  = $selected_array;
           $element['all_array']  = $all_buzzword_array;
           $element['mandatory']  = $isMandatory;
           $this -> _formElements -> addElement($element);
        }


        /** get error array from form
         * this method returns the error array with error messages
         *
         * @return array an array of error messages
         *
         * @author CommSy Development Group
         */
        function getErrorArray() {

           $error_array = array();
           $current = $this -> _formElements -> getFirst();
           while ($current) {
            if (!empty($current['failure']) and $current['failure']) {
               if (!empty($current['failuretext']) and $current['failuretext']) {
                   $error_array[] = $current['failuretext'];
                }
            }
            $current = $this -> _formElements -> getNext();
           }
           return $error_array;
        }


        /** set an individual failure message for a form element
         *
         * @param string element_name Name of the element that will get the new failure message
         * @param string newFailureText New failure text
         */
        function setFailureTextForElement( $element_name, $newFailureText ) {
           $current = $this -> _formElements -> getFirst();
           while ($current) {
              if ($current['name']==$element_name) {
                 $current['failuretext'] = $newFailureText;
                 $this -> _formElements -> replaceElement($current);
              }
              $current = $this -> _formElements -> getNext();
           }
        }

        /** reset form
         *  reset this form (errors and elements)
         */
   function reset () {
      $this->_formElements = new cs_array_list();
      unset($this->_error_array);
   }


   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for the form
    */
   function getInfoForHeaderAsHTML () {
      // needed cause some forms are implemented in old style
   }

}
?>