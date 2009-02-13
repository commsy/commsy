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

/////////////////////////////////////////////////////////////////////////////////
/// 2php functions //////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_get2php ($text) {
   $text = rawurldecode($text);
   return $text;
}

function _text_form2php ($text) {
   // Fix up line feed characters from different clients (Windows, Mac => Unix)
   $text = preg_replace('/\r\n?/', "\n", $text);
   $text = trim($text);

   // security out of fckeditor
   // dann muss die ganze Ausgabenseite überdacht werden
   /*
   preg_match('$<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->$',$text,$values);
   foreach ($values as $key => $value) {
      $text = str_replace($value,'COMMSY_FCKEDITOR'.$key,$text);
   }
   $text = str_replace('"','&quot;',$text);
   $text = str_replace('<','&lt;',$text);
   $text = str_replace('>','&gt;',$text);
   foreach ($values as $key => $value) {
      $text = str_replace('COMMSY_FCKEDITOR'.$key,$value,$text);
   }
   */

   // corrections for FCKeditor
   if ( strstr($text,'<!-- KFC TEXT --><!-- KFC TEXT -->') ) {
      $text = str_replace('<!-- KFC TEXT -->','',$text);
      if ( !empty($text) ) {
         $text = '<!-- KFC TEXT -->'.$text.'<!-- KFC TEXT -->';
      }
   }

   return $text;
}

function _text_db2php ($text) {
   // dies ist nötig auf grund von mysql_real_escape_string in _text_php2db
   $text = str_replace('\n','COMMSYLN',$text);
   $text = str_replace('\*','COMMSYSTERN',$text);
   $text = str_replace('\_','COMMSYSTRICH',$text);
   $text = str_replace('\!','COMMSYAUSRUFEZEICHEN',$text);
   $text = str_replace('\-','COMMSYMINUS',$text);
   $text = str_replace('\#','COMMSYSCHWEINEGATTER',$text);
   $text = str_replace('\(:','COMMSYWIKIBEGIN',$text);

   // jsMath for latex math fonts
   // see http://www.math.union.edu/~dpvc/jsMath/
   global $c_jsmath_enable;
   if ( isset($c_jsmath_enable)
        and $c_jsmath_enable
      ) {
      if ( strstr($text,'{$') ) {
         $matches = array();
         $exp = '/\\{\\$(.*?)\\$\\}/e';
         $found = preg_match_all($exp,$text,$matches);
         if ( $found > 0 ) {
            foreach ($matches[0] as $key => $value) {
               $value_new = 'COMMSYMATH'.$key;
               $text = str_replace($value,$value_new,$text);
            }
         }
      }
   }

   $text = stripslashes($text);

   // jsMath for latex math fonts
   // see http://www.math.union.edu/~dpvc/jsMath/
   if ( !empty($found)
        and $found > 0
      ) {
      foreach ($matches[0] as $key => $value) {
         $value_new = 'COMMSYMATH'.$key;
         $text = str_replace($value_new,$value,$text);
      }
   }

   $text = str_replace('COMMSYLN',LF,$text);
   $text = str_replace('COMMSYSTERN','\*',$text);
   $text = str_replace('COMMSYSTRICH','\_',$text);
   $text = str_replace('COMMSYAUSRUFEZEICHEN','\!',$text);
   $text = str_replace('COMMSYMINUS','\-',$text);
   $text = str_replace('COMMSYSCHWEINEGATTER','\#',$text);
   $text = str_replace('COMMSYWIKIBEGIN','\(:',$text);
   return $text;
}

function _text_file2php ($text) {
   $text = str_replace('&quot;','"',$text);
   return $text;
}


/////////////////////////////////////////////////////////////////////////////////
/// 2db functions ///////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_php2db ($text) {
   /*
   #$text = _format_html_entity_decode($text);
   if ( get_magic_quotes_gpc() ) {
      $text = stripslashes($text);
   }
   $text = mysql_real_escape_string($text);
   */
   global $environment;
   if ( !isset($environment) ) {
      $environment =  $this->_environment;
   }
   $db_connection = $environment->getDBConnector();
   $text = $db_connection->text_php2db($text);
   return $text;
}


/////////////////////////////////////////////////////////////////////////////////
/// 2form functions /////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_php2form ($text) {
   // do nothing
   return ($text);
}

/////////////////////////////////////////////////////////////////////////////////
/// 2html functions /////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_php2html_short ($text) {
   //$text = htmlspecialchars($text);
   $text = _emphasize_text($text);
   $text = _decode_backslashes($text);
   return $text;
}

function _text_php2html_short_with_urls ($text) {
   $text = _text_php2html_short($text);
   $text = activate_urls($text);
   $text = parseText2ID($text);
   return $text;
}

function _text_php2html_long ($text) {
   //$text = htmlspecialchars($text);
   $text = nl2br($text);
   $text = _emphasize_text($text);
   $text = activate_urls($text);
   $text = _display_headers($text);
   $text = _format_html_long($text);
   $text = parseText2ID($text);
   $text = _decode_backslashes($text);
   $text = _br_with_nl($text);
   //$text = _preserve_whitespaces($text);
   return $text;
}


/////////////////////////////////////////////////////////////////////////////////
/// 2mail functions /////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_php2mail ($text) {
   //$text = _format_html_entity_decode($text);
   return $text;
}


/////////////////////////////////////////////////////////////////////////////////
/// 2file functions /////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_php2file ($text) {
   $text = str_replace('"','&quot;',$text);
   $text = str_replace('&lt;','<',$text);
   $text = str_replace('&gt;','>',$text);
   return $text;
}

/////////////////////////////////////////////////////////////////////////////////
/// 2rss functions /////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
function _text_php2rss ($text) {
   $text = str_replace('&','&amp;',$text);
   $text = str_replace('<','&lt;',$text);
   $text = utf8_encode($text);
   return $text;
}

/////////////////////////////////////////////////////////////////////////////////
/// utility functions ///////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
define ("AS_HTML_LONG", 1);
define ("AS_HTML_SHORT", 2);
define ("AS_HTML_SHORT_WITH_LINKS", 3);
define ("AS_FORM", 4);
define ("AS_DB", 5);
define ("AS_FILE", 6);
define ("AS_MAIL", 7);
define ("AS_RSS", 8);
define ("NONE", 10);
define ("FROM_FORM", 11);
define ("FROM_DB", 12);
define ("FROM_FILE", 13);
define ("FROM_GET", 14);

function encode ($mode, $value) {
   if (!empty($value)) {
      if (is_array($value)) {
         return _array_encode($value,$mode);
      } else {
         return _text_encode($value,$mode);
      }
   } else {
      return $value;
   }
}

function getRubricMessageTageName($rubric,$plural = false){
   global $environment;
   $translator = $environment->getTranslationObject();
   switch ($rubric) {
      case CS_MATERIAL_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_MATERIAL_INDEX');
         }else{
            return $translator->getMessage('COMMON_MATERIAL');
         }
      case CS_ANNOUNCEMENT_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_ANNOUNCEMENT_INDEX');
         }else{
            return $translator->getMessage('COMMON_ANNOUNCEMENT');
         }
      case CS_DATE_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_DATE_INDEX');
         }else{
            return $translator->getMessage('COMMON_DATE');
         }
      case CS_TODO_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_TODO_INDEX');
         }else{
            return $translator->getMessage('COMMON_TODO');
         }
      case CS_GROUP_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_GROUP_INDEX');
         }else{
            return $translator->getMessage('COMMON_GROUP');
         }
      case CS_TOPIC_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_TOPIC_INDEX');
         }else{
            return $translator->getMessage('COMMON_TOPIC');
         }
      case CS_INSTITUTION_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_INSTITUTION_INDEX');
         }else{
            return $translator->getMessage('COMMON_INSTITUTION');
         }
      case CS_USER_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_USER_INDEX');
         }else{
            return $translator->getMessage('COMMON_USER');
         }
      case CS_DISCUSSION_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_DISCUSSION_INDEX');
         }else{
            return $translator->getMessage('COMMON_DISCUSSION');
         }
      case CS_MYROOM_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_MYROOM_INDEX');
         }else{
            return $translator->getMessage('COMMON_MYROOM');
         }
      case CS_PROJECT_TYPE :
         if ($plural){
            return $translator->getMessage('COMMON_PROJECT_INDEX');
         }else{
            return $translator->getMessage('COMMON_PROJECT');
         }
   }
}

function _text_encode ($text, $mode) {
   switch ($mode) {
      case NONE :
         return $text;
      case AS_HTML_LONG :
         return _text_php2html_long($text);
      case AS_HTML_SHORT :
         return _text_php2html_short($text);
      case AS_HTML_SHORT_WITH_LINKS :
         return _text_php2html_short_with_urls($text);
      case AS_MAIL :
         return _text_php2mail($text);
      case AS_RSS :
         return _text_php2rss($text);
      case AS_FORM :
         return _text_php2form($text);
      case AS_DB :
         return _text_php2db($text);
      case AS_FILE :
         return _text_php2file($text);
      case FROM_FORM :
         return _text_form2php($text);
      case FROM_DB :
         return _text_db2php($text);
      case FROM_FILE :
         return _text_file2php($text);
      case FROM_GET :
         return _text_get2php($text);
   }
   include_once('functions/error_functions.php');
   trigger_error('You need to specify a mode for text translation.', E_USER_WARNING);
}

function _array_encode ($array, $mode) {
   $retour_array = array();
   if (is_array($array) and count($array) > 0) {
      foreach ($array as $key => $value) {
         if (is_array($value)) {
            $retour_array[$key] = _array_encode($value, $mode);
         }
         else {
            //sometimes we submit some Values in this arrays- eg. for checked boxes. In this case: don't change...
            if (preg_match('|<VALUE>.*</VALUE>|', $value)) {
               $retour_array[$key] = $value;
            }
            else {
               $retour_array[$key] = _text_encode($value, $mode);
            }
         }
      }
   }
   return $retour_array;
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
            $line_html.= _close_list($last_list_type);
            $list_open = false;
         }
         $line_html.= "\n".'<hr/>'."\n";
         $hr_line = true;
      }

      //process lists
      elseif (!($hr_line) and preg_match('/^(-|#)(\s*)(.*)/s', $line, $matches)) {
         $list_type = $matches[1];

         if (!$list_open) {
            $line_html .= _open_list($list_type);
            $list_open = true;
            if ($list_type != $last_list_type) {
               $last_list_type = $list_type;
            }
         } else {
            if ($list_type != $last_list_type) {
               $line_html.= _close_list($last_list_type);
               $line_html.= _open_list($list_type);
               $last_list_type = $list_type;
            }
         }
         $line_html.= '<li>'.$matches[3].'</li>'."\n";
      }

      //All other lines without anything special
      else {
         if ($list_open) {
            $line_html.= _close_list($last_list_type);
            $list_open = false;
         }
         $line_html .= $line;
      }
      $html .= $line_html;
   }
   if ($list_open) {
      $html .= _close_list($last_list_type);
      $list_open = false;
   }
   return $html;
}

/**
 * decode html entitys to html-code
 *
 * &amp; -> &
 * &quot; -> "
 * &#039; -> '
 * &lt; -> <
 * &gt; -> >
 */
function _format_html_entity_decode ($text) {
   $retour = $text;
   $retour = html_entity_decode($retour);
   $retour = str_replace("&#039;","'",$retour); // html_entity_decode doesn't decode "&#039;"
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

function _emphasize_text ($text) {
   // fett
   $text = preg_replace('/(^|\n|\t|\s|[ >\/_[{(])\*([^*]+)\*($|\n|\t|[ <\/_.)\]},!?;])/', '$1<span style="font-weight:bold;">$2</span>$3', $text);
   // kursiv
   $text = preg_replace('/(^|\n|\t|\s|[ >\/_[{(])\_([^_]+)\_($|\n|\t|[ <\/_.)\]},!?;])/', '$1<span style=font-style:italic;>$2</span>$3', $text);
   return $text;
}

function activate_urls ($text) {
   $url_string = '§((http://|https://|ftp://|www\.)'; //everything starting with http, https or ftp followed by "://" or www. is a url and will be avtivated
   $url_string .= "([".RFC1738_CHARS."]+?))"; //All characters allowed for FTP an HTTP URL's by RFC 1738 (non-greedy because of potential trailing punctuation marks)
   $url_string .= '([.?:),;!]*($|\s|<|&quot;))§'; //after the url is a space character- and perhaps before it a punctuation mark (which does not belong to the url)
   $text = preg_replace($url_string, '<a href="$1" target="_blank">$1</a>$4', $text);

   $text = preg_replace('$<a href="www$','<a href="http://www',$text); //add "http://" to links that were activated with www in front only

   // mailto. A space or a linebreak has to be in front of everymail link. No links in bigger words (especially in urls) will be activated
   $text = preg_replace('§( |^|>|\n)(mailto:)?((['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)@(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*\.([A-z]{2,})))§', ' <a href="mailto:$3">$3</a>', $text);
   return $text;
}

/** Wenn im Text Gruppierungen von zwei oder mehr Leerzeichen
 *  vorkommen, werden diese durch entsprechende &nbsp; Tags
 *  ersetzt, um die Ursprüngliche formatierung zu bewaren
 *
 *  !WIRD ZUR ZEIT NICHT VERWENDET!
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

/** returns a string that is x characters at the most but won't
 *  break in the middle of a word.
 *
 * @param text that uld be chunked
 * @param length size of the caracters
 *
 * @return array retour_array the prepared array
 */
function chunkText ($text, $length) {
   $first_tag = '(:';
   $last_tag  = ':)';

   $text = trim($text);
   $mySubstring = preg_replace('/^(.{1,$length})[ .,].*/', '\\1', $text); // ???
   if (strlen($mySubstring) > $length) {
      $mySubstring = substr($text, 0, $length);
      if ( strstr($text,$first_tag)
           and strstr($text,$last_tag)
         ) {
         if ( strrpos($mySubstring,$last_tag) < strrpos($mySubstring,$first_tag) ) {
            $mySubstring2 = substr($text, $length);
            $mySubstring .= substr($mySubstring2,0,strpos($mySubstring2,$last_tag)+2);
            $mySubstring .= ' ';
         }
      }
      if ( strstr($mySubstring,' ') ) {
         $mySubstring = substr($mySubstring,0,strrpos($mySubstring,' '));
      }
      $mySubstring .= ' ...';
   }
   $mySubstring = preg_replace('/\n/', ' ', $mySubstring);
   return $mySubstring;
}
/** returns an URL that is x characters at the most
 *  special needed for _activate_urls in cs_view.php
 *  in a preg_replace_callback - function
 *
 * @param array from preg_replace_function
 *
 * @return text for replacement in preg_replace_function
 */
function spezial_chunkURL ($text) {
   $text = $text[1];
   $text = chunkText($text,45);
   return '">'.$text.'</a>';
}


function parseText2Id ($text) {
   global $environment;
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
            $result = preg_replace('/'.$word.'\['.$reference.'\]/i', ahref_curl($environment->getCurrentContextID(), 'discussion', 'detail', $params, $word, $word, '', 'anchor'.$reference), $result);
            unset($params);
         } else {
            $params = array();
            $params['iid'] = $reference;
            $result = preg_replace('/'.$word.'\['.$reference.'\]/i', ahref_curl($environment->getCurrentContextID(), 'content', 'detail', $params, $word, '', '', ''), $result);
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
   preg_match_all('§\[(http?://['.RFC1738_CHARS.']*)\|([\w'.SPECIAL_CHARS.' -]+)\|(\w+)\]§', $text, $matches_with_long_urls); //
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

   // commsy urls - experimental
   /*
   preg_match_all('/\[(commsy.php\?[\S]*)\|([A-z0-9'.UC_CHARS.LC_CHARS.' -]*)\|([A-z]*)\]/', $text, $matches_with_commsy_urls); //A-z0-9'.UC_CHARS.LC_CHARS.'&#=
   if (count($matches_with_commsy_urls[0]) > 0) {
      $result = $text;
      $http_part = $matches_with_commsy_urls[1];
      $word_part = $matches_with_commsy_urls[2];
      $flag_part = $matches_with_commsy_urls[3];
      for ($i = 0; $i < count($http_part); $i++) {
         $http = $http_part[$i];
         $word = $word_part[$i];
         $flag = $flag_part[$i];
         if (!empty($word) and !empty($http) and !empty($flag)) {
            if ($flag == 'internal') {
               $result = preg_replace('%\['.$http.'\|'.$word.'\|'.$flag.'\]%', '<a href="'.$http.'">'.$word.'</a>', $result);
            } else {
               $result = preg_replace('%\['.$http.'\|'.$word.'\|'.$flag.'\]%', '<a href="'.$http.'" target="_blank">'.$word.'</a>', $result);
            }
         }
      }
      $text = $result;
   }*/

   // ids without text: [<number>] becomes a link under [<number>] to the commsy-object with id <number>
   preg_match_all('/\[(\d+)\]/', $text, $matches_stand_alone);//(^| |\n|>|\t)\[(\d+)\][.:,;-?!]*(<| |$)
   $matches_stand_alone = array_unique($matches_stand_alone[1]);
   if (!empty($matches_stand_alone)) {
      $result = $text;
      foreach ($matches_stand_alone as $item) {
         if ($item <= 100) {
            $params = array();
            $params['iid'] = $current_item_id;
            $result = preg_replace('/\['.$item.'\]/i', ahref_curl($environment->getCurrentContextID(), 'discussion', 'detail', $params, "[".$item."]", "[".$item."]", '', 'anchor'.$item), $result);
            unset($params);
         }
         else {
            $params = array();
            $params['iid'] = $item;
            $result = preg_replace('/\['.$item.'\]/i', ahref_curl($environment->getCurrentContextID(), 'content', 'detail', $params, "[".$item."]", '', '', ''), $result);
            unset($params);
         }
      }
      $text = $result;
   }

   return $text;
}

/**
 * Extended implementation of the standard PHP-Function
 *
 * Needed to ensure proper searching in CommSy with standard PHP settings
 * When the 'locale' setting of PHP is not set properly, the search for language specific characters
 * like 'ä', 'ü', 'ö', 'á' etc doesn't work correct, because the standard PHP strtoupper doesn't translate
 * them (http://de3.php.net/manual/en/function.strtoupper.php)
 *
 * Our extended implementation translates correct without respect to 'locale'
 */

function cs_strtoupper ($value) {
   return (strtoupper(strtr($value, LC_CHARS, UC_CHARS)));
}

/**
 * Extended implementation of the standard PHP-Function
 *
 * Needed to ensure proper searching in CommSy with standard PHP settings
 * When the 'locale' setting of PHP is not set properly, the search for language specific characters
 * like 'ä', 'ü', 'ö', 'á' etc doesn't work correct, because the standard PHP strtolower doesn't translate
 * them (http://de3.php.net/manual/en/function.strtolower.php)
 *
 * Our extended implementation translates correct without respect to 'locale'
 */

function cs_strtolower ($value) {
   return (strtolower(strtr($value, UC_CHARS, LC_CHARS)));
}

/** Translates HTML-Enteties into their ISO8859-1 counterpart
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * It is implemented here, hence the PHP-Version used now (2.7.2003) doesn't...
 * If it is implemented in the PHP version (like it says on php.net), it can be deleted!
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * @param String containing html-enteties that shall be translated
 *
 * @return String with translated html-enteties
 */

if (!function_exists('html_entity_decode')) {
   function html_entity_decode ($string) {
      $trans_tbl = get_html_translation_table(HTML_ENTITIES);
      $trans_tbl = array_flip($trans_tbl);
      $ret = strtr($string, $trans_tbl);
      return $ret;
   }
}

/** Formats text in an arrayfield
 *
 * @param array Array
 * @param field Array Field
 * @param mode text encode mode
 *
 * @return text encoded text
 *
 * @author CommSy Development Group
 */
function getFormattedArrayField ($array, $field, $mode) {
   return text_encode($array[$field],$mode);
}

//Used to prevent another XML problem...
//When going from form to form in a material with a topic attached
//that has a < or > in it's title, the browser replaces the html-entity &lt; or &gt;
//with the original symbol, which confuses the XML-Parser
//So we use our own entities to prevent this
//Mechanism will be cleaned up in v3.0
function convertTagBrakes2Html ($text) {
   $text = str_replace('>', '&unsergt;', $text);
   $text = str_replace('<', '&unserlt;', $text);
   return $text;
}

//Change above thing back...
function convertHtml2TagBrakes ($text) {
   $text = str_replace('&unsergt;','>', $text);
   $text = str_replace('&unserlt;','<', $text);
   return $text;
}

// Checks if a string is a valid email-address.
// It does not recognize all options specified by RFC 2822, especially quoted strings with whitespaces
// are not recognized, but we would have to build a parser to accomplish that...
function isEmailValid($email) {
   $result = preg_match('§(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)@(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)\.[A-z]+§',$email);
   return $result;
}

// Checks if there are umlauts or special characters in the string.
function withUmlaut($value) {
   $retour = true;
   $regs = array();
   ereg('[A-Za-z0-9]+',$value,$regs);
   if ( $regs[0] == $value ) {
      $retour = false;
   }
   return $retour;
}

function toggleUmlaut($value) {
   $retour = $value;
   $retour = str_replace('Ä','Ae',$retour);
   $retour = str_replace('ä','ae',$retour);
   $retour = str_replace('Ö','Oe',$retour);
   $retour = str_replace('ö','oe',$retour);
   $retour = str_replace('Ü','Ue',$retour);
   $retour = str_replace('ü','ue',$retour);
   $retour = str_replace('ß','ss',$retour);
   return $retour;
}

function cs_unserialize ( $extra ) {
   $retour = '';

   if ( !empty($extra) ) {
      $extra_array = unserialize($extra);
      if ( empty($extra_array) ) {
         $text = $extra;
         $counter = 0;
         $laenge = array();
         $temp_text = array();
         while ( strstr($text,'<!-- KFC TEXT -->') ) {
            $pos1 = strpos($text,'<!-- KFC TEXT -->');
            $text_temp = substr($text,$pos1+17);
            $pos2 = strpos($text_temp,'<!-- KFC TEXT -->');
            $text_value = substr($text_temp,0,$pos2);
            $laenge[$counter] = strlen('<!-- KFC TEXT -->'.$text_value.'<!-- KFC TEXT -->');
            $temp_text['FCK_TEXT_'.$counter] = '<!-- KFC TEXT -->'.$text_value.'<!-- KFC TEXT -->';
            $text = str_replace('<!-- KFC TEXT -->'.$text_value.'<!-- KFC TEXT -->','FCK_TEXT_'.$counter,$text);
            $counter++;
         }
         preg_match_all('$s:([0-9]*):"FCK_TEXT_([0-9]*)$',$text,$values);
         foreach ( $values[0] as $key => $wert ) {
            $wert2 = str_replace($values[1][$key],$laenge[$values[2][$key]],$wert);
            $text = str_replace($wert,$wert2,$text);
         }

         preg_match_all('$FCK_TEXT_[0-9]*$',$text,$values);
         foreach ( $values[0] as $key => $wert ) {
            $text = str_replace($wert,$temp_text[$wert],$text);
         }
         $extra_array = unserialize($text);
         if ( empty($extra_array) ) {
            preg_match_all('$s:([0-9]*):"([^(";)]*)";$',$text,$values);
            if ( !empty($values[0]) ) {
               foreach ( $values[0] as $key => $wert ) {
                  if (strlen($values[2][$key]) != $values[1][$key] ) {
                     $wert2 = str_replace($values[1][$key],strlen($values[2][$key]),$wert);
                     $text = str_replace($wert,$wert2,$text);
                  }
               }
            }
            $extra_array = unserialize($text);
            if ( empty($extra_array) ) {
               $text = str_replace('(','[',$text);
               $text = str_replace(')',']',$text);
               $text = str_replace(':"','DOPPELPUNKTHOCH',$text);
               $text = str_replace('";','HOCHSEMIKOLON',$text);
               $text = str_replace('"','\'',$text);
               $text = str_replace('DOPPELPUNKTHOCH',':"',$text);
               $text = str_replace('HOCHSEMIKOLON','";',$text);
               preg_match_all('$s:([0-9]*):"([^(";)]*)";$',$text,$values);
               if ( !empty($values[0]) ) {
                  foreach ( $values[0] as $key => $wert ) {
                     if (strlen($values[2][$key]) != $values[1][$key] ) {
                        $wert2 = str_replace($values[1][$key],strlen($values[2][$key]),$wert);
                        $text = str_replace($wert,$wert2,$text);
                     }
                  }
               }
            }
            $extra_array = unserialize($text);
         }
      }
   }
   if ( !empty($extra_array) ) {
      $retour = $extra_array;
   }
   return $retour;
}
?>