<?php
function logToFile($message){
   global $c_enable_logging_to_file, $c_logging_file;
   if($c_enable_logging_to_file and (isset($c_logging_file) and ($c_logging_file != ''))){
      $logfile = fopen($c_logging_file, "a");
      $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $message;   
      fwrite($logfile, $str . "\n");
      fclose($logfile);
   }
}

function debugToFile($message){
   logToFile('DEBUG --- ' . $message);
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
?>
