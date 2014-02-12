<?php



$wiki_skin_choice='pmwiki';
if (isset($_POST['wiki_skin_choice'])){
   $wiki_skin_choice = $_POST['wiki_skin_choice'];
}

$wiki_title='pmwiki';
if (isset($_POST['wiki_title'])){
   $wiki_title = $_POST['wiki_title'];
}

$wiki_admin_pw = 'admin';
if (isset($_POST['wiki_admin_pw'])){
   $wiki_admin_pw = $_POST['wiki_admin_pw'];
}

$wiki_edit_pw = 'edit';
if (isset($_POST['wiki_edit_pw'])){
   $wiki_edit_pw = $_POST['wiki_edit_pw'];
}

$wiki_read_pw = '';
if (isset($_POST['wiki_read_pw'])){
   $wiki_read_pw = $_POST['wiki_read_pw'];
}



if (isset($_POST['option']) and $_POST['option']=='Wiki einrichten'){
   $old_dir = getcwd();
   chdir('..');

   $i = 1;
   $directory = '0'.$i;
   $new_directory = false;
   while (!$new_directory){
      $directory_handle = @opendir($directory);
      if (!$directory_handle) {
         mkdir($directory);
         $new_directory = true;
      }else{
         $i++;
         $directory = '0'.$i;
      }
   }
   chdir($directory);
   if ( !file_exists('index.php') ) {
      copy('../../../cookbook/WikiInstallation/wiki_index.php','index.php');
   }
   $directory_handle = @opendir('local');
   if (!$directory_handle) {
      mkdir('local');
   } else {
      closedir($directory_handle);
   }
   chdir('local');

   if ( !file_exists('config.php') ) {
      copy('../../../../cookbook/WikiInstallation/wiki_config.php','config.php');
   }

   $str  = '<?php '."\n";
   $str .= '$WIKI_SKIN = "'.$wiki_skin_choice.'"; '."\n";
   $str .= '$WIKI_EDIT_PASSWD = "'.$wiki_edit_pw.'"; '."\n";
   $str .= '$WIKI_ADMIN_PASSWD = "'.$wiki_admin_pw.'"; '."\n";
   $str .= '$WIKI_UPLOAD_PASSWD = "'.$wiki_edit_pw.'"; '."\n";
   $str .= '$WIKI_READ_PASSWD = "'.$wiki_read_pw.'"; '."\n";
   $str .= '$WIKI_WIKI_TITLE = "'.$wiki_title.'"; '."\n";
   $str .= '$WIKI_LANGUAGE = "'.'de'.'"; '."\n";
   $str .= '?>';
   file_put_contents('inc_config.php',$str);
   chdir($old_dir);

   $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
   $url = str_replace('phpinc/installwiki.php','',$url);
   $url .= $directory.'/';
   header('Location: '.$url);
   header('HTTP/1.0 302 Found');
   exit();

}

$skin_array = array();
$old_dir = getcwd();
chdir('../..');
$directory_handle = @opendir('pub/skins');
if ($directory_handle) {
   while (false !== ($dir = readdir($directory_handle))) {
      if($dir !='home' and $dir !='...' and $dir !='..' and $dir !='.' and $dir !='print' and $dir !='CVS'){
         $skin_array[] = $dir;
      }
   }
}
chdir($old_dir);


$html  ='<div style="width:100%;">';
$html .='<form style="text-align:left; font-size:10pt; margin:0px; padding:0px;" action="phpinc/installwiki.php" method="post" enctype="multipart/form-data" name="f">';
$html .= '<table style="font-size: 10pt; border-collapse: collapse; margin-bottom: 10px;" summary="Layout">';

$html .='<tr>';
$html .='<td>';
$html .= 'Titel';
$html .='</td><td>';
$html .= '<input style="font-size: 10pt;" name="wiki_title" value="'.$wiki_title.'" maxlength="200" size="28" tabindex="13" class="text" type="text">';
$html .='</td></tr>';

$html .='<tr>';
$html .='<td>';
$html .= 'Passwort (administrieren)';
$html .='</td><td>';
$html .= '<input style="font-size: 10pt;" name="wiki_admin_pw" value="'.$wiki_admin_pw.'" maxlength="200" size="10" tabindex="13" class="text" type="text">';
$html .='</td></tr>';

$html .='<tr>';
$html .='<td>';
$html .= 'Passwort (bearbeiten)';
$html .='</td><td>';
$html .= '<input style="font-size: 10pt;" name="wiki_edit_pw" value="'.$wiki_edit_pw.'" maxlength="200" size="10" tabindex="13" class="text" type="text">';
$html .='</td></tr>';

$html .='<tr>';
$html .='<td>';
$html .= 'Passwort (lesen)';
$html .='</td><td>';
$html .= '<input style="font-size: 10pt;" name="wiki_read_pw" value="'.$wiki_read_pw.'" maxlength="200" size="10" tabindex="13" class="text" type="text">';
$html .='</td></tr>';





$html .='<tr>';
$html .='<td>';
$html .= 'Darstellung';
$html .='</td><td>';
$html .= '<select name="wiki_skin_choice" size="0" tabindex="13" style="width: 15em; font-size: 10pt;">';
foreach ($skin_array as $skin){
   $html .='<option value="'.$skin.'" ';
   if ($skin == $wiki_skin_choice){
      $html .='selected="selected" ';
   }
   $html .=' >'.$skin.'</option>'."\n";
}
$html .='</select>'."\n";
$html .='</td></tr>';

$html .='<tr>';
$html .='<td>';
$html .= '';
$html .='</td><td>';
$html .='<input type="submit" name="option" value="Wiki einrichten" tabindex="13" style="font-size:10pt;"/>';
$html .='</td></tr>';
$html .='</table>';
$html .='</form></div>';
echo($html);

?>