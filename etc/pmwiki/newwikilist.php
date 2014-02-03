<?php



$old_dir = getcwd();
$wiki_array = array();
if ($handle = opendir('.')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." and $file != ".." and is_dir($file) and is_numeric($file) ) {
          chdir($file.'/local');
          $temp_array = array();
          $temp_array['dir'] = $file;
          #$temp_array['time'] = filemtime($file);
          if ( file_exists('inc_config.php') ){
             $temp_array['time'] = filemtime('inc_config.php');
             include('inc_config.php');
             $temp_array['title'] = $WIKI_WIKI_TITLE;
             $wiki_array[] = $temp_array;
             $wiki_array[] = $temp_array;
          } elseif ( file_exists('commsy_config.php') ) {
             $temp_array['time'] = filemtime('commsy_config.php');
             $commsy_config = file_get_contents('commsy_config.php');
             if ( !empty($commsy_config) ) {
                $treffer = array();
                preg_match('~COMMSY_WIKI_TITLE[ ]*=[ ]*["|\'](.*)["|\']~u',$commsy_config,$treffer);
                if ( !empty($treffer[1]) ) {
                   $temp_array['title'] = stripslashes($treffer[1]);
                   $wiki_array[] = $temp_array;
                }
             }
          }
          unset($temp_array);
          chdir('../..');
        }
    }
    closedir($handle);
}
$wiki_array = sortby($wiki_array);

$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

$count = count($wiki_array);


if (empty($wiki_array)){
   echo('* Bislang sind keine Wikis vorhanden.'."\n");
} elseif($count == 1){
   echo('!!!Das neueste Wiki'."\n");
   for ($i=0; $i < $count; $i++) {
      $url2 = str_replace('index.php',$wiki_array[$i]['dir'].'/',$url);
      echo('* [=<a href="'.$url2.'">'.$wiki_array[$i]['title'].'</a>=] ('.date("d.m.Y, H:i",$wiki_array[$i]['time']).')'."\n");
  }

}elseif($count < 10){
   echo('!!!Die neuesten '.$count.' Wikis'."\n");
   for ($i=0; $i < $count; $i++) {
      $url2 = str_replace('index.php',$wiki_array[$i]['dir'].'/',$url);
      echo('* [=<a href="'.$url2.'">'.$wiki_array[$i]['title'].'</a>=] ('.date("d.m.Y, H:i",$wiki_array[$i]['time']).')'."\n");
  }

}else{
   for ($i=0; $i < 10; $i++){
      if (isset($wiki_array[$i])){
         $url2 = str_replace('index.php',$wiki_array[$i]['dir'].'/',$url);
         echo('* [=<a href="'.$url2.'">'.$wiki_array[$i]['title'].'</a>=] ('.date("d.m.Y, H:i",$wiki_array[$i]['time']).')'."\n");
      }
  }
}

function sortby ($array) {
   // prepare temp array to sort
   if (count($array) > 1) {
      $old_list = $array;
      $temp_array = array();
      for ($i=0; $i<count($old_list); $i++) {
         $temp_array2['position'] = $i;
         $temp_array2['time'] = $old_list[$i]['time'];
         $temp_array[] = $temp_array2;
      }

      // sort temp aray
      usort($temp_array,create_function('$a,$b','return strnatcasecmp($a[\''.'time'.'\'],$b[\''.'time'.'\']);'));

      // create sorted list array
      unset($array);
      $array = array();
      for ($i=0; $i<count($temp_array); $i++) {
          $array[$i] = $old_list[$temp_array[$i]['position']];
      }
      $array = array_reverse($array);
   }
   return $array;
}



chdir($old_dir);
?>