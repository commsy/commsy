<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
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

@include_once('etc/cs_constants.php');

/**
 * Generates Backtrace info in order to trace a function call to its originator.
 * To use, call from within the function which is to be traced.
 *
 * @return An HTML formatted string that contains the backtrace.
 *
 */
function Debug_GetBacktrace()
{
   $s = '';
   $MAXSTRLEN = 64;

   $s = '<pre align=left>';
   $traceArr = debug_backtrace();
   array_shift($traceArr);
   $tabs = sizeof($traceArr)-1;
   foreach($traceArr as $arr)
   {
       for ($i=0; $i < $tabs; $i++) $s .= ' &nbsp; ';
       $tabs -= 1;
       $s .= '<font face="Courier New,Courier">';
       if (isset($arr['class'])) $s .= $arr['class'].'.';
       $args = array();
       if(!empty($arr['args'])) foreach($arr['args'] as $v)
       {
           if (is_null($v)) $args[] = 'null';
           else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
           else if (is_object($v)) $args[] = 'Object:'.get_class($v);
           else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
           else
           {
               $v = (string) @$v;
               $str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
               if (strlen($v) > $MAXSTRLEN) $str .= '...';
               $args[] = "\"".$str."\"";
           }
       }
       $s .= $arr['function'].'('.implode(', ',$args).')</font>';
       $Line = (isset($arr['line'])? $arr['line'] : "unknown");
       $File = (isset($arr['file'])? $arr['file'] : "unknown");
       $s .= sprintf("<font color=#808080 size=-1> # line %4d, file: <a href=\"file:/%s\">%s</a></font>",
           $Line, $File, $File);
       $s .= "\n";
   }
   $s .= '</pre>';
   return $s;
}


/**
 * Generates a version string depending on release or development status.
 * Release version is generated based on the cvs tag.
 * Development version is taken from the file VERSION in the source root.
 *
 * @return  returns a string
 *
 */
function getCommSyVersion() {
   $releasestring     = '$Name$';   // this is replaced on a "cvs release"
   if (strlen($releasestring) > 9 ) {
      $temp1 = stristr($releasestring, 'Rel');
      $temp2 = substr($temp1, 4);
      $temp3 = substr($temp2, 0, strlen($temp2)-1);
      $temp4 = strtr($temp3, '-', '.');
      $commsyversion = $temp4;
   }
   if ( empty($commsyversion) ) {
      $fp = fopen('version','r','1');      // file system access is quite expensive, but the best we can do
      $version = fgets($fp,'50');
      $commsyversion = $version;
   }
   return $commsyversion;
}

/**
 * Used to check a command (button) selected by a form post.
 * Use this function instead of a simple string compare because
 * of special charactes used in different languages.
 *
 * @param   $option  the option selected by the form button
 * @param   $string  the string this option is to be compared with
 *
 * @return  returns true or false
 */
function isOption( $option, $string ) {
   return (strcmp( $option, $string ) == 0) || (strcmp( htmlentities($option), $string ) == 0 || (strcmp( $option, htmlentities($string) )) == 0 );
}

/**
 * Generates a xml string out of an array.
 * This function is used at the management of the extras field in the mysql-database
 *
 * Because of a problem with the XML parser with < and >, we have to convert these characters
 * to their html-entitys (gt; and &lt;). These characters are stored encoded in the DB!
 * We change them back, when we get the XML-string out ouf the DB. See: XML2Array.
 * This happens only in extra-fields.
 *
 * @return  returns a string
 *
 */
function array2XML ($array) {
   $xml = '';
   if (!empty($array) and is_array($array)) {
      $keys = array_keys($array);
      foreach ($keys as $key) {
         if (is_array($array[$key])) {
            if (count($array[$key]) > 0) {
               $data = array2XML($array[$key]);
            } else {
               $data = '';
            }
         } else {
            $data = $array[$key];
            // convert > and < to their html entities (gt; and &lt;)
            if ( strstr($data,"<") ) {
               $data = ereg_replace("<", "%CS_LT;", $data);
            }
            if ( strstr($data,">") ) {
               $data = ereg_replace(">", "%CS_GT;", $data);
            }
            if ( strstr($data,"&") ) {
               $data = ereg_replace("&", "%CS_AND;", $data);
            }
         }

         // xml_parser have problems with integers as keys,
         // so we set an XML_ before integers and delete it,
         // when we get is out of the databes. See: XML2Array
         if ( is_int($key) ) {
            $key = 'XML_'.$key;
         }
         $xml .= '<'.strtoupper($key).'>'.$data.'</'.strtoupper($key).'>'."\n";
      }
   }
   return $xml;
}

function XMLToArray($xml) {
   if ($xml instanceof SimpleXMLElement) {
      $children = $xml->children();
      $return = null;
   } else {
      $xml = simplexml_load_string($xml);
      if ( $xml instanceof SimpleXMLElement ) {
         $children = $xml->children();
      } else {
         $children = array();
      }
      $return = null;
   }
   foreach ($children as $element => $value) {
      if ( strstr($element,'XML_') ) {
         $element_begin = substr($element,0,4);
         if ($element_begin = 'XML_') {
            $element = substr($element,4);
         }
      }
      if ($value instanceof SimpleXMLElement) {
         $counter = 0;
         foreach ($value->children() as $children) {
            $counter++;
         }
         if ($counter > 0) {
            $return[$element] = XMLToArray($value);
         } else {
            if ( !empty($element) and $element == 'extras') {
               $value = unserialize(utf8_decode((string)$value));
            } elseif ( isset($value) ) {
               // convert > and < to their html entities (gt; and &lt;)
               if ( strstr($value,"%CS_AND;") ) {
                  $value = ereg_replace("%CS_AND;", "&", $value);
               }
               if ( strstr($value,"%CS_LT;") ) {
                  $value = ereg_replace("%CS_LT;", "<", $value);
               }
               if ( strstr($value,"%CS_GT;") ) {
                  $value = ereg_replace("%CS_GT;", ">", $value);
               }
               $value = utf8_decode($value); // needed for PHP5
            } else {
               $value = '';
            }
            if (!isset($return[$element])) {
               if ( is_array($value) ) {
                  $return[$element] = $value;
               } else {
                  $return[$element] = (string)$value;
               }
            } else {
               if (!is_array($return[$element])) {
                  $return[$element] = array($return[$element], (string)$value);
               } else {
                  $return[$element][] = (string)$value;
               }
            }
         }
      }
   }
   if (is_array($return)) {
      return $return;
   } else {
      return false;
   }
}

  /**
   * Generates an array out of an xml-string.
   * This function is used at the management of the extras field in the mysql-database
   * Uses expat XML parser / PHP XML-functions.
   *
   * @return  returns an array
   */
   function XML2Array ($text) {
      $text = '<SAVE>'.$text.'</SAVE>';
      $text = utf8_encode($text); // needed for PHP5
      $result = XMLToArray($text);
      return $result;
   }

/** can be deleted
 * Generates a string out of an array.
 * This function is used at the management of the session information
 *
 * So you can store arrays in a text field of the mysql_database
 * If xml version is complete we should use this
 *
 * @return  returns a string
 */
function array2String($array) {
   $temp_array = array();
   while(list($key, $val) = each($array)) {
      $key = ($key and !is_int($key)) ? '"'.$key.'" => ' : '';
      if(!is_array($val)) {
         $temp_array[] = $key.'"'.$val.'"';
      } else {
          $temp_array[] = $key.array2String($val);
      }
   }
   $array_string = 'array('.implode(", ", $temp_array).')';
   return $array_string;
}

/** can be deleted after database data refresh, next installation
 * Generates a string out of an array.
 * This function is used at the management of the session information
 *
 * @return  returns a string
 *
 */
function string2Array($string) {
   $array = array();
   eval("\$array = ".$string.";");
   return $array;
}

/** like in_array only for multible arrays
 * Returns true if needle is found in haystack
 *
 * @param   $needle string or integer
 * @param   $haystack array or multiarray
 *
 * @return  returns boolean
 */
function in_multi_array($needle, $haystack) {
   $in_multi_array = false;
   if (in_array($needle, $haystack)) {
      $in_multi_array = true;
   } else {
      $keys = array_keys($haystack);
      reset($keys);
      $key = current($keys);
      if (empty($key)) {
         $key = 'NULL_WAS_THE_KEY'; // 0 and nothing are the same for php
      }
      while ($key) {
         if ($key == 'NULL_WAS_THE_KEY') {
            $key = 0;
         }
         if (count($haystack) > $key and is_array($haystack[$key])) {
            if (in_multi_array($needle, $haystack[$key])) {
               $in_multi_array = true;
               break;
            }
         }
         $key = next($keys);
      }
   }
   return $in_multi_array;
}

/** like array_merge only for multible arrays
 * returns merged array
 *
 * @param   $array1 array
 * @param   $array2 array
 *
 * @return  $array1 array merged array
 */
function multi_array_merge ($array1, $array2) {
   foreach ($array2 as $key => $value) {
      if (is_array($value)) {
         if (empty($array1[$key])) {
            $array1[$key] = $value;
         } else {
            $array1[$key] = multi_array_merge($array1[$key],$value);
         }
      } else {
         $array1[$key] = $value;
      }
   }
   return $array1;
}

/** is xml string in correct xml systax ?
 * returns true if xml string is correct - false if not
 *
 * @param   $xml string to test
 *
 * @return  $retour boolean
 *
 */
function isXMLcorrect ($xml) {
   $retour = false;
   if (ereg("^<[_[:alnum:]]+>([ƒ÷‹‰ˆ¸ﬂÈÛ¥`!'ß$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>([ƒ÷‹‰ˆ¸ﬂÈÛ¥`!'ß$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>[ƒ÷‹‰ˆ¸ﬂÈÛ¥`!'ß$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>([ƒ÷‹‰ˆ¸ﬂÈÛ¥`!'ß$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>([ƒ÷‹‰ˆ¸ﬂÈÛ¥`!'ß$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*</[_[:alnum:]]+>)*)</[_[:alnum:]]+>)*)</[_[:alnum:]]+>)*)</[_[:alnum:]]+>)*)</[_[:alnum:]]+>$",$xml)) {
      $retour = true;
   } else {
      include_once('functions/error_functions.php');
      trigger_error('XML-String ['.$xml.'] is not shapely.',E_USER_ERROR);
   }
   return $retour;
}

/** it uniques an one-dimensional array
 * returns the unique array
 *
 * @param   $array
 *
 * @return  $unique_array
 *
 */
function arrayUnique ($array) {
    $unique_array = array();
    $i=0;
    foreach($array as $key => $value){
        if(!in_array($value, $unique_array)){
            $unique_array[$i] = $value;
            $i++;
        }
    }
    return $unique_array;
}

function array_value_remove ($value, $array) {
   if (in_array($value,$array)) {
       for ($i=0; $i < count($array); $i++) {
         if ($value == $array[$i]) {
            array_splice($array, $i, 1);
         }
      }
   }
}

/** redirect to a given commsy page
 * first save session than
 * construct a commsy url (custom url) related to a given schema
 * and redirect to the commsy page
 *
 * Module and function specify a target script to be called with this curl,
 * parameter and fragment are used to pass values to that script,
 * filehack is used to fake a files realname into a curl (used by
 * the material manager).
 *
 * @param   $context_id id of the current context
 * @param   $module     commsy module referring to
 * @param   $function   functions are represented by filesnames (without extension)
 * @param   $parameter  (optional) normal parameters ARRAY
 * @param   $fragment   (optional) anchor what goes behind a '#'
 * @param   $filehack   (optional) for faking real filenames into file downloads
 * @param   $file       (optional) for switching between commsy tools
 */
function redirect ($context_id, $module, $function, $parameter='', $fragment='', $filehack='', $file='') {
   include_once('functions/curl_functions.php');
   redirect_with_url(_curl(false,$context_id,$module,$function,$parameter,$fragment,$filehack,$file));
}

/** redirect to a given commsy page
 * first save session than
 * redirect to the commsy page by the given url (use curl_function)
 *
 * @param   $url   url as result of a curl function
 */
function redirect_with_url ($url) {
   global $environment;

   $session = $environment->getSessionItem();
   // only save session when the session has not reseted
   if (isset($session)) {
      $session_id = $session->getSessionID();
      if (!empty($session_id)) {
         $session_manager = $environment->getSessionManager();
         $session_manager->save($session);
      }
   }

   if ( isset($_POST) ) {
      $post_content = array2XML($_POST);
   } else {
      $post_content = '';
   }
   $log = false;
   $post_content_big = strtoupper($post_content);
   if ( empty($post_content_big)
        or ( !empty($post_content_big)
             and ( stristr($post_content_big,'SELECT') !==false
                   or stristr( $post_content_big, 'INSERT') !==false
                   or stristr( $post_content_big, 'UPDATE') !==false
                 )
           )
      ) {
      $log = true;
    }
    if ($log) {
      $array = array();
      if ( isset($_GET['iid']) ) {
         $array['iid'] = $_GET['iid'];
      } elseif ( isset($_POST['iid']) ) {
         $array['iid'] = $_POST['iid'];
      }
      if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
         $array['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
      } else {
         $array['user_agent'] = 'No Info';
      }
      $current_user = $environment->getCurrentUser();
      $array['remote_addr']      = $_SERVER['REMOTE_ADDR'];
      $array['script_name']      = $_SERVER['SCRIPT_NAME'];
      $array['query_string']     = $_SERVER['QUERY_STRING'];
      $array['request_method']   = $_SERVER['REQUEST_METHOD'];
      $array['post_content']     = $post_content;
      $array['user_item_id']     = $current_user->getItemID();
      $array['user_user_id']     = $current_user->getUserID();
      $array['context_id']       = $environment->getCurrentContextID();
      $array['module']           = $environment->getCurrentModule();
      $array['function']         = $environment->getCurrentFunction();
      $array['parameter_string'] = $environment->getCurrentParameterString();

      $log_manager = $environment->getLogManager();
      $log_manager->saveArray($array);
      unset($log_manager);
   }

   header('Location: '.$url);
   header('HTTP/1.0 302 Found');
   exit();
}

/** redirect to a given commsy page
 * first save history in session
 * second save session than
 * construct a commsy url (custom url) related to a given schema
 * and redirect to the commsy page
 *
 * Module and function specify a target script to be called with this curl,
 * parameter and fragment are used to pass values to that script,
 * filehack is used to fake a files realname into a curl (used by
 * the material manager).
 *
 * @param   $context_id id of the current context
 * @param   $module     commsy module referring to
 * @param   $function   functions are represented by filesnames (without extension)
 * @param   $parameter  (optional) normal parameters ARRAY
 * @param   $fragment   (optional) anchor what goes behind a '#'
 * @param   $filehack   (optional) for faking real filenames into file downloads
 */
function redirect_with_history_save ($context_id, $module, $function, $parameter='', $fragment='', $filehack='') {
   global $environment;

   $session = $environment->getSessionItem();

   $history = $session->getValue('history');
   $current_page['context'] = $environment->getCurrentContextID();
   $current_page['module'] = $environment->getCurrentModule();
   $current_page['function'] = $environment->getCurrentFunction();
   $current_page['parameter'] = $environment->getCurrentParameterArray();
   if (empty($history)) {
      $history[0] = $current_page;
   } else {
      $new_history[0] = $current_page;
      if ($new_history[0] != $history[0]) {
         $history = array_merge($new_history,$history);
      }
   }
   while (count($history) > 5) {
      array_pop($history);
   }
   $session->setValue('history',$history);

   // redirect
   redirect_with_url(curl($context_id,$module,$function,$parameter,$fragment,$filehack));
}

/** print value in mode print_r
 * methode to test and debug
 *
 * @param   $value
 */
function pr ($value) {
   echo('<pre>');
   print_r($value);
   echo('</pre>'.LF.LF);
}

/** print xml_value in mode print_r
 * methode to test and debug
 *
 * @param   $value
 */
function pr_xml ($value) {
   echo('<pre>');
   print_r(str_replace('&lt;','<br/>&lt;',htmlentities($value)));
   echo('</pre>'.LF.LF);
}

/** checks if url is valid
 *
 * @return   boolean  is URL valid [= commsy conform]
 */
function isURLValid () {
   global $_GET;
   global $environment;

   if ( (empty($_GET['fct'])  and !empty($_GET['mod']))
        or
        (!empty($_GET['fct']) and empty($_GET['mod']))
      ) {
       return false;
   }

   if ( isset($_GET['mod']) ) {
      $module = $_GET['mod'];
   } else {
      $module = 'home';
   }
   if ( isset($_GET['fct']) ) {
      $funct = $_GET['fct'];
   } else {
      $funct = 'index';
   }

   if (
        // context
        $module != 'context' and

        // server
        $module != 'portal' and

        // portal
        $module != 'project' and
        $module != 'community' and
        $module != 'privateroom' and

        // community room
        $module != CS_ANNOUNCEMENT_TYPE and
        $module != 'link_item' and
        $module != 'campus' and
        $module != 'institution' and
        $module != CS_TOPIC_TYPE  and
        $module != 'annotation' and
        $module != 'contact' and
        $module != 'campus_search' and
        $module != 'agb' and

        // project room
        $module != 'project' and
        $module != 'home' and
        $module != 'todo' and
        $module != CS_DATE_TYPE and
        $module != 'datescalendar' and
        $module != 'discussion' and
        $module != 'discarticle' and
        $module != 'chat' and
        $module != 'material' and
        $module != 'buzzword' and
        $module != 'user' and
        $module != 'group' and
        $module != 'chat' and
        $module != 'myroom' and
        $module != CS_TAG_TYPE and

        // admin
        $module != 'user' and
        $module != 'admin' and
        $module != 'account' and
        $module != 'preferences' and
        $module != 'rooms'  and
        $module != 'task' and
        $module != 'sources' and
        $module != 'internal' and
        $module != 'mail'  and
        $module != 'internal_color' and
        $module != 'material_admin' and
        $module != 'language' and
        $module != 'configuration' and

        // common
        $module != 'context' and
        $module != 'help' and
        $module != 'room' and
        $module != 'picture' and
        $module != 'content' and
        $module != 'commsy' and
        $module != 'material_attach' and
        $module != 'material_bib' and
        $module != 'section' and
        $module != 'clipboard' and
        $module != 'link' and
        $module != 'copies' and

        // server admin
        $module != 'server' and
        $module != 'labels' and
        $module != 'buzzwords' and
        $module != 'rubric' and

        // soap (only for testing)
        $module != 'soap' and

        // log
        $module != 'log' and

        // upload file for external tools
        $module != 'file'
      ) {
      return false;
    }

    if ( $funct != 'detail' and
         $funct != 'edit' and
         $funct != 'home' and
         $funct != 'index' and
         $funct != 'account' and
         $funct != 'initialize' and
         $funct != 'login' and
         $funct != 'logout' and
         $funct != 'overview' and
         $funct != 'join' and
         $funct != 'attach' and
         $funct != 'assigned_index' and
         $funct != 'clipboard_index' and
         $funct != 'password' and
         $funct != 'close' and
         $funct != 'printview' and
         $funct != 'auth' and
         $funct != 'meta' and
         $funct != 'process' and
         $funct != 'getfile' and
         $funct != 'admin' and
         $funct != 'move' and
         $funct != 'to_moderator' and
         $funct != 'to_user' and
         $funct != 'portal' and
         $funct != 'agb' and
         $funct != 'get_file' and
         $funct != 'material' and
         $funct != 'change' and
         $funct != 'import' and
         $funct != 'context' and
         $funct != 'forget' and
         $funct != 'mail' and
         $funct != 'move' and
         $funct != 'move2' and
         $funct != 'sponsor' and
         $funct != 'extra' and
         $funct != 'info_text_edit' and
         $funct != 'info_text_form_edit' and
         $funct != 'color' and
         $funct != 'member' and
         $funct != 'htmltextarea' and
         $funct != 'discussion' and
         $funct != 'rubric' and
         $funct != 'buzzwords' and
         $funct != 'listviews' and
         $funct != 'defaults' and
         $funct != 'upload' and
         $funct != 'wiki' and
         $funct != 'path' and
         $funct != 'tags' and
         $funct != 'ims_import' and

         // module == account
         $funct != 'accountmerge' and
         $funct != 'accountchange' and
         $funct != 'automatic' and
         $funct != 'status' and
         $funct != 'action' and

         // module == configuration
         $funct != 'time' and
         $funct != 'preferences' and
         $funct != 'usageinfo' and
         $funct != 'informationbox' and
         $funct != 'service' and
         $funct != 'privateroom_newsletter' and
         $funct != 'dates' and
         $funct != 'plugin' and
         $funct != 'authentication' and
         $funct != 'export' and
         $funct != 'backup' and
         $funct != 'room_opening' and
         $funct != 'archive' and
         $funct != 'grouproom' and
         $funct != 'portalhome' and
         $funct != 'scribd' and

         $funct != 'account_options' and
         $funct != 'structure_options' and
         $funct != 'rubric_options' and
         $funct != 'room_options' and
         $funct != 'template_options' and
         $funct != 'rubric_extras' and

         $funct != 'item_index' and

         //chat
         $funct != 'process' and
         $funct != 'output' and

         // server admin
         $funct != 'statistic' and
         $funct != 'news' and
         $funct != 'language' and
         $funct != 'outofservice' and

         // admin portal
         $funct != 'room' and

         // edit rooms at portal
         $funct != 'common' and

         // zip upload functionality
         $funct != 'showzip' and
         $funct != 'showzip_file' and

         // external tools
         $funct != 'homepage' and
         $funct != 'chat' and
         $funct != 'forward' and

         $funct != 'short' and // ??? L.

         // print stuff
         $funct != 'print_index' and
         $funct != 'print_detail' and
         $funct != 'print_statistic' and

         // language / message tags
         $funct != 'unused' and

         //soap
         $funct != 'ims' and

         // soap (only for testing)
         $funct != 'test' and

         // plugins
         $funct != 'ads' and

         // upload file for external tools
         $funct != 'upload'
       ) {
       return false;
    }
    return true;
}

function getmicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}

/** converts the item type into the module name
 *
 * @return   string module name
 */
function Type2Module($type){
   $module = '';
   if ($type == CS_TOPIC_TYPE) {
      $module = 'topic';
   } elseif ($type == CS_ANNOUNCEMENT_TYPE) {
      $module = CS_ANNOUNCEMENT_TYPE;
   } elseif ($type == CS_DATE_TYPE) {
      $module = 'date';
   } elseif ($type == CS_DISCARTICLE_TYPE) {
      $module = 'discussion';
   } elseif ($type == CS_SECTION_TYPE) {
      $module = 'material';
   } elseif ($type == 'materials') {
      $module = 'material';
   }else {
      $module = $type;
   }
   return $module;
}

/** converts the module name into item type
 *
 * @return   string item type
 */
function Module2Type ($module) {
   $module = cs_strtolower($module);
   $type = '';
   if ($module == 'topics') {
      $type = CS_TOPIC_TYPE;
   } elseif ($module == CS_ANNOUNCEMENT_TYPE) {
      $type = CS_ANNOUNCEMENT_TYPE;
   } elseif ($module == 'dates') {
      $type = CS_DATE_TYPE;
   } else {
      $type = $module;
   }
   return $type;
}

/** converts the table name into item type
 *
 * @return   string item type
 */
function DBTable2Type ($table) {
   $table = cs_strtolower($table);
   $type = '';
   if ($table == 'annotations') {
      $type = CS_ANNOTATION_TYPE;
   } elseif ($table == 'dates') {
      $type = CS_DATE_TYPE;
   } elseif ($table == 'discussionarticles') {
      $type = CS_DISCARTICLE_TYPE;
   } elseif ($table == 'discussions') {
      $type = CS_DISCUSSION_TYPE;
   } elseif ($table == 'labels') {
      $type = CS_LABEL_TYPE;
   } elseif ($table == 'materials') {
      $type = CS_MATERIAL_TYPE;
   } elseif ($table == 'files') {
      $type = CS_FILE_TYPE;
   } elseif ($table == 'todos') {
      $type = CS_TODO_TYPE;
   } elseif ($table == 'links') {
      $type = CS_LINK_TYPE;
   } elseif ($table == 'link_items') {
      $type = CS_LINKITEM_TYPE;
   } elseif ($table == 'item_link_file') {
      $type = CS_LINKITEMFILE_TYPE;
   } elseif ($table == 'homepage_link_page_page') {
      $type = CS_LINKHOMEPAGEHOMEPAGE_TYPE;
   } else {
      $type = $table;
   }
   return $type;
}

function type2Table ($type) {
   $table = '';
   if ($type == CS_DISCUSSION_TYPE) {
      $table = 'discussions';
   } elseif ($type == CS_DISCARTICLE_TYPE) {
      $table = 'discussionarticles';
   } elseif ($type == CS_MATERIAL_TYPE) {
      $table = 'materials';
   } else {
      $table = $type;
   }
   return $table;
}


/** Checks if a user may edit an item regularly (creator or item public) or because he special rights (admin etc)
*
* TBD: When page item exists, move this function!!!!!!
*
*/
function mayEditRegular($user, $item) {
    $value = true;
    if (!empty($user) and !empty($item)) {
        if (!($user->isUser()
            and ($user->getItemID() == $item->getCreatorID()
            or $item->isPublic()))) {
            $value = false;
        }
    }
    return $value;
}
?>