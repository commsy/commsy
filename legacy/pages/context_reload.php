<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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
include_once('functions/curl_functions.php');

$params2 = $environment->getCurrentParameterArray();
$params2['jscheck'] = '1';
/*
if ( !empty($_POST['user_id']) ) {
   $params2['user_id'] = $_POST['user_id'];
}
if ( !empty($_POST['password']) ) {
   $params2['password'] = $_POST['password'];
}
*/
$params1 = $params2;
$params1['isJS'] = '1';
$url1 = _curl( false,
             $environment->getCurrentContextID(),
             $environment->getCurrentModule(),
             $environment->getCurrentFunction(),
             $params1
          );
$url2 = curl( $environment->getCurrentContextID(),
            $environment->getCurrentModule(),
            $environment->getCurrentFunction(),
            $params2
          );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
   <head>
     <noscript>
       <meta http-equiv="refresh" content="0; URL=<?PHP echo($url2); ?>">
      </noscript>
      <script type="text/javascript">
         <!--
            function reload () {
               var ssl = -1;
               var https = document.URL.substring(0,5);
               if ( https == "https" ) {
                  var ssl = 1;
               }
               
               document.location.href="<?PHP echo($url1); ?>&https="+ssl;
            }
         -->
      </script>
   </head>
   <body onload="reload();">
   </body>
</html>