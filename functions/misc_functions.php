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
               $str = htmlspecialchars(mb_substr($v,0,$MAXSTRLEN), ENT_NOQUOTES, 'UTF-8');
               if (mb_strlen($v) > $MAXSTRLEN) $str .= '...';
               $args[] = "\"".$str."\"";
           }
       }
       $s .= $arr['function'].'('.implode(', ',$args).')</font>';
       $Line = (isset($arr['line'])? $arr['line'] : "unknown");
       $File = (isset($arr['file'])? $arr['file'] : "unknown");
       $s .= "<font color=#808080 size=-1> # line " . $Line . ", file: <a href=\"file:/" . $File . "\">" . $File . "</a></font>";
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
   if (mb_strlen($releasestring) > 9 ) {
      $temp1 = mb_stristr($releasestring, 'Rel');
      $temp2 = mb_substr($temp1, 4);
      $temp3 = mb_substr($temp2, 0, mb_strlen($temp2)-1);
      $temp4 = strtr($temp3, '-', '.');
      $commsyversion = trim($temp4);
   }
   if ( empty($commsyversion) ) {
      $fp = fopen('version','r','1');      // file system access is quite expensive, but the best we can do
      $version = fgets($fp,'50');
      $commsyversion = trim($version);
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
   return (strcmp( $option, $string ) == 0) || (strcmp( htmlentities($option, ENT_NOQUOTES, 'UTF-8'), $string ) == 0 || (strcmp( $option, htmlentities($string, ENT_NOQUOTES, 'UTF-8') )) == 0 );
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
               $data = mb_ereg_replace("<", "%CS_LT;", $data);
            }
            if ( strstr($data,">") ) {
               $data = mb_ereg_replace(">", "%CS_GT;", $data);
            }
            if ( strstr($data,"&") ) {
               $data = mb_ereg_replace("&", "%CS_AND;", $data);
            }
         }

         // xml_parser have problems with integers as keys,
         // so we set an XML_ before integers and delete it,
         // when we get is out of the databes. See: XML2Array
         if ( is_int($key) ) {
            $key = 'XML_'.$key;
         }
         $xml .= '<'.mb_strtoupper($key, 'UTF-8').'>'.$data.'</'.mb_strtoupper($key, 'UTF-8').'>'."\n";
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
         $element_begin = mb_substr($element,0,4);
         if ($element_begin = 'XML_') {
            $element = mb_substr($element,4);
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
               $value = mb_unserialize(utf8_decode((string)$value));
            } elseif ( isset($value) ) {
               // convert > and < to their html entities (gt; and &lt;)
               if ( strstr($value,"%CS_AND;") ) {
                  $value = mb_ereg_replace("%CS_AND;", "&", $value);
               }
               if ( strstr($value,"%CS_LT;") ) {
                  $value = mb_ereg_replace("%CS_LT;", "<", $value);
               }
               if ( strstr($value,"%CS_GT;") ) {
                  $value = mb_ereg_replace("%CS_GT;", ">", $value);
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
   if (mb_ereg("^<[_[:alnum:]]+>([ÄÖÜäöüßéó´`!'§$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>([ÄÖÜäöüßéó´`!'§$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>[ÄÖÜäöüßéó´`!'§$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>([ÄÖÜäöüßéó´`!'§$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*|(<[_[:alnum:]]+>([ÄÖÜäöüßéó´`!'§$%&/()=?[:alnum:][:punct:][:blank:][:cntrl:]]*</[_[:alnum:]]+>)*)</[_[:alnum:]]+>)*)</[_[:alnum:]]+>)*)</[_[:alnum:]]+>)*)</[_[:alnum:]]+>$",$xml)) {
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
   $post_content_big = mb_strtoupper($post_content, 'UTF-8');
   if ( empty($post_content_big)
        or ( !empty($post_content_big)
             and ( mb_stristr($post_content_big,'SELECT') !==false
                   or mb_stristr( $post_content_big, 'INSERT') !==false
                   or mb_stristr( $post_content_big, 'UPDATE') !==false
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

   // TODO: maybe it becomes necessary to not store ajax requests in history
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
   if ( is_object($value)
        and !empty($value->_environment)
      ) {
      $env = $value->_environment;
      unset($value->_environment);
   }
   echo('<pre>');
   print_r($value);
   echo('</pre>'.LF.LF);
   if ( !empty($env) ) {
      $value->_environment = $env;
   }
}

/** print xml_value in mode print_r
 * methode to test and debug
 *
 * @param   $value
 */
function pr_xml ($value) {
   echo('<pre>');
   print_r(str_replace('&lt;','<br/>&lt;',htmlentities($value, ENT_NOQUOTES, 'UTF-8')));
   echo('</pre>'.LF.LF);
}

/** print value in mode print_r only for one user
 * methode to test and debug
 *
 * @param   $value
 */
function pr_user ($value, $user) {
   global $environment;
   if($environment->getCurrentUser()->getUserID() == $user){
      pr($value);
   }
}

function el($value) {
   error_log(print_r($value, true), 0);  
}

/** checks if url is valid
 *
 * @return   boolean  is URL valid [= commsy conform]
 */
function isURLValid () {
   global $environment;

   if ( !empty($_GET['mod'])
        and $environment->isPlugin($_GET['mod'])
      ) {
      return true;
   }

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
   		$module != 'search' and
        $module != 'agb' and
        $module != 'agbroom' and

        // project room
        $module != 'project' and
        $module != 'home' and
        $module != 'todo' and
        $module != CS_DATE_TYPE and
        $module != 'datescalendar' and
        $module != 'discussion' and
        $module != 'discarticle' and
        $module != 'step' and
        $module != 'chat' and
        $module != 'material' and
        $module != 'entry' and
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
        $module != 'fdviewer' and

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
        $module != 'file' and

        // plugin
        $module != 'plugin' and

        // ajax
        $module != 'ajax' and
        
        // Scorm
        $module != 'scorm' and
   		
   		// download
   		$module != 'download' and
   		$module != 'limesurvey' and
   		
   		// individual css
   		$module != 'individual'
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
         $funct != 'getlogfile' and
    	 $funct != 'getTemp' and
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
    	 $funct != 'limesurvey' and
         $funct != 'wordpress' and
         $funct != 'path' and
         $funct != 'tags' and
         $funct != 'ims_import' and
         $funct != 'ical_import' and

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
         $funct != 'privateroom_home_options' and
         $funct != 'dates' and
         $funct != 'authentication' and
         $funct != 'export' and
         $funct != 'backup' and
         $funct != 'room_opening' and
         $funct != 'archive' and
         $funct != 'grouproom' and
         $funct != 'portalhome' and
         $funct != 'portalupload' and
         $funct != 'scribd' and
         $funct != 'datasecurity' and
         $funct != 'inactive' and
         $funct != 'inactiveprocess' and
    	   $funct != 'assignroom' and

         $funct != 'account_options' and
         $funct != 'structure_options' and
         $funct != 'rubric_options' and
         $funct != 'room_options' and
         $funct != 'template_options' and
         $funct != 'rubric_extras' and

         $funct != 'item_index' and

         $funct != 'dbbackup' and
         
         $funct != 'mediaintegration' and

         $funct != 'export_import' and

         //chat
         $funct != 'process' and
         $funct != 'output' and

         // server admin
         $funct != 'statistic' and
         $funct != 'news' and
         $funct != 'language' and
         $funct != 'outofservice' and
         $funct != 'connection' and
    		$funct != 'update' and

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
         $funct != 'plugin' and
         $funct != 'plugins' and
         $funct != 'ads' and

         // upload file for external tools
         $funct != 'upload' and

         // automtic generated accounts
         $funct != 'autoaccounts' and

         // ajax
         $funct != 'privateroom_home' and
         $funct != 'privateroom_home_configuration' and
         $funct != 'privateroom_home_portlet_configuration' and
         $funct != 'privateroom_myroom_configuration' and
         $funct != 'privateroom_my_entries_configuration' and
         $funct != 'privateroom_entry' and
         $funct != 'privateroom_myroom' and
         $funct != 'privateroom_myentries' and
         $funct != 'privateroom_matrix_configuration' and
         $funct != 'privateroom_buzzword_configuration' and
         $funct != 'privateroom_mycalendar_configuration' and
         $funct != 'privateroom_mycalendar' and
         $funct != 'privateroom_roomwide_search' and
         $funct != 'privateroom_tag_configuration' and
         $funct != 'ckeditor_image_upload' and
         $funct != 'ckeditor_image_browse' and
         $funct != 'uploadify' and
         $funct != 'mdo_perform_search' and
         $funct != 'assessment' and
         $funct != 'search' and
         $funct != 'search_index' and
    	 $funct != 'rubric_popup' and
    	 $funct != 'popup' and
    	 $funct != 'path' and
    	 $funct != 'picture' and
    	 $funct != 'widgets' and
    	 $funct != 'widget_new_entries' and
    	 $funct != 'widget_released_entries' and
    	 $funct != 'widget_released_entries_for_me' and
    	 $funct != 'portfolio' and
    	 $funct != 'widget_stack' and
         
         $funct != 'workflow'
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

function plugin_hook ($hook_function, $params = null, $with_config_check = true) {
   global $environment;
   global $c_plugin_array;

   if ( isset($c_plugin_array)
        and !empty($c_plugin_array)
      ) {
      $current_context_item = $environment->getCurrentPortalItem();
      foreach ($c_plugin_array as $plugin) {
         if ( isset($current_context_item)
              and ( $current_context_item->isPluginOn($plugin)
              		  or !$with_config_check
              		)
            ) {
            $plugin_class = $environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,$hook_function) ) {
               $plugin_class->$hook_function($params);
            }
         }
      }
   }
}

function plugin_hook_plugin ($plugin, $hook_function, $params = null) {
   global $environment;
   global $c_plugin_array;
   if ( in_array($plugin,$c_plugin_array) ) {
      $do_it = false;
      $plugin_class = $environment->getPluginClass($plugin);
      $current_context_item = $environment->getCurrentContextItem();
      if ( method_exists($plugin_class,'isConfigurableInRoom')
           and $plugin_class->isConfigurableInRoom($current_context_item->getItemType())
         ) {
         $current_context_item = $environment->getCurrentContextItem();
      } elseif ( method_exists($plugin_class,'isConfigurableInPortal')
                 and $plugin_class->isConfigurableInPortal()
               ) {
         $current_context_item = $environment->getCurrentPortalItem();
      }
      if ( isset($current_context_item)
           and $current_context_item->isPluginActive($plugin)
         ) {
         $do_it = true;
      }
      if ( $do_it
           and isset($plugin_class)
           and method_exists($plugin_class,$hook_function)
         ) {
         $plugin_class->$hook_function($params);
      }
   }
}

function plugin_hook_output_all ($hook_function, $params = null, $separator = '', $with_config_check = true) {
   if ( !empty($separator)
        and $separator == 'ARRAY'
      ) {
      $retour = array(); 
   } else {
      $retour = '';
   }
   global $environment;
   global $c_plugin_array;

   if ( isset($c_plugin_array)
        and !empty($c_plugin_array)
      ) {
      $first = true;
      foreach ($c_plugin_array as $plugin) {
     		$output = plugin_hook_output($plugin,$hook_function,$params,$with_config_check);
         if ( !empty($output) ) {
            if ( !empty($separator)
                 and $separator == 'ARRAY'
                 and is_array($output)
               ) {
               $retour = array_merge($retour,$output);
            } elseif ( !empty($separator)
                 and $separator == 'MULTIARRAY'
                 and is_array($output)
               ) {
               $retour[] = $output;
            } elseif ( !empty($separator)
                       and $separator == 'ONE'
                     ) {
               $retour = $output;
               break;
            } else {
               if ( $first ) {
                  $first = false;
               } else {
                  $retour .= $separator;
               }
               $retour .= $output;
            }
         }
      }
   }
   return $retour;
}

function plugin_hook_output ($plugin,$hook_function,$params = NULL,$with_config_check = true) {
   $retour = '';
   global $environment;
   global $c_plugin_array;
   if ( in_array($plugin,$c_plugin_array) ) {
      $do_it = false;
      $plugin_class = $environment->getPluginClass($plugin);
      if ( $hook_function == 'getSOAPAPIArray'
      	  or !$with_config_check
      	) {
      	$do_it = true;
      } else {
	      $current_context_item = $environment->getCurrentContextItem();
	      if ( method_exists($plugin_class,'isConfigurableInRoom')
	           and $plugin_class->isConfigurableInRoom($current_context_item->getItemType())
	         ) {
	         $current_context_item = $environment->getCurrentContextItem();
	      } elseif ( method_exists($plugin_class,'isConfigurableInPortal')
	                 and $plugin_class->isConfigurableInPortal()
	               ) {
	         $current_context_item = $environment->getCurrentPortalItem();
	      }
	      if ( isset($current_context_item)
	           and $current_context_item->isPluginActive($plugin)
	         ) {
	         $do_it = true;
	      }
      }
      if ( $do_it
           and isset($plugin_class)
           and method_exists($plugin_class,$hook_function)
         ) {
      	$retour = $plugin_class->$hook_function($params);
      }
   }
   return $retour;
}

function plugin_hook_method_active ( $hook_function ) {
   global $environment;
   global $c_plugin_array;
   $retour = false;

   if ( isset($c_plugin_array)
        and !empty($c_plugin_array)
      ) {
      $current_context_item = $environment->getCurrentContextItem();
      foreach ($c_plugin_array as $plugin) {
         if ( isset($current_context_item)
              and $current_context_item->isPluginOn($plugin)
            ) {
            $plugin_class = $environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,$hook_function) ) {
               $retour = true;
               break;
            }
         }
      }
   }
   return $retour;
}

// Function to recursively add a directory,
// sub-directories and files to a zip archive
function addFolderToZip($dir, $zipArchive, $zipdir = ''){
   if ( is_dir($dir) ) {
      if ( $dh = opendir($dir) ) {
         //Add the directory
         if ( $dir !== "." ) {
            $zipArchive->addEmptyDir($dir);
         }

         // Loop through all the files
         while ( ($file = readdir($dh)) !== false ) {
            if ( $dir !== "." ) {
               $file_path = $dir.DIRECTORY_SEPARATOR.$file;
            } else {
               $file_path = $file;
            }
            if ( !empty($zipdir) ) {
               $zip_path = $zipdir.DIRECTORY_SEPARATOR.$file;
            } else {
               $zip_path = $file_path;
            }

            //If it's a folder, run the function again!
            if ( !is_file($file_path) ) {
                // Skip parent and root directories
                if ( ($file !== ".") and ($file !== "..") ) {
                   addFolderToZip($file_path , $zipArchive, $zip_path);
                }
            } else {
               // Add the files
               $result = $zipArchive->addFile($file_path, $zip_path);
            }
         }
      }
   }
   return $zipArchive;
}

// up oder down...(up)
function getMarkerColor(){
   global $environment;
   $room = $environment->getCurrentContextItem();
   $color = $room->getColorArray();

   $tabs_background_color = $color['tabs_background'];
   if ( $tabs_background_color[0] == '#' ) {
      $tabs_background_color = substr($tabs_background_color,1);
   }
   $r = hexdec($tabs_background_color[0].$tabs_background_color[1]);
   $g = hexdec($tabs_background_color[2].$tabs_background_color[3]);
   $b = hexdec($tabs_background_color[4].$tabs_background_color[5]);

   $HSL = array();

   $var_R = ($r / 255);
   $var_G = ($g / 255);
   $var_B = ($b / 255);

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

   if($HSL['V'] < 0.91){
      return 'yellow'; // white also hell
   } elseif ($HSL['V'] > 0.91) {
      return 'green'; // black also dunkel
   }
}


function getSortImage($direction){
   global $environment;
   $room = $environment->getCurrentContextItem();
   $color = $room->getColorArray();

   $tabs_background_color = $color['tabs_background'];
   if ( $tabs_background_color[0] == '#' ) {
      $tabs_background_color = substr($tabs_background_color,1);
   }
   $r = hexdec($tabs_background_color[0].$tabs_background_color[1]);
   $g = hexdec($tabs_background_color[2].$tabs_background_color[3]);
   $b = hexdec($tabs_background_color[4].$tabs_background_color[5]);

   $HSL = array();

   $var_R = ($r / 255);
   $var_G = ($g / 255);
   $var_B = ($b / 255);

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

   if($HSL['V'] < 0.91){
      return 'images/sort_' . $direction . '_white.gif';
   } elseif ($HSL['V'] > 0.91) {
      return 'images/sort_' . $direction . '_black.gif';
   } else {
      return 'images/sort_' . $direction . '.gif';
   }
}

function checkColorArray($color_array){
   foreach($color_array as $key => $color){
      if($key != 'schema' and $color[0] != '#'){
         $color = strtolower($color);
         if($color === 'black') {$color_array[$key] = '#000000';}
         elseif($color === 'aliceblue') {$color_array[$key] = '#f0f8ff';}
         elseif($color === 'blueviolet') {$color_array[$key] = '#8a2be2';}
         elseif($color === 'cadetblue') {$color_array[$key] = '#5f9ea0';}
         elseif($color === 'cadetblue1') {$color_array[$key] = '#98f5ff';}
         elseif($color === 'cadetblue2') {$color_array[$key] = '#8ee5ee';}
         elseif($color === 'cadetblue3') {$color_array[$key] = '#7ac5cd';}
         elseif($color === 'cadetblue4') {$color_array[$key] = '#53868b';}
         elseif($color === 'cornflowerblue') {$color_array[$key] = '#6495ed';}
         elseif($color === 'darkslateblue') {$color_array[$key] = '#483d8b';}
         elseif($color === 'darkturquoise') {$color_array[$key] = '#00ced1';}
         elseif($color === 'deepskyblue') {$color_array[$key] = '#00bfff';}
         elseif($color === 'deepskyblue1') {$color_array[$key] = '#00bfff';}
         elseif($color === 'deepskyblue2') {$color_array[$key] = '#00b2ee';}
         elseif($color === 'deepskyblue3') {$color_array[$key] = '#009acd';}
         elseif($color === 'deepskyblue4') {$color_array[$key] = '#00688b';}
         elseif($color === 'dodgerblue') {$color_array[$key] = '#1e90ff';}
         elseif($color === 'dodgerblue1') {$color_array[$key] = '#1e90ff';}
         elseif($color === 'dodgerblue2') {$color_array[$key] = '#1c86ee';}
         elseif($color === 'dodgerblue3') {$color_array[$key] = '#1874cd';}
         elseif($color === 'dodgerblue4') {$color_array[$key] = '#104e8b';}
         elseif($color === 'lightblue') {$color_array[$key] = '#add8e6';}
         elseif($color === 'lightblue1') {$color_array[$key] = '#bfefff';}
         elseif($color === 'lightblue2') {$color_array[$key] = '#b2dfee';}
         elseif($color === 'lightblue3') {$color_array[$key] = '#9ac0cd';}
         elseif($color === 'lightblue4') {$color_array[$key] = '#68838b';}
         elseif($color === 'lightcyan') {$color_array[$key] = '#e0ffff';}
         elseif($color === 'lightcyan1') {$color_array[$key] = '#e0ffff';}
         elseif($color === 'lightcyan2') {$color_array[$key] = '#d1eeee';}
         elseif($color === 'lightcyan3') {$color_array[$key] = '#b4cdcd';}
         elseif($color === 'lightcyan4') {$color_array[$key] = '#7a8b8b';}
         elseif($color === 'lightskyblue') {$color_array[$key] = '#87cefa';}
         elseif($color === 'lightskyblue1') {$color_array[$key] = '#b0e2ff';}
         elseif($color === 'lightskyblue2') {$color_array[$key] = '#a4d3ee';}
         elseif($color === 'lightskyblue3') {$color_array[$key] = '#8db6cd';}
         elseif($color === 'lightskyblue4') {$color_array[$key] = '#607b8b';}
         elseif($color === 'lightslateblue') {$color_array[$key] = '#8470ff';}
         elseif($color === 'lightsteelblue') {$color_array[$key] = '#b0c4de';}
         elseif($color === 'lightsteelblue1') {$color_array[$key] = '#cae1ff';}
         elseif($color === 'lightsteelblue2') {$color_array[$key] = '#bcd2ee';}
         elseif($color === 'lightsteelblue3') {$color_array[$key] = '#a2b5cd';}
         elseif($color === 'lightsteelblue4') {$color_array[$key] = '#6e7b8b';}
         elseif($color === 'mediumaquamarine') {$color_array[$key] = '#66cdaa';}
         elseif($color === 'mediumblue') {$color_array[$key] = '#0000cd';}
         elseif($color === 'mediumslateblue') {$color_array[$key] = '#7b68ee';}
         elseif($color === 'mediumturquoise') {$color_array[$key] = '#48d1cc';}
         elseif($color === 'midnightblue') {$color_array[$key] = '#191970';}
         elseif($color === 'navyblue') {$color_array[$key] = '#000080';}
         elseif($color === 'paleturquoise') {$color_array[$key] = '#afeeee';}
         elseif($color === 'paleturquoise1') {$color_array[$key] = '#bbffff';}
         elseif($color === 'paleturquoise2') {$color_array[$key] = '#aeeeee';}
         elseif($color === 'paleturquoise3') {$color_array[$key] = '#96cdcd';}
         elseif($color === 'paleturquoise4') {$color_array[$key] = '#668b8b';}
         elseif($color === 'powderblue') {$color_array[$key] = '#b0e0e6';}
         elseif($color === 'royalblue') {$color_array[$key] = '#4169e1';}
         elseif($color === 'royalblue1') {$color_array[$key] = '#4876ff';}
         elseif($color === 'royalblue2') {$color_array[$key] = '#436eee';}
         elseif($color === 'royalblue3') {$color_array[$key] = '#3a5fcd';}
         elseif($color === 'royalblue4') {$color_array[$key] = '#27408b';}
         elseif($color === 'skyblue') {$color_array[$key] = '#87ceeb';}
         elseif($color === 'skyblue1') {$color_array[$key] = '#87ceff';}
         elseif($color === 'skyblue2') {$color_array[$key] = '#7ec0ee';}
         elseif($color === 'skyblue3') {$color_array[$key] = '#6ca6cd';}
         elseif($color === 'skyblue4') {$color_array[$key] = '#4a708b';}
         elseif($color === 'slateblue') {$color_array[$key] = '#6a5acd';}
         elseif($color === 'slateblue1') {$color_array[$key] = '#836fff';}
         elseif($color === 'slateblue2') {$color_array[$key] = '#7a67ee';}
         elseif($color === 'slateblue3') {$color_array[$key] = '#6959cd';}
         elseif($color === 'slateblue4') {$color_array[$key] = '#473c8b';}
         elseif($color === 'steelblue') {$color_array[$key] = '#4682b4';}
         elseif($color === 'steelblue1') {$color_array[$key] = '#63b8ff';}
         elseif($color === 'steelblue2') {$color_array[$key] = '#5cacee';}
         elseif($color === 'steelblue3') {$color_array[$key] = '#4f94cd';}
         elseif($color === 'steelblue4') {$color_array[$key] = '#36648b';}
         elseif($color === 'aquamarine') {$color_array[$key] = '#7fffd4';}
         elseif($color === 'aquamarine1') {$color_array[$key] = '#7fffd4';}
         elseif($color === 'aquamarine2') {$color_array[$key] = '#76eec6';}
         elseif($color === 'aquamarine3') {$color_array[$key] = '#66cdaa';}
         elseif($color === 'aquamarine4') {$color_array[$key] = '#458b74';}
         elseif($color === 'azure') {$color_array[$key] = '#f0ffff';}
         elseif($color === 'azure1') {$color_array[$key] = '#f0ffff';}
         elseif($color === 'azure2') {$color_array[$key] = '#e0eeee';}
         elseif($color === 'azure3') {$color_array[$key] = '#c1cdcd';}
         elseif($color === 'azure4') {$color_array[$key] = '#838b8b';}
         elseif($color === 'blue') {$color_array[$key] = '#0000ff';}
         elseif($color === 'blue1') {$color_array[$key] = '#0000ff';}
         elseif($color === 'blue2') {$color_array[$key] = '#0000ee';}
         elseif($color === 'blue3') {$color_array[$key] = '#0000cd';}
         elseif($color === 'blue4') {$color_array[$key] = '#00008b';}
         elseif($color === 'cyan') {$color_array[$key] = '#00ffff';}
         elseif($color === 'cyan1') {$color_array[$key] = '#00ffff';}
         elseif($color === 'cyan2') {$color_array[$key] = '#00eeee';}
         elseif($color === 'cyan3') {$color_array[$key] = '#00cdcd';}
         elseif($color === 'cyan4') {$color_array[$key] = '#008b8b';}
         elseif($color === 'navy') {$color_array[$key] = '#000080';}
         elseif($color === 'turquoise') {$color_array[$key] = '#40e0d0';}
         elseif($color === 'turquoise1') {$color_array[$key] = '#00f5ff';}
         elseif($color === 'turquoise2') {$color_array[$key] = '#00e5ee';}
         elseif($color === 'turquoise3') {$color_array[$key] = '#00c5cd';}
         elseif($color === 'turquoise4') {$color_array[$key] = '#00868b';}
         elseif($color === 'rosybrown') {$color_array[$key] = '#bc8f8f';}
         elseif($color === 'rosybrown1') {$color_array[$key] = '#ffc1c1';}
         elseif($color === 'rosybrown2') {$color_array[$key] = '#eeb4b4';}
         elseif($color === 'rosybrown3') {$color_array[$key] = '#cd9b9b';}
         elseif($color === 'rosybrown4') {$color_array[$key] = '#8b6969';}
         elseif($color === 'saddlebrown') {$color_array[$key] = '#8b4513';}
         elseif($color === 'sandybrown') {$color_array[$key] = '#f4a460';}
         elseif($color === 'beige') {$color_array[$key] = '#f5f5dc';}
         elseif($color === 'brown') {$color_array[$key] = '#a52a2a';}
         elseif($color === 'brown1') {$color_array[$key] = '#ff4040';}
         elseif($color === 'brown2') {$color_array[$key] = '#ee3b3b';}
         elseif($color === 'brown3') {$color_array[$key] = '#cd3333';}
         elseif($color === 'brown4') {$color_array[$key] = '#8b2323';}
         elseif($color === 'burlywood') {$color_array[$key] = '#deb887';}
         elseif($color === 'burlywood1') {$color_array[$key] = '#ffd39b';}
         elseif($color === 'burlywood2') {$color_array[$key] = '#eec591';}
         elseif($color === 'burlywood3') {$color_array[$key] = '#cdaa7d';}
         elseif($color === 'burlywood4') {$color_array[$key] = '#8b7355';}
         elseif($color === 'chocolate') {$color_array[$key] = '#d2691e';}
         elseif($color === 'chocolate1') {$color_array[$key] = '#ff7f24';}
         elseif($color === 'chocolate2') {$color_array[$key] = '#ee7621';}
         elseif($color === 'chocolate3') {$color_array[$key] = '#cd661d';}
         elseif($color === 'chocolate4') {$color_array[$key] = '#8b4513';}
         elseif($color === 'peru') {$color_array[$key] = '#cd853f';}
         elseif($color === 'tan') {$color_array[$key] = '#d2b48c';}
         elseif($color === 'tan1') {$color_array[$key] = '#ffa54f';}
         elseif($color === 'tan2') {$color_array[$key] = '#ee9a49';}
         elseif($color === 'tan3') {$color_array[$key] = '#cd853f';}
         elseif($color === 'tan4') {$color_array[$key] = '#8b5a2b';}
         elseif($color === 'darkslategray') {$color_array[$key] = '#2f4f4f';}
         elseif($color === 'darkslategray1') {$color_array[$key] = '#97ffff';}
         elseif($color === 'darkslategray2') {$color_array[$key] = '#8deeee';}
         elseif($color === 'darkslategray3') {$color_array[$key] = '#79cdcd';}
         elseif($color === 'darkslategray4') {$color_array[$key] = '#528b8b';}
         elseif($color === 'darkslategrey') {$color_array[$key] = '#2f4f4f';}
         elseif($color === 'dimgray') {$color_array[$key] = '#696969';}
         elseif($color === 'dimgrey') {$color_array[$key] = '#696969';}
         elseif($color === 'lightgray') {$color_array[$key] = '#d3d3d3';}
         elseif($color === 'lightgrey') {$color_array[$key] = '#d3d3d3';}
         elseif($color === 'lightslategray') {$color_array[$key] = '#778899';}
         elseif($color === 'lightslategrey') {$color_array[$key] = '#778899';}
         elseif($color === 'slategray') {$color_array[$key] = '#708090';}
         elseif($color === 'slategray1') {$color_array[$key] = '#c6e2ff';}
         elseif($color === 'slategray2') {$color_array[$key] = '#b9d3ee';}
         elseif($color === 'slategray3') {$color_array[$key] = '#9fb6cd';}
         elseif($color === 'slategray4') {$color_array[$key] = '#6c7b8b';}
         elseif($color === 'slategrey') {$color_array[$key] = '#708090';}
         elseif($color === 'gray') {$color_array[$key] = '#bebebe';}
         elseif($color === 'gray0') {$color_array[$key] = '#000000';}
         elseif($color === 'gray1') {$color_array[$key] = '#030303';}
         elseif($color === 'gray10') {$color_array[$key] = '#1a1a1a';}
         elseif($color === 'gray100') {$color_array[$key] = '#ffffff';}
         elseif($color === 'gray11') {$color_array[$key] = '#1c1c1c';}
         elseif($color === 'gray12') {$color_array[$key] = '#1f1f1f';}
         elseif($color === 'gray13') {$color_array[$key] = '#212121';}
         elseif($color === 'gray14') {$color_array[$key] = '#242424';}
         elseif($color === 'gray15') {$color_array[$key] = '#262626';}
         elseif($color === 'gray16') {$color_array[$key] = '#292929';}
         elseif($color === 'gray17') {$color_array[$key] = '#2b2b2b';}
         elseif($color === 'gray18') {$color_array[$key] = '#2e2e2e';}
         elseif($color === 'gray19') {$color_array[$key] = '#303030';}
         elseif($color === 'gray2') {$color_array[$key] = '#050505';}
         elseif($color === 'gray20') {$color_array[$key] = '#333333';}
         elseif($color === 'gray21') {$color_array[$key] = '#363636';}
         elseif($color === 'gray22') {$color_array[$key] = '#383838';}
         elseif($color === 'gray23') {$color_array[$key] = '#3b3b3b';}
         elseif($color === 'gray24') {$color_array[$key] = '#3d3d3d';}
         elseif($color === 'gray25') {$color_array[$key] = '#404040';}
         elseif($color === 'gray26') {$color_array[$key] = '#424242';}
         elseif($color === 'gray27') {$color_array[$key] = '#454545';}
         elseif($color === 'gray28') {$color_array[$key] = '#474747';}
         elseif($color === 'gray29') {$color_array[$key] = '#4a4a4a';}
         elseif($color === 'gray3') {$color_array[$key] = '#080808';}
         elseif($color === 'gray30') {$color_array[$key] = '#4d4d4d';}
         elseif($color === 'gray31') {$color_array[$key] = '#4f4f4f';}
         elseif($color === 'gray32') {$color_array[$key] = '#525252';}
         elseif($color === 'gray33') {$color_array[$key] = '#545454';}
         elseif($color === 'gray34') {$color_array[$key] = '#575757';}
         elseif($color === 'gray35') {$color_array[$key] = '#595959';}
         elseif($color === 'gray36') {$color_array[$key] = '#5c5c5c';}
         elseif($color === 'gray37') {$color_array[$key] = '#5e5e5e';}
         elseif($color === 'gray38') {$color_array[$key] = '#616161';}
         elseif($color === 'gray39') {$color_array[$key] = '#636363';}
         elseif($color === 'gray4') {$color_array[$key] = '#0a0a0a';}
         elseif($color === 'gray40') {$color_array[$key] = '#666666';}
         elseif($color === 'gray41') {$color_array[$key] = '#696969';}
         elseif($color === 'gray42') {$color_array[$key] = '#6b6b6b';}
         elseif($color === 'gray43') {$color_array[$key] = '#6e6e6e';}
         elseif($color === 'gray44') {$color_array[$key] = '#707070';}
         elseif($color === 'gray45') {$color_array[$key] = '#737373';}
         elseif($color === 'gray46') {$color_array[$key] = '#757575';}
         elseif($color === 'gray47') {$color_array[$key] = '#787878';}
         elseif($color === 'gray48') {$color_array[$key] = '#7a7a7a';}
         elseif($color === 'gray49') {$color_array[$key] = '#7d7d7d';}
         elseif($color === 'gray5') {$color_array[$key] = '#0d0d0d';}
         elseif($color === 'gray50') {$color_array[$key] = '#7f7f7f';}
         elseif($color === 'gray51') {$color_array[$key] = '#828282';}
         elseif($color === 'gray52') {$color_array[$key] = '#858585';}
         elseif($color === 'gray53') {$color_array[$key] = '#878787';}
         elseif($color === 'gray54') {$color_array[$key] = '#8a8a8a';}
         elseif($color === 'gray55') {$color_array[$key] = '#8c8c8c';}
         elseif($color === 'gray56') {$color_array[$key] = '#8f8f8f';}
         elseif($color === 'gray57') {$color_array[$key] = '#919191';}
         elseif($color === 'gray58') {$color_array[$key] = '#949494';}
         elseif($color === 'gray59') {$color_array[$key] = '#969696';}
         elseif($color === 'gray6') {$color_array[$key] = '#0f0f0f';}
         elseif($color === 'gray60') {$color_array[$key] = '#999999';}
         elseif($color === 'gray61') {$color_array[$key] = '#9c9c9c';}
         elseif($color === 'gray62') {$color_array[$key] = '#9e9e9e';}
         elseif($color === 'gray63') {$color_array[$key] = '#a1a1a1';}
         elseif($color === 'gray64') {$color_array[$key] = '#a3a3a3';}
         elseif($color === 'gray65') {$color_array[$key] = '#a6a6a6';}
         elseif($color === 'gray66') {$color_array[$key] = '#a8a8a8';}
         elseif($color === 'gray67') {$color_array[$key] = '#ababab';}
         elseif($color === 'gray68') {$color_array[$key] = '#adadad';}
         elseif($color === 'gray69') {$color_array[$key] = '#b0b0b0';}
         elseif($color === 'gray7') {$color_array[$key] = '#121212';}
         elseif($color === 'gray70') {$color_array[$key] = '#b3b3b3';}
         elseif($color === 'gray71') {$color_array[$key] = '#b5b5b5';}
         elseif($color === 'gray72') {$color_array[$key] = '#b8b8b8';}
         elseif($color === 'gray73') {$color_array[$key] = '#bababa';}
         elseif($color === 'gray74') {$color_array[$key] = '#bdbdbd';}
         elseif($color === 'gray75') {$color_array[$key] = '#bfbfbf';}
         elseif($color === 'gray76') {$color_array[$key] = '#c2c2c2';}
         elseif($color === 'gray77') {$color_array[$key] = '#c4c4c4';}
         elseif($color === 'gray78') {$color_array[$key] = '#c7c7c7';}
         elseif($color === 'gray79') {$color_array[$key] = '#c9c9c9';}
         elseif($color === 'gray8') {$color_array[$key] = '#141414';}
         elseif($color === 'gray80') {$color_array[$key] = '#cccccc';}
         elseif($color === 'gray81') {$color_array[$key] = '#cfcfcf';}
         elseif($color === 'gray82') {$color_array[$key] = '#d1d1d1';}
         elseif($color === 'gray83') {$color_array[$key] = '#d4d4d4';}
         elseif($color === 'gray84') {$color_array[$key] = '#d6d6d6';}
         elseif($color === 'gray85') {$color_array[$key] = '#d9d9d9';}
         elseif($color === 'gray86') {$color_array[$key] = '#dbdbdb';}
         elseif($color === 'gray87') {$color_array[$key] = '#dedede';}
         elseif($color === 'gray88') {$color_array[$key] = '#e0e0e0';}
         elseif($color === 'gray89') {$color_array[$key] = '#e3e3e3';}
         elseif($color === 'gray9') {$color_array[$key] = '#171717';}
         elseif($color === 'gray90') {$color_array[$key] = '#e5e5e5';}
         elseif($color === 'gray91') {$color_array[$key] = '#e8e8e8';}
         elseif($color === 'gray92') {$color_array[$key] = '#ebebeb';}
         elseif($color === 'gray93') {$color_array[$key] = '#ededed';}
         elseif($color === 'gray94') {$color_array[$key] = '#f0f0f0';}
         elseif($color === 'gray95') {$color_array[$key] = '#f2f2f2';}
         elseif($color === 'gray96') {$color_array[$key] = '#f5f5f5';}
         elseif($color === 'gray97') {$color_array[$key] = '#f7f7f7';}
         elseif($color === 'gray98') {$color_array[$key] = '#fafafa';}
         elseif($color === 'gray99') {$color_array[$key] = '#fcfcfc';}
         elseif($color === 'grey') {$color_array[$key] = '#bebebe';}
         elseif($color === 'grey0') {$color_array[$key] = '#000000';}
         elseif($color === 'grey1') {$color_array[$key] = '#030303';}
         elseif($color === 'grey10') {$color_array[$key] = '#1a1a1a';}
         elseif($color === 'grey100') {$color_array[$key] = '#ffffff';}
         elseif($color === 'grey11') {$color_array[$key] = '#1c1c1c';}
         elseif($color === 'grey12') {$color_array[$key] = '#1f1f1f';}
         elseif($color === 'grey13') {$color_array[$key] = '#212121';}
         elseif($color === 'grey14') {$color_array[$key] = '#242424';}
         elseif($color === 'grey15') {$color_array[$key] = '#262626';}
         elseif($color === 'grey16') {$color_array[$key] = '#292929';}
         elseif($color === 'grey17') {$color_array[$key] = '#2b2b2b';}
         elseif($color === 'grey18') {$color_array[$key] = '#2e2e2e';}
         elseif($color === 'grey19') {$color_array[$key] = '#303030';}
         elseif($color === 'grey2') {$color_array[$key] = '#050505';}
         elseif($color === 'grey20') {$color_array[$key] = '#333333';}
         elseif($color === 'grey21') {$color_array[$key] = '#363636';}
         elseif($color === 'grey22') {$color_array[$key] = '#383838';}
         elseif($color === 'grey23') {$color_array[$key] = '#3b3b3b';}
         elseif($color === 'grey24') {$color_array[$key] = '#3d3d3d';}
         elseif($color === 'grey25') {$color_array[$key] = '#404040';}
         elseif($color === 'grey26') {$color_array[$key] = '#424242';}
         elseif($color === 'grey27') {$color_array[$key] = '#454545';}
         elseif($color === 'grey28') {$color_array[$key] = '#474747';}
         elseif($color === 'grey29') {$color_array[$key] = '#4a4a4a';}
         elseif($color === 'grey3') {$color_array[$key] = '#080808';}
         elseif($color === 'grey30') {$color_array[$key] = '#4d4d4d';}
         elseif($color === 'grey31') {$color_array[$key] = '#4f4f4f';}
         elseif($color === 'grey32') {$color_array[$key] = '#525252';}
         elseif($color === 'grey33') {$color_array[$key] = '#545454';}
         elseif($color === 'grey34') {$color_array[$key] = '#575757';}
         elseif($color === 'grey35') {$color_array[$key] = '#595959';}
         elseif($color === 'grey36') {$color_array[$key] = '#5c5c5c';}
         elseif($color === 'grey37') {$color_array[$key] = '#5e5e5e';}
         elseif($color === 'grey38') {$color_array[$key] = '#616161';}
         elseif($color === 'grey39') {$color_array[$key] = '#636363';}
         elseif($color === 'grey4') {$color_array[$key] = '#0a0a0a';}
         elseif($color === 'grey40') {$color_array[$key] = '#666666';}
         elseif($color === 'grey41') {$color_array[$key] = '#696969';}
         elseif($color === 'grey42') {$color_array[$key] = '#6b6b6b';}
         elseif($color === 'grey43') {$color_array[$key] = '#6e6e6e';}
         elseif($color === 'grey44') {$color_array[$key] = '#707070';}
         elseif($color === 'grey45') {$color_array[$key] = '#737373';}
         elseif($color === 'grey46') {$color_array[$key] = '#757575';}
         elseif($color === 'grey47') {$color_array[$key] = '#787878';}
         elseif($color === 'grey48') {$color_array[$key] = '#7a7a7a';}
         elseif($color === 'grey49') {$color_array[$key] = '#7d7d7d';}
         elseif($color === 'grey5') {$color_array[$key] = '#0d0d0d';}
         elseif($color === 'grey50') {$color_array[$key] = '#7f7f7f';}
         elseif($color === 'grey51') {$color_array[$key] = '#828282';}
         elseif($color === 'grey52') {$color_array[$key] = '#858585';}
         elseif($color === 'grey53') {$color_array[$key] = '#878787';}
         elseif($color === 'grey54') {$color_array[$key] = '#8a8a8a';}
         elseif($color === 'grey55') {$color_array[$key] = '#8c8c8c';}
         elseif($color === 'grey56') {$color_array[$key] = '#8f8f8f';}
         elseif($color === 'grey57') {$color_array[$key] = '#919191';}
         elseif($color === 'grey58') {$color_array[$key] = '#949494';}
         elseif($color === 'grey59') {$color_array[$key] = '#969696';}
         elseif($color === 'grey6') {$color_array[$key] = '#0f0f0f';}
         elseif($color === 'grey60') {$color_array[$key] = '#999999';}
         elseif($color === 'grey61') {$color_array[$key] = '#9c9c9c';}
         elseif($color === 'grey62') {$color_array[$key] = '#9e9e9e';}
         elseif($color === 'grey63') {$color_array[$key] = '#a1a1a1';}
         elseif($color === 'grey64') {$color_array[$key] = '#a3a3a3';}
         elseif($color === 'grey65') {$color_array[$key] = '#a6a6a6';}
         elseif($color === 'grey66') {$color_array[$key] = '#a8a8a8';}
         elseif($color === 'grey67') {$color_array[$key] = '#ababab';}
         elseif($color === 'grey68') {$color_array[$key] = '#adadad';}
         elseif($color === 'grey69') {$color_array[$key] = '#b0b0b0';}
         elseif($color === 'grey7') {$color_array[$key] = '#121212';}
         elseif($color === 'grey70') {$color_array[$key] = '#b3b3b3';}
         elseif($color === 'grey71') {$color_array[$key] = '#b5b5b5';}
         elseif($color === 'grey72') {$color_array[$key] = '#b8b8b8';}
         elseif($color === 'grey73') {$color_array[$key] = '#bababa';}
         elseif($color === 'grey74') {$color_array[$key] = '#bdbdbd';}
         elseif($color === 'grey75') {$color_array[$key] = '#bfbfbf';}
         elseif($color === 'grey76') {$color_array[$key] = '#c2c2c2';}
         elseif($color === 'grey77') {$color_array[$key] = '#c4c4c4';}
         elseif($color === 'grey78') {$color_array[$key] = '#c7c7c7';}
         elseif($color === 'grey79') {$color_array[$key] = '#c9c9c9';}
         elseif($color === 'grey8') {$color_array[$key] = '#141414';}
         elseif($color === 'grey80') {$color_array[$key] = '#cccccc';}
         elseif($color === 'grey81') {$color_array[$key] = '#cfcfcf';}
         elseif($color === 'grey82') {$color_array[$key] = '#d1d1d1';}
         elseif($color === 'grey83') {$color_array[$key] = '#d4d4d4';}
         elseif($color === 'grey84') {$color_array[$key] = '#d6d6d6';}
         elseif($color === 'grey85') {$color_array[$key] = '#d9d9d9';}
         elseif($color === 'grey86') {$color_array[$key] = '#dbdbdb';}
         elseif($color === 'grey87') {$color_array[$key] = '#dedede';}
         elseif($color === 'grey88') {$color_array[$key] = '#e0e0e0';}
         elseif($color === 'grey89') {$color_array[$key] = '#e3e3e3';}
         elseif($color === 'grey9') {$color_array[$key] = '#171717';}
         elseif($color === 'grey90') {$color_array[$key] = '#e5e5e5';}
         elseif($color === 'grey91') {$color_array[$key] = '#e8e8e8';}
         elseif($color === 'grey92') {$color_array[$key] = '#ebebeb';}
         elseif($color === 'grey93') {$color_array[$key] = '#ededed';}
         elseif($color === 'grey94') {$color_array[$key] = '#f0f0f0';}
         elseif($color === 'grey95') {$color_array[$key] = '#f2f2f2';}
         elseif($color === 'grey96') {$color_array[$key] = '#f5f5f5';}
         elseif($color === 'grey97') {$color_array[$key] = '#f7f7f7';}
         elseif($color === 'grey98') {$color_array[$key] = '#fafafa';}
         elseif($color === 'grey99') {$color_array[$key] = '#fcfcfc';}
         elseif($color === 'darkgreen') {$color_array[$key] = '#006400';}
         elseif($color === 'darkkhaki') {$color_array[$key] = '#bdb76b';}
         elseif($color === 'darkolivegreen') {$color_array[$key] = '#556b2f';}
         elseif($color === 'darkolivegreen1') {$color_array[$key] = '#caff70';}
         elseif($color === 'darkolivegreen2') {$color_array[$key] = '#bcee68';}
         elseif($color === 'darkolivegreen3') {$color_array[$key] = '#a2cd5a';}
         elseif($color === 'darkolivegreen4') {$color_array[$key] = '#6e8b3d';}
         elseif($color === 'darkseagreen') {$color_array[$key] = '#8fbc8f';}
         elseif($color === 'darkseagreen1') {$color_array[$key] = '#c1ffc1';}
         elseif($color === 'darkseagreen2') {$color_array[$key] = '#b4eeb4';}
         elseif($color === 'darkseagreen3') {$color_array[$key] = '#9bcd9b';}
         elseif($color === 'darkseagreen4') {$color_array[$key] = '#698b69';}
         elseif($color === 'forestgreen') {$color_array[$key] = '#228b22';}
         elseif($color === 'greenyellow') {$color_array[$key] = '#adff2f';}
         elseif($color === 'lawngreen') {$color_array[$key] = '#7cfc00';}
         elseif($color === 'lightseagreen') {$color_array[$key] = '#20b2aa';}
         elseif($color === 'limegreen') {$color_array[$key] = '#32cd32';}
         elseif($color === 'mediumseagreen') {$color_array[$key] = '#3cb371';}
         elseif($color === 'mediumspringgreen') {$color_array[$key] = '#00fa9a';}
         elseif($color === 'mintcream') {$color_array[$key] = '#f5fffa';}
         elseif($color === 'olivedrab') {$color_array[$key] = '#6b8e23';}
         elseif($color === 'olivedrab1') {$color_array[$key] = '#c0ff3e';}
         elseif($color === 'olivedrab2') {$color_array[$key] = '#b3ee3a';}
         elseif($color === 'olivedrab3') {$color_array[$key] = '#9acd32';}
         elseif($color === 'olivedrab4') {$color_array[$key] = '#698b22';}
         elseif($color === 'palegreen') {$color_array[$key] = '#98fb98';}
         elseif($color === 'palegreen1') {$color_array[$key] = '#9aff9a';}
         elseif($color === 'palegreen2') {$color_array[$key] = '#90ee90';}
         elseif($color === 'palegreen3') {$color_array[$key] = '#7ccd7c';}
         elseif($color === 'palegreen4') {$color_array[$key] = '#548b54';}
         elseif($color === 'seagreen') {$color_array[$key] = '#2e8b57';}
         elseif($color === 'seagreen1') {$color_array[$key] = '#54ff9f';}
         elseif($color === 'seagreen2') {$color_array[$key] = '#4eee94';}
         elseif($color === 'seagreen3') {$color_array[$key] = '#43cd80';}
         elseif($color === 'seagreen4') {$color_array[$key] = '#2e8b57';}
         elseif($color === 'springgreen') {$color_array[$key] = '#00ff7f';}
         elseif($color === 'springgreen1') {$color_array[$key] = '#00ff7f';}
         elseif($color === 'springgreen2') {$color_array[$key] = '#00ee76';}
         elseif($color === 'springgreen3') {$color_array[$key] = '#00cd66';}
         elseif($color === 'springgreen4') {$color_array[$key] = '#008b45';}
         elseif($color === 'yellowgreen') {$color_array[$key] = '#9acd32';}
         elseif($color === 'chartreuse') {$color_array[$key] = '#7fff00';}
         elseif($color === 'chartreuse1') {$color_array[$key] = '#7fff00';}
         elseif($color === 'chartreuse2') {$color_array[$key] = '#76ee00';}
         elseif($color === 'chartreuse3') {$color_array[$key] = '#66cd00';}
         elseif($color === 'chartreuse4') {$color_array[$key] = '#458b00';}
         elseif($color === 'green') {$color_array[$key] = '#00ff00';}
         elseif($color === 'green1') {$color_array[$key] = '#00ff00';}
         elseif($color === 'green2') {$color_array[$key] = '#00ee00';}
         elseif($color === 'green3') {$color_array[$key] = '#00cd00';}
         elseif($color === 'green4') {$color_array[$key] = '#008b00';}
         elseif($color === 'khaki') {$color_array[$key] = '#f0e68c';}
         elseif($color === 'khaki1') {$color_array[$key] = '#fff68f';}
         elseif($color === 'khaki2') {$color_array[$key] = '#eee685';}
         elseif($color === 'khaki3') {$color_array[$key] = '#cdc673';}
         elseif($color === 'khaki4') {$color_array[$key] = '#8b864e';}
         elseif($color === 'darkorange') {$color_array[$key] = '#ff8c00';}
         elseif($color === 'darkorange1') {$color_array[$key] = '#ff7f00';}
         elseif($color === 'darkorange2') {$color_array[$key] = '#ee7600';}
         elseif($color === 'darkorange3') {$color_array[$key] = '#cd6600';}
         elseif($color === 'darkorange4') {$color_array[$key] = '#8b4500';}
         elseif($color === 'darksalmon') {$color_array[$key] = '#e9967a';}
         elseif($color === 'lightcoral') {$color_array[$key] = '#f08080';}
         elseif($color === 'lightsalmon') {$color_array[$key] = '#ffa07a';}
         elseif($color === 'lightsalmon1') {$color_array[$key] = '#ffa07a';}
         elseif($color === 'lightsalmon2') {$color_array[$key] = '#ee9572';}
         elseif($color === 'lightsalmon3') {$color_array[$key] = '#cd8162';}
         elseif($color === 'lightsalmon4') {$color_array[$key] = '#8b5742';}
         elseif($color === 'peachpuff') {$color_array[$key] = '#ffdab9';}
         elseif($color === 'peachpuff1') {$color_array[$key] = '#ffdab9';}
         elseif($color === 'peachpuff2') {$color_array[$key] = '#eecbad';}
         elseif($color === 'peachpuff3') {$color_array[$key] = '#cdaf95';}
         elseif($color === 'peachpuff4') {$color_array[$key] = '#8b7765';}
         elseif($color === 'bisque') {$color_array[$key] = '#ffe4c4';}
         elseif($color === 'bisque1') {$color_array[$key] = '#ffe4c4';}
         elseif($color === 'bisque2') {$color_array[$key] = '#eed5b7';}
         elseif($color === 'bisque3') {$color_array[$key] = '#cdb79e';}
         elseif($color === 'bisque4') {$color_array[$key] = '#8b7d6b';}
         elseif($color === 'coral') {$color_array[$key] = '#ff7f50';}
         elseif($color === 'coral1') {$color_array[$key] = '#ff7256';}
         elseif($color === 'coral2') {$color_array[$key] = '#ee6a50';}
         elseif($color === 'coral3') {$color_array[$key] = '#cd5b45';}
         elseif($color === 'coral4') {$color_array[$key] = '#8b3e2f';}
         elseif($color === 'honeydew') {$color_array[$key] = '#f0fff0';}
         elseif($color === 'honeydew1') {$color_array[$key] = '#f0fff0';}
         elseif($color === 'honeydew2') {$color_array[$key] = '#e0eee0';}
         elseif($color === 'honeydew3') {$color_array[$key] = '#c1cdc1';}
         elseif($color === 'honeydew4') {$color_array[$key] = '#838b83';}
         elseif($color === 'orange') {$color_array[$key] = '#ffa500';}
         elseif($color === 'orange1') {$color_array[$key] = '#ffa500';}
         elseif($color === 'orange2') {$color_array[$key] = '#ee9a00';}
         elseif($color === 'orange3') {$color_array[$key] = '#cd8500';}
         elseif($color === 'orange4') {$color_array[$key] = '#8b5a00';}
         elseif($color === 'salmon') {$color_array[$key] = '#fa8072';}
         elseif($color === 'salmon1') {$color_array[$key] = '#ff8c69';}
         elseif($color === 'salmon2') {$color_array[$key] = '#ee8262';}
         elseif($color === 'salmon3') {$color_array[$key] = '#cd7054';}
         elseif($color === 'salmon4') {$color_array[$key] = '#8b4c39';}
         elseif($color === 'sienna') {$color_array[$key] = '#a0522d';}
         elseif($color === 'sienna1') {$color_array[$key] = '#ff8247';}
         elseif($color === 'sienna2') {$color_array[$key] = '#ee7942';}
         elseif($color === 'sienna3') {$color_array[$key] = '#cd6839';}
         elseif($color === 'sienna4') {$color_array[$key] = '#8b4726';}
         elseif($color === 'deeppink') {$color_array[$key] = '#ff1493';}
         elseif($color === 'deeppink1') {$color_array[$key] = '#ff1493';}
         elseif($color === 'deeppink2') {$color_array[$key] = '#ee1289';}
         elseif($color === 'deeppink3') {$color_array[$key] = '#cd1076';}
         elseif($color === 'deeppink4') {$color_array[$key] = '#8b0a50';}
         elseif($color === 'hotpink') {$color_array[$key] = '#ff69b4';}
         elseif($color === 'hotpink1') {$color_array[$key] = '#ff6eb4';}
         elseif($color === 'hotpink2') {$color_array[$key] = '#ee6aa7';}
         elseif($color === 'hotpink3') {$color_array[$key] = '#cd6090';}
         elseif($color === 'hotpink4') {$color_array[$key] = '#8b3a62';}
         elseif($color === 'indianred') {$color_array[$key] = '#cd5c5c';}
         elseif($color === 'indianred1') {$color_array[$key] = '#ff6a6a';}
         elseif($color === 'indianred2') {$color_array[$key] = '#ee6363';}
         elseif($color === 'indianred3') {$color_array[$key] = '#cd5555';}
         elseif($color === 'indianred4') {$color_array[$key] = '#8b3a3a';}
         elseif($color === 'lightpink') {$color_array[$key] = '#ffb6c1';}
         elseif($color === 'lightpink1') {$color_array[$key] = '#ffaeb9';}
         elseif($color === 'lightpink2') {$color_array[$key] = '#eea2ad';}
         elseif($color === 'lightpink3') {$color_array[$key] = '#cd8c95';}
         elseif($color === 'lightpink4') {$color_array[$key] = '#8b5f65';}
         elseif($color === 'mediumvioletred') {$color_array[$key] = '#c71585';}
         elseif($color === 'mistyrose') {$color_array[$key] = '#ffe4e1';}
         elseif($color === 'mistyrose1') {$color_array[$key] = '#ffe4e1';}
         elseif($color === 'mistyrose2') {$color_array[$key] = '#eed5d2';}
         elseif($color === 'mistyrose3') {$color_array[$key] = '#cdb7b5';}
         elseif($color === 'mistyrose4') {$color_array[$key] = '#8b7d7b';}
         elseif($color === 'orangered') {$color_array[$key] = '#ff4500';}
         elseif($color === 'orangered1') {$color_array[$key] = '#ff4500';}
         elseif($color === 'orangered2') {$color_array[$key] = '#ee4000';}
         elseif($color === 'orangered3') {$color_array[$key] = '#cd3700';}
         elseif($color === 'orangered4') {$color_array[$key] = '#8b2500';}
         elseif($color === 'palevioletred') {$color_array[$key] = '#db7093';}
         elseif($color === 'palevioletred1') {$color_array[$key] = '#ff82ab';}
         elseif($color === 'palevioletred2') {$color_array[$key] = '#ee799f';}
         elseif($color === 'palevioletred3') {$color_array[$key] = '#cd6889';}
         elseif($color === 'palevioletred4') {$color_array[$key] = '#8b475d';}
         elseif($color === 'violetred') {$color_array[$key] = '#d02090';}
         elseif($color === 'violetred1') {$color_array[$key] = '#ff3e96';}
         elseif($color === 'violetred2') {$color_array[$key] = '#ee3a8c';}
         elseif($color === 'violetred3') {$color_array[$key] = '#cd3278';}
         elseif($color === 'violetred4') {$color_array[$key] = '#8b2252';}
         elseif($color === 'firebrick') {$color_array[$key] = '#b22222';}
         elseif($color === 'firebrick1') {$color_array[$key] = '#ff3030';}
         elseif($color === 'firebrick2') {$color_array[$key] = '#ee2c2c';}
         elseif($color === 'firebrick3') {$color_array[$key] = '#cd2626';}
         elseif($color === 'firebrick4') {$color_array[$key] = '#8b1a1a';}
         elseif($color === 'pink') {$color_array[$key] = '#ffc0cb';}
         elseif($color === 'pink1') {$color_array[$key] = '#ffb5c5';}
         elseif($color === 'pink2') {$color_array[$key] = '#eea9b8';}
         elseif($color === 'pink3') {$color_array[$key] = '#cd919e';}
         elseif($color === 'pink4') {$color_array[$key] = '#8b636c';}
         elseif($color === 'red') {$color_array[$key] = '#ff0000';}
         elseif($color === 'red1') {$color_array[$key] = '#ff0000';}
         elseif($color === 'red2') {$color_array[$key] = '#ee0000';}
         elseif($color === 'red3') {$color_array[$key] = '#cd0000';}
         elseif($color === 'red4') {$color_array[$key] = '#8b0000';}
         elseif($color === 'tomato') {$color_array[$key] = '#ff6347';}
         elseif($color === 'tomato1') {$color_array[$key] = '#ff6347';}
         elseif($color === 'tomato2') {$color_array[$key] = '#ee5c42';}
         elseif($color === 'tomato3') {$color_array[$key] = '#cd4f39';}
         elseif($color === 'tomato4') {$color_array[$key] = '#8b3626';}
         elseif($color === 'darkorchid') {$color_array[$key] = '#9932cc';}
         elseif($color === 'darkorchid1') {$color_array[$key] = '#bf3eff';}
         elseif($color === 'darkorchid2') {$color_array[$key] = '#b23aee';}
         elseif($color === 'darkorchid3') {$color_array[$key] = '#9a32cd';}
         elseif($color === 'darkorchid4') {$color_array[$key] = '#68228b';}
         elseif($color === 'darkviolet') {$color_array[$key] = '#9400d3';}
         elseif($color === 'lavenderblush') {$color_array[$key] = '#fff0f5';}
         elseif($color === 'lavenderblush1') {$color_array[$key] = '#fff0f5';}
         elseif($color === 'lavenderblush2') {$color_array[$key] = '#eee0e5';}
         elseif($color === 'lavenderblush3') {$color_array[$key] = '#cdc1c5';}
         elseif($color === 'lavenderblush4') {$color_array[$key] = '#8b8386';}
         elseif($color === 'mediumorchid') {$color_array[$key] = '#ba55d3';}
         elseif($color === 'mediumorchid1') {$color_array[$key] = '#e066ff';}
         elseif($color === 'mediumorchid2') {$color_array[$key] = '#d15fee';}
         elseif($color === 'mediumorchid3') {$color_array[$key] = '#b452cd';}
         elseif($color === 'mediumorchid4') {$color_array[$key] = '#7a378b';}
         elseif($color === 'mediumpurple') {$color_array[$key] = '#9370db';}
         elseif($color === 'mediumpurple1') {$color_array[$key] = '#ab82ff';}
         elseif($color === 'mediumpurple2') {$color_array[$key] = '#9f79ee';}
         elseif($color === 'mediumpurple3') {$color_array[$key] = '#8968cd';}
         elseif($color === 'mediumpurple4') {$color_array[$key] = '#5d478b';}
         elseif($color === 'lavender') {$color_array[$key] = '#e6e6fa';}
         elseif($color === 'magenta') {$color_array[$key] = '#ff00ff';}
         elseif($color === 'magenta1') {$color_array[$key] = '#ff00ff';}
         elseif($color === 'magenta2') {$color_array[$key] = '#ee00ee';}
         elseif($color === 'magenta3') {$color_array[$key] = '#cd00cd';}
         elseif($color === 'magenta4') {$color_array[$key] = '#8b008b';}
         elseif($color === 'maroon') {$color_array[$key] = '#b03060';}
         elseif($color === 'maroon1') {$color_array[$key] = '#ff34b3';}
         elseif($color === 'maroon2') {$color_array[$key] = '#ee30a7';}
         elseif($color === 'maroon3') {$color_array[$key] = '#cd2990';}
         elseif($color === 'maroon4') {$color_array[$key] = '#8b1c62';}
         elseif($color === 'orchid') {$color_array[$key] = '#da70d6';}
         elseif($color === 'orchid1') {$color_array[$key] = '#ff83fa';}
         elseif($color === 'orchid2') {$color_array[$key] = '#ee7ae9';}
         elseif($color === 'orchid3') {$color_array[$key] = '#cd69c9';}
         elseif($color === 'orchid4') {$color_array[$key] = '#8b4789';}
         elseif($color === 'plum') {$color_array[$key] = '#dda0dd';}
         elseif($color === 'plum1') {$color_array[$key] = '#ffbbff';}
         elseif($color === 'plum2') {$color_array[$key] = '#eeaeee';}
         elseif($color === 'plum3') {$color_array[$key] = '#cd96cd';}
         elseif($color === 'plum4') {$color_array[$key] = '#8b668b';}
         elseif($color === 'purple') {$color_array[$key] = '#a020f0';}
         elseif($color === 'purple1') {$color_array[$key] = '#9b30ff';}
         elseif($color === 'purple2') {$color_array[$key] = '#912cee';}
         elseif($color === 'purple3') {$color_array[$key] = '#7d26cd';}
         elseif($color === 'purple4') {$color_array[$key] = '#551a8b';}
         elseif($color === 'thistle') {$color_array[$key] = '#d8bfd8';}
         elseif($color === 'thistle1') {$color_array[$key] = '#ffe1ff';}
         elseif($color === 'thistle2') {$color_array[$key] = '#eed2ee';}
         elseif($color === 'thistle3') {$color_array[$key] = '#cdb5cd';}
         elseif($color === 'thistle4') {$color_array[$key] = '#8b7b8b';}
         elseif($color === 'violet') {$color_array[$key] = '#ee82ee';}
         elseif($color === 'antiquewhite') {$color_array[$key] = '#faebd7';}
         elseif($color === 'antiquewhite1') {$color_array[$key] = '#ffefdb';}
         elseif($color === 'antiquewhite2') {$color_array[$key] = '#eedfcc';}
         elseif($color === 'antiquewhite3') {$color_array[$key] = '#cdc0b0';}
         elseif($color === 'antiquewhite4') {$color_array[$key] = '#8b8378';}
         elseif($color === 'floralwhite') {$color_array[$key] = '#fffaf0';}
         elseif($color === 'ghostwhite') {$color_array[$key] = '#f8f8ff';}
         elseif($color === 'navajowhite') {$color_array[$key] = '#ffdead';}
         elseif($color === 'navajowhite1') {$color_array[$key] = '#ffdead';}
         elseif($color === 'navajowhite2') {$color_array[$key] = '#eecfa1';}
         elseif($color === 'navajowhite3') {$color_array[$key] = '#cdb38b';}
         elseif($color === 'navajowhite4') {$color_array[$key] = '#8b795e';}
         elseif($color === 'oldlace') {$color_array[$key] = '#fdf5e6';}
         elseif($color === 'whitesmoke') {$color_array[$key] = '#f5f5f5';}
         elseif($color === 'gainsboro') {$color_array[$key] = '#dcdcdc';}
         elseif($color === 'ivory') {$color_array[$key] = '#fffff0';}
         elseif($color === 'ivory1') {$color_array[$key] = '#fffff0';}
         elseif($color === 'ivory2') {$color_array[$key] = '#eeeee0';}
         elseif($color === 'ivory3') {$color_array[$key] = '#cdcdc1';}
         elseif($color === 'ivory4') {$color_array[$key] = '#8b8b83';}
         elseif($color === 'linen') {$color_array[$key] = '#faf0e6';}
         elseif($color === 'seashell') {$color_array[$key] = '#fff5ee';}
         elseif($color === 'seashell1') {$color_array[$key] = '#fff5ee';}
         elseif($color === 'seashell2') {$color_array[$key] = '#eee5de';}
         elseif($color === 'seashell3') {$color_array[$key] = '#cdc5bf';}
         elseif($color === 'seashell4') {$color_array[$key] = '#8b8682';}
         elseif($color === 'snow') {$color_array[$key] = '#fffafa';}
         elseif($color === 'snow1') {$color_array[$key] = '#fffafa';}
         elseif($color === 'snow2') {$color_array[$key] = '#eee9e9';}
         elseif($color === 'snow3') {$color_array[$key] = '#cdc9c9';}
         elseif($color === 'snow4') {$color_array[$key] = '#8b8989';}
         elseif($color === 'wheat') {$color_array[$key] = '#f5deb3';}
         elseif($color === 'wheat1') {$color_array[$key] = '#ffe7ba';}
         elseif($color === 'wheat2') {$color_array[$key] = '#eed8ae';}
         elseif($color === 'wheat3') {$color_array[$key] = '#cdba96';}
         elseif($color === 'wheat4') {$color_array[$key] = '#8b7e66';}
         elseif($color === 'white') {$color_array[$key] = '#ffffff';}
         elseif($color === 'blanchedalmond') {$color_array[$key] = '#ffebcd';}
         elseif($color === 'darkgoldenrod') {$color_array[$key] = '#b8860b';}
         elseif($color === 'darkgoldenrod1') {$color_array[$key] = '#ffb90f';}
         elseif($color === 'darkgoldenrod2') {$color_array[$key] = '#eead0e';}
         elseif($color === 'darkgoldenrod3') {$color_array[$key] = '#cd950c';}
         elseif($color === 'darkgoldenrod4') {$color_array[$key] = '#8b6508';}
         elseif($color === 'lemonchiffon') {$color_array[$key] = '#fffacd';}
         elseif($color === 'lemonchiffon1') {$color_array[$key] = '#fffacd';}
         elseif($color === 'lemonchiffon2') {$color_array[$key] = '#eee9bf';}
         elseif($color === 'lemonchiffon3') {$color_array[$key] = '#cdc9a5';}
         elseif($color === 'lemonchiffon4') {$color_array[$key] = '#8b8970';}
         elseif($color === 'lightgoldenrod') {$color_array[$key] = '#eedd82';}
         elseif($color === 'lightgoldenrod1') {$color_array[$key] = '#ffec8b';}
         elseif($color === 'lightgoldenrod2') {$color_array[$key] = '#eedc82';}
         elseif($color === 'lightgoldenrod3') {$color_array[$key] = '#cdbe70';}
         elseif($color === 'lightgoldenrod4') {$color_array[$key] = '#8b814c';}
         elseif($color === 'lightgoldenrodyellow') {$color_array[$key] = '#fafad2';}
         elseif($color === 'lightyellow') {$color_array[$key] = '#ffffe0';}
         elseif($color === 'lightyellow1') {$color_array[$key] = '#ffffe0';}
         elseif($color === 'lightyellow2') {$color_array[$key] = '#eeeed1';}
         elseif($color === 'lightyellow3') {$color_array[$key] = '#cdcdb4';}
         elseif($color === 'lightyellow4') {$color_array[$key] = '#8b8b7a';}
         elseif($color === 'palegoldenrod') {$color_array[$key] = '#eee8aa';}
         elseif($color === 'papayawhip') {$color_array[$key] = '#ffefd5';}
         elseif($color === 'cornsilk') {$color_array[$key] = '#fff8dc';}
         elseif($color === 'cornsilk1') {$color_array[$key] = '#fff8dc';}
         elseif($color === 'cornsilk2') {$color_array[$key] = '#eee8cd';}
         elseif($color === 'cornsilk3') {$color_array[$key] = '#cdc8b1';}
         elseif($color === 'cornsilk4') {$color_array[$key] = '#8b8878';}
         elseif($color === 'gold') {$color_array[$key] = '#ffd700';}
         elseif($color === 'gold1') {$color_array[$key] = '#ffd700';}
         elseif($color === 'gold2') {$color_array[$key] = '#eec900';}
         elseif($color === 'gold3') {$color_array[$key] = '#cdad00';}
         elseif($color === 'gold4') {$color_array[$key] = '#8b7500';}
         elseif($color === 'goldenrod') {$color_array[$key] = '#daa520';}
         elseif($color === 'goldenrod1') {$color_array[$key] = '#ffc125';}
         elseif($color === 'goldenrod2') {$color_array[$key] = '#eeb422';}
         elseif($color === 'goldenrod3') {$color_array[$key] = '#cd9b1d';}
         elseif($color === 'goldenrod4') {$color_array[$key] = '#8b6914';}
         elseif($color === 'moccasin') {$color_array[$key] = '#ffe4b5';}
         elseif($color === 'yellow') {$color_array[$key] = '#ffff00';}
         elseif($color === 'yellow1') {$color_array[$key] = '#ffff00';}
         elseif($color === 'yellow2') {$color_array[$key] = '#eeee00';}
         elseif($color === 'yellow3') {$color_array[$key] = '#cdcd00';}
         elseif($color === 'yellow4') {$color_array[$key] = '#8b8b00';}
      }
   }
   return $color_array;
}

function getCurrentCommSyFunctions(){
   global $environment;
   global $c_minimized_js;
   
   $path_norm = 'htdocs/javascript/jQuery/commsy/';
   $path_min = 'htdocs/javascript/jQuery/commsy_min/';
   
   // search normal commsy_functions
   $files_found = array();
   $pattern = '/commsy_functions_(.*?)\.js/';
   if( $dir = opendir($path_norm) ) {
      while( $file = readdir($dir) ) {
         $matches = array();
         preg_match($pattern, $file, $matches);
         if( sizeof($matches) > 1 ) {
            $files_found[] = array(   'path'      =>   $path_norm . $matches[0],
                                      'inc_path'  =>   'commsy/' . $matches[0],
                                      'version'   =>   $matches[1]);
         }
      }
   }
   
   // no files found?
   if( empty($files_found) ) {
      include_once('functions/error_functions.php');
      trigger_error('commsy_functions is missing', E_USER_ERROR);
   }
   
   // multiple files found?
   if( sizeof($files_found) > 1 ) {
      $modification_time = 0;
      $temp_file = '';
      foreach($files_found as $file) {
         $modification_time_temp = filemtime($file['path']);
         if( $modification_time < $modification_time_temp ) {
            $modification_time = $modification_time_temp;
            $temp_file = $file;
         }
      }
      $files_found = array($temp_file);
   }
   
   // create min version if not existing or out of date
   $min_file_path = $path_min . 'commsy_functions_' . $files_found[0]['version'] . '.min.js';
   if(   !file_exists($min_file_path) ||
         filemtime($files_found[0]['path']) >= filemtime($min_file_path) ) {
      include_once ('classes/external_classes/class.JavaScriptPacker.php');
      $unpacked = file_get_contents($files_found[0]['path']);
      $packer = new JavaScriptPacker($unpacked, 62, true, false);
      $packed = $packer->pack();
      unset($packer);
      
      if( !is_dir($path_min) ) {
         mkdir($path_min);
      }
      $file_handle = fopen($min_file_path, 'w');
      fwrite($file_handle, $packed);
      fclose($file_handle);
   }
   
   // check for using min js version
   if(isset($c_minimized_js) && $c_minimized_js === false) {
      // use normal js
      return $files_found[0]['inc_path'];
   } else {
      // use minimized js
      return 'commsy_min/commsy_functions_' . $files_found[0]['version'] . '.min.js';
   }
   
   return $functions_file;
}

function isPHP5 () {
   $retour = false;
   $php_version = phpversion();
   if ( $php_version < 6
        and $php_version >= 5
      ) {
      $retour = true;
   }
   return $retour;
}

function init_progress_bar($count,$title = 'Total entries to be processed',$value = '100%') {
   global $bash;
   echo BRLF.$title.": ".$count.LF;
   echo BRLF."|....................................................................................................|".$value.LF;
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'.LF;
   echo BRLF."|";
   flush();
}

function update_progress_bar($total) {
   static $counter_upb = 0;
   static $percent = 0;
   $counter_upb++;
   $cur_percent = (int)(($counter_upb*100)/($total) );
   if ($percent < $cur_percent) {
      $add = $cur_percent-$percent;
      while ($add>0) {
         $add--;
         echo(".");
      }
      $percent = $cur_percent;
      flush();
   }
   if ($counter_upb==$total) {
      $counter_upb = 0;
      $percent = 0;
      echo('|');
   }
}
?>
