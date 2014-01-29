<?php
// Copyright (c)2002-2008 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
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

chdir('../..');
error_reporting(E_ALL);
include('htdocs/install/includes/language.php');
session_start();

$filename = 'etc/cs_config.php';

if (file_exists($filename)) {

   // Wenn CommSy schon installiert wurde -->
   include_once($filename);
   header("Location: ../".$c_single_entry_point);
   header('HTTP/1.0 302 Found');
   exit;

} else {

   // Wenn CommSy noch nicht installiert -> Installation ausführen

   ob_start();

   // Absoluter Pfad auslesen

   $url = "";

   if (isset($configArray['siteUrl'])) {

      $url = $configArray['siteUrl'];
      $path = $configArray['sitePath'];

   } else {

      $port = ( $_SERVER['SERVER_PORT'] == 80 ) ? '' : ":".$_SERVER['SERVER_PORT'];
      if ( $port == 443 ) {
         $domain = 'https://'.$_SERVER['SERVER_NAME'];
      } else {
         $domain = 'http://'.$_SERVER['SERVER_NAME'].$port;
      }
      $root = $_SERVER['PHP_SELF'];
      $root = str_replace("install.php","",$root);
      $url = $root;
      $path = $_SERVER['SCRIPT_FILENAME'];
   }

   if(isset($_POST['lang']))
   {
      $_SESSION['lang'] = $_POST['lang'];
      if(isset($_POST['page']) and !empty($_POST['page']))
      {
         $action = $_POST['page'];
      } else {
         unset($action);
      }
   }

   if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and !isset($_SESSION['lang']))
   {
      $lang = mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0 ,2);
      if($lang != 'de')
      {
         $lang = 'en';
      }

   } elseif(isset($_SESSION['lang'])) {
      $lang = $_SESSION['lang'];
   } else {
      $lang = 'en';
   }

   if(isset($_POST['submit']))
   {
      switch($_POST['page'])
      {
         case 'start':
            $action = 'license';
            break;

         case 'license':
            if(strstr($_POST['submit'],$language[$lang]['buttonprev']))
            {
               unset($action);
            } else {
               if(isset($_POST['license']))
               {
                  $action = 'chmod';
                  $_SESSION['license'] = 1;
               } else {
                  $action = 'license';
               }
            }
            break;
         case 'chmod':
            if(strstr($_POST['submit'],$language[$lang]['buttonprev']))
            {
               $action = 'license';
            } elseif(strstr($_POST['submit'],$language[$lang]['buttonrecheck'])) {
               $action = 'chmod';
            } else {
               $action = 'database';
            }
            break;
         case 'database':
            if(strstr($_POST['submit'],$language[$lang]['buttonprev']))
            {
               $action = 'chmod';
            } elseif(isset($_POST['test']) and $_POST['test'] == 'do' and !isset($_POST['checkdone']))
            {
               $_SESSION['host'] = $_POST['host'];
               $_SESSION['dbname'] = $_POST['dbname'];
               $_SESSION['dbuser'] = $_POST['user'];
               $_SESSION['dbpass'] = $_POST['password'];
               $action = 'database';
            } elseif(strstr($_POST['submit'],$language[$lang]['buttonnext']) and isset($_POST['checkdone']) and $_POST['checkdone'] == 'yes')
            {
               $action = 'basedata';
            }
            break;
         case 'basedata':
            if(strstr($_POST['submit'],$language[$lang]['buttonprev']))
            {
               $action = 'database';
            } else {
               $action = 'done';
            }
            break;

         default: unset($action); break;
      }
   }



}
header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">

<head>

 <!-- Titel der Seite -->

 <title>CommSy Installation</title>

 <!-- Meta der Seite -->

 <meta http-equiv="content-type" content="text/html; charset=utf-8" />
 <meta http-equiv="imagetoolbar" content="no" />

 <!-- Copyright der Seite -->

 <meta name="author" content="CommSy Team - http://www.commsy.net" />

 <!-- Icon -->

 <link rel="shortcut icon" href="../favicon.ico" />

 <!-- CSS der Seite -->

 <link rel="stylesheet" type="text/css" href="includes/main.css" title="Default Style" />

 <!-- Javascript -->

 <script type="text/javascript" src="includes/input.js"></script>

 <!--[if lt IE 7]>
 <script language="javascript" type="text/javascript" src="includes/pngfix.js"></script>
 <![endif]-->

</head>

<body id="top">

<!-- HIDDEN PRELOAD STATUS IMAGES -->

<img src="includes/status_overview.jpg" alt="" border="0" style="display: none;" />
<img src="includes/status_license.jpg" alt="" border="0" style="display: none;" />
<img src="includes/status_chmod.jpg" alt="" border="0" style="display: none;" />
<img src="includes/status_database.jpg" alt="" border="0" style="display: none;" />
<img src="includes/status_basedata.jpg" alt="" border="0" style="display: none;" />
<img src="includes/status_done.jpg" alt="" border="0" style="display: none;" />

<!-- HIDDEN PRELOAD STATUS IMAGES -->
<table style="width: 100%; height: 100%;" cellspacing="0" cellpadding="0">
<tr>
<td style="width: 100%; height: 100%;" valign="middle" align="center">

<div id="layer">
<div id="flags" style="display:inline; float:right;">
<?php
if(isset($action))
{
   $page = $action;
} else {
   $page = "";
}
?>
<form name="changelanguage" action="index.php" method="post" style="display:inline;">
<input type="hidden" name="page" value="<?php echo($page);?>" />
<input type="image" name="lang" value="de" src="../images/flags/de.gif" alt="Sprache auf Deutsch umstellen" />
<input type="image" name="lang" value="en" src="../images/flags/gb.gif" alt="change language to english" />
</form>
</div><br/>

<?php
if (!isset($action)) {

   echo "<div id=\"status\"><img src=\"includes/status_overview.jpg\" alt=\"\" border=\"0\" /></div>";

   // UEBERSICHT

   echo "<div id=\"text\">";
   echo "<h1>".$language[$lang]['welcome']."</h1>";
   echo "<p align=\"justify\">".$language[$lang]['welcometext']."</p>";
   echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
   echo "<tr><td width=\"50%\" style=\"vertical-align:top;\">";
   echo "<p><strong>".$language[$lang]['reqnecessary']."</strong></p>";
   echo "<p>".$language[$lang]['php5'];
   echo "<br/>".$language[$lang]['mysql5']."</p>";
   echo "<p>".$language[$lang]['phpmodules'].":
         <br/>- GD Graphics Library - gd2
         <br/>- MySQL Database Library - mysql
         </p>";
   echo "<p>".$language[$lang]['phpsettings_title'].":
        <br/>- magic_quotes_gpc = off (default = on)
        <br/>- register_globals = off (default = off)
        <br/>- memory_limit = 24M (default = 8M)
   </p>";
   echo "<p>CommSy CronJobs" .
        "<br/>".$language[$lang]['commsycron'].
        "</p>";
   echo "<p><strong>".$language[$lang]['newdatabase']."</strong></p>";
   echo "<p>".$language[$lang]['newdatabasetext']."</p><br />";
   echo "</td>";
   echo "<td width=\"50%\" style=\"vertical-align:top;\">";
   echo "<p><strong>".$language[$lang]['reqoptional']."</strong>";
   echo "<br/>OpenSSL";
   echo "<p>".$language[$lang]['phpmodules'].":
         <br/>- OpenSSL Library - php_openssl
         <br/>- Clamav Virusscanning Library - clamavlib
         </p>";
   echo "<p>".$language[$lang]['addsoftware'].":
         <br/>- ".$language[$lang]['addsoftware_clamav']."
         <br/>- ".$language[$lang]['addsoftware_fck']."
         <br/>- ".$language[$lang]['addsoftware_jsmath']."
         <br/>- ".$language[$lang]['addsoftware_chat']."
         <br/>- ".$language[$lang]['addsoftware_commsywiki']."
         </p>";
   // Buttons

   echo '<br/>';
   echo '<form name="installation" method="post" action="index.php">';
   echo '<input type="hidden" name="page" value="start" />';
   echo '<input type="submit" name="submit" value="&nbsp;&nbsp;'.$language[$lang]['buttonstart'].'&nbsp;&nbsp;&raquo;&nbsp;&nbsp;" />';
   echo '</form>';
   echo "</td>
         </tr>";
   echo "</table>";

   // Buttons

   echo "</div>";

} elseif (isset($action) and $action == "license") {

   echo "<div id=\"status\"><img src=\"includes/status_license.jpg\" alt=\"\" border=\"0\" /></div>";

   // LIZENZ

   echo "<div id=\"text\">";
   echo "<h1>".$language[$lang]['license']."</h1>";
   echo '<div id="lizenz">';
   echo '<p><pre>';
   $licensetext = file_get_contents('docs/LICENSE');
   echo $licensetext;
   echo '</pre></p>';
   echo '</div>';

   echo '<form name="installation" method="post" action="index.php">';
   echo '<input type="hidden" name="page" value="license" />';
   echo '<p><input type="checkbox" name="license" value="read"';
   if(isset($_SESSION['license']))
   {
      echo ' checked="checked"';
   }
   echo ' />&nbsp;&nbsp;'.$language[$lang]['licenseaccept'].'</p>';

   // Buttons


   echo '<br /><table width="100%" cellspacing="0" cellpadding="0"><tr>';

   echo '<td align="left"><p><input type="submit" name="submit" value="&nbsp;&nbsp;&laquo;&nbsp;&nbsp;'.$language[$lang]['buttonprev'].'&nbsp;&nbsp;" /></p></td>';
   echo '<td align="right"><p><input type="submit" name="submit" value="&nbsp;&nbsp;'.$language[$lang]['buttonnext'].'&nbsp;&nbsp;&raquo;&nbsp;&nbsp;" /></p></td>';

   echo '</tr></table>';
   echo '</form>';

   // Buttons

   echo "</div>";

} elseif (isset($action) and $action == "chmod") {

   echo "<div id=\"status\"><img src=\"includes/status_chmod.jpg\" alt=\"\" border=\"0\" /></div>";

   // CHMOD

   echo "<div id=\"text\">";
   echo "<h1>".$language[$lang]['version']."</h1>";

   $php_version = phpversion();
   if ( $php_version >= 5.2 ) {
      $checkphp = "<font style=\"color: #32C040;\"><strong>".$php_version."</strong></font>\n";
   } else {
      $checkphp = "<font style=\"color: #FF0030;\"><strong>".$php_version."</strong></font>\n";
   }

   echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
   echo "<tr>";
   echo "<td width=\"40%\">PHP</td>";
   echo "<td width=\"20%\">>= 5.2</td>";
   echo "<td width=\"40%\" align=\"right\">".$checkphp."</td>";
   echo "</tr>";
   echo "</table>";
   echo "<br/>";
   echo "<h1>".$language[$lang]['phpmodules']."</h1>";

   if ( !function_exists('mysql_connect') )
   {
      $mysql_php = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   } else {
      $mysql_php = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
   }

   if ( !function_exists('gd_info') )
   {
      $gd_php = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   } else {
      $gd_php = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
   }

   if ( !function_exists('mb_encode_mimeheader') )
   {
      $mb_php = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   } else {
      $mb_php = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
   }

   echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
   echo "<tr>";
   echo "<td width=\"40%\">mysql</td>";
   echo "<td width=\"20%\"></td>";
   echo "<td width=\"40%\" align=\"right\">".$mysql_php."</td>";
   echo "</tr>";
   echo "<tr>";
   echo "<td width=\"40%\">gd2</td>";
   echo "<td width=\"20%\"></td>";
   echo "<td width=\"40%\" align=\"right\">".$gd_php."</td>";
   echo "</tr>";
   echo "<tr>";
   echo "<td width=\"40%\">mbstring</td>";
   echo "<td width=\"20%\"></td>";
   echo "<td width=\"40%\" align=\"right\">".$mb_php."</td>";
   echo "</tr>";
   echo "</table>";
   echo "<br/>";
   echo "<h1>".$language[$lang]['phpsettings']."</h1>";

   if(get_magic_quotes_gpc() > 0)
   {
      $mqgpc = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   } else {
      $mqgpc = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
   }

   if(ini_get('register_global') == 1)
   {
      $regglobals = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   } else {
      $regglobals = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
   }

   $memory_limit = ini_get('memory_limit');
   $memory_limit = str_replace("M",'',$memory_limit);
   if($memory_limit < 24)
   {
      $memlimit = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   } else {
      $memlimit = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
   }

   echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
   echo "<tr>";
   echo "<td width=\"40%\">magic_quotes_gpc</td>";
   echo "<td width=\"20%\">Off</td>";
   echo "<td width=\"40%\" align=\"right\">".$mqgpc."</td>";
   echo "</tr>";
   echo "<tr>";
   echo "<td width=\"40%\">register_globals</td>";
   echo "<td width=\"20%\">Off</td>";
   echo "<td width=\"40%\" align=\"right\">".$regglobals."</td>";
   echo "</tr>";
   echo "<tr>";
   echo "<td>memory_limit</td>";
   echo "<td>&ge; 24M</td>";
   echo "<td align=\"right\">".$memlimit."</td>";
   echo "</tr>";
   echo "</table>";
   echo "<br/>";

   echo "<h1>".$language[$lang]['chmod']."</h1>";

   if (is_writable("etc")) {

      $checketc = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
      $checketc1 = 1;

   } else {

      $checketc = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   }

   if (is_writable("var")) {

      $checkvar = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
      $checkvar1 = 1;

   } else {

      $checkvar = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
   }

   if(file_exists("var/temp"))
   {
      if (is_writable("var/temp")) {

         $checkvartemp = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
         $checkvartemp1 = 1;

      } else {

         $checkvartemp = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";

      }
   } else {
      if (mkdir("var/temp",0770)) {

         $checkvartemp = "<font style=\"color: #32C040;\"><strong>".$language[$lang]['ok']."</strong></font>\n";
         $checkvartemp1 = 1;

      } else {

         $checkvartemp = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";

      }
   }

   echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
   echo "<tr>";
   echo "<td width=\"40%\">../etc/ (".$language[$lang]['for_installation'].")</td>";
   echo "<td width=\"20%\">&nbsp;</td>";
   echo "<td width=\"40%\" align=\"right\">".$checketc."</td>";
   echo "</tr>";
   echo "<tr>";
   echo "<td width=\"40%\">../var/</td>";
   echo "<td width=\"20%\">&nbsp;</td>";
   echo "<td width=\"40%\" align=\"right\">".$checkvar."</td>";
   echo "</tr>";
   echo "<tr>";
   echo "<td>../var/temp/</td>";
   echo "<td>&nbsp;</td>";
   echo "<td align=\"right\">".$checkvartemp."</td>";
   echo "</tr>";
   echo "</table>";

   echo '<form name="installation" method="post" action="index.php">';
   echo '<input type="hidden" name="page" value="chmod" />';
   echo '<br /><table width="100%" cellspacing="0" cellpadding="0"><tr>';

   echo '<td align="left" width="33%"><p><input type="submit" name="submit" value="&nbsp;&nbsp;&laquo;&nbsp;&nbsp;'.$language[$lang]['buttonprev'].'&nbsp;&nbsp;" /></p></td>';
   echo '<td align="center" width="33%"><p><input type="submit" name="submit" value="&nbsp;&nbsp;&nbsp;&nbsp;'.$language[$lang]['buttonrecheck'].'&nbsp;&nbsp;" /></p></td>';
   echo '<td align="right" width="33%">';

   if (isset($checkvar1) and isset($checkvartemp1)) {

      echo '<p><input type="submit" name="submit" value="&nbsp;&nbsp;'.$language[$lang]['buttonnext'].'&nbsp;&nbsp;&raquo;&nbsp;&nbsp;" /></p>';

   }

   echo '</td>';

   echo '</tr></table>';
   echo '</form>';

   echo "</div>";

} elseif (isset($action) and $action == "database") {

   echo "<div id=\"status\"><img src=\"includes/status_database.jpg\" alt=\"\" border=\"0\" /></div>";

   // DATENBANK

   echo "<div id=\"text\">";

   echo "<h1>".$language[$lang]['databaseinstall']."</h1>";

   if (isset($_POST['test']) and $_POST['test'] == 'do') {

      $host = $_SESSION['host'];
      $dbname = $_SESSION['dbname'];
      $user = $_SESSION['dbuser'];
      $password = $_SESSION['dbpass'];
      $success = true;

      if ( !@mysql_connect($host,$user,$password) ) {
          $sqlcheck = "<font style=\"color: #FF0030;\">".$language[$lang]['error']."</font>";
          $success = false;
      } else {
         $sqlcheck = "<font style=\"color: #32C040;\">".$language[$lang]['ok']."</font>";
         $sqlcheck1 = 1;
      }

      if ( !@mysql_select_db($dbname) ) {
          $databasekcheck = "<font style=\"color: #FF0030;\">".$language[$lang]['error']."</font>";
          $success = false;
      } else {
         $databasekcheck = "<font style=\"color: #32C040;\">".$language[$lang]['ok']."</font>";
         $databasekcheck1 = 1;
      }

      $mysql_version = @mysql_get_server_info();
      if ( empty($mysql_version) ) {
         $checkmysql = "<font style=\"color: #FF0030;\"><strong>".$language[$lang]['error']."</strong></font>\n";
         $success = false;
      } elseif ( !empty($mysql_version) and $mysql_version >= 5 ) {
         $checkmysql = "<font style=\"color: #32C040;\"><strong>".$mysql_version."</strong></font>\n";
         $databasekcheck2 = 1;
      } else {
         $checkmysql = "<font style=\"color: #FF0030;\"><strong>".$mysql_version."</strong></font>\n";
         $success = false;
      }

      echo '<form name="installation" method="post" action="index.php">';
      echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
      echo "<tr>";
      echo "<td>MySQL (> 5)</td>";
      echo "<td>".$checkmysql."</td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td>".$language[$lang]['connection'].":&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
      echo "<td><strong>".$sqlcheck."</strong></td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td>".$language[$lang]['database'].":</td>";
      echo "<td><strong>".$databasekcheck."</strong></td>";
      echo "</tr>";
      echo "</table>";

      echo "<br />";
   }

      echo '<form name="installation" method="post" action="index.php">';
      echo '<input type="hidden" name="page" value="database" />';
      echo '<input type="hidden" name="test" value="do" />';
      if ( !isset($success) or !$success ) {
      echo '<p>'.$language[$lang]['databaseinstalltext'].':</p>';
      echo '<table cellspacing="0" cellpadding="0" border="0">';
      echo '<tr>';
      echo '<td>Host:&nbsp;&nbsp;&nbsp;&nbsp;</td>';
      if(!isset($_SESSION['dbhost']))
      {
         $host = 'localhost';
      } else {
         $host = $_SESSION['dbhost'];
      }
      echo '<td><input type="text" name="host" value="'.$host.'" class="formular" tabindex="1"/></td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>Datenbank Name:&nbsp;&nbsp;&nbsp;&nbsp;</td>';
      echo '<td><input type="text" name="dbname"';
      if(isset($_SESSION['dbname']))
      {
         echo ' value="'.$_SESSION['dbname'].'"';
      }
      echo ' class="formular" tabindex="2"/></td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>User:&nbsp;&nbsp;&nbsp;&nbsp;</td>';
      echo '<td><input type="text" name="user"';
      if(isset($_SESSION['dbuser']))
      {
         echo ' value="'.$_SESSION['dbuser'].'"';
      }
      echo ' class="formular" tabindex="3"/></td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>Passwort:&nbsp;&nbsp;&nbsp;&nbsp;</td>';
      echo '<td><input type="password" name="password"';
      if(isset($_SESSION['password']))
      {
         echo ' value="'.$_SESSION['password'].'"';
      }
      echo ' class="formular" tabindex="4" onkeydown="if ((event.which && event.which == 13) ||
    (event.keyCode && event.keyCode == 13))
    {document.installation.submit[1].click();return false;}
    else return true;"
/></td>';
      echo '</tr>';
      echo '</table>';
      }

      echo "<br />";
      echo '<table width="100%" cellspacing="0" cellpadding="0"><tr>';
      echo '<td width="33%" align="left"><p><input name="submit" type="submit" value="&nbsp;&nbsp;&laquo;&nbsp;&nbsp;'.$language[$lang]['buttonprev'].'&nbsp;&nbsp;" tabindex="6"/></p></td>';
      echo '<td align="center" width="33%">';

      if(isset($_POST['test']) and (!isset($sqlcheck1) or !isset($databasekcheck1) or !isset($databasekcheck2)))
      {
         echo '
         <input type="hidden" name="test" value="do" />
         <p><input type="submit" name="submit" value="&nbsp;&nbsp;&nbsp;&nbsp;'.$language[$lang]['buttonrecheck'].'&nbsp;&nbsp;" /></p></td>';
      }
      echo '</td>';
      echo '<td width="33%" align="right">';
      if(!isset($_POST['test']))
      {
         echo '<p><input name="submit" type="submit" value="&nbsp;&nbsp;'.$language[$lang]['buttonnext'].'&nbsp;&nbsp;&raquo;&nbsp;&nbsp;" tabindex="5"/></p>';
      } elseif(isset($_POST['test']) and isset($sqlcheck1) and isset($databasekcheck1) and isset($databasekcheck2))
      {
         echo '
         <input type="hidden" name="checkdone" value="yes" />
         <p><input name="submit" type="submit" value="&nbsp;&nbsp;'.$language[$lang]['buttonnext'].'&nbsp;&nbsp;&raquo;&nbsp;&nbsp;" /></p>';
      }
      echo '</td>';
      echo '</tr></table>';

   echo '</form>';

   echo "</div>";

} elseif (isset($action) and $action == "basedata") {

   echo "<div id=\"status\"><img src=\"includes/status_basedata.jpg\" alt=\"\" border=\"0\" /></div>";

   // STAMMDATEN

   echo "<div id=\"text\">";

   echo "<h1>".$language[$lang]['basedata']."</h1>";

    echo '<form name="installation" method="post" action="index.php">';
   echo '<input type="hidden" name="page" value="basedata" />';
   echo '<p>'.$language[$lang]['basedatatext'].'</p>';
   echo '<table cellspacing="0" cellpadding="0" border="0">';
   echo '<tr>';
   echo '<td>Domain:&nbsp;&nbsp;&nbsp;&nbsp;</td>';
   echo "<td><input type=\"text\" name=\"domain\" value=\"".$domain."\" class=\"formular\" /></td>";
   echo '</tr>';
   echo '<tr>';
   echo '<td>'.$language[$lang]['urlpath'].':&nbsp;&nbsp;&nbsp;&nbsp;</td>';
   echo "<td><input type=\"text\" name=\"urlpath\" value=\"".mb_substr($url, 0, -18)."\" class=\"formular\" /></td>";
   echo '</tr>';
   echo '<tr>';
   echo '<td>'.$language[$lang]['abspath'].':&nbsp;&nbsp;&nbsp;&nbsp;</td>';
   echo "<td><input type=\"text\" name=\"abspath\" value=\"".mb_substr($path, 0, -25)."\" class=\"formular\" /></td>";
   echo '</tr>';
   echo '</table>';

   echo '<br />';

   echo '<table width="100%" cellspacing="0" cellpadding="0"><tr>';
   echo '<td align="left"><p><input type="submit" name="submit" value="&nbsp;&nbsp;&laquo;&nbsp;&nbsp;'.$language[$lang]['buttonprev'].'&nbsp;&nbsp;" /></p></td>';
   echo '<td align="right"><p><input type="submit" name="submit" value="&nbsp;&nbsp;'.$language[$lang]['buttonnext'].'&nbsp;&nbsp;&raquo;&nbsp;&nbsp;" /></p></td>';
   echo '</tr></table>';

         echo '</form>';

   echo "</div>";

} else if (isset($action) and $action == "done") {

   echo "<div id=\"status\"><img src=\"includes/status_done.jpg\" alt=\"\" border=\"0\" /></div>";

   // POST DATEN AUSLESEN

   $urlpath = $_POST['urlpath'];
   $abspath = $_POST['abspath'];

   // MY SQL DATEI ERSTELLEN UND DATEN EINTRAGEN
   $schreibe1  = '$db["normal"]["host"]';
   $schreibe2  = '$db["normal"]["user"]';
   $schreibe3  = '$db["normal"]["password"]';
   $schreibe4  = '$db["normal"]["database"]';
   $schreibe5  = '$c_commsy_url_path';
   $schreibe6  = '$c_commsy_path_file';

   $schreibe8  = '$_SERVER["HTTP_HOST"]';
   $schreibe9  = '$_SERVER["SERVER_PORT"]';
   $schreibe10 = '$c_commsy_domain';

   $schreibe11  = '// include first special commsy settings'."\n";
   $schreibe11 .= '@include_once("etc/commsy/settings.php");'."\n";

   $schreibe12 = '$c_security_key';
   $sec_key = md5(date("Y-m-d H:i:s"));

   $schreibe13 = '$_SERVER["SCRIPT_NAME"]';
   $schreibe14 = '$_SERVER["PHP_SELF"]';
   $schreibe15 = '$_SERVER["SCRIPT_FILENAME"]';
   $schreibe16 = '$path';
   $schreibe17 = '$retour';

   $daten = "<?php
// Copyright (c)2008 Matthias Finck, Iver Jackewitz, Dirk Blössl
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

function deleteLastSlash ( $schreibe16 ) {
   $schreibe17 = $schreibe16;
   if ( !empty($schreibe16) ) {
      if ( substr($schreibe16,strlen($schreibe16)-1) == '/'
           or substr($schreibe16,strlen($schreibe16)-1) == '\\\\'
         ) {
         $schreibe17 = substr($schreibe16,0,strlen($schreibe16)-1);
      }
   }
   return $schreibe17;
}

// Database setup
$schreibe1 = \"".$_SESSION['host']."\";
$schreibe2 = \"".$_SESSION['dbuser']."\";
$schreibe3 = \"".$_SESSION['dbpass']."\";
$schreibe4 = \"".$_SESSION['dbname']."\";

// Path setup
if ( !empty($schreibe8) ) {
   if ( !empty($schreibe9)
        and $schreibe9 == 443
      ) {
      $schreibe10 = 'https://';
   } else {
      $schreibe10 = 'http://';
   }
   $schreibe10 .= $schreibe8;
} else {
   $schreibe10 = \"".$_POST['domain']."\";
}
$schreibe10 = deleteLastSlash($schreibe10);

if ( !empty($schreibe13) ) {
   $schreibe5 = dirname($schreibe13);
} elseif ( !empty($schreibe14) ) {
   $schreibe5 = dirname($schreibe14);
} else {
   $schreibe5 = \"".$_POST['urlpath']."\";
}
$schreibe5 = deleteLastSlash($schreibe5);

if ( !empty($schreibe15) ) {
   $schreibe6 = dirname($schreibe15);
   $schreibe6 = str_replace('/htdocs','',$schreibe6);
   $schreibe6 = str_replace('/install','',$schreibe6);
   $schreibe6 = str_replace('/',DIRECTORY_SEPARATOR,$schreibe6);
} else {
   $schreibe6 = \"".$_POST['abspath']."\";
}
$schreibe6 = deleteLastSlash($schreibe6);

// security key
$schreibe12 = \"".$sec_key."\";

// include more commsy settings
@include_once('etc/config_meta.php');
?>";
   $mysqlfile = "etc/cs_config.php";
   if (!file_put_contents($mysqlfile,$daten)) {
      echo('ERROR: can not write config file');
   }

   // DATENBANK INSTALLIEREN
   $file_rows = file("docs/db_dump_mysql.sql");
   include_once("etc/cs_config.php");
   include_once("etc/commsy/default.php");
   include_once('classes/db_mysql_connector.php');
   $db_connector = new db_mysql_connector($db["normal"]);
   $statement = "";
   foreach ($file_rows as $file_row) {
      $statement .= $file_row . "\n";
      if ( strstr($file_row, ";") ) {
         $db_connector->performQuery($statement);
         $statement = "";
      }
   }
   unset($db_connector);

   echo "<div id=\"text\">";

   echo "<h1>".$language[$lang]['done']."</h1>";
   echo "<p>".$language[$lang]['donetext']."</p>";
   echo "<br />";
   echo '&raquo;&nbsp;<a href="../'.$c_single_entry_point.'">'.$language[$lang]['toportal'].'</a><br />';

   echo "</div>";

}

?>

</div>

</td>
</tr>
</table>

</body>
</html>

<?php

ob_end_flush();

?>