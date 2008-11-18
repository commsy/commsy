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

      $search = array();
      $replace = array();

      $search[]  = '§<([/]{0,1}[j|J][a|A][v|V][a|A][s|S][c|C][r|R][i|I][p|P][t|T])§';
      $replace[] = '&lt;$1';
      $search[]  = '§<([/]{0,1}[s|S][c|C][r|R][i|I][p|P][t|T])§';
      $replace[] = '&lt;$1';
      $search[]  = '§<([/]{0,1}[j|J][s|S][c|C][r|R][i|I][p|P][t|T])§';
      $replace[] = '&lt;$1';
      $search[]  = '§<(\?)§';
      $replace[] = '&lt;$1';
      $search[]  = '§(\?)>§';
      $replace[] = '$1&gt;';
      $search[]  = '§<([/]{0,1}[e|E][m|M][b|B][e|E][d|D])§';
      $replace[] = '&lt;$1';
      $search[]  = '§<([/]{0,1}[o|O][b|B][j|J][e|E][c|C][t|T])§';
      $replace[] = '&lt;$1';
      $text = preg_replace($search,$replace,$text);

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
      $text = htmlspecialchars($text);
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
      preg_match('$<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->$',$text,$values);
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
      $text = htmlspecialchars($text);
      $text = $this->_emphasize_text($text);
      $text = $this->_decode_backslashes($text);
      return $text;
   }
    // @segment-end 51609

   function _text_as_html_short_coding_format($text) {
       $text = $this->_text_as_html_short($text);
      $text = str_replace('&lt;BR&gt;', "<br />", $text);
      $text = str_replace('&lt;SPACE&gt;', "&nbsp;", $text);
      $text = preg_replace('§&lt;DISABLED&gt;(.*)&lt;/DISABLED&gt;§','<span class="disabled">$1</span>',$text);
      return $text;
   }

   function _text_as_form ($text) {
      $text = $this->_cs_htmlspecialchars($text);
      $text = str_replace('"','&quot;',$text);
      return $text;
   }

   function _text_as_form1 ($text) {
      $text = $this->_cs_htmlspecialchars1($text);
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
       $text = preg_replace ('§<br( /)?>(</h\d>)§', '$2', $text);
      $text = preg_replace ('§<br( /)?>(</li>)§', '</li>', $text);

      return $text;
   }

   function _emphasize_text ($text) {
      // bold
      //$text = preg_replace('/(^|\n|\t|\s|[ >\/_[{(])\*([^*]+)\*($|\n|\t|[ <\/_.)\]},!?;])/', '$1<span style="font-weight:bold;">$2</span>$3', $text);
      $text = preg_replace('§\*([^*]+)\*§', '<span style="font-weight:bold;">$1</span>', $text);
      // italic
      #$text = preg_replace('§_([^_]+)_§', '<span style="font-style:italic;">$1</span>', $text);
      $text = preg_replace('§(^|\n|\t|\s|[ >\/[{(])_([^_]+)_($|\n|\t|[ <\/.)\]},!?;])§', '$1<span style=font-style:italic;>$2</span>$3', $text);
      return $text;
   }

   function _activate_urls ($text) {
      $url_string = '§([ |\n|>]{1})((http://|https://|ftp://|www\.)'; //everything starting with http, https or ftp followed by "://" or www. is a url and will be avtivated
      $url_string .= "([".RFC1738_CHARS."]+?))"; //All characters allowed for FTP an HTTP URL's by RFC 1738 (non-greedy because of potential trailing punctuation marks)
      $url_string .= '([.?:),;!]*($|\s|<|&quot;))§'; //after the url is a space character- and perhaps before it a punctuation mark (which does not belong to the url)
      $text = preg_replace($url_string, '$1<a href="$2" target="_blank" title="$2">$2</a>$5', $text);
      $text = preg_replace_callback('§">(.[^"]+)</a>§','spezial_chunkURL',$text);

      $text = preg_replace('$<a href="www$','<a href="http://www',$text); //add "http://" to links that were activated with www in front only
      // mailto. A space or a linebreak has to be in front of everymail link. No links in bigger words (especially in urls) will be activated
      $text = preg_replace('§( |^|>|\n)(mailto:)?((['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)@(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*\.([A-z]{2,})))§', ' <a href="mailto:$3">$3</a>', $text);
      return $text;
   }

   function _display_headers ($text) {
      $matches = array();

      while (preg_match('/(^|\n)(\s*)(!+)(\s*)(.*)/', $text, $matches)) {
         $bang_number = strlen($matches[3]);
         $head_level = max(5 - $bang_number, 1); //normal (one '!') is h4, biggest is h1; The more '!', the bigger the heading
         $heading = '<h'.$head_level.'>'."\n   ".$matches[5]."\n".'</h'.$head_level.'>'."\n";
         $text = preg_replace('/(^|\n)(\s*)(!+)(\s*)(.*)/', $heading, $text, 1);
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
      $lines = preg_split('/\s*\n/', $text);
      foreach ($lines as $line) {
         $line_html = '';
         $hr_line = false;
         //find horizontal rulers
         if (preg_match('/^--(-+)\s*($|\n|<)/', $line)) {
            if ($list_open) {
               $line_html.=$this->_close_list($last_list_type);
               $list_open = false;
            }
            $line_html.= "\n".'<hr/>'."\n";
            $hr_line = true;
         }

         //process lists
         elseif (!($hr_line) and preg_match('/^(-|#)(\s*)(.*)/s', $line, $matches)) {
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
      global $current_item_id;
      $matches_stand_alone = array();
      $matches_with_text = array();

      // ids with text: <text>[<number>] becomes a link under <text> to the commsy-object with id <number>
      preg_match_all('|([\w.'.SPECIAL_CHARS.'-]+)\[(\d+)\]|i', $text, $matches_with_text);
      if (count($matches_with_text[0]) > 0) {
         $result = $text;
         $word_part = $matches_with_text[1];
         $reference_part = $matches_with_text[2];
         for ($i = 0; $i < count($word_part); $i ++) {
            $word = $word_part[$i];
            $reference = $reference_part[$i];
            if ($reference < 100) {
               $params = array();
               $params['iid'] = $current_item_id;
               $result = preg_replace('/'.$word.'\['.$reference.'\]/i', ahref_curl($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, $word, $word, '', 'anchor'.$reference), $result);
               unset($params);
            } else {
               $params = array();
               $params['iid'] = $reference;
               $result = preg_replace('/'.$word.'\['.$reference.'\]/i', ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, $word, '', '', ''), $result);
               unset($params);
            }
         }
         $text = $result;
      }

      // urls with text: <text>[<url>] becomes a link under <text> to the url <url>
      preg_match_all('|([.\w'.SPECIAL_CHARS.'-]+)\[(https?:\/\/['.RFC1738_CHARS.']*)\]|i', $text, $matches_with_urls);//preg_match_all('/(\S+)(\[http:\/\/\S*\])[.:,;-?!]*($|\n|\t|<| )/', $text, $matches_with_urls);
      if (count($matches_with_urls[0]) > 0) {
   $result = $text;
   $word_part = $matches_with_urls[1];
   $http_part = $matches_with_urls[2];
   for ($i = 0; $i < count($word_part); $i++) {
      $word = $word_part[$i];
      $http = $http_part[$i];
      if (!empty($word)) {
         if (!stristr($word,'|')) {
       $result = preg_replace('%'.$word.'\['.$http.'\]%', '<a href="'.$http.'" target="_blank">'.$word.'</a>', $result);
         }
      } else {
         $result = preg_replace('%'.$word.'\['.$http.'\]%', '<a href="'.$http.'" target="_blank">'.$http_part[$i].'</a>', $result);
      }
   }
   $text = $result;
      }

      // long urls: [<url>|<sentence with spaces>|<flag>] becomes a link to <url> under <sentence with spaces>
      // <flag> cann be "internal" or "_blank". Internal opens <url> in this browser window, _blank uses another
      preg_match_all('§\[(http?://['.RFC1738_CHARS.']*)\|([\w'.SPECIAL_CHARS.' \)?!&;-]+)\|(\w+)\]§', $text, $matches_with_long_urls);
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
      preg_match_all('§\[([0-9]*)\|([\w'.SPECIAL_CHARS.' \)?!&;-]+)\]§', $text, $matches_with_long_urls);
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
      preg_match_all('/\[(\d+)\]/', $text, $matches_stand_alone);//(^| |\n|>|\t)\[(\d+)\][.:,;-?!]*(<| |$)
      $matches_stand_alone = array_unique($matches_stand_alone[1]);
      if (!empty($matches_stand_alone)) {
   $result = $text;
   foreach ($matches_stand_alone as $item) {
      if ($item < 100) {
               $params = array();
               $params['iid'] = $current_item_id;
         $result = preg_replace('/\['.$item.'\]/i', ahref_curl($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, "[".$item."]", "[".$item."]", '', 'anchor'.$item), $result);
               unset($params);
      }
      else {
               $params = array();
               $params['iid'] = $item;
         $result = preg_replace('/\['.$item.'\]/i', ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, "[".$item."]", '', '', ''), $result);
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
     preg_match_all('/ {2,}/', $text, $matches);
     $matches = array_unique($matches[0]);
     rsort($matches);

     foreach ($matches as $match) {
       $replacement = ' ';

       for ($x = 1; $x < strlen($match); $x++) {
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

      $return_description = "";
      $width_string = '';

      //split description in paragraphs
      //$paragraphs = preg_split('§\s*\n(\s*\n)+§', $description);
      $paragraphs = preg_split('§(\s*<br( /)?>{2,})§', $description,-1,PREG_SPLIT_DELIM_CAPTURE);
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
         $file_name_array[] = htmlentities($file->getDisplayName());
      }
      foreach ($paragraphs as $paragraph) {
         $imgmatches = array();
         $zipmatches = array();
         //find everything in the form [<filename>.<extension>] or  [<filename>.<extension>|<align>] with <extension> one of jpg, jpeg, gif, png and <align> one of 'left', 'right', 'none'
#			$image_found_in_paragraph = preg_match_all('§\[([A-z0-9_%&$-'.SPECIAL_CHARS.']+\.(png|PNG|jpe?g|JPE?G|gif|GIF|swf|SWF))(\|(left|right|none))?\]§',$paragraph,$matches);
         $image_found_in_paragraph = preg_match_all('§\[([A-z0-9_%&$-\s'.SPECIAL_CHARS.';]+\.[A-z0-9_%&$-'.SPECIAL_CHARS.']{3,4})(\|(left|right|none))?\]§',$paragraph,$imgmatches);
         $zip_with_html_found_in_paragraph = preg_match_all('§\[([A-z0-9_%&$-\s'.SPECIAL_CHARS.';]+\.zip)(\|(html))?\]§',$paragraph,$zipmatches);
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
               if ($align == '' or stristr($file->getFilename(),'swf')) {
                  $align = '';
                  $align_text = '';
               } elseif ($align == 'none'){
                  $align = '';
                  $align_text = '|none';
               } else {
                  $align_text = '|'.$align;
                  $align = ' float:'.$align.';';
               }
               if ( stristr(strtolower($file->getFilename()),'swf') ) {
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
                  $image_text .= '      <param name="wmode" value="transparent" />'.LF;
                  $image_text .= '      <param name="play" value="false" />'.LF;
                  $image_text .= '      <param name="loop" value="false" />'.LF;
                  $image_text .= '      <param name="devicefont" value="true" />'.LF;
                  $image_text .= '      <embed src="'.$source.'" quality="high"
                                                  scale="exactfit"
                                                  menu="true"
                                                  bgcolor="#000000"
                                                  wmode="transparent"
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
               } elseif ( !stristr(strtolower($file->getFilename()),'png')
                      and !stristr(strtolower($file->getFilename()),'jpg')
                      and !stristr(strtolower($file->getFilename()),'jpeg')
                      and !stristr(strtolower($file->getFilename()),'gif')
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
                  if ( $disc_manager->existsFile($thumb_name) ) {
                     $params = array();
                     $params['picture'] = $thumb_name;
                     $thumb_url = curl( $this->_environment->getCurrentContextID(),
                                  'picture',
                                  'getfile',
                                 $params,
                                 '',
                                 ''.
                                 'commsy.php' );
                     unset($params);
                     $image_text = '<img src="'.$thumb_url.'" alt="'.$name.'"/>';
                     $width_string ='';
                  } else {

                     $width_string = 'width:200px;';
                     if (function_exists('gd_info')) {
                        $image_in_info = GetImageSize($file->getDiskFileName());
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
                     $paragraph = str_replace('['.htmlentities($name).$align_text.']', '<div style="'.$align.' padding:5px;">'.'<a href="'.$file->getUrl().'">'.$image_text.'</a>'.'</div>',$paragraph);
                  } else {
                     $paragraph = str_replace('['.htmlentities($name).']','<div style="'.$align.' padding:5px;">'.'</div>'.$image_text,$paragraph);
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

                                           $paragraph = str_replace(
                           '['.htmlentities($name).'|html]','<a href="commsy.php?cid='.$this->_environment->getCurrentContextID().'&amp;mod=material&amp;fct=showzip&amp;iid='.$file->getFileID().'" target="help" onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=800, height=600\');">'.$name.'</a>',$paragraph);
                            }
                            if(($file->getHasHTML() == '1')) {
                            $paragraph = str_replace(
                           '['.htmlentities($name).'|html]',$file->getFileIcon().'&nbsp;<a href="'.$file->getUrl().'">'.$name.'</a>',$paragraph);
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
      $thumb_name = $name;
      $point_position = strrpos($thumb_name,'.');
      $thumb_name = substr_replace ( $thumb_name, '_thumb.png', $point_position , strlen($thumb_name));
      return $thumb_name;
   }

   function _newFormating ( $text ) {

      $reg_exp_father_array = array();
      $reg_exp_father_array[]       = '/\\(:(.*?):\\)/e';

      $reg_exp_array = array();
      $reg_exp_array['(:flash']       = '/\\(:flash (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:quicktime']   = '/\\(:quicktime (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:wmplayer']    = '/\\(:wmplayer (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:image']       = '/\\(:image (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:item']        = '/\\(:item ([0-9]*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:link']        = '/\\(:link (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:file']        = '/\\(:file (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:zip']         = '/\\(:zip (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:youtube']     = '/\\(:youtube (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:googlevideo'] = '/\\(:googlevideo (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:vimeo']       = '/\\(:vimeo (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:mp3']         = '/\\(:mp3 (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      if($this->_environment->isScribdAvailable()){
        $reg_exp_array['(:office']      = '/\\(:office (.*?)(\\s.*?)?\\s*?:\\)/e';
      }

      // jsMath for latex math fonts
      // see http://www.math.union.edu/~dpvc/jsMath/
      global $c_jsmath_enable;
      if ( isset($c_jsmath_enable)
           and $c_jsmath_enable
         ) {
         $reg_exp_father_array[]   = '/\\{\\$[\\$]{0,1}(.*?)\\$[\\$]{0,1}\\}/e';
         $reg_exp_array['{$$']     = '/\\{\\$\\$(.*?)\\$\$\\}/e'; // must be before next one
         $reg_exp_array['{$']      = '/\\{\\$(.*?)\\$\\}/e';
      }

      // is there wiki syntax ?
      if ( !empty($reg_exp_array) ) {
         $reg_exp_keys = array_keys($reg_exp_array);
         $clean_text = false;
         foreach ($reg_exp_keys as $key) {
            if ( stristr($text,$key) ) {
               $clean_text = true;
               break;
            }
         }
      }

      // clean wikistyle text from HTML-Code (via fckeditor)
      // and replace wikisyntax
      if ($clean_text) {
         $matches = array();
         foreach ($reg_exp_father_array as $exp) {
         $found = preg_match_all($exp,$text,$matches);
         if ( $found > 0 ) {
            $matches[0] = array_unique($matches[0]); // doppelte einsparen
            foreach ($matches[0] as $value) {
               $value_new = strip_tags($value);
               foreach ($reg_exp_array as $key => $reg_exp) {
                  if ( $key == '(:flash' and stristr($value_new,'(:flash') ) {
                     $value_new = $this->_format_flash($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:wmplayer' and stristr($value_new,'(:wmplayer') ) {
                     $value_new = $this->_format_wmplayer($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:quicktime' and stristr($value_new,'(:quicktime') ) {
                     $value_new = $this->_format_quicktime($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:image' and stristr($value_new,'(:image') ) {
                     $value_new = $this->_format_image($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:item' and stristr($value_new,'(:item') ) {
                     $value_new = $this->_format_item($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:link' and stristr($value_new,'(:link') ) {
                     $value_new = $this->_format_link($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:file' and stristr($value_new,'(:file') ) {
                     $value_new = $this->_format_file($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:zip' and stristr($value_new,'(:zip') ) {
                     $value_new = $this->_format_zip($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:youtube' and stristr($value_new,'(:youtube') ) {
                     $value_new = $this->_format_youtube($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:googlevideo' and stristr($value_new,'(:googlevideo') ) {
                     $value_new = $this->_format_googlevideo($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:vimeo' and stristr($value_new,'(:vimeo') ) {
                     $value_new = $this->_format_vimeo($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:mp3' and stristr($value_new,'(:mp3') ) {
                     $value_new = $this->_format_mp3($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:office' and stristr($value_new,'(:office') ) {
                     $value_new = $this->_format_office($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '{$' and stristr($value_new,'{$') ) {
                     $value_new = $this->_format_math1($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '{$$' and stristr($value_new,'{$$') ) {
                     $value_new = $this->_format_math2($value_new,$this->_getArgs($value_new,$reg_exp));
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
            $file_name_array[htmlentities($file->getDisplayName())] = $file;
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
         $image_text .= '   wmode="transparent"'.LF;
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
         $image_text .= '<param name="wmode" value="transparent" />'.LF;
         $image_text .= '<embed type="application/x-shockwave-flash"'.LF;
         $image_text .= '   src="http://www.youtube.com/v/'.$source.'"'.LF;
         $image_text .= '   wmode="transparent"'.LF;
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
         $ext = cs_strtolower(substr(strrchr($source,'.'),1));
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
         $image_text .= '>';
         $image_text .= '<param name="fileName" value="'.$source.'" />'.LF;
         $image_text .= '<param name="autoStart" value="'.$play.'" />'.LF;
         $image_text .= '<param name="showControls" value="true" />'.LF;
         $image_text .= '<param name="showStatusBar" value="true" />'.LF;
         $image_text .= '<param name="wmode" value="transparent" />'.LF;
         $image_text .= '<embed type="application/x-mplayer2"'.LF;
         $image_text .= '   pluginspage="http://www.microsoft.com/Windows/MediaPlayer/"'.LF;
         $image_text .= '   src="'.$source.'"'.LF;
         $image_text .= '   name="MediaPlayer18"'.LF;
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
         $ext = cs_strtolower(substr(strrchr($source,'.'),1));
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

      if ( strtolower($ext) == 'mp3' ) {
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
         $image_text .= '<param name="wmode" value="transparent" />'.LF;
         $image_text .= '<param name="autoplay" value="'.$play.'" />'.LF;
         $image_text .= '<param name="loop" value="false" />'.LF;
         $image_text .= '<param name="devicefont" value="true" />'.LF;
         $image_text .= '<param name="class" value="mov" />'.LF;
         $image_text .= '<embed src="'.$source.'"'.LF;
         $image_text .= '   quality="high"'.LF;
         $image_text .= '   scale="tofit"'.LF;
         $image_text .= '   controller=true'.LF;
         $image_text .= '   bgcolor="#000000"'.LF;
         $image_text .= '   wmode="transparent"'.LF;
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


   function _format_flash ( $text, $array ) {
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
         $ext = cs_strtolower(substr(strrchr($source,'.'),1));
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

      if ( !empty($args['navigation'])
           and $args['navigation']
         ) {
         $with_player = true;
      } elseif ( !empty($args['navigation'])
                 and !$args['navigation']
               ) {
         $with_player = false;
      } elseif ( $ext == 'swf' ) {
         $with_player = false;
      } else {
         $with_player = true;
      }

      if ( !empty($source) ) {
         $image_text = '';
         if ( $ext == 'swf'
              and !$with_player
            ) {
            $image_text .= '<div style="'.$float.' padding:10px;">'.LF;
            $image_text .= '   <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'.LF;
            if ( !empty($args['width']) ) {
               $image_text .= '           width="'.$args['width'].'"'.LF;
            }
            if ( !empty($args['height']) ) {
               $image_text .= '           height="'.$args['height'].'"'.LF;
            }
            $image_text .= '           codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">'.LF;
            $image_text .= '      <param name="movie" value="'.$source.'" />'.LF;
            $image_text .= '      <param name="quality" value="high" />'.LF;
            $image_text .= '      <param name="scale" value="exactfit" />'.LF;
            $image_text .= '      <param name="menu" value="true" />'.LF;
            $image_text .= '      <param name="bgcolor" value="#000000" />'.LF;
            $image_text .= '      <param name="wmode" value="transparent" />'.LF;
            $image_text .= '      <param name="play" value="'.$play.'" />'.LF;
            $image_text .= '      <param name="loop" value="false" />'.LF;
            $image_text .= '      <param name="devicefont" value="true" />'.LF;
            $image_text .= '      <embed src="'.$source.'" quality="high"'.LF;
            $image_text .= '             scale="exactfit"'.LF;
            $image_text .= '             menu="true"'.LF;
            $image_text .= '             bgcolor="#000000"'.LF;
            $image_text .= '             wmode="transparent"'.LF;
            $image_text .= '             play="'.$play.'"'.LF;
            $image_text .= '             loop="false"'.LF;
            $image_text .= '             devicefont="true"'.LF;
            if ( !empty($args['width']) ) {
               $image_text .= '             width="'.$args['width'].'"'.LF;
            }
            if ( !empty($args['height']) ) {
               $image_text .= '             height="'.$args['height'].'"'.LF;
            }
            $image_text .= '             swLiveConnect="false"'.LF;
            $image_text .= '             type="application/x-shockwave-flash"'.LF;
            $image_text .= '             pluginspage="http://www.macromedia.com/go/getflashplayer">'.LF;
            $image_text .= '      </embed>'.LF;
            $image_text .= '   </object>'.LF;
            $image_text .= '</div>'.LF;
         }

         else {
            // use flv media player for swf, flv
            // see: http://www.jeroenwijering.com/?item=JW_FLV_Media_Player
            if ( empty($args['width']) ) {
               $args['width'] = '300';
                if ( $this->_environment->getCurrentBrowser() == 'MSIE' ) {
                     $args['width'] += 20;
                }
            }
            if ( empty($args['height']) ) {
                $args['height'] = '250';
                if ( $this->_environment->getCurrentBrowser() == 'MSIE' ) {
                    $args['height'] += 10;
                }
            }
            $source = str_replace('&amp;','&',$source);
            $source = urlencode($source);
            if ($this->_environment->getCurrentBrowser() == 'MSIE' ) {
               $image_text .= '<div style="'.$float.' padding:10px;">'.LF;
               $image_text .= '<embed'.LF;
               $image_text .= '  src="mediaplayer.swf"'.LF;
               $image_text .= '  width="'.$args['width'].'"'.LF;
               $image_text .= '  height="'.$args['height'].'"'.LF;
               $image_text .= '  allowfullscreen="true"'.LF;
               $image_text .= '  flashvars="file='.$source.'&autostart='.$play.'&type='.$ext;
               if ( !$extern ) {
                  $image_text .= '&showdigits=true';
               } else {
                  $image_text .= '&showdigits=false';
                  $image_text .= '&showicons=false';
               }
               if ( !$with_player ) {
                  $image_text .= '&shownavigation=false';
               }
               $image_text .= '" />'.LF;
               $image_text .= '</div>'.LF;
            } else {
               $div_number = $this->_getDivNumber();
               $image_text .= '<div id="id'.$div_number.'" style="'.$float.' padding:10px;">'.getMessage('COMMON_GET_FLASH').'</div>'.LF;
               $image_text .= '<script type="text/javascript">'.LF;
               $image_text .= '  var so = new SWFObject(\'mediaplayer.swf\',\'mpl\',\''.$args['width'].'\',\''.$args['height'].'\',\'8\');'.LF;
               $image_text .= '  so.addParam(\'allowfullscreen\',\'true\');'.LF;
               $image_text .= '  so.addVariable(\'file\',"'.$source.'");'.LF;
               $image_text .= '  so.addVariable(\'autostart\',\''.$play.'\');'.LF;
               $image_text .= '  so.addVariable(\'overstretch\',\'fit\');'.LF;
               $image_text .= '  so.addVariable(\'showstop\',\'true\');'.LF;
               if ( !empty($ext) ) {
                  $image_text .= '  so.addVariable(\'type\',\''.$ext.'\');'.LF;
               }
               if ( !$extern ) {
                  $image_text .= '  so.addVariable(\'showdigits\',\'true\');'.LF;
               } else {
                  $image_text .= '  so.addVariable(\'showdigits\',\'false\');'.LF;
                  $image_text .= '  so.addVariable(\'showicons\',\'false\');'.LF;
               }
               if ( !$with_player ) {
                  $image_text .= '  so.addVariable(\'shownavigation\',\'false\');'.LF;
               }
               $image_text .= '  so.write(\'id'.$div_number.'\');'.LF;
               $image_text .= '</script>'.LF;
            }
         }
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
         $ext = cs_strtolower(substr(strrchr($source,'.'),1));
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
         if ( !empty($array[2]) and !empty($file_name_array[$array[2]]) ) {
            $file = $file_name_array[$array[2]];
         }
         if ( isset($file) ) {
            if ( stristr(strtolower($file->getFilename()),'png')
                 or stristr(strtolower($file->getFilename()),'jpg')
                 or stristr(strtolower($file->getFilename()),'jpeg')
                 or stristr(strtolower($file->getFilename()),'gif')
               ) {
               $source = $file->getUrl();

               $thumb_name = $this->_create_thumb_name_from_image_name($file->getDiskFileNameWithoutFolder());
               //if there is a thumb file, use it instead
               $disc_manager = $this->_environment->getDiscManager();
               if ( $disc_manager->existsFile($thumb_name) ) {
                  $params = array();
                  $params['picture'] = $thumb_name;
                  $thumb_source = curl( $this->_environment->getCurrentContextID(),
                                        'picture',
                                        'getfile',
                                        $params,
                                        '',
                                        $thumb_name, // ''. ???
                                        'commsy.php' );
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

   function _format_file ( $text, $array ) {
      $retour = '';
      $image_text = '';
      if ( !empty($array[1]) ) {
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[1]];
         if ( isset($file) ) {

            if ( !empty($array[2]) ) {
               $args = $this->_parseArgs($array[2]);
            } else {
               $args = array();
            }

            if ( empty($args['icon'])
                 or ( !empty($args['icon'])
                      and $args['icon'] == 'true'
                    )
               ) {
               $icon = $file->getFileIcon().' ';
            } else {
               $icon = '';
            }
            if ( empty($args['size'])
                 or ( !empty($args['size'])
                      and $args['size'] == 'true'
                    )
               ) {
               $kb = ' ('.$file->getFileSize().' KB)';
            } else {
               $kb = '';
            }
            if ( !empty($args['text']) ) {
               $name = $args['text'];
            } else {
               $name = $file->getDisplayName();
            }

            if ( !empty($args['target']) ) {
               $target = ' target="'.$args['target'].'"';
            } elseif ( !empty($args['newwin']) ) {
               $target = ' target=_blank;';
            } else {
               $target = '';
            }
            $source = $file->getUrl();
            $image_text = '<a href="'.$source.'"'.$target.'>'.$icon.$name.'</a>'.$kb;
         }
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
               $image_text = '<a href="commsy.php?cid='.$this->_environment->getCurrentContextID().'&amp;mod=material&amp;fct=showzip&amp;iid='.$file->getFileID().'" target="help" onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=800, height=600\');">'.$name.'</a>';
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
      preg_match_all('/([-+]|(?>(\\w+)[:=]{0,1}))?("[^"]*"|\'[^\']*\'|\\S+)/',$x, $terms, PREG_SET_ORDER);
      foreach($terms as $t) {
         $v = preg_replace('/^([\'"])?(.*)\\1$/', '$2', $t[3]);
         if ($t[2]) { $z['#'][] = $t[2]; $z[$t[2]] = $v; }
         else { $z['#'][] = $t[1]; $z[$t[1]][] = $v; }
         $z['#'][] = $v;
      }
      return $z;
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