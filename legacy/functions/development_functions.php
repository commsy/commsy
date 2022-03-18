<?php
function logToFile($message){
   if(is_array($message)) {
      logArrayToFile($message);
   }
}

function debugToFile($message){
   if(!is_array($message)){
      logToFile('DEBUG --- ' . $message);
   } else {
      debugArrayToFile($message);
   }
}

function logArrayToFile($array, $var_name = 'ARRAY'){
   $keys = array_keys($array);
   foreach($keys as $key){
      $value = $array[$key];
      $name_length = strlen($var_name);
      $temp_name = '';
      for ($index = 0; $index < $name_length; $index++) {
         $temp_name .= ' '; 
      }
      if(is_array($value)){
         logToFile($var_name);
         logArrayToFile($value, $temp_name . ' [\'' . $key . '\']');
      } else {
         logToFile($var_name . ' [\''.$key . '\'] => ' . $value);
      }
   }
}

function debugArrayToFile($array, $var_name = 'ARRAY'){
   $keys = array_keys($array);
   foreach($keys as $key){
      $value = $array[$key];
      $name_length = strlen($var_name);
      $temp_name = '';
      for ($index = 0; $index < $name_length; $index++) {
         $temp_name .= ' '; 
      }
      if(is_array($value)){
         logToFile('DEBUG --- ' . $var_name);
         logArrayToFile($value, $temp_name . ' [\'' . $key . '\']');
      } else {
         logToFile('DEBUG --- ' . $var_name . ' [\''.$key . '\'] => ' . $value);
      }
   }
}
