<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez, Johannes Schultze
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

/** upper class for views of commsy
 * this class implements an upper class of views for commsy
 */
class cs_view {

   /**
    * string - a name that can uniquely identify this view if
    *          multiple views are displayed on one page
    */
   var $_name = NULL;


   var $_view_name = NULL;

   /**
    * integer - containing the id of the project room
    */
   var $_room_id = NULL;

   /**
    * string - containing the name of the module of commsy
    */
   var $_module = NULL;

   /**
    * string - containing the function (index,edit,...) of the module of commsy
    */
   var $_function = NULL;

   /**
    * object - holding the CommSy environment
    */
   var $_environment = NULL;

   /**
    * object - holding the translation object
    */
   var $_translator = NULL;

   /**
    * boolean - true, if display modifying actions - false, if not
    */
   var $_with_modifying_actions = true;

   /**
    * string - containing the anchor of the detail view
    */
   var $_anchor = NULL;

   var $_shown_as_printable = false;

   var $_parameter = NULL;

   var $_view_title = 'title unknown';

   var $_item_file_list = NULL;

   var $_with_slimbox = false;

   public $_class_factory = NULL;


   /** constructor: cs_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_view ($params) {
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
      $this->_with_modifying_actions = true;
      if ( !empty($params['with_modifying_actions']) ) {
         $this->_with_modifying_actions = $params['with_modifying_actions'];
      }
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      if ( $current_context->isClosed()
           or $current_user->isOnlyReadUser()
         ) {
         $this->_with_modifying_actions = false;
      }
      $this->_class_factory = $this->_environment->getClassFactory();
      $this->_room_id = $this->_environment->getCurrentContextID();
      $this->_module = $this->_environment->getCurrentModule();
      $this->_function = $this->_environment->getCurrentFunction();
      $this->_translator = $this->_environment->getTranslationObject();
   }

   function getEnvironment () {
      return $this->_environment;
   }

   function getViewName(){
     return $this->_view_name;
   }

   function setViewName($value){
     $this->_view_name = (string)$value;
   }

   /** set anchor of view
    * this method sets the anchor of the view
    *
    * @param string value anchor of the view
    *
    * @author CommSy Development Group
    */
   function setAnchor ($value) {
      $this->_anchor = (string)$value;
   }

   /** set flag: display without modifying methods
    * this method sets the flag to display without modifying methods
    *
    * @author CommSy Development Group
    */
   function withoutModifyingActions () {
      $this->_with_modifying_actions = false;
   }

   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page (e.g. javascript)
    *
    * @return string nothing - needs to be overwritten
    *
    * @version $Revision$
    * @author CommSy Development Group
    */
   function getInfoForHeaderAsHTML () {
      $html = '';
      return $html;
   }

   function getStylesForHeaderAsHTML () {
      $html = '';
      return $html;
   }

   function getJavaScriptInfoArrayForHeaderAsHTML($array){
      if ($this->_with_slimbox){
         $array['slimbox'] = true;
         $array['mootools'] = true;
      }
      return $array;
   }

   /** get information for body as HTML
    * this method returns information in HTML-Code needs for the body of the HTML-Page
    *
    * @return string nothing - needs to be overwritten
    *
    * @version $Revision$
    * @author CommSy Development Group
    */
   function getInfoForBodyAsHTML () {
      $html = '';
      return $html;
   }

   /** get information about the fuction
    * this method returns information about the function of the view
    *
    * @return string function
    *
    * @version $Revision$
    * @author CommSy Development Group
    */
   function getFunction () {
      return $this->_function;
   }

   function setViewTitle($text){
      $this->_view_title = $text;
   }

   function getViewTitle(){
      return $this->_view_title;
   }
   /** set information about the module
    * this method sets information about the module of the view
    *
    * @return string function
    */
   function setModule ($value) {
      $this->_module = (string)$value;
   }

   function setPrintableView() {
      $this->_shown_as_printable = true;
   }
   function isPrintableView() {
      return $this->_shown_as_printable;
   }

   /** get view as HTML
    * this method returns the view in HTML-Code, needs to be overwritten
    */
   function asHTML () {
      echo('as HTML needs to be overwritten !!!<br />'."\n");
   }

   /** get prinatble view as HTML
    * this method returns the view in HTML-Code, needs to be overwritten
    */
   function asPrintableHTML () {
      echo('asPrintableHTML needs to be overwritten !!!<br />'."\n");
   }

   //Text functions --------------------------------------------------------------------------------------------------------------------------------------------

   private function _cleanBadCode ( $text ) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      $search = array();
      $replace = array();

//      $search[]  = '§<([/]{0,1}[j|J][a|A][v|V][a|A][s|S][c|C][r|R][i|I][p|P][t|T])§';
//      $replace[] = '&lt;$1';
//      $search[]  = '§<([/]{0,1}[s|S][c|C][r|R][i|I][p|P][t|T])§';
//      $replace[] = '&lt;$1';
//      $search[]  = '§<([/]{0,1}[j|J][s|S][c|C][r|R][i|I][p|P][t|T])§';
//      $replace[] = '&lt;$1';
//      $search[]  = '§<(\?)§';
//      $replace[] = '&lt;$1';
//      $search[]  = '§(\?)>§';
//      $replace[] = '$1&gt;';
//      $search[]  = '§<([/]{0,1}[e|E][m|M][b|B][e|E][d|D])§';
//      $replace[] = '&lt;$1';
//      $search[]  = '§<([/]{0,1}[o|O][b|B][j|J][e|E][c|C][t|T])§';
//      $replace[] = '&lt;$1';

      $search[]  = '~<([/]{0,1}[j|J][a|A][v|V][a|A][s|S][c|C][r|R][i|I][p|P][t|T])~u';
      $replace[] = '&lt;$1';
      $search[]  = '~<([/]{0,1}[s|S][c|C][r|R][i|I][p|P][t|T])~u';
      $replace[] = '&lt;$1';
      $search[]  = '~<([/]{0,1}[j|J][s|S][c|C][r|R][i|I][p|P][t|T])~u';
      $replace[] = '&lt;$1';
      $search[]  = '~<(\?)~u';
      $replace[] = '&lt;$1';
      $search[]  = '~(\?)>~u';
      $replace[] = '$1&gt;';
      $search[]  = '~<([/]{0,1}[e|E][m|M][b|B][e|E][d|D])~u';
      $replace[] = '&lt;$1';
      $search[]  = '~<([/]{0,1}[o|O][b|B][j|J][e|E][c|C][t|T])~u';
      $replace[] = '&lt;$1';

      $text = preg_replace($search,$replace,$text);

      return $text;
   }

   function _cleanDataFromTextArea ( $text ) {

      ### hack ###
      # unmotiviertes br ausserhalb des fck texts
      # keine Ahnung wo das her kommt
      # ij 28.05.2009
      $text = str_replace('<!-- KFC TEXT --><br />','<!-- KFC TEXT -->COMMSY_BR',$text);
      $text = str_replace('<!-- KFC TEXT --><br type="_moz" />','<!-- KFC TEXT -->COMMSY_BR',$text);
      ### hack ###

      #preg_match('~<!-- KFC TEXT [a-z0-9 ]*-->[\S|\s]*<!-- KFC TEXT [a-z0-9 ]*-->~u',$text,$values);
      preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u',$text,$values);
      foreach ($values as $key => $value) {
         $text = str_replace($value,'COMMSY_FCKEDITOR'.$key,$text);
      }
      $text = $this->_cleanDataFromTextAreaNotFromFCK($text);
      foreach ($values as $key => $value) {
         $text = str_replace('COMMSY_FCKEDITOR'.$key,$this->_cleanDataFromTextAreaFromFCK($value),$text);
      }

      ### hack ###
      $text = str_replace('COMMSY_BR',BRLF,$text);
      ### hack ###

      return $text;
   }

   function _cleanDataFromTextAreaNotFromFCK ( $text ) {
      // word and open office bugs
      $retour = str_replace('</meta>','',$text);
      // word and open office bugs

      $retour = $this->_htmlentities_smaller($retour);
      return $retour;
   }

   function _cleanDataFromTextAreaFromFCK ( $text ) {
      /*
      $values = array();
      preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u',$text,$values);
      if ( !empty($values[1]) ) {
         $hash = $values[1];
         $temp_text = str_replace('<!-- KFC TEXT '.$hash.' -->','',$text);
         include_once('functions/security_functions.php');
         if ( getSecurityHash($temp_text) != $hash ) {
            $text = '<!-- KFC TEXT '.$hash.' -->'.$this->_cleanDataFromTextAreaNotFromFCK($temp_text).'<!-- KFC TEXT '.$hash.' -->';
         }
      } else {
         #$text = $this->_cleanDataFromTextAreaNotFromFCK($text);
      }
      */
      return $text;
   }

   function _cs_htmlspecialchars ($text) {
      global $c_html_textarea;
      $text = $this->_cleanBadCode($text);
      if ( !isset($c_html_textarea) or !$c_html_textarea ) {
         $text = str_replace('<img','&lt;img',$text);
      }
      return $text;
   }

   function _cs_htmlspecialchars1 ($text) {
      $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
      global $c_html_textarea;
      if ( !isset($c_html_textarea) or !$c_html_textarea ) {
         $text = str_replace('<img','&lt;img',$text);
      }
      return $text;
   }

   function _cs_htmlspecialchars2 ($text) {
      $text = $this->_cleanBadCode($text);
      return $text;
   }

   function _text_as_html_long ($text,$htmlTextArea=true) {
      preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u',$text,$values);
      foreach ($values as $key => $value) {
         $text = str_replace($value,'COMMSY_FCKEDITOR'.$key,$text);
      }
      $text = $this->_text_as_html_long1($text,$htmlTextArea);
      foreach ($values as $key => $value) {
         $text = str_replace('COMMSY_FCKEDITOR'.$key,$this->_text_as_html_long2($value,$htmlTextArea),$text);
      }

      return $text;
   }

   // text not from FCKeditor
   function _text_as_html_long1 ($text,$htmlTextArea=false) {
      $text = $this->_cs_htmlspecialchars($text,$htmlTextArea);
      $text = nl2br($text);
      $text = $this->_decode_backslashes_1($text);
      $text = $this->_preserve_whitespaces($text);
      $text = $this->_newFormating($text);
      $text = $this->_emphasize_text($text);
      $text = $this->_activate_urls($text);
      $text = $this->_display_headers($text);
      $text = $this->_format_html_long($text);
      $text = $this->_parseText2ID($text);
      $text = $this->_decode_backslashes_2($text);
      $text = $this->_delete_unnecassary_br($text);
      $text = $this->_br_with_nl($text);
      return $text;
   }

   function _text_as_html_long_form ($text) {
      $text = nl2br($text);
      $text = $this->_preserve_whitespaces($text);
      $text = $this->_emphasize_text($text);
      $text = $this->_activate_urls($text);
      $text = $this->_display_headers($text);
      $text = $this->_format_html_long($text);
      $text = $this->_parseText2ID($text);
      $text = $this->_decode_backslashes($text);
      $text = $this->_delete_unnecassary_br($text);
      $text = $this->_br_with_nl($text);
      $text = $this->_cs_htmlspecialchars2($text);
      return $text;
   }

   // text from FCKeditor
   function _text_as_html_long2 ($text) {
      $text = $this->_cs_htmlspecialchars($text);
      $text = $this->_decode_backslashes_1_fck($text);
      $text = $this->_newFormating($text);
      $text = $this->_decode_backslashes_2_fck($text);
      $text = $this->_activate_urls($text);
      $text = $this->_parseText2ID($text);
      // html bug of fckeditor
      $text = str_replace('<br type="_moz" />','<br />',$text);
      return $text;
   }

   // @segment-begin 51609  _text_as_html_short($text)
   function _text_as_html_short ($text) {
      $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
      $text = $this->_emphasize_text($text);
      $text = $this->_decode_backslashes($text);
      return $text;
   }
    // @segment-end 51609

   function _text_as_html_short_coding_format($text) {
       $text = $this->_text_as_html_short($text);
      $text = str_replace('&lt;BR&gt;', "<br />", $text);
      $text = str_replace('&lt;SPACE&gt;', "&nbsp;", $text);
      $text = preg_replace('~&lt;DISABLED&gt;(.*)&lt;/DISABLED&gt;~u','<span class="disabled">$1</span>',$text);
      return $text;
   }

   function _text_as_form ($text) {
      $text = $this->_cs_htmlspecialchars($text);
      $text = str_replace('"','&quot;',$text);
      return $text;
   }

   function _text_as_form1 ($text) {
      $text = $this->_cs_htmlspecialchars1($text);
      $text = str_replace('"','&quot;',$text);
      return $text;
   }

   function _text_as_form2 ($text) {
      $text = $this->_text_as_html_long_form($text);
      return $text;
   }

   function _text_as_form_for_html_editor ($text) {
      $text = $this->_cs_htmlspecialchars($text);
      return $text;
   }

   //help functions for text functions ----------------------------------------------------------------------------------------------------------------------
   function _delete_unnecassary_br($text) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      $text = preg_replace ('~<br( /)?>(</h\d>)~u', '$2', $text);
      $text = preg_replace ('~<br( /)?>(</li>)~u', '</li>', $text);
      return $text;
   }

   function _emphasize_text ($text) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      // bold
      //$text = preg_replace('/(^|\n|\t|\s|[ >\/_[{(])\*([^*]+)\*($|\n|\t|[ <\/_.)\]},!?;])/', '$1<span style="font-weight:bold;">$2</span>$3', $text);
      $text = preg_replace('~\*([^*]+)\*~u', '<span style="font-weight:bold;">$1</span>', $text);
      // italic
      #$text = preg_replace('§_([^_]+)_§', '<span style="font-style:italic;">$1</span>', $text);
      $text = preg_replace('~(^|\n|\t|\s|[ >\/[{(])_([^_]+)_($|\n|\t|[ <\/.)\]},!?;])~u', '$1<span style=font-style:italic;>$2</span>$3', $text);
      return $text;
   }

   function _activate_urls ($text) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      $url_string = '^(?<=([\s|\n|>|\(]{1}))((http://|https://|ftp://|www\.)'; //everything starting with http, https or ftp followed by "://" or www. is a url and will be avtivated
      $url_string .= "([".RFC1738_CHARS."]+?))"; //All characters allowed for FTP an HTTP URL's by RFC 1738 (non-greedy because of potential trailing punctuation marks)
      $url_string .= '(?=([\.\?:\),;!]*($|\s|<|&quot;|&nbsp;)))^u'; //after the url is a space character- and perhaps before it a punctuation mark (which does not belong to the url)
      //$text = preg_replace($url_string, '$1<a href="$2" target="_blank" title="$2">$2</a>$5', $text);
      $text = preg_replace($url_string, '<a href="$2" target="_blank" title="$2">$2</a>', $text);
      $text = preg_replace_callback('~">(.[^"]+)</a>~u','spezial_chunkURL',$text);
      $text = preg_replace('~<a href="www~u','<a href="http://www',$text); //add "http://" to links that were activated with www in front only
      // mailto. A space or a linebreak has to be in front of everymail link. No links in bigger words (especially in urls) will be activated
      $text = preg_replace('^( |\^|>|\n)(mailto:)?((['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)@(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*\.([A-z]{2,})))^u', ' <a href="mailto:$3">$3</a>', $text);
      return $text;
   }

   function _display_headers ($text) {
      $matches = array();

      while (preg_match('~(^|\n)(\s*)(!+)(\s*)(.*)~u', $text, $matches)) {
         $bang_number = mb_strlen($matches[3]);
         $head_level = max(5 - $bang_number, 1); //normal (one '!') is h4, biggest is h1; The more '!', the bigger the heading
         $heading = '<h'.$head_level.'>'."\n   ".$matches[5]."\n".'</h'.$head_level.'>'."\n";
         $text = preg_replace('~(^|\n)(\s*)(!+)(\s*)(.*)~u', $heading, $text, 1);
      }

      return $text;
   }

   function _format_html_long ($text) {
      $html = '';
      $matches = array();
      $list_type = '';
      $last_list_type = '';
      $list_open = false;

      //split up paragraphs in lines
      $lines = preg_split('~\s*\n~u', $text);
      foreach ($lines as $line) {
         $line_html = '';
         $hr_line = false;
         //find horizontal rulers
         if (preg_match('~^--(-+)\s*($|\n|<)~u', $line)) {
            if ($list_open) {
               $line_html.=$this->_close_list($last_list_type);
               $list_open = false;
            }
            $line_html.= "\n".'<hr/>'."\n";
            $hr_line = true;
         }

         //process lists
         elseif (!($hr_line) and preg_match('~^(-|#)(\s*)(.*)~su', $line, $matches)) {
            $list_type = $matches[1];

            if (!$list_open) {
               $line_html .=$this->_open_list($list_type);
               $list_open = true;
               if ($list_type != $last_list_type) {
                  $last_list_type = $list_type;
               }
            } else {
               if ($list_type != $last_list_type) {
                  $line_html.=$this->_close_list($last_list_type);
                  $line_html.=$this->_open_list($list_type);
                  $last_list_type = $list_type;
               }
            }
            $line_html.= '<li>'.$matches[3].'</li>'."\n";
         }

         //All other lines without anything special
         else {
            if ($list_open) {
               $line_html.=$this->_close_list($last_list_type);
               $list_open = false;
            }
            $line_html .= $line;
         }
         $html .= $line_html;
      }
      if ($list_open) {
         $html .=$this->_close_list($last_list_type);
         $list_open = false;
      }
      return $html;
   }

   function _parseText2Id ($text) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      global $current_item_id;
      $matches_stand_alone = array();
      $matches_with_text = array();
      // ids with text: <text>[<number>] becomes a link under <text> to the commsy-object with id <number>
      preg_match_all('~([\w.'.SPECIAL_CHARS.'&;-]+)\[(\d+)\]~iu', $text, $matches_with_text);
      if (count($matches_with_text[0]) > 0) {
         $result = $text;
         $word_part = $matches_with_text[1];
         $reference_part = $matches_with_text[2];
         for ($i = 0; $i < count($word_part); $i ++) {
            $word = $word_part[$i];
            $reference = $reference_part[$i];
            if ($reference <= 100) {
               if($this->_environment->getCurrentModule() == 'discussion'){
                  $params = array();
                  $params['iid'] = $current_item_id;
                  $result = preg_replace('~'.$word.'\['.$reference.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, $word, $word, '', 'anchor'.$reference), $result);
                  unset($params);
               }
            } else {
               $params = array();
               $params['iid'] = $reference;
               $result = preg_replace('~'.$word.'\['.$reference.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, $word, '', '', ''), $result);
               unset($params);
            }
         }
         $text = $result;
      }

      // urls with text: <text>[<url>] becomes a link under <text> to the url <url>
      preg_match_all('^([.\w'.SPECIAL_CHARS.'-]+)\[(https?:\/\/['.RFC1738_CHARS.']*)\]^iu', $text, $matches_with_urls);//preg_match_all('/(\S+)(\[http:\/\/\S*\])[.:,;-?!]*($|\n|\t|<| )/', $text, $matches_with_urls);
      if (count($matches_with_urls[0]) > 0) {
   $result = $text;
   $word_part = $matches_with_urls[1];
   $http_part = $matches_with_urls[2];
   for ($i = 0; $i < count($word_part); $i++) {
      $word = $word_part[$i];
      $http = $http_part[$i];
      if (!empty($word)) {
         if (!mb_stristr($word,'|')) {
       $result = preg_replace('~'.$word.'\['.$http.'\]~u', '<a href="'.$http.'" target="_blank">'.$word.'</a>', $result);
         }
      } else {
         $result = preg_replace('~'.$word.'\['.$http.'\]~u', '<a href="'.$http.'" target="_blank">'.$http_part[$i].'</a>', $result);
      }
   }
   $text = $result;
      }

      // long urls: [<url>|<sentence with spaces>|<flag>] becomes a link to <url> under <sentence with spaces>
      // <flag> cann be "internal" or "_blank". Internal opens <url> in this browser window, _blank uses another
      preg_match_all('^\[(http?://['.RFC1738_CHARS.']*)\|([\w'.SPECIAL_CHARS.' \)?!&;-]+)\|(\w+)\]^u', $text, $matches_with_long_urls);
      if (count($matches_with_long_urls[0]) > 0) {
         $result = $text;
   $http_part = $matches_with_long_urls[1];
   $word_part = $matches_with_long_urls[2];
   $flag_part = $matches_with_long_urls[3];
   for ($i = 0; $i < count($http_part); $i++) {
      $http = $http_part[$i];
      $word = $word_part[$i];
      $flag = $flag_part[$i];
      if (!empty($word) and !empty($http) and !empty($flag)) {
         $search = '['.$http.'|'.$word.'|'.$flag.']';
         $replace = '<a href="'.$http.'" target="_blank">'.$word.'</a>';
         if ($flag == 'internal') {
       $replace = '<a href="'.$http.'">'.$word.'</a>';
         }
         $result = str_replace ( $search, $replace, $result);
      }
   }
   $text = $result;
      }

      // long urls: [ITEM_ID|<sentence with spaces>] becomes a link to <url> under <sentence with spaces>
      preg_match_all('^\[([0-9]*)\|([\w'.SPECIAL_CHARS.' \)?!&;-]+)\]^u', $text, $matches_with_long_urls);
      #preg_match_all('§\[([0-9]*)\|([\w'.SPECIAL_CHARS.' -]+)\]§', $text, $matches_with_long_urls);
      if (count($matches_with_long_urls[0]) > 0) {
         $result = $text;
   $http_part = $matches_with_long_urls[1];
   $word_part = $matches_with_long_urls[2];
   for ($i = 0; $i < count($http_part); $i++) {
      $http = $http_part[$i];
      $word = $word_part[$i];
      if (!empty($word) and !empty($http)) {
         $search = '['.$http.'|'.$word.']';
         $params = array();
         $params['iid'] = $http;
         $replace = ahref_curl($this->_environment->getCurrentContextID(),'content','detail',$params,$word);
         $result = str_replace ( $search, $replace, $result);
      }
   }
   $text = $result;
      }

      // ids without text: [<number>] becomes a link under [<number>] to the commsy-object with id <number>
      preg_match_all('~\[(\d+)\]~u', $text, $matches_stand_alone);//(^| |\n|>|\t)\[(\d+)\][.:,;-?!]*(<| |$)
      $matches_stand_alone = array_unique($matches_stand_alone[1]);
      if (!empty($matches_stand_alone)) {
   $result = $text;
   foreach ($matches_stand_alone as $item) {
      if ($item <= 100) {
         if($this->_environment->getCurrentModule() == 'discussion'){
               $params = array();
               $params['iid'] = $current_item_id;
         $result = preg_replace('~\['.$item.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, "[".$item."]", "[".$item."]", '', 'anchor'.$item), $result);
               unset($params);
         }
      }
      else {
               $params = array();
               $params['iid'] = $item;
         $result = preg_replace('~\['.$item.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, "[".$item."]", '', '', ''), $result);
               unset($params);
      }
   }
   $text = $result;
      }
      return $text;
   }

   function _decode_backslashes_1 ($text) {
      $retour = $text;
      $retour = str_replace("\*","\STERN",$retour);
      $retour = str_replace("\_","\STRICH",$retour);
      $retour = str_replace("\!","\AUSRUFEZEICHEN",$retour);
      $retour = str_replace("\-","\MINUS",$retour);
      $retour = str_replace("\#","\SCHWEINEGATTER",$retour);
      $retour = str_replace("\(:","\WIKIBEGIN",$retour);
      return $retour;
   }

   function _decode_backslashes_1_fck ($text) {
      $retour = $text;
      $retour = str_replace("\(:","\WIKIBEGIN",$retour);
      return $retour;
   }

   function _decode_backslashes_2 ($text) {
      $retour = $text;
      $retour = str_replace("\STERN","*",$retour);
      $retour = str_replace("\STRICH","_",$retour);
      $retour = str_replace("\AUSRUFEZEICHEN","!",$retour);
      $retour = str_replace("\MINUS","-",$retour);
      $retour = str_replace("\SCHWEINEGATTER","#",$retour);
      $retour = str_replace("\WIKIBEGIN","(:",$retour);
      return $retour;
   }

   function _decode_backslashes_2_fck ($text) {
      $retour = $text;
      $retour = str_replace("\WIKIBEGIN","(:",$retour);
      return $retour;
   }

   function _decode_backslashes ($text) {
      $retour = $text;
      $retour = str_replace("\*","*",$retour);
      $retour = str_replace("\_","_",$retour);
      $retour = str_replace("\!","!",$retour);
      $retour = str_replace("\-","-",$retour);
      $retour = str_replace("\#","#",$retour);
      return $retour;
   }

   function _br_with_nl ($text) {
      $text = str_replace('<br />','<br />'."\n",$text);
      return $text;
   }

   /*
   returns the html-code for opening a list
   */
   function _open_list ($list_type) {
      $html = '';
      if ($list_type == '#') {
         $html.= '<ol>'."\n";
      }
      elseif ($list_type == '-') {
         $html.= '<ul>'."\n";
      }
      return $html;
   }

   /*
   returns the html-code for closing a list
   */
   function _close_list ($list_type) {
      $html = '';
      if ($list_type == '#') {
         $html.= '</ol>'."\n";
      }
      elseif ($list_type == '-') {
         $html.= '</ul>'."\n";
      }
      return $html;
   }

   /** Wenn im Text Gruppierungen von zwei oder mehr Leerzeichen
 *  vorkommen, werden diese durch entsprechende &nbsp; Tags
 *  ersetzt, um die Ursprüngliche formatierung zu bewaren
 *
 *  Wurde aufgrund folgenden Bugs erstellt:
 *  http://sourceforge.net/tracker/index.php?func=detail&aid=1062265&group_id=49014&atid=516467
 */

   function _preserve_whitespaces($text) {
     preg_match_all('~ {2,}~u', $text, $matches);
     $matches = array_unique($matches[0]);
     rsort($matches);

     foreach ($matches as $match) {
       $replacement = ' ';

       for ($x = 1; $x < mb_strlen($match); $x++) {
         $replacement .= '&nbsp;';
       }
       $text = str_replace($match, $replacement, $text);
     }

     return $text;
   }

   /*
    * Displays png, jpg and gif images in the description area of the materials
    * Images have to be attached to the material in order to be displayed!
    * Syntax:
    * [<file_name>]
    * or
    * [<file_name>|<align>], with <align> one of 'left', 'right', 'none'
    * 'left' and 'right' position the image to the left or the right of the text, which will flow around the images
    * 'none' will place the image in the text at the position of the declaration- only small images will look fine... Blame Html ;)
    *
    * [<file_name] is equal to [<file_name>|right]
    */
   function _show_images($description,$item,$with_links = true) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------

      $return_description = "";
      $width_string = '';

      //split description in paragraphs
      //$paragraphs = preg_split('§\s*\n(\s*\n)+§', $description);
      $paragraphs = preg_split('~(\s*<br( /)?>{2,})~u', $description,-1,PREG_SPLIT_DELIM_CAPTURE);
      $file_list = $item->getFileList();
      if ( $item->isA(CS_SECTION_TYPE) ) {
         $material_item = $item->getLinkedItem();
         $file_list2 = $material_item->getFileList();
         if ( isset($file_list2) and !empty($file_list2) and $file_list2->getCount() > 0 ) {
            $file_list->addList($file_list2);
         }
         unset($file_list2);
         unset($material_item);
      }
      $file_array = $file_list->to_Array();
      unset($file_list);
      $file_name_array = array();
      //create array with filenames
      foreach ($file_array as $file) {
         $file_name_array[] = htmlentities($file->getDisplayName(), ENT_NOQUOTES, 'UTF-8');
      }
      foreach ($paragraphs as $paragraph) {
         $imgmatches = array();
         $zipmatches = array();
         //find everything in the form [<filename>.<extension>] or  [<filename>.<extension>|<align>] with <extension> one of jpg, jpeg, gif, png and <align> one of 'left', 'right', 'none'
#			$image_found_in_paragraph = preg_match_all('§\[([A-z0-9_%&$-'.SPECIAL_CHARS.']+\.(png|PNG|jpe?g|JPE?G|gif|GIF|swf|SWF))(\|(left|right|none))?\]§',$paragraph,$matches);
         $image_found_in_paragraph = preg_match_all('^\[([A-z0-9_%&$-\s'.SPECIAL_CHARS.';]+\.[A-z0-9_%&$-'.SPECIAL_CHARS.']{3,4})(\|(left|right|none))?\]^u',$paragraph,$imgmatches);
         $zip_with_html_found_in_paragraph = preg_match_all('^\[([A-z0-9_%&$-\s'.SPECIAL_CHARS.';]+\.zip)(\|(html))?\]^u',$paragraph,$zipmatches);
         $images = $imgmatches[1];
         $zips = $zipmatches[1];
         if (isset($imgmatches[3])) {
            $align_array = $imgmatches[3];
         } else {
            $align_array = array();
         }

         if ($image_found_in_paragraph > 0) {
            //create an array of the images that are appended to the material
            $files = array();
            if ( count($file_array) > 0 ) {
               $files = array();
               for ($i = 0; $i < count($images); $i++) {
                  $position = array_search($images[$i],$file_name_array);
                  if ($position !== FALSE) {
                     $temp = array();
                     $file = $file_array[$position];
                     $temp['file'] = $file;
                     if (isset($align_array[$i])) {
                        $temp['align'] = $align_array[$i]; //may be empty, check later
                     } else {
                        $temp['align'] = '';
                     }
                     $files[] = $temp;
                  }
               }
            }
            foreach ($files AS $file) {
               $align = $file['align'];
               $file = $file['file'];
               $name = $file->getDisplayName();
               $align_text = '';
               if ($align == '' or mb_stristr($file->getFilename(),'swf')) {
                  $align = '';
                  $align_text = '';
               } elseif ($align == 'none'){
                  $align = '';
                  $align_text = '|none';
               } else {
                  $align_text = '|'.$align;
                  $align = ' float:'.$align.';';
               }
               if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'swf') ) {
                  $with_links = false;
                  $show_width = '';
                  $show_height = '';

                  if (function_exists('gd_info')) {
                     $image_in_info = GetImageSize ($file->getDiskFileName());
                     $x_orig= $image_in_info[0];
                     $y_orig= $image_in_info[1];

                     $verhaeltnis = $x_orig/$y_orig;
                     $max_width = 500;
                     $max_height = 450;
                     $show_verhaeltnis = $max_width/$max_height;

                     if ($verhaeltnis > $show_verhaeltnis) {
                        $show_width = $max_width;
                        $show_height = $y_orig * ($max_width/$x_orig);
                     } else {
                        $show_width = $x_orig * ($max_height/$y_orig);
                        $show_height = $max_height;
                     }
                     $show_width .= 'px';
                     $show_height .= 'px';
                  }

                  $source = $file->getURL();
                  $image_text = '<div style="width: 100%; text-align: center; margin-bottom: 10px;">'.LF;
                  $image_text .= '   <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
                                                width="'.$show_width.'"
                                                height="'.$show_height.'"
                                                codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">'.LF;
                  $image_text .= '      <param name="movie" value="'.$source.'" />'.LF;
                  $image_text .= '      <param name="quality" value="high" />'.LF;
                  $image_text .= '      <param name="scale" value="exactfit" />'.LF;
                  $image_text .= '      <param name="menu" value="true" />'.LF;
                  $image_text .= '      <param name="bgcolor" value="#000000" />'.LF;
                  $image_text .= '      <param name="wmode" value="opaque" />'.LF;
                  $image_text .= '      <param name="play" value="false" />'.LF;
                  $image_text .= '      <param name="loop" value="false" />'.LF;
                  $image_text .= '      <param name="devicefont" value="true" />'.LF;
                  $image_text .= '      <embed src="'.$source.'" quality="high"
                                                  scale="exactfit"
                                                  menu="true"
                                                  bgcolor="#000000"
                                                  wmode="opaque"
                                                  play="false"
                                                  loop="false"
                                                  devicefont="true"
                                                  width="'.$show_width.'"
                                                  height="'.$show_height.'"
                                                  swLiveConnect="false"
                                                  type="application/x-shockwave-flash"
                                                  pluginspage="http://www.macromedia.com/go/getflashplayer">'.LF;
                  $image_text .= '      </embed>'.LF;
                  $image_text .= '   </object>'.LF;
                  $image_text .= '</div>'.LF;
               } elseif ( !mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                      and !mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                      and !mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                      and !mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                    ) {
                  $disc_manager = $this->_environment->getDiscManager();
                  if ( $disc_manager->existsFile($file->getDiskFileNameWithoutFolder()) ) {
                     $image_text = '<a href="'.$file->getUrl().'" target="_blank">'.$file->getFileIcon().' '.$file->getDisplayName().'</a>';
                     $with_links = false;
                  } else {
                     $image_text = '';
                  }

               } else {
                  $thumb_name = $this->_create_thumb_name_from_image_name($file->getDiskFileNameWithoutFolder());
                  //if there is a thumb file, use it instead
                  $disc_manager = $this->_environment->getDiscManager();
                  global $c_single_entry_point;
                  if ( $disc_manager->existsFile($thumb_name) ) {
                     $params = array();
                     $params['picture'] = $thumb_name;
                     $thumb_url = curl( $this->_environment->getCurrentContextID(),
                                  'picture',
                                  'getfile',
                                 $params,
                                 '',
                                 ''.
                                 $c_single_entry_point );
                     unset($params);
                     $image_text = '<img src="'.$thumb_url.'" alt="'.$name.'"/>';
                     $width_string ='';
                  } else {

                     $width_string = 'width:200px;';
                     if (function_exists('gd_info')) {
                        $image_in_info = @GetImageSize($file->getDiskFileName());
                        $x_orig= $image_in_info[0];
                        if ($x_orig < 200) {
                           $width_string = '';
                        }
                     }
                     $image_text = '<img src="'.$file->getUrl().'" alt="'.$name.'"/>';
                  }
                  //images can have links to their original file, or be  just images in a page...
               }
               if ( !empty($image_text) ) {
                  if ( $with_links ) {
                     $paragraph = str_replace('['.htmlentities($name, ENT_NOQUOTES, 'UTF-8').$align_text.']', '<div style="'.$align.' padding:5px;">'.'<a href="'.$file->getUrl().'">'.$image_text.'</a>'.'</div>',$paragraph);
                  } else {
                     $paragraph = str_replace('['.htmlentities($name, ENT_NOQUOTES, 'UTF-8').']','<div style="'.$align.' padding:5px;">'.'</div>'.$image_text,$paragraph);
                  }
               }
            }
            $return_description .= '<div>'.$paragraph."</div>".LF;


         }

         if($zip_with_html_found_in_paragraph > 0) {
             if ( count($file_array) > 0 ) {
               $files = array();
               for ($i = 0; $i < count($zips); $i++) {
                  $position = array_search($zips[$i],$file_name_array);
                  if ($position !== FALSE) {
                     $temp = array();
                     $file = $file_array[$position];
                     $temp['file'] = $file;
                     $files[] = $temp;
                  }
               }
            }
            foreach ($files AS $file) {
               $file = $file['file'];
               $name = $file->getDisplayName();
                  if(($file->getMime() == 'application/x-zip-compressed')
                  or ($file->getMime() == 'application/x-compressed')
                  or($file->getMime() == 'application/zip')
                  or($file->getMime() == 'multipart/x-zip')) {

                        if(($file->getHasHTML() == '0') or ($file->getHasHTML() == "")) {
                        include_once('pages/html_upload.php');
                     }
                            if($file->getHasHTML() == '2') {
                              global $c_single_entry_point;

                                           $paragraph = str_replace(
                           '['.htmlentities($name, ENT_NOQUOTES, 'UTF-8').'|html]','<a href="'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID().'&amp;mod=material&amp;fct=showzip&amp;iid='.$file->getFileID().'" target="help" onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=800, height=600\');">'.$name.'</a>',$paragraph);
                            }
                            if(($file->getHasHTML() == '1')) {
                            $paragraph = str_replace(
                           '['.htmlentities($name, ENT_NOQUOTES, 'UTF-8').'|html]',$file->getFileIcon().'&nbsp;<a href="'.$file->getUrl().'">'.$name.'</a>',$paragraph);
                            }
                         }

                      $return_description = '<div>'.$paragraph.'</div>';

            }
         }
         if(($image_found_in_paragraph <= 0) and ($zip_with_html_found_in_paragraph <= 0)) {
            $return_description .= '<div>'.$paragraph."</div>".LF;
         }
      }
      return $return_description ;
   }

   function _create_thumb_name_from_image_name($name) {
      //$thumb_name = $name;
      //$point_position = mb_strrpos($thumb_name,'.');
      //$thumb_name = substr_replace ( $thumb_name, '_thumb.png', $point_position , mb_strlen($thumb_name));
      //$string = substr($string, 0, $position_needle).$replace.substr($string, $position_needle+$length_needle);
      //$thumb_name = substr($thumb_name, 0, $point_position).'_thumb.png'.substr($thumb_name, $point_position+mb_strlen($thumb_name));
      $thumb_name = $name . '_thumb';
      return $thumb_name;
   }

   function _newFormating ( $text ) {

      $reg_exp_father_array = array();
      $reg_exp_father_array[]       = '~\\(:(.*?):\\)~eu';
      $reg_exp_father_array[]       = '~\[(.*?)\]~eu';

      $reg_exp_array = array();
      $reg_exp_array['(:flash']       = '~\\(:flash (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:quicktime']   = '~\\(:quicktime (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:wmplayer']    = '~\\(:wmplayer (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:image']       = '~\\(:image (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:item']        = '~\\(:item ([0-9]*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:link']        = '~\\(:link (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:file']        = '~\\(:file (.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:zip']         = '~\\(:zip (.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:youtube']     = '~\\(:youtube (.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:googlevideo'] = '~\\(:googlevideo (.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:vimeo']       = '~\\(:vimeo (.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:mp3']         = '~\\(:mp3 (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
      $reg_exp_array['(:lecture2go']  = '~\\(:lecture2go (.*?)(\\s.*?)?\\s*?:\\)~eu';
      if ( $this->_environment->isScribdAvailable() ) {
         $reg_exp_array['(:office']      = '~\\(:office (.*?)(\\s.*?)?\\s*?:\\)~eu';
      }
      // Test auf erforderliche Software; Windows-Server?
      //$reg_exp_array['(:pdf']      = '/\\(:pdf (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:slideshare']      = '~\\(:slideshare (.*?):\\)~eu';
      $reg_exp_array['[slideshare']      = '~\[slideshare (.*?)\]~eu';
      $reg_exp_array['(:flickr']      = '~\\(:flickr (.*?):\\)~eu';

      // jsMath for latex math fonts
      // see http://www.math.union.edu/~dpvc/jsMath/
      global $c_jsmath_enable;
      if ( isset($c_jsmath_enable)
           and $c_jsmath_enable
         ) {
         $reg_exp_father_array[]   = '~\\{\\$[\\$]{0,1}(.*?)\\$[\\$]{0,1}\\}~eu';
         $reg_exp_array['{$$']     = '~\\{\\$\\$(.*?)\\$\$\\}~eu'; // must be before next one
         $reg_exp_array['{$']      = '~\\{\\$(.*?)\\$\\}~eu';
      }

      // is there wiki syntax ?
      if ( !empty($reg_exp_array) ) {
         $reg_exp_keys = array_keys($reg_exp_array);
         $clean_text = false;
         foreach ($reg_exp_keys as $key) {
            if ( mb_stristr($text,$key) ) {
               $clean_text = true;
               break;
            }
         }
      }

      // clean wikistyle text from HTML-Code (via fckeditor)
      // and replace wikisyntax
      if ($clean_text) {

         $text_converter = $this->_environment->getTextConverter();

         $matches = array();
         foreach ($reg_exp_father_array as $exp) {
            $found = preg_match_all($exp,$text,$matches);
            if ( $found > 0 ) {
               $matches[0] = array_unique($matches[0]); // doppelte einsparen
               foreach ($matches[0] as $value) {

                  # delete HTML-tags and string conversion #########
                  $value_new = strip_tags($value);
                  $value_new = str_replace('&nbsp;',' ',$value_new);
                  ##################################################

                  foreach ($reg_exp_array as $key => $reg_exp) {
                     if ( $key == '(:flash' and mb_stristr($value_new,'(:flash') ) {
                        $value_new = $text_converter->formatFlash($value_new,$this->_getArgs($value_new,$reg_exp),$this->_getItemFileListForView());
                        break;
                     } elseif ( $key == '(:wmplayer' and mb_stristr($value_new,'(:wmplayer') ) {
                        $value_new = $this->_format_wmplayer($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:quicktime' and mb_stristr($value_new,'(:quicktime') ) {
                        $value_new = $this->_format_quicktime($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:image' and mb_stristr($value_new,'(:image') ) {
                        $value_new = $this->_format_image($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:item' and mb_stristr($value_new,'(:item') ) {
                        $value_new = $this->_format_item($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:link' and mb_stristr($value_new,'(:link') ) {
                        $value_new = $this->_format_link($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:file' and mb_stristr($value_new,'(:file') ) {
                        $value_new = $text_converter->formatFile($value_new,$this->_getArgs($value_new,$reg_exp),$this->_getItemFileListForView());
                        break;
                     } elseif ( $key == '(:zip' and mb_stristr($value_new,'(:zip') ) {
                        $value_new = $this->_format_zip($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:youtube' and mb_stristr($value_new,'(:youtube') ) {
                        $value_new = $this->_format_youtube($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:googlevideo' and mb_stristr($value_new,'(:googlevideo') ) {
                        $value_new = $this->_format_googlevideo($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:vimeo' and mb_stristr($value_new,'(:vimeo') ) {
                        $value_new = $this->_format_vimeo($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:mp3' and mb_stristr($value_new,'(:mp3') ) {
                        $value_new = $this->_format_mp3($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:lecture2go' and mb_stristr($value_new,'(:lecture2go') ) {
                        $value_new = $this->_format_lecture2go($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:office' and mb_stristr($value_new,'(:office') ) {
                        $value_new = $this->_format_office($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:pdf' and mb_stristr($value_new,'(:pdf') ) {
                        $value_new = $this->_format_pdf($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:slideshare' and mb_stristr($value_new,'(:slideshare') ) {
                        $value_new = $this->_format_slideshare($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '[slideshare' and mb_stristr($value_new,'[slideshare') ) {
                        $value_new = $this->_format_slideshare($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '{$' and mb_stristr($value_new,'{$') ) {
                        $value_new = $this->_format_math1($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '{$$' and mb_stristr($value_new,'{$$') ) {
                        $value_new = $this->_format_math2($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     } elseif ( $key == '(:flickr' and mb_stristr($value_new,'(:flickr') ) {
                        $value_new = $this->_format_flickr($value_new,$this->_getArgs($value_new,$reg_exp));
                        break;
                     }
                  }
                  $text = str_replace($value,$value_new,$text);
               }
            }
         }
      }
      return $text;
   }

   function _getItemFileListForView () {
      if ( !isset($this->_item_file_list) ) {
          if ( isset($this->_item) ) {
            if ( $this->_item->isA(CS_MATERIAL_TYPE) ) {
               $file_list = $this->_item->getFileListWithFilesFromSections();
            } elseif ( $this->_item->isA(CS_DISCUSSION_TYPE) ) {
               $file_list = $this->_item->getFileListWithFilesFromArticles();
            } else {
               $file_list = $this->_item->getFileList();
            }
          } else {
            if ($this->_environment->getCurrentModule() == 'home') {
               $current_context_item = $this->_environment->getCurrentContextItem();
               if ($current_context_item->withInformationBox()){
                  $id = $current_context_item->getInformationBoxEntryID();
                  $manager = $this->_environment->getItemManager();
                  $item = $manager->getItem($id);
                  $entry_manager = $this->_environment->getManager($item->getItemType());
                  $entry = $entry_manager->getItem($id);
                  $file_list = $entry->getFileList();
               }
            } else {
               $file_list = $this->_environment->getCurrentContextItem()->getFileList();
            }
         }
         if ( isset($this->_item) and $this->_item->isA(CS_SECTION_TYPE) ) {
            $material_item = $this->_item->getLinkedItem();
            $file_list2 = $material_item->getFileList();
            if ( isset($file_list2) and !empty($file_list2) and $file_list2->getCount() > 0 ) {
               $file_list->addList($file_list2);
            }
            unset($file_list2);
            unset($material_item);
         }
         $file_array = $file_list->to_Array();
         unset($file_list);
         $file_name_array = array();
         foreach ($file_array as $file) {
            $file_name_array[htmlentities($file->getDisplayName(), ENT_NOQUOTES, 'UTF-8')] = $file;
         }
         unset($file_array);
         $this->_item_file_list = $file_name_array;
         unset($file_name_array);
      }
      return $this->_item_file_list;
   }

   function _format_vimeo ($text, $array){
      $retour = '';
      if ( !empty($array[1]) ) {
         $source = $array[1];
      }
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( empty($args['width']) ) {
         $args['width'] = '240';
      }
      if ( empty($args['height']) ) {
         $args['height'] = '180';
      }

      if ( !empty($source) ) {
         $image_text = '';
         $image_text .= LF.'<div style="'.$float.' padding:10px;">'.LF;
         $image_text .= '<object '.LF;
         #if ($this->_environment->getCurrentBrowser() == 'MSIE' ) {
            $image_text .= '   type="application/x-shockwave-flash"'.LF;
         #}
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= 'data="http://vimeo.com/moogaloop.swf?clip_id='.$source.LF;
         $image_text .= '&amp;server=vimeo.com&amp;fullscreen=1&amp;show_title=1'.LF;
         $image_text .= '&amp;show_byline=1&amp;show_portrait=1&amp;color=00ADEF">'.LF;
         $image_text .= '>'.LF;
         $image_text .= "   <param name='wmode' value='opaque' />".LF;
         $image_text .= "   <param name='quality' value='best' />".LF;
         $image_text .= "   <param name='allowfullscreen' value='true' />".LF;
         $image_text .= "   <param name='scale' value='showAll' />".LF;
         $image_text .= "   <param name='movie' value='http://vimeo.com/moogaloop.swf?clip_id=".$source."&amp;server=vimeo.com&amp;fullscreen=1&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;color=00ADEF' />".LF;
         $image_text .= '</object>';
         $image_text .= '</div>'.LF;
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   function _format_googlevideo ($text, $array){
      $retour = '';
      if ( !empty($array[1]) ) {
         $source = $array[1];
      }
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( !empty($source) ) {
         $image_text = '';
         $image_text .= LF.'<div style="'.$float.' padding:10px;">'.LF;
         $image_text .= '<embed type="application/x-shockwave-flash"'.LF;
         $image_text .= '   src="http://video.google.com/googleplayer.swf?docId='.$source.'&hl=en"'.LF;
         $image_text .= '   wmode="opaque"'.LF;
         $image_text .= '   id="VideoPlayback"'.LF;
         $image_text .= '   flashvars=""'.LF;
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '>'.LF;
         $image_text .= '</embed>'.LF;
         $image_text .= '</div>'.LF;
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   function _format_youtube ($text, $array){
      $retour = '';
      if ( !empty($array[1]) ) {
         $source = $array[1];
      }
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( !empty($source) ) {
         $image_text = '';
         $image_text .= LF.'<div style="'.$float.' padding:10px;">'.LF;
         $image_text .= '<object '.LF;
         if ($this->_environment->getCurrentBrowser() == 'MSIE' ) {
            $image_text .= '   type="application/x-shockwave-flash"'.LF;
         }
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '>'.LF;
         $image_text .= '<param name="movie" value="http://www.youtube.com/v/'.$source.'" />'.LF;
         $image_text .= '<param name="wmode" value="opaque" />'.LF;
         $image_text .= '<embed type="application/x-shockwave-flash"'.LF;
         $image_text .= '   src="http://www.youtube.com/v/'.$source.'"'.LF;
         $image_text .= '   wmode="opaque"'.LF;
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '>'.LF;
         $image_text .= '</embed>'.LF;
         $image_text .= '</object>';
         $image_text .= '</div>'.LF;
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   function _format_wmplayer ($text, $array){
      $retour = '';
      if ( empty($array[1]) ) {
         // internal resource
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[2]];
         if ( isset($file) ) {
            $source = $file->getURL();
            $ext = $file->getExtension();
            $extern = false;
         }
      } else {
         $source = $array[1].$array[2];
         $ext = cs_strtolower(mb_substr(strrchr($source,'.'),1));
         $extern = true;
      }
      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['play']) ) {
         $play = $args['play'];
      } else {
         $play = 'false';
      }
      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }
      if ( !empty($source) ) {
         $image_text = '';
         $image_text .= '<div style="'.$float.' padding:10px;">'.LF;
         $image_text .= '<OBJECT ID="MediaPlayer18"'.LF;
         $image_text .= '   CLASSID="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95"'.LF;
         $image_text .= '   CODEBASE="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715"'.LF;
         $image_text .= '   STANDBY="Loading Microsoft Windows Media Player components..."'.LF;
         $image_text .= '   TYPE="application/x-oleobject"'.LF;
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '>'.LF;
         $image_text .= '<param name="fileName" value="'.$source.'" />'.LF;
         $image_text .= '<param name="autoStart" value="'.$play.'" />'.LF;
         $image_text .= '<param name="showControls" value="true" />'.LF;
         $image_text .= '<param name="showStatusBar" value="true" />'.LF;
         $image_text .= '<param name="wmode" value="opaque" />'.LF;
         $image_text .= '<embed type="application/x-mplayer2"'.LF;
         $image_text .= '   pluginspage="http://www.microsoft.com/Windows/MediaPlayer/"'.LF;
         $image_text .= '   src="'.$source.'"'.LF;
         $image_text .= '   name="MediaPlayer18"'.LF;
         $image_text .= '   wmode="opaque"'.LF;
         $image_text .= '   autostart='.$play.''.LF;
         $image_text .= '   showcontrols=1'.LF;
         $image_text .= '   showStatusBar=1'.LF;
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '>';
         $image_text .= '</embed>'.LF;
         $image_text .= '</object>';
         $image_text .= '</div>'.LF;
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   function _format_quicktime ($text, $array) {
      $retour = '';
      if ( empty($array[1]) ) {
         // internal resource
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[2]];
         if ( isset($file) ) {
            $source = $file->getURL();
            $ext = $file->getExtension();
            $extern = false;
         }
      } else {
         $source = $array[1].$array[2];
         $ext = cs_strtolower(mb_substr(strrchr($source,'.'),1));
         $extern = true;
      }
      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['play']) ) {
         $play = $args['play'];
      } else {
         $play = 'false';
      }
      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( mb_strtolower($ext, 'UTF-8') == 'mp3' ) {
         $args['height'] = 16;
      }

      if ( !empty($source) ) {
         $image_text = '';
         $image_text .= '<div style="'.$float.' padding:10px;">'.LF;
         $image_text .= '<object type="video/quicktime" ';
         $image_text .= '   classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" ';
         $image_text .= '   codebase="http://www.apple.com/qtactivex/qtplugin.cab" ';
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '>';
         $image_text .= '<param name="src" value="'.$source.'" />'.LF;
         $image_text .= '<param name="controller" value="true" />'.LF;
         $image_text .= '<param name="quality" value="high" />'.LF;
         $image_text .= '<param name="scale" value="tofit" />'.LF;
         $image_text .= '<param name="bgcolor" value="#000000" />'.LF;
         $image_text .= '<param name="wmode" value="opaque" />'.LF;
         $image_text .= '<param name="autoplay" value="'.$play.'" />'.LF;
         $image_text .= '<param name="loop" value="false" />'.LF;
         $image_text .= '<param name="devicefont" value="true" />'.LF;
         $image_text .= '<param name="class" value="mov" />'.LF;
         $image_text .= '<embed src="'.$source.'"'.LF;
         $image_text .= '   quality="high"'.LF;
         $image_text .= '   scale="tofit"'.LF;
         $image_text .= '   controller=true'.LF;
         $image_text .= '   bgcolor="#000000"'.LF;
         $image_text .= '   wmode="opaque"'.LF;
         $image_text .= '   autoplay="'.$play.'"'.LF;
         $image_text .= '   loop=false'.LF;
         $image_text .= '   devicefont=true'.LF;
         if ( !empty($args['width']) ) {
            $image_text .= '   width="'.$args['width'].'"'.LF;
         }
         if ( !empty($args['height']) ) {
            $image_text .= '   height="'.$args['height'].'"'.LF;
         }
         $image_text .= '   type="video/quicktime"'.LF;
         $image_text .= '   class="mov"'.LF;
         $image_text .= '   pluginspage="http://www.apple.com/quicktime/download/">'.LF;
         $image_text .= '</embed>'.LF;
         $image_text .= '</object>'.LF;
         $image_text .= '</div>'.LF;
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   function _format_mp3 ( $text, $array ) {
      if ($this->_environment->getCurrentBrowser() == 'MSIE' ) {
         // der IE7 unter Windows Vista verkleinert das Flash-Fenster von unten
         // so dass die Steuerelemente nicht mehr zu sehen sind
         // daher wird hier auf den Mediaplayer ausgewichen
         return $this->_format_wmplayer($text, $array);
//         return $this->_format_quicktime($text, $array); Alte Ausweichoption, die nicht funktioniert hat.
      }

      $retour = '';
      if ( empty($array[1]) ) {
         // internal resource
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[2]];
         if ( isset($file) ) {
            $source = $file->getURL();
            $ext = $file->getExtension();
            $extern = false;
         }
      } else {
         $source = $array[1].$array[2];
         $ext = cs_strtolower(mb_substr(strrchr($source,'.'),1));
         $extern = true;
      }
      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['play']) ) {
         $play = $args['play'];
      } else {
         $play = 'false';
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( !empty($source) ) {
         $image_text = '';
         // use flv media player for mp3
         // see: http://www.jeroenwijering.com/?item=JW_FLV_Media_Player
         if ( empty($args['width']) ) {
            $args['width'] = '250';
         }
         $args['height'] = '20';
         $source = str_replace('&amp;','&',$source);
         $source = urlencode($source);
         $div_number = $this->_getDivNumber();
         $image_text .= '<div id="id'.$div_number.'" style="'.$float.' padding:10px;">'.getMessage('COMMON_GET_FLASH').'</div>'.LF;
         $image_text .= '<script type="text/javascript">'.LF;
         $image_text .= '  var so = new SWFObject(\'mediaplayer.swf\',\'mpl\',\''.$args['width'].'\',\''.$args['height'].'\',\'8\');'.LF;
         $image_text .= '  so.addParam(\'wmode\',\'opaque\');'.LF;
         $image_text .= '  so.addVariable(\'file\',"'.$source.'");'.LF;
         $image_text .= '  so.addVariable(\'autostart\',\''.$play.'\');'.LF;
         $image_text .= '  so.addVariable(\'showstop\',\'true\');'.LF;
         if ( !empty($ext) ) {
            $image_text .= '  so.addVariable(\'type\',\''.$ext.'\');'.LF;
         }
         $image_text .= '  so.addVariable(\'showdigits\',\'true\');'.LF;
         $image_text .= '  so.addVariable(\'shownavigation\',\'true\');'.LF;
         $image_text .= '  so.write(\'id'.$div_number.'\');'.LF;
         $image_text .= '</script>'.LF;

         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   // experimental
   function _format_lecture2go ( $text, $array ) {

      $retour = '';
      $source = $array[1];

      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['play']) ) {
         $play = $args['play'];
      } else {
         $play = 'false';
      }

      $factor = 0.625; // orig = 1;
      $current_context_item = $this->_environment->getCurrentContextItem();
      if ( $current_context_item->isDesign6() ) {
         $factor = 0.524;
      }
      if ( !empty($args['width']) ) {
         $width = $args['width'];
      } else {
         $width = 960*$factor;
      }
      if ( !empty($args['height']) ) {
         $height = $args['height'];
      } else {
         $height = 500*$factor+(14/$factor)-6;
      }

      if ( !empty($args['image']) ) {
         $image = $args['image'];
      } else {
         $image = 'http://lecture2go.uni-hamburg.de/logo/l2g-flash.jpg';
      }

      if ( !empty($args['server']) ) {
         $server = $args['server'];
      } else {
         $server = 'rtmp://fms.rrz.uni-hamburg.de:1936/vod';
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      # color
      $current_context_item = $this->_environment->getCurrentContextItem();
      $color_array = $current_context_item->getColorArray();
      if ( !empty($color_array['tabs_background']) ) {
         $color = $color_array['tabs_background'];
         $color = str_replace('#','0x',$color);
      } else {
         $color = '0xF17B0D';
      }

      if ( !empty($source) ) {
         $image_text = '';
         $div_number = $this->_getDivNumber();
         if ( $this->_environment->getCurrentBrowser() != 'MSIE' ) {
            $image_text .= '<script type="text/javascript" src="http://lecture2go.rrz.uni-hamburg.de/flowplayer/js/flashembed.min.js"></script>'.LF;
            $image_text .= '<script src="http://lecture2go.rrz.uni-hamburg.de/dini/flash_detect/new_detection_kit/AC_OETags.js" language="javascript" type="text/javascript"></script>'.LF;
            $image_text .= '<script type="text/javascript">'.LF;
            $image_text .= 'function insertFile(serv,file){'.LF;
            $image_text .= '    flashembed("id'.$div_number.'",'.LF;
            $image_text .= '                {'.LF;
            $image_text .= '                    wmode: "opaque",'.LF;
            $image_text .= '                    src:"http://lecture2go.rrz.uni-hamburg.de/flowplayer/FlowPlayerDark.swf",'.LF;
            // breite und hoehe definieren
            $image_text .= '                    width: '.$width.','.LF;
            $image_text .= '                    height: '.$height.''.LF;
            $image_text .= '                },'.LF;
            $image_text .= '                {config: {'.LF;
            $image_text .= '                    autoPlay: '.$play.','.LF;
            $image_text .= '                    controlBarBackgroundColor: "'.$color.'",'.LF;
            $image_text .= '                    controlBarGloss: "yes",'.LF;
            // logo
            $image_text .= '                    splashImageFile: "'.$image.'",'.LF;
            // mp4 datei
            $image_text .= '                    videoFile: "mp4:"+file,'.LF;
            // hier streamer, dieser ist variabel:
            // beispiel: rtmp://fms.rrz.uni-hamburg.de:1936/vod
            //           rtmp://fms.rrz.uni-hamburg.de:1938/vod
            //           rtmp://fms.rrz.uni-hamburg.de:1942/vod
            $image_text .= '                    streamingServerURL: serv,'.LF;
            // hier wasserzeichen, wird nach lizenzaktivierung angezeigt
            $image_text .= '                    showWatermark:"always",'.LF;
            $image_text .= '                    watermarkUrl:"http://lecture2go.uni-hamburg.de/wasserzeichen/l2g.jpg",'.LF;
            $image_text .= '                    watermarkLinkUrl:"http://lecture2go.uni-hamburg.de"'.LF;
            $image_text .= '                }}'.LF;
            $image_text .= '            );'.LF;
            $image_text .= '}'.LF;

            // @serv pfad zum streamer
            // @file pfad zur datei
            $image_text .= 'function showPlayer(serv,file){'.LF;
            $image_text .= '    var requiredMajorVersion=9;'.LF;
            $image_text .= '    var requiredMinorVersion=0;'.LF;
            $image_text .= '    var requiredRevision=115;'.LF;

            // Version check based upon the values entered above in "Globals"
            $image_text .= '    var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);'.LF;

            // Check to see if the version meets the requirements for playback
            $image_text .= '    if (hasReqestedVersion) {'.LF;
            $image_text .= '        insertFile(serv,file);'.LF;
            $image_text .= '    }'.LF;
            $image_text .= '}'.LF;
            $image_text .= '</script>'.LF;
            $text_without_flash = '<div>'.$this->_translator->getMessage('COMMON_GET_FLASH_LECTURE2GO').'</div>';
            $image_text .= '<div id="id'.$div_number.'" style="'.$float.' padding:10px; width:'.$width.'px; height:'.$height.'px;">'.LF.$text_without_flash.LF.'</div>'.LF;
            $image_text .= '<script type="text/javascript">showPlayer("'.$server.'","'.$source.'")</script>'.LF;
         }

         // in IE7 occur problems with mootools and flashembed.min.js
         // so embed flowplayer directly into html
         else {
            $image_text .= LF.'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" '.LF;
            $image_text .= 'width="'.$width.'" height="'.$height.'" id="'.$div_number.'"'.LF;
            $image_text .= '>'.LF;
            $image_text .= '   <param name="movie" value="http://lecture2go.rrz.uni-hamburg.de/flowplayer/FlowPlayerDark.swf" />'.LF;
            $image_text .= '   <param name="quality" value="high" />'.LF;
            $image_text .= '   <param name="bgcolor" value="#ffffff" />'.LF;
            $image_text .= '   <param name="wmode" value="opaque" />'.LF;
            $image_text .= '   <param name="allowfullscreen" value="true" />'.LF;
            $image_text .= '   <param name="allowscriptaccess" value="always" />'.LF;
            $image_text .= '   <param name="type" value="application/x-shockwave-flash" />'.LF;
            $image_text .= '   <param name="pluginspage" value="http://www.adobe.com/go/getflashplayer" />'.LF;
            $image_text .= '   <param name="flashvars" value=\'config={
                    autoPlay: '.$play.',
                    controlBarBackgroundColor: "'.$color.'",
                    controlBarGloss: "yes",
                    splashImageFile: "'.$image.'",
                    videoFile: "mp4:'.$source.'",
                    streamingServerURL: "'.$server.'",
                    showWatermark:"always",
                    watermarkUrl:"http://lecture2go.uni-hamburg.de/wasserzeichen/l2g.jpg",
                    watermarkLinkUrl:"http://lecture2go.uni-hamburg.de"
                }\' />'.LF;
            $image_text .= '</object>'.LF;
         }
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }

   function _format_office ($text, $array){

      // Abfrage auf curl-Funktionen einbauen.

      $retour = '';

      if ( !empty($array[1]) ) {
         $source = $array[1];
      }
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['orientation'])
           and ( $args['orientation'] == 'portrait'
                 or $args['orientation'] == 'landscape'
               )
         ) {
         $orientation = $args['orientation'];
      } else {
         $orientation = 'portrait';
      }

      if ( !empty($source) ) {
        global $c_commsy_path_file;
        include_once($c_commsy_path_file . '/classes/external_classes/scribd/scribd.php');
        $file_name_array = $this->_getItemFileListForView();
        $file = $file_name_array[$source];

        if ( isset($file) ) {
            if(($file->getScribdDocId() == '') && ($file->getScribdAccessKey() == '')){
                $scribd_api_key = $this->_environment->getServerItem()->getScribdApiKey();
                $scribd_secret = $this->_environment->getServerItem()->getScribdSecret();
                $scribd = new Scribd($scribd_api_key, $scribd_secret, "CommSy");
                $filename = $c_commsy_path_file . "/" . $file->getDiskFileName();
                $doc_type = null;
                $access = "private";
                $rev_id = null;
                $result = $scribd->upload($filename, $doc_type, $access, $rev_id);
                $file->setScribdDocId($result['doc_id']);
                $file->setScribdAccessKey($result['access_key']);
                $file->saveExtras();
                $result['doc_id'] = $file->getScribdDocId();
                $result['access_key'] = $file->getScribdAccessKey();
            } else {
                $result['doc_id'] = $file->getScribdDocId();
                $result['access_key'] = $file->getScribdAccessKey();
            }
        }

        $office_text = '';

//        $office_text .= "<script type='text/javascript' src='http://www.scribd.com/javascripts/view.js'></script>".LF;
//        $office_text .= "<div id='embedded_flash_" . $result['doc_id'] . "' >".LF;
//        $office_text .= "</div>".LF;
//
//        $office_text .= '<script type="text/javascript">'.LF;
//        $office_text .= "var scribd_doc = scribd.Document.getDoc(" . $result['doc_id'] . ", '" . $result['access_key'] . "');".LF;
//        if ( $orientation == 'portrait' ) {
//           $office_text .= "scribd_doc.addParam('height', 740);".LF;
//           $office_text .= "scribd_doc.addParam('width', 520);".LF;
//        } elseif ( $orientation == 'landscape' ) {
//           $office_text .= "scribd_doc.addParam('height', 420);".LF;
//           $office_text .= "scribd_doc.addParam('width', 520);".LF;
//        }
//        $office_text .= "scribd_doc.addParam('page', 1);".LF;
//        $office_text .= "scribd_doc.addParam('public', true);".LF;
//        $office_text .= "scribd_doc.addParam('mode', 'slideshow');".LF;
//        $office_text .= "scribd_doc.write('embedded_flash_" . $result['doc_id'] . "');".LF;
//        $office_text .= "</script>".LF;

//            $office_text .= '<object codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" id="doc_539262106603200" name="doc_539262106603200" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" align="middle"  height="500" width="100%">'.LF;
//            $office_text .= '<param name="movie" value="http://documents.scribd.com/ScribdViewer.swf?document_id=' . $result['doc_id'] . '&access_key=' . $result['access_key'] . '&page=&version=1&auto_size=true">'.LF;
//            $office_text .= '<param name="quality" value="high">'.LF;
//            $office_text .= '<param name="play" value="true">'.LF;
//            $office_text .= '<param name="loop" value="true">'.LF;
//            $office_text .= '<param name="scale" value="showall">'.LF;
//            $office_text .= '<param name="wmode" value="opaque">'.LF;

//            original: opaque, aber da div layer probleme, lieber transparent -> prüfen
//            $office_text .= '<param name="wmode" value="opaque">'.LF;

//            $office_text .= '<param name="devicefont" value="false">'.LF;
//            $office_text .= '<param name="bgcolor" value="#ffffff">'.LF;
//            $office_text .= '<param name="menu" value="true">'.LF;
//            $office_text .= '<param name="allowFullScreen" value="true">'.LF;
//            $office_text .= '<param name="allowScriptAccess" value="always">'.LF;
//            $office_text .= '<param name="salign" value="">'.LF;

        if ( $orientation == 'portrait' ) {
           $scribdHeight = 740;
           $scribdWidth = 520;
        } elseif ( $orientation == 'landscape' ) {
           $scribdHeight = 420;
           $scribdWidth = 520;
        }
        $office_text .= '<embed src="http://documents.scribd.com/ScribdViewer.swf?document_id=' . $result['doc_id'] . '&access_key=' . $result['access_key'] . '&page=&version=1&auto_size=true" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" play="true" loop="true" scale="showall" wmode="opaque" devicefont="false" bgcolor="#ffffff" name="doc_' . $result['doc_id'] . '_object" menu="false" allowfullscreen="true" allowscriptaccess="always" salign="" type="application/x-shockwave-flash" align="middle" height="' . $scribdHeight . '" width="' . $scribdWidth . '"></embed>'.LF;

//            $office_text .= '</object>'.LF;
      }

      $retour = $office_text;
      return $retour;
   }

   function _format_pdf ($text, $array){
      $retour = '';

      if ( !empty($array[1]) ) {
         $source = $array[1];
      }
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($source) ) {
         global $c_commsy_path_file, $c_commsy_domain, $c_commsy_url_path;
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$source];

         if ( isset($file) ) {

            if(($file->getFdViewerFile() == '')){
                $oldDir = getcwd();
                chdir($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());
                $ausgabe = exec('pdf2swf ' . $file->getDiskFileNameWithoutFolder());
                $ausgabe = exec('swfcombine ' . $c_commsy_path_file . '/etc/fdviewer/fdviewer.swf \'#1\'=' . $c_commsy_path_file . "/" . mb_substr($file->getDiskFileNameWithoutFolder(), 0, -3) . 'swf -o ' . mb_substr($file->getDiskFileNameWithoutFolder(), 0, -3) . 'fdviewer.swf');
                chdir($oldDir);

                $file->setFdViewerFile(mb_substr($file->getDiskFileNameWithoutFolder(), 0, -3) . 'fdviewer.swf');
                $file->saveExtras();
            }

            global $c_single_entry_point;
            $embed = $c_single_entry_point.'?cid=' . $this->_environment->getCurrentContextID() . '&mod=fdviewer&fct=getfile&file=' . $file->getFdViewerFile();
            $retour .= '<object width="600" height="500">';
            $retour .= '<param name="movie" value="' . $embed . '">';
            $retour .= '<embed src="' . $embed . '" width="600" height="500">';
            $retour .= '</embed>';
            $retour .= '</object>';
         }
      }
      return $retour;
   }

   function _format_slideshare ($text, $array){
      $retour = '';
      if ( !empty($array[1]) ) {
         $slideshare_doc = array();
         $slideshare_id = array();
         if(mb_substr($array[0],0,1) == '('){
            $slideshare_doc[] = $array[1];
            $slideshare_id[] = $array[1];
         }
         if(mb_substr($array[0],0,1) == '['){
            // Different PHP-versions/installations can handle either '&' or '&amp;'
            preg_match('~(?<=id=)(.*)(?=&amp;doc)~u', $array[1], $slideshare_id);
            if(empty($slideshare_id)){
               preg_match('~(?<=id=)(.*)(?=&doc)~u', $array[1], $slideshare_id);
            }
            preg_match('~(?<=&amp;doc=)(.*)~u', $array[1], $slideshare_doc);
            if(empty($slideshare_doc)){
               preg_match('~(?<=&doc=)(.*)~u', $array[1], $slideshare_doc);
            }
         }
         $retour .= '<div style="width:425px;text-align:left" id="__ss_' . $slideshare_id[0] . '">';
         $retour .= '<object style="margin:0px" width="425" height="355">';
         $retour .= '<param name="movie" value="http://static.slideshare.net/swf/ssplayer2.swf?doc=' . $slideshare_doc[0] . '&rel=0&stripped_title=building-a-better-debt-lead" />';
         $retour .= '<param name="allowFullScreen" value="true"/>';
         $retour .= '<param name="allowScriptAccess" value="always"/>';
         $retour .= '<embed src="http://static.slideshare.net/swf/ssplayer2.swf?doc=' . $slideshare_doc[0] . '&rel=0" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="355" wmode="opaque">';
         $retour .= '</embed>';
         $retour .= '</object>';
         $retour .= '</div>';
      }
      return $retour;
   }

   function _format_flickr($text, $array){
      $retour = '';
      if ( !empty($array[1]) ) {
// Erste Version mit Angabe von Benutzer und Tag/Set
//         $flickr_array = split(' ', $array[1]);
//         $flickr_user_array = split('=', $flickr_array[0]);
//         $flickr_user = $flickr_user_array[1];
//         $flicker_id_stream = fopen('http://api.flickr.com/services/rest/?method=flickr.people.findByUsername&api_key=4f97257bac19849ee0bcdeb67537b01c&username=' . $flickr_user,"r");
//         $flicker_id_stream_contents = stream_get_contents($flicker_id_stream);
//         fclose($flicker_id_stream);
//         if(mb_stristr($flicker_id_stream_contents, 'stat="ok"')){
//            $xml_parser = xml_parser_create();
//            xml_parse_into_struct($xml_parser, $flicker_id_stream_contents, $values, $index);
//            foreach($values as $value){
//               if($value['tag'] == 'USER' and $value['type'] == 'open'){
//                  if(isset($value['attributes']['ID'])){
//                     $flicker_id = $value['attributes']['ID'];
//                  }
//               }
//            }
//         } else {
//            $flicker_id_stream = fopen('http://api.flickr.com/services/rest/?method=flickr.people.getInfo&api_key=4f97257bac19849ee0bcdeb67537b01c&user_id=' . $flickr_user,"r");
//            $flicker_id_stream_contents = stream_get_contents($flicker_id_stream);
//            fclose($flicker_id_stream);
//            if(mb_stristr($flicker_id_stream_contents, 'stat="ok"')){
//               $flicker_id = $flickr_user;
//            }
//         }
//         if(isset($flicker_id)){
//            $flickr_source_array = split('=', $flickr_array[1]);
//            if($flickr_source_array[0] == 'tag'){
//               $flickr_tag = $flickr_source_array[1];
//               //$retour .= '<iframe align="center" src="http://www.flickr.com/slideShow/index.gne?group_id=&user_id=' . $flicker_id . '&set_id=&tags=' . $flickr_tag . '" frameBorder="0" width="500" height="500" scrolling="no"></iframe>'.LF;
//               $retour .= '<object type="text/html" data="http://www.flickr.com/slideShow/index.gne?user_id=' . $flicker_id . '&tags=' . $flickr_tag . '" width="500" height="500"> </object>'.LF;
//            } elseif ($flickr_source_array[0] == 'set'){
//               $flickr_set = $flickr_source_array[1];
//               //$retour .= '<iframe align="center" src="http://www.flickr.com/slideShow/index.gne?group_id=&user_id=' . $flicker_id . '&set_id=&set_id=' . $flickr_set . '" frameBorder="0" width="500" height="500" scrolling="no"></iframe>'.LF;
//               //$retour .= '<object type="text/html" data="http://www.flickr.com/slideShow/index.gne?user_id=' . $flicker_id . '&set_id=' . $flickr_set . '" width="500" height="500"> </object>'.LF;
//            }
//         } else {
//            $retour .= $this->_translator->getMessage('WIKI_FLICKR_NO_ID_FOUND');
//         }
         $flickr_link_array = split('/', $array[1]);
// Zweite Version - allerdings wird ein API-Key benötigt.
//         $flicker_id_stream = fopen('http://api.flickr.com/services/rest/?method=flickr.people.findByUsername&api_key=4f97257bac19849ee0bcdeb67537b01c&username=' . $flickr_link_array[4],"r");
//         $flicker_id_stream_contents = stream_get_contents($flicker_id_stream);
//         fclose($flicker_id_stream);
//         pr($flicker_id_stream_contents);
//         if(mb_stristr($flicker_id_stream_contents, 'stat="ok"')){
//            $xml_parser = xml_parser_create();
//            xml_parse_into_struct($xml_parser, $flicker_id_stream_contents, $values, $index);
//            foreach($values as $value){
//               if($value['tag'] == 'USER' and $value['type'] == 'open'){
//                  if(isset($value['attributes']['ID'])){
//                     $flicker_id = $value['attributes']['ID'];
//                  }
//               }
//            }
//            if($flickr_link_array[5] == 'sets'){
//               $retour .= '<object type="text/html" data="http://www.flickr.com/slideShow/index.gne?user_id=' . $flicker_id . '&set_id=' . $flickr_link_array[6] . '" width="500" height="500"> </object>'.LF;
//            } elseif ($flickr_link_array[5] == 'tags'){
//               $retour .= '<object type="text/html" data="http://www.flickr.com/slideShow/index.gne?user_id=' . $flicker_id . '&tags=' . $flickr_link_array[6] . '" width="500" height="500"> </object>'.LF;
//            }
//         } else {
//            $retour .= $this->_translator->getMessage('WIKI_FLICKR_NO_ID_FOUND');
//         }
         if($flickr_link_array[5] == 'sets'){
            $retour .= '<object type="text/html" data="http://www.flickr.com/slideShow/index.gne?set_id=' . $flickr_link_array[6] . '" width="500" height="500"> </object>'.LF;
         }
      }
      return $retour;
   }

   function _getDivNumber() {
      if ( !isset($this->_div_number) ) {
         $this->_div_number = '1';
      } else {
         $this->_div_number++;
      }
      return $this->_div_number;
   }

   function _format_image ( $text, $array ) {
      $retour = '';
      $image_text = '';
      if ( empty($array[1]) ) {
         // internal resource
         $file_name_array = $this->_getItemFileListForView();
         $temp_file_name = htmlentities($array[2], ENT_NOQUOTES, 'UTF-8');
         if ( !empty($array[2]) and !empty($file_name_array[$temp_file_name]) ) {
            $file = $file_name_array[$temp_file_name];
         }
         if ( isset($file) ) {
            if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                 or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                 or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                 or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
               ) {
               $source = $file->getUrl();
               $thumb_name = $this->_create_thumb_name_from_image_name($file->getDiskFileNameWithoutFolder());
               //if there is a thumb file, use it instead
               $disc_manager = $this->_environment->getDiscManager();
               if ( $disc_manager->existsFile($thumb_name) ) {
                  $params = array();
                  $params['picture'] = $thumb_name;
                  global $c_single_entry_point;
                  $thumb_source = curl( $this->_environment->getCurrentContextID(),
                                        'picture',
                                        'getfile',
                                        $params,
                                        '',
                                        $thumb_name, // ''. ???
                                        $c_single_entry_point );
                  unset($params);
               } else {
                  $width_auto = 200;
                  if ( function_exists('gd_info')
                       and file_exists($file->getDiskFileName())
                     ) {
                     $image_in_info = GetImageSize($file->getDiskFileName());
                     $x_orig= $image_in_info[0];
                     if ( $x_orig < $width_auto ) {
                        $width_auto = '';
                     }
                  }
               }
            }
         }
      } else {
         $source = $array[1].$array[2];
      }

      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['alt']) ) {
         $alt = $args['alt'];
      } elseif ( !empty($source) )  {
         $alt = 'image: '.$source;
      }
      if ( !empty($args['gallery']) ) {
         $gallery = '['.$args['gallery'].']';
      } elseif ( !empty($source) )  {
         $gallery = '';
      }
      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }
      if ( !empty($args['height'])
           and is_numeric($args['height'])
         ) {
         $height = 'height:'.$args['height'].'px;';
      } else {
         $height = '';
      }
      if ( !empty($args['width'])
           and is_numeric($args['width'])
         ) {
         $width = 'width:'.$args['width'].'px;';
      } elseif ( !empty($width_auto)
                 and empty($height)
               ) {
         $width = 'width:'.$width_auto.'px;';
      } else {
         $width = '';
      }
      if ( ( !empty($thumb_source)
             and empty($height)
             and empty($width)
           )
         ) {
         $source2 = $thumb_source;
      } elseif ( !empty($source) ) {
         $source2 = $source;
      }

      if ( !empty($source) ) {
         $image_text .= '<div style="'.$float.$height.$width.' padding:5px;">';
         $image_text .= '<a href="'.$source.'" rel="lightbox'.$gallery.'">';
         $image_text .= '<img style="'.$height.$width.'" src="'.$source2.'" alt="'.$alt.'"/>';
         $image_text .= '</a>';
         $image_text .= '</div>';
      }

      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }


   function _format_item ( $text, $array ) {
      $retour = '';
      $image_text = '';
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['text']) ) {
         $word = $args['text'];
      } else {
         $word = '';
      }

      if ( !empty($args['target']) ) {
         $target = $args['target'];
      } elseif ( !empty($args['newwin']) ) {
         $target = '_blank';
      } else {
         $target = '';
      }

      if ( !empty($array[1]) ) {
          $params = array();
          $params['iid'] = $array[1];
          if ( empty($word) ) {
             $word = $array[1];
          }
          $image_text = ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, $word, '', $target, '');
      }
      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   function _format_link ( $text, $array ) {
      $retour = '';
      $image_text = '';
      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['text']) ) {
         $word = $args['text'];
      } else {
         $word = '';
      }

      if ( !empty($args['target']) ) {
         $target = ' target="'.$args['target'].'"';
      } elseif ( !empty($args['newwin']) ) {
         $target = ' target=_blank;';
      } else {
         $target = '';
      }

      if ( empty($array[1]) ) {
         $source = 'http://'.$array[2];
      } else {
         $source = $array[1].$array[2];
      }

      if ( !empty($source) ) {
          if ( empty($word) ) {
             $word = $source;
          }
          $image_text = '<a href="'.$source.'"'.$target.'>'.$word.'</a>';
      }
      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   function _format_zip ( $text, $array ) {
      $retour = '';
      $image_text = '';
      if ( !empty($array[1]) ) {
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[1]];
         if ( isset($file)
              and ( $file->getMime() == 'application/x-zip-compressed'
                    or $file->getMime() == 'application/x-compressed'
                    or $file->getMime() == 'application/zip'
                    or $file->getMime() == 'multipart/x-zip'
                  )
            ) {

            if ( $file->getHasHTML() == '0'
                 or $file->getHasHTML() == ""
                ) {
                include_once('pages/html_upload.php');
            }

            if($file->getHasHTML() == '2') {
               if ( !empty($array[2]) ) {
                  $args = $this->_parseArgs($array[2]);
               } else {
                  $args = array();
               }

               if ( !empty($args['text']) ) {
                  $name = $args['text'];
               } else {
                  $name = $file->getDisplayName();
               }

               $source = $file->getUrl();
               global $c_single_entry_point;
               $image_text = '<a href="'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID().'&amp;mod=material&amp;fct=showzip&amp;iid='.$file->getFileID().'" target="help" onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=800, height=600\');">'.$name.'</a>';
            }
         }
      }

      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   // jsMath for latex math fonts
   // see http://www.math.union.edu/~dpvc/jsMath/
   function _format_math1 ( $text, $array ) {
      $retour = '';
      $image_text = '';
      $image_text = '<span class="math">'.$array[1].'</span>';
      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   // jsMath for latex math fonts
   // see http://www.math.union.edu/~dpvc/jsMath/
   function _format_math2 ( $text, $array ) {
      $retour = '';
      $image_text = '';
      $image_text = '<div class="math">'.$array[1].'</div>';
      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   function _parseArgs ($x) {
      $z = array();
      preg_match_all('~([-+]|(?>(\\w+)[:=]{0,1}))?("[^"]*"|\'[^\']*\'|\\S+)~u',$x, $terms, PREG_SET_ORDER);
      foreach($terms as $t) {
         $v = preg_replace('~^([\'"])?(.*)\\1$~u', '$2', $t[3]);
         if ($t[2]) { $z['#'][] = $t[2]; $z[$t[2]] = $v; }
         else { $z['#'][] = $t[1]; $z[$t[1]][] = $v; }
         $z['#'][] = $v;
      }
      return $this->_checkSecurity($z);
   }

   function _checkSecurity ( $array ) {
      $retour = array();
      foreach ( $array as $key => $value ) {
         if ( $key == 'float'
              and $value != 'left'
              and $value != 'right'
            ) {
            #include_once('functions/error_functions.php');
            #trigger_error('float must be left or right',E_USER_WARNING);
         } elseif ( $key == 'text'
                    or $key == 'alt'
                    or $key == 'gallery'
                    or $key == 'image'
                    or $key == 'server'
                  ) {
            $retour[$key] = $this->_htmlentities_small($value);
         } elseif ( $key == 'width'
                    and !is_numeric($value)
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('width must be a number',E_USER_WARNING);
         } elseif ( $key == 'height'
                    and !is_numeric($value)
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('height must be a number',E_USER_WARNING);
         } elseif ( $key == 'width'
                    and $value > 1000
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('width must be under 1000',E_USER_WARNING);
         } elseif ( $key == 'height'
                    and $value > 1000
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('height must be under 1000',E_USER_WARNING);
         } elseif ( $key == 'icon'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('icon must be true or false',E_USER_WARNING);
         } elseif ( $key == 'size'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('size must be true or false',E_USER_WARNING);
         } elseif ( $key == 'play'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('play must be true or false',E_USER_WARNING);
         } elseif ( $key == 'navigation'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('navigation must be true or false',E_USER_WARNING);
         } elseif ( $key == 'orientation'
                    and $value != 'portrait'
                    and $value != 'landscape'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('orientation must be portrait or landscape',E_USER_WARNING);
         } elseif ( $key == 'target'
                    and $value != '_blank'
                    and $value != '_top'
                    and $value != '_parent'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('target must be _blank, _top or _parent',E_USER_WARNING);
         } else {
            $retour[$key] = $value;
         }
      }
      return $retour;
   }

   function _htmlentities_small ( $value ) {
      $value = str_replace('<','&lt;',$value);
      $value = str_replace('>','&gt;',$value);
      $value = str_replace('"','&quot;',$value);
      $value = str_replace('\'','&quot;',$value);
      return $value;
   }

   function _htmlentities_smaller ( $value ) {
      $value = str_replace('<','&lt;',$value);
      $value = str_replace('>','&gt;',$value);
      $value = str_replace('"','&quot;',$value);
      return $value;
   }

  function _getArgs ($data,$reg_exp) {
     $variable_array = array();
     $matches = array();
     $found = preg_match_all($reg_exp,$data,$matches);
     $j = 0;
     while (isset($matches[$j][0])) {
        $variable_array[$j] = trim($matches[$j][0]);
        $j++;
     }
     return $variable_array;
  }

   function translatorChangeToPortal () {
     $current_portal = $this->_environment->getCurrentPortalItem();
     if (isset($current_portal)) {
       $this->_translator->setContext(CS_PORTAL_TYPE);
       $this->_translator->setRubricTranslationArray($current_portal->getRubricTranslationArray());
       $this->_translator->setEmailTextArray($current_portal->getEmailTextArray());
     }
   }

   function translatorChangeToCurrentContext () {
     $current_context = $this->_environment->getCurrentContextItem();
     if (isset($current_context)) {
         if ($current_context->isCommunityRoom()) {
          $this->_translator->setContext(CS_COMMUNITY_TYPE);
         } elseif ($current_context->isProjectRoom()) {
          $this->_translator->setContext(CS_PROJECT_TYPE);
         } elseif ($current_context->isPortal()) {
          $this->_translator->setContext(CS_PORTAL_TYPE);
       } else {
          $this->_translator->setContext(CS_SERVER_TYPE);
       }
       $this->_translator->setRubricTranslationArray($current_context->getRubricTranslationArray());
       $this->_translator->setEmailTextArray($current_context->getEmailTextArray());
     }
   }

  /** return a text indicating the modification state of an item
      * this method returns a string like [new] or [modified] depending
      * on the read state of the current user.
      *
       * @param  object item       a CommSy item (cs_item)
       *
       * @return string value
       * @author CommSy Development Group
       */
   function _getItemChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         // Add change info for annotations (TBD)
      } else {
         $info_text = '';
      }
      return $info_text;
   }
}
?>