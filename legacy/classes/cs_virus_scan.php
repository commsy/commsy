<?php
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

class cs_virus_scan {

   var $_path = NULL;
   var $_bin  = NULL;
   var $_ouput = NULL;
   var $_virus_name = NULL;
   var $_environment = NULL;
   var $_translator = NULL;

   function __construct($environment) {
      global $symfonyContainer;
      $c_virus_use_php = $symfonyContainer->getParameter('commsy.clamscan.virus_use_php');
      $c_virus_scan_path = $symfonyContainer->getParameter('commsy.clamscan.virus_scan_path');
      $c_virus_scan_bin = $symfonyContainer->getParameter('commsy.clamscan.virus_scan_bin');

      $this->_path = $c_virus_scan_path;
      $this->_bin  = $c_virus_scan_bin;
      $this->_php  = $c_virus_use_php;
      $this->_environment = $environment;
      $this->_translator = $this->_environment->getTranslationObject();
   }

   function isClean($filename, $orig_filename='') {
      $ret_val = true;

      if ( !empty($filename) and file_exists($filename) ) {
         // call scanner on file
         if ( isset($this->_php) and !empty($this->_php) and $this->_php ) {
            // viren scanning with PHP - clamscan - lib
            if ( $virus_name = cl_scanfile($filename) ) {
               if ( !empty($orig_filename) ) {
                  $filename_text = $orig_filename;
               } else {
                  $filename_text = $filename;
               }
               if ( mb_strtoupper($virus_name, 'UTF-8') != 'OVERSIZED.ZIP' ) {
                  $this->_virus_name = $virus_name;
                  $this->_output = $this->_translator->getMessage('VIRUS_VIRUS_FOUND',$virus_name,$filename_text);
                  unlink($filename);
                  $ret_val = false;
               }
            }
         } elseif ( file_exists($this->_path."/".$this->_bin) ) {
            // viren scanning with shell command
            $output = shell_exec($this->_path."/".$this->_bin." ".escapeshellcmd($filename." | grep FOUND"));
            if (($output != '') and (mb_stristr($output,'FOUND'))) {
               // maybe its only the filename, so remove it from output
               $output = str_replace($filename.': ', "", $output);
               if ( mb_stristr($output,'FOUND')
                    and !mb_stristr($output,'Oversized.Zip')
                  ) {
                  // still a 'FOUND' in output?
                  $ret_val = false;
                  $virus_name = str_replace(' FOUND', "", $output);
                  $this->_virus_name = $virus_name;
                  if (!empty($orig_filename)) {
                     $filename_text = $orig_filename;
                  } else {
                     $filename_text = $filename;
                  }
                  $this->_output = $this->_translator->getMessage('VIRUS_VIRUS_FOUND',$virus_name,$filename_text);
                  unlink($filename);
               }
            }
         } else {
            $ret_val = false;
            $this->_output = $this->_translator->getMessage('VIRUS_SCANNER_NOT_FOUND',$this->_path."/".$this->_bin);
         }
      }
      return $ret_val;
   }

   function getOutput () {
      $retour = '';
      if (isset($this->_output)) {
         $retour = $this->_output;
      }
      return $retour;
   }

   function getVirusName () {
      $retour = '';
      if (isset($this->_virus_name)) {
         $retour = $this->_virus_name;
      }
      return $retour;
   }
}
?>