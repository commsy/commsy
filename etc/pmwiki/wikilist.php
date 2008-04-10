<?php
$old_dir = getcwd();
$wiki_array = array();
if ($handle = opendir('.')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." and $file != ".." and is_dir($file) and is_numeric($file) ) {
          chdir($file.'/local');
          if ( file_exists('inc_config.php') ){
             include('inc_config.php');
             $temp_array = array();
             $temp_array['title'] = $WIKI_WIKI_TITLE;
             $temp_array['dir'] = $file;
             $wiki_array[] = $temp_array;
          }elseif ( file_exists('commsy_config.php') ){
             include('commsy_config.php');
             $temp_array = array();
             $temp_array['title'] = $COMMSY_WIKI_TITLE;
             $temp_array['dir'] = $file;
             $wiki_array[] = $temp_array;
          }
          chdir('../..');
        }
    }
    closedir($handle);
}
$wiki_array = sortby($wiki_array);

$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

if (empty($wiki_array)){
   echo('* Bislang sind keine Wikis vorhanden.'."\n");
}else{
   foreach($wiki_array as $wiki){
      $url2 = str_replace('index.php',$wiki['dir'].'/',$url);
      echo('* [=<a href="'.$url2.'">'.$wiki['title'].'</a>=] '."\n");
   }
}

function sortby ($array) {
   // prepare temp array to sort
   if (count($array) > 1) {
      $old_list = $array;
      $temp_array = array();
      for ($i=0; $i<count($old_list); $i++) {
         $temp_array2['position'] = $i;
         $temp_array2['title'] = $old_list[$i]['title'];
         $temp_array[] = $temp_array2;
      }
      // sort temp aray
      usort($temp_array,create_function('$a,$b','return strnatcasecmp($a[\''.'title'.'\'],$b[\''.'title'.'\']);'));

      // create sorted list array
      unset($array);
      $array = array();
      for ($i=0; $i<count($temp_array); $i++) {
          $array[$i] = $old_list[$temp_array[$i]['position']];
      }
   }
   return $array;
}



chdir($old_dir);
?>