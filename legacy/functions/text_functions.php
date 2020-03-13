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

function encode ($mode, $value) {
   $retour = $value;
   global $environment;
   if ( !empty($environment) ) {
      $text_converter = $environment->getTextConverter();
      $retour = $text_converter->encode($mode, $value);
      unset($text_converter);
   } else {
      include_once('functions/error_functions.php');
      trigger_error('can not encode data',E_USER_WARNING);
   }
   return $retour;
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
   $mySubstring = preg_replace('~^(.{1,$length})[ .,].*~u', '\\1', $text); // ???
   if (mb_strlen($mySubstring) > $length) {
      $mySubstring = mb_substr($text, 0, $length);
      if ( strstr($text,$first_tag)
           and strstr($text,$last_tag)
         ) {
         if ( mb_strrpos($mySubstring,$last_tag) < mb_strrpos($mySubstring,$first_tag) ) {
            $mySubstring2 = mb_substr($text, $length);
            $mySubstring .= mb_substr($mySubstring2,0,mb_strpos($mySubstring2,$last_tag)+2);
            $mySubstring .= ' ';
         }
      }
      if ( strstr($mySubstring,' ') ) {
         $mySubstring = mb_substr($mySubstring,0,mb_strrpos($mySubstring,' '));
      }
      $mySubstring .= ' ...';
   }
   $mySubstring = preg_replace('~\n~u', ' ', $mySubstring);
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
   // ------------------
   // --->UTF8 - OK<----
   // ------------------
   $text = $text[1];
   $text = chunkText($text,45);
   return '">'.$text.'</a>';
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
   return (mb_strtoupper(strtr($value, LC_CHARS, UC_CHARS), 'UTF-8'));
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
   return (mb_strtolower(strtr($value, UC_CHARS, LC_CHARS), 'UTF-8'));
}

// Checks if a string is a valid email-address.
// It does not recognize all options specified by RFC 2822, especially quoted strings with whitespaces
// are not recognized, but we would have to build a parser to accomplish that...
function isEmailValid($email) {
   // ------------------
   // --->UTF8 - OK<----
   // ------------------
   $result = preg_match('^(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)@(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)\.[A-z]+^u',$email);
   return $result;
}

// Checks if there are umlauts or special characters in the string.
function withUmlaut($value) {
   // ------------------
   // --->UTF8 - OK<----
   // ------------------
   $retour = true;
   $regs = array();
   mb_ereg('[A-Za-z0-9\._]+',$value,$regs);
   if ( $regs[0] == $value ) {
      $retour = false;
   }
   return $retour;
}

function toggleUmlaut($value) {
   // ------------------
   // --->UTF8 - OK<----
   // ------------------
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

function toggleUmlautTemp($value) {
   // ------------------
   // --->UTF8 - OK<----
   // ------------------
   $retour = $value;
   $retour = str_replace('Ä','AAAEEE',$retour);
   $retour = str_replace('ä','aaaeee',$retour);
   $retour = str_replace('Ö','OOOEEE',$retour);
   $retour = str_replace('ö','oooeee',$retour);
   $retour = str_replace('Ü','UUUEEE',$retour);
   $retour = str_replace('ü','uuueee',$retour);
   $retour = str_replace('ß','SsSsSs',$retour);
   return $retour;
}

function toggleUmlautTempBack($value) {
   // ------------------
   // --->UTF8 - OK<----
   // ------------------
   $retour = $value;
   $retour = str_replace('AAAEEE','Ä',$retour);
   $retour = str_replace('aaaeee','ä',$retour);
   $retour = str_replace('OOOEEE','Ö',$retour);
   $retour = str_replace('oooeee','ö',$retour);
   $retour = str_replace('UUUEEE','Ü',$retour);
   $retour = str_replace('uuueee','ü',$retour);
   $retour = str_replace('SsSsSs','ß',$retour);
   return $retour;
}

function mb_unserialize($serial_str) {
   $retour = @unserialize($serial_str);
   if ( empty($retour) ) {
        $serial_str = preg_replace_callback('/s:(\d+):"(.*?)";/s', function($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, $serial_str );

        $retour = @unserialize($serial_str);
        if ( empty($retour) ) {
            $retour = @unserialize(_correct_a($serial_str));
        }
   }
   return $retour;
}

function _correct_a ( $value ) {
   $retour = $value;

   $found = array();
   preg_match_all('~a:([0-9]*):~',$value,$found);
   if ( !empty($found[1][0]) ) {
      $begin = substr($value,0,strpos($value,'{')+1);
      $middle = substr($value,strpos($value,'{')+1,strrpos($value,'}')-strpos($value,'{')-1);
      $end = substr($value,strrpos($value,'}'));
      if ( count($found[1]) > 1 ) {
         $middle = _correct_a($middle);
      }
      $count_sem = 0;
      $count_klam = 0;
      for ( $i=0; $i<strlen($middle); $i++) {
         if ( $count_klam == 0
              and $middle[$i] == ';'
            ) {
            $count_sem = $count_sem + 0.5;
         }
         if ( $middle[$i] == '{' ) {
            $count_klam++;
         } elseif ( $middle[$i] == '}' ) {
            $count_klam--;
         }
      }
      if ( $count_sem == round($count_sem,0)
           and $count_sem != $found[1][0]
         ) {
         $begin = str_replace($found[1][0],$count_sem,$begin);
         $retour = $begin.$middle.$end;
      }
   }
   return $retour;
}

function cs_ucfirst($text){
    $return_text = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    return $return_text.mb_substr($text, 1, mb_strlen($text, 'UTF-8'), 'UTF-8');
}

function cs_lcfirst($text){
    $return_text = mb_strtolower(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    return $return_text.mb_substr($text, 1, mb_strlen($text, 'UTF-8'), 'UTF-8');
}

// von http://de3.php.net/sprintf
if (!function_exists('mb_sprintf')) {
  function mb_sprintf($format) {
      $argv = func_get_args() ;
      array_shift($argv) ;
      return mb_vsprintf($format, $argv) ;
  }
}
if (!function_exists('mb_vsprintf')) {
  /**
   * Works with all encodings in format and arguments.
   * Supported: Sign, padding, alignment, width and precision.
   * Not supported: Argument swapping.
   */
  function mb_vsprintf($format, $argv, $encoding=null) {
      if (is_null($encoding))
          $encoding = mb_internal_encoding();

      // Use UTF-8 in the format so we can use the u flag in preg_split
      $format = mb_convert_encoding($format, 'UTF-8', $encoding);

      $newformat = ""; // build a new format in UTF-8
      $newargv = array(); // unhandled args in unchanged encoding

      while ($format !== "") {

        // Split the format in two parts: $pre and $post by the first %-directive
        // We get also the matched groups
        list ($pre, $sign, $filler, $align, $size, $precision, $type, $post) =
            preg_split("!\%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u",
                       $format, 2, PREG_SPLIT_DELIM_CAPTURE) ;

        $newformat .= mb_convert_encoding($pre, $encoding, 'UTF-8');

        if ($type == '') {
          // didn't match. do nothing. this is the last iteration.
        }
        elseif ($type == '%') {
          // an escaped %
          $newformat .= '%%';
        }
        elseif ($type == 's') {
          $arg = array_shift($argv);
          $arg = mb_convert_encoding($arg, 'UTF-8', $encoding);
          $padding_pre = '';
          $padding_post = '';

          // truncate $arg
          if ($precision !== '') {
            $precision = intval(substr($precision,1));
            if ($precision > 0 && mb_strlen($arg,$encoding) > $precision)
              $arg = mb_substr($precision,0,$precision,$encoding);
          }

          // define padding
          if ($size > 0) {
            $arglen = mb_strlen($arg, $encoding);
            if ($arglen < $size) {
              if($filler==='')
                  $filler = ' ';
              if ($align == '-')
                  $padding_post = str_repeat($filler, $size - $arglen);
              else
                  $padding_pre = str_repeat($filler, $size - $arglen);
            }
          }

          // escape % and pass it forward
          $newformat .= $padding_pre . str_replace('%', '%%', $arg) . $padding_post;
        }
        else {
          // another type, pass forward
          $newformat .= "%$sign$filler$align$size$precision$type";
          $newargv[] = array_shift($argv);
        }
        $format = strval($post);
      }
      // Convert new format back from UTF-8 to the original encoding
      $newformat = mb_convert_encoding($newformat, $encoding, 'UTF-8');
      return vsprintf($newformat, $newargv);
   }
}

function cs_utf8_encode ($value) {
   if ( mb_check_encoding($value, 'UTF-8') ) {
      return $value;
   } elseif ( mb_check_encoding($value, 'ISO-8859-1') ) {
      return utf8_encode($value);
   }
}

/**
* Encodes String to UTF8
* @param string $string
* @return string
*/
function cs_utf8_encode2($string) {
   if ( check_utf8($string) ) {
      return $string;
   } else {
      if ( function_exists('mb_convert_encoding') ) {
         return mb_convert_encoding($string,'utf-8');
      } else {
         return utf8_encode($string);
      }
   }
}

function check_utf8($str) {
    $len = strlen($str);
    for($i = 0; $i < $len; $i++){
        $c = ord($str[$i]);
        if ($c > 128) {
            if (($c > 247)) return false;
            elseif ($c > 239) $bytes = 4;
            elseif ($c > 223) $bytes = 3;
            elseif ($c > 191) $bytes = 2;
            else return false;
            if (($i + $bytes) > $len) return false;
            while ($bytes > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) return false;
                $bytes--;
            }
        }
    }
    return true;
} // end of check_utf8

function is_utf8 ($string) {
   return check_utf8($string);
}
?>