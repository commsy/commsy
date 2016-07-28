<?PHP
// include first default commsy settings
@include_once('etc/commsy/default.php');

// include then special config files
$config_path = realpath(dirname(__FILE__)) . '/commsy/';
$config_folder = opendir($config_path);
$config_array = array();
if ( $config_folder ) {
   while ( false !== ( $config_entry = readdir($config_folder) ) ) {
      if ( !is_dir($config_path.'/'.$config_entry)
           and is_file($config_path.'/'.$config_entry)
           and !strstr($config_entry,'-dist')
           and $config_entry != 'default.php'
           and substr($config_entry,(int)(strlen($config_entry)-4)) == '.php'
         ) {
         $config_array[] = $config_entry;
      }
   }
} else {
   echo('ERROR: can not open config folder');
   exit();
}
if ( !empty($config_array) ) {
   foreach ( $config_array as $config_file ) {
      include_once($config_path.$config_file);
   }
}
unset($config_file);
unset($config_entry);
unset($config_array);
unset($config_path);
unset($config_folder);
?>