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
 * Reads the version string from VERSION file
 *
 * @return  returns a string
 *
 */
function getCommSyVersion()
{
    return file_get_contents('../VERSION');
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
   		
         // download
         $module != 'download' and
         $module != 'limesurvey' and
         $module != 'export_privateroom' and
   		
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
      if ( empty($current_context_item)
           and $environment->inServer()
         ) {
         $current_context_item = $environment->getServerItem();
      }
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

function plugin_hook_output_all ($hook_function, $params = null, $separator = '', $with_config_check = true, $except = array()) {
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
        if(!in_array($plugin, $except)) {
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

   $tabs_background_color = '3B658E';
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
