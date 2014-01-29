<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

// chdir('..');
chdir('../..');

include_once('etc/cs_config.php');
include_once('functions/misc_functions.php');

$time_start = getmicrotime();

// disable timeout
set_time_limit(0);

// load the Whitelist
$filename = 'scripts/message_tag_cleaner/message_tag_cleaner_whitelist.dat';
if ( false === ( $whitelist = file($filename) ) ) {
   echo("Whitelist file not found at '".$filename."'");
   exit();
}

// set mode
switch ($_POST['option']) {
   case 'unbenutzte Tags suchen':
      $mode = 'show_orphans';
      break;
   case 'benutzte Tags suchen':
      $mode = 'show_used';
      break;
   case 'Tags in der Whitelist anzeigen':
      $mode = 'show_whitelist';
      break;
   case 'markierte Tags löschen':
      $mode = 'delete';
      break;
   default:
      $mode = 'none';
}

//- Function Definitions ----------------------------------------------------\\
function printTopLink($force_reset = false) {
  static $counter = 0;

  if ($force_reset) {
    $counter = 10;
  } else {
    $counter++;
  }

  if ($counter >= 10) {
    $counter = 0;
    echo('<div align="right"><a href="#top" class="toplink">nach oben</a></div>');
  } else {
    echo("</br></br>");
  }
  echo("\n");
}

function isWhitelisted($TagID){
  global $whitelist;

  $whitelisted = false;

  if (count($whitelist) > 0) {
    foreach ($whitelist as $entry) {
      if (preg_match('~'.trim($entry).'~u', $TagID)) {
        $whitelisted = true;
        break;
      }
    }
  }

  return $whitelisted;
}

function isOrphan($TagID) {
  return searchDirForOrphans('./', $TagID);
}

function searchDirForOrphans($directory, $TagID) {
  $orphan = true;
  $directory_handle  = opendir($directory);

  while(false !== ($entry = readdir($directory_handle))) {
    if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
      if (false == searchDirForOrphans($directory.'/'.$entry, $TagID)) {
        $orphan = false;
        break;
      }
    } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
      if (false == searchFileForOrphans($directory.'/'.$entry, $TagID)) {
        $orphan = false;
        break;
      }
    }
  }
  return $orphan;
}

function searchFileForOrphans($filename, $TagID) {
  $orphan = true;
  $file_content = file($filename);

  foreach($file_content as $line) {
    if(preg_match('~\''.$TagID.'\'~u', $line)) {
      $orphan = false;
      break;
    }
  }
  return $orphan;
}

function showUsedTags() {
  global $message;

  $used_tags = array();
  $used_tags = searchDirForUsed('./', $used_tags);

  ksort($used_tags);

  $num_found = count($used_tags);
  echo("benutzte MessageTags: ".$num_found."</br>\n");

  if (isset($_POST['show_unknown']) and $_POST['show_unknown']) {
    $num_unknown = 0;
    $unknown_tags = array();
    foreach ($used_tags as $tag_name => $tag_info) {
      if (!array_key_exists($tag_name, $message)) {
        $unknown_tags[$tag_name] = $tag_info;
        $num_unknown++;
      }
    }
    if (isset($_POST['show_used']) and $_POST['show_used']) {
      echo ('<a href="#unknown_tags">');
    }
    echo("Davon nicht in der Datenbank enthalten: ".$num_unknown."</br>\n");
    if (isset($_POST['show_used']) and $_POST['show_used']) {
      echo ('</a>');
    }
  }
  echo("</br>\n");

  if (isset($_POST['show_used']) and $_POST['show_used']) {
    if (isset($_POST['show_unknown']) and $_POST['show_unknown']) {
      echo('<h2>Benutzte Tags</h2>');
      echo("\n");
    }
    foreach ($used_tags as $tag_name => $tag_info) {
      echo("<b>".$tag_name."</b>");
      echo("</br>\n");
      for ($i = 0; $i < count($tag_info); $i = $i + 2) {
        echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
        echo($tag_info[$i]);
        echo("</br>\n");

        echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
        echo($tag_info[$i+1]);
        printTopLink();
      }
    }

    echo('benutzte MessageTags: '.$num_found.'</br>');
  }

  if (isset($_POST['show_unknown']) and $_POST['show_unknown']) {
    if (isset($_POST['show_used']) and $_POST['show_used']) {
      echo("Davon nicht in der Datenbank enthalten: ".$num_unknown."</br>\n");
      echo("<hr>\n");
      echo('<a name="unknown_tags"><h2>Unbekannte Tags</h2></a>');
      printTopLink(true);
      echo("\n");
    }
    foreach ($unknown_tags as $tag_name => $tag_info) {
      echo("<b>".$tag_name."</b>");
      echo("</br>\n");
      for ($i = 0; $i < count($tag_info); $i = $i + 2) {
        echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
        echo($tag_info[$i]);
        echo("</br>\n");

        echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
        echo($tag_info[$i+1]);
        printTopLink();
      }
    }
  }
}

function searchDirForUsed($directory, $used_tags) {
  $directory_handle  = opendir($directory);

  while(false !== ($entry = readdir($directory_handle))) {
    if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
      $used_tags = searchDirForUsed($directory.'/'.$entry, $used_tags);
    } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
      $used_tags = searchFileForUsed($directory.'/'.$entry, $used_tags);
    }
  }
  return $used_tags;
}

function searchDirForUsed2($directory, $used_tags) {
  $directory_handle  = opendir($directory);

  while ( false !== ($entry = readdir($directory_handle)) ) {
     if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
        $used_tags = searchDirForUsed2($directory.'/'.$entry, $used_tags);
     } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
        $used_tags = searchFileForUsed2($directory.'/'.$entry, $used_tags);
     }
  }
  return $used_tags;
}

function searchFileForUsed($filename, $used_tags) {
  $file_content = file($filename);

  for($i = 0; $i < count($file_content); $i++) {
    if(preg_match_all('~getMessage\([\s\S]*\'([A-Z0-9_]+)\'~Uu', $file_content[$i], $matches)) {
      if (count($matches) > 0) {
        for ($j=0; $j < count($matches[0]); $j++) {
          $tag_pos = $matches[1][$j];
          $used_tags[$tag_pos][] = $filename . ': Line ' . (string)($i + 1);
          $used_tags[$tag_pos][] = htmlentities(trim($file_content[$i]), ENT_NOQUOTES, 'UTF-8');
        }
      }
    }
    if(preg_match_all('~getMessageInLang\([\s\S]*,\s*\'([A-Z0-9_]+)\'~Uu', $file_content[$i], $matches)) {
      if (count($matches) > 0) {
        for ($j=0; $j < count($matches[0]); $j++) {
          $tag_pos = $matches[1][$j];
          $used_tags[$tag_pos][] = $filename . ': Line ' . (string)($i + 1);
          $used_tags[$tag_pos][] = htmlentities(trim($file_content[$i]), ENT_NOQUOTES, 'UTF-8');
        }
      }
    }
  }
  return $used_tags;
}

function searchFileForUsed2($filename, $used_tags) {
  $file_content = file($filename);

  for($i = 0; $i < count($file_content); $i++) {
    if ( preg_match_all('~getMessage\([\s\S]*\'([A-Z0-9_]+)\'~Uu', $file_content[$i], $matches) ) {
      if (count($matches) > 0) {
        for ($j=0; $j < count($matches[1]); $j++) {
            if ( mb_strlen($matches[1][$j]) > 1 and !in_array($matches[1][$j],$used_tags) ) {
               $used_tags[] = $matches[1][$j];
            }
         }
      }
    }
    if ( preg_match_all('~getMessageInLang\([\s\S]*,\s*\'([A-Z0-9_]+)\'~Uu', $file_content[$i], $matches) ) {
      if (count($matches) > 0) {
        for ($j=0; $j < count($matches[1]); $j++) {
            if ( mb_strlen($matches[1][$j]) > 1 and !in_array($matches[1][$j],$used_tags) ) {
               $used_tags[] = $matches[1][$j];
            }
        }
      }
    }
  }
  return $used_tags;
}

//- Setup stuff -------------------------------------------------------------\\

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();

$translator = $environment->getTranslationObject();
$message = $translator->getCompleteMessageArray();
$num_all = count($message);

$num_whitelisted = 0;
foreach ($message as $key => $value) {
  if (isWhitelisted($key)) {
    $num_whitelisted++;
    if ($mode == 'show_whitelist') {
      $whitelist_results[] = $key;
    }
  }
}

//- Operations --------------------------------------------------------------\\
if ($mode == 'delete') {
  if (isset($_POST['really_delete']) and $_POST['really_delete']) {
    include_once('classes/cs_language.php');
    $lang = new cs_language('', $message);

    foreach ($_POST['orphans'] as $TagID) {
      $lang->deleteMessage($TagID);
    }

    $translator->setMessageArray($lang->getMessageArray());
    $translator->saveMessages();

    // get the updated message array and count
    $message = $translator->getCompleteMessageArray();
    $num_all = count($message);
  }
}


//- Start HTML-Code here ----------------------------------------------------\\
?>

<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <?
  // ------------
  // --->UTF8<---
  //$retour .= '   <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.LF;
  // --->UTF8<---
  // ------------
  ?>
  <meta http-equiv="expires" content="0">
  <meta name="MSSmartTagsPreventParsing" content="TRUE">
  <title>CommSy MessageTag Cleaner v0.2</title>
  <style>
    a.toplink {
      font-size: smaller;
      text-decoration: none;
      color: black;
    }
  </style>
</head>

<body>
  <form enctype="multipart/form-data" method="post" action="" name="MessageTagCleaner">
  <a name="top">
<?PHP
  echo('
    <input type="submit" name="option" value="unbenutzte Tags suchen"></br></br>
  ');
  if ($mode == 'show_orphans') {
    echo('
      <input type="checkbox" name="really_delete">Wirklich löschen</br>
      <input type="submit" name="option" value="markierte Tags löschen"></br></br>
    ');
  }
  echo('<input type="checkbox" name="show_used"');
  if ($mode == 'none' || $_POST['show_used']) {
    echo(' checked');
  }
  echo('
    >Benutzte Tags anzeigen</br>

    <input type="checkbox" name="show_unknown"
  ');
  if (isset($_POST['show_unknown']) and $_POST['show_unknown']) {
    echo(' checked');
  }
  echo('
    >Benutzte aber unbekannte Tags anzeigen</br>

    <input type="submit" name="option" value="benutzte Tags suchen"></br></br>
    <input type="submit" name="option" value="Tags in der Whitelist anzeigen"></br></br>
    </br>
    MessageTags whitelisted: '.$num_whitelisted.'</br>
    MessageTags insgesamt: '.$num_all.'</br>
  ');

// show orphans
  if ($mode == 'show_orphans') {
    $num_orphans = 0;
    $current_item = 0;

    echo('
      <script type="text/javascript">
      <!--
      function markAll() {
        for(var i = 0; i < document.MessageTagCleaner.length; i++) {
          if (document.MessageTagCleaner.elements[i].type == "checkbox") {
            document.MessageTagCleaner.elements[i].checked = true;
          }
        }
      }

      function markNone() {
        for(var i = 0; i < document.MessageTagCleaner.length; i++) {
          if (document.MessageTagCleaner.elements[i].type == "checkbox") {
            document.MessageTagCleaner.elements[i].checked = false;
          }
        }
      }

      function markInvert() {
        for(var i = 0; i < document.MessageTagCleaner.length; i++) {
          if (document.MessageTagCleaner.elements[i].type == "checkbox") {
            if(document.MessageTagCleaner.elements[i].checked) {
              document.MessageTagCleaner.elements[i].checked = false;
            } else {
              document.MessageTagCleaner.elements[i].checked = true;
            }
          }
        }
      }
      //-->
      </script>

      </br>
      <a href="#" onClick="markAll()">Alles Markieren</a>&nbsp;
      <a href="#" onClick="markNone()">Nichts Markieren</a>&nbsp;
      <a href="#" onClick="markInvert()">Markierung umkehren</a>&nbsp;
      </br>
      </br>
    ');

    $used_tags = array();
    $used_tags = searchDirForUsed2('./', $used_tags);
    sort($used_tags);

    $tags_not_used = array();
    $num_not_used = 0;
    foreach ($message as $tag_name => $translation) {
      if (!in_array($tag_name, $used_tags)) {
        echo('<input type="checkbox" name="orphans[]" value="'.$tag_name.'" checked>'.$tag_name.' - '.$translation['de'].'</br>');
        $tags_not_used[] = $tag_name;
        $num_not_used++;
        flush();
      }
    }

    echo('
      </br>
      Anzahl nicht benutzter Tags: '.$num_not_used.'
      </br>
      </br>
      <a href="#" onClick="markAll()">Alles Markieren</a>&nbsp;
      <a href="#" onClick="markNone()">Nichts Markieren</a>&nbsp;
      <a href="#" onClick="markInvert()">Markierung umkehren</a>&nbsp;
    ');

// show whitelist
  } elseif ($mode == 'show_whitelist') {
    if (isset($whitelist_results)) {
      foreach ($whitelist_results as $entry) {
        echo($entry.'</br>');
      }
    } else {
      echo('Kein MessageTag passt zu den Einträgen der Whitelist.');
    }

// show used
  } elseif ($mode == 'show_used') {
    showUsedTags();

// delete
  } elseif ($mode == 'delete') {
    if (isset($_POST['really_delete']) and $_POST['really_delete']) {
      echo('</br>');
      echo('Anzahl gelöschter MessageTags: '.count($_POST['orphans']).'</br>');
      echo('Gelöschte MessageTags:</br>');

      foreach ($_POST['orphans'] as $TagID) {
        echo($TagID.'</br>');
      }
    } else {
      echo('</br>');
      echo('Keine Tags gelöscht, da die Sicherheitscheckbox nicht angeklickt wurde.');
    }
  }
  echo('</form>');

  $time_end = getmicrotime();
  $time = round($time_end - $time_start,3);
  echo('</br>');
  echo("Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60)));
?>
</body>
</html>