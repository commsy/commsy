<?PHP
function XML2Array ($text) {
   $text = str_replace('&', '&amp;', $text);
   $text = str_replace('\'', '&apos;', $text);
   $text = '<SAVE>'.$text.'</SAVE>';
   $p = xml_parser_create();
   xml_parse_into_struct($p,$text,$vals,$index);
   $error = xml_get_error_code($p);
   xml_parser_free($p);
   if ( $error != XML_ERROR_NONE ) {
      $error_text = xml_error_string($error);
      include_once('functions/error_functions.php');
      trigger_error('XML-string is not wellformed: '.$error_text, E_USER_WARNING);
      $result = array();
   } else {
     $result = _convertIntoArray($vals);
     $result = $result['SAVE'];
   }
   return $result;
}

function _convertIntoArray ($vals) {
   if (count($vals) == 0) {
      include_once('functions/error_functions.php');
      trigger_error('XML-string is not wellformed', E_USER_WARNING);
   } else {
      $retour = array();
      $entry = array_shift($vals);
      while ($entry) {
         if ( $entry['type'] == 'complete' ) {
            if (isset($entry['value'])) {
               $retour[$entry['tag']] = $entry['value'];
            } else {
               $retour[$entry['tag']] = '';
            }
         } elseif ( $entry['type'] == 'open' ) {
            $retour[$entry['tag']] = _convertIntoArray($vals);
         } elseif ( $entry['type'] == 'close' ) {
            // Always corresponds to last open-tag.
            // Otherwise the XML-parser stops after open-tag.
            break; // Exit while-loop NOW!
         }
         $entry = array_shift($vals);
      }
   }
   return $retour;
}

/**
 * Generates a xml string out of an array.
 * This function is used at the management of the extras field in the mysql-database
 *
 * @return  returns a string
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
         }
         $xml .= '<'.$key.'>'.$data.'</'.$key.'>'."\n";
      }
   }
   return $xml;
}
?>