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

// function to check files for virus
function page_edit_virusscan_isClean ($filename_on_disc, $filename_orig) {
   global $page;
   global $environment;
   $class_factory = $environment->getClassFactory();

   global $symfonyContainer;
   $c_virus_scan = $symfonyContainer->getParameter('commsy.clamscan.virus_scan');
   $c_virus_scan_cron = $symfonyContainer->getParameter('commsy.clamscan.virus_scan_cron');

   $retour = true;
   if (isset($c_virus_scan) and $c_virus_scan) {
      if ( !isset($c_virus_scan_cron) or !$c_virus_scan_cron ) {
         include_once('classes/cs_virus_scan.php');
         $virus_scanner = new cs_virus_scan($environment);
         if (!$virus_scanner->isClean($filename_on_disc,$filename_orig)) {
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $params['width'] = 500;
            $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            unset($params);
            $errorbox->setText($virus_scanner->getOutput());
            $page->add($errorbox);
            $retour = false;
         }
      }
   }
   return $retour;
}
?>