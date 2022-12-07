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

function el($value) {
   error_log(print_r($value, true), 0);
}

function getmicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}

/** converts the item type into the module name
 *
 * @return   string module name
 */
function type2Module($type){
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
