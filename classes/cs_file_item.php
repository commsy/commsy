<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

include_once('functions/text_functions.php');
include_once('classes/cs_item.php');

class cs_file_item extends cs_item {

   /**
    * array - containing the data of this item, including lists of linked item;
    */
   // NOTE: this should go in the upper class cs_item
   var $_data = array();

   /**
    * array - array of boolean values. TRUE if key is changed
    */
   // NOTE: this should go in the upper class cs_item
   var $_changed = array();

   /**
    * define this->_mime types. Should be class constants, but PHP ...
    */
   var $_mime = array();

   /**
    * define this->_icon. Should be class constants, but PHP ...
    */
   var $_icon = array();

   private $_portal_id = NULL;
   private $_virus_name = '';

   /** constructor: cs_file_item
    * the only available constructor, initial values for internal variables
    */
   function cs_file_item ($environment) {

      // No icon yet ... (TBD) mj 20.03.03
      $this->_mime['tex']   = 'application/x-tex';
      $this->_mime['dvi']   = 'application/x-dvi';

      // Text
      $this->_icon['htm']     = "text.png";
      $this->_mime['htm']     = 'text/html';
      $this->_icon['html']    = "text.png";
      $this->_mime['html']    = 'text/html';
      $this->_icon['txt']     = "text.png";
      $this->_mime['txt']     = 'text/plain';
      $this->_icon['text']    = "text.png";
      $this->_mime['text']    = 'text/plain';
      $this->_icon['xml']     = "text.png";
      $this->_mime['xml']     = 'text/xml';
      $this->_icon['xsl']     = "text.png";
      $this->_mime['xsl']     = 'text/xml';

      // Pictures
      $this->_icon['jpg']     = "picture.png";
      $this->_mime['jpg']     = 'image/jpeg';
      $this->_icon['jpeg']    = "picture.png";
      $this->_mime['jpeg']    = 'image/jpeg';
      $this->_icon['gif']     = "picture.png";
      $this->_mime['gif']     = 'image/gif';
      $this->_icon['tif']     = "picture.png";
      $this->_mime['tif']     = 'image/tiff';
      $this->_icon['tiff']    = "picture.png";
      $this->_mime['tiff']    = 'image/tiff';
      $this->_icon['png']     = "picture.png";
      $this->_mime['png']     = 'image/png';
      $this->_icon['qt']      = "picture.gif";
      $this->_mime['qt']      = 'image/quicktime';
      $this->_icon['pict']    = "picture.png";
      $this->_mime['pict']    = 'image/pict';
      $this->_icon['psd']     = "picture.png";
      $this->_mime['psd']     = 'image/x-photoshop';
      $this->_icon['bmp']     = "picture.png";
      $this->_mime['bmp']     = 'image/bmp';
      $this->_icon['svg']     = "picture.png";
      // MISSING MIME-TYPE FOR SVG (TBD) mj 20.03.03

      // Archives
      $this->_icon['zip']     = "archive.png";
      $this->_mime['zip']     = 'application/x-zip-compressed';
      $this->_icon['tar']     = "archive.png";
      $this->_mime['tar']     = 'application/x-tar';
      $this->_icon['gz']      = "archive.png";
      $this->_mime['gz']      = 'application/x-compressed';
      $this->_icon['tgz']     = "archive.png";
      $this->_mime['tgz']     = 'application/x-compressed';
      $this->_icon['z']       = "archive.png";
      $this->_mime['z']       = 'application/x-compress';
      $this->_icon['hqx']     = "archive.png";
      $this->_mime['hqx']     = 'application/mac-binhex40';
      $this->_icon['sit']     = "archive.png";
      $this->_mime['sit']     = 'application/x-stuffit';

      // Audio
      $this->_icon['au']      = "sound.png";
      $this->_mime['au']      = 'audio/basic';
      $this->_icon['wav']     = "sound.png";
      $this->_mime['wav']     = 'audio/wav';
      $this->_icon['mp3']     = "sound.png";
      $this->_mime['mp3']     = 'audio/mpeg';
      $this->_icon['aif']     = "sound.png";
      $this->_mime['aif']     = 'audio/x-aiff';
      $this->_icon['aiff']    = "sound.png";
      $this->_mime['aiff']    = 'audio/x-aiff';

      // Video
      $this->_icon['avi']     = "movie.png";
      $this->_mime['avi']     = 'video/avi';
      $this->_icon['mov']     = "movie.png";
      $this->_mime['mov']     = 'video/quicktime';
      $this->_icon['moov']    = "movie.png";
      $this->_mime['moov']    = 'video/quicktime';
      $this->_icon['mpg']     = "movie.png";
      $this->_mime['mpg']     = 'video/mpeg';
      $this->_icon['mpeg']    = "movie.png";
      $this->_mime['mpeg']    = 'video/mpeg';
      $this->_icon['dif']     = "movie.png";
      $this->_mime['dif']     = 'video/x-dv';
      $this->_icon['dv']      = "movie.png";
      $this->_mime['dv']      = 'video/x-dv';
      $this->_icon['flv']     = "movie.png";
      // Missing MIME-type for Flash Video File (TBD) ij 14.07.06

      // Vendor-specific
      $this->_icon['pdf']     = "pdf.png";
      $this->_mime['pdf']     = 'application/pdf';
      $this->_icon['fdf']     = "pdf.png";
      $this->_mime['fdf']     = 'application/vnd.fdf';
      $this->_icon['doc']     = "doc.png";
      $this->_mime['doc']     = 'application/msword';
      $this->_icon['dot']     = "doc.png";
      $this->_mime['dot']     = 'application/msword';
      $this->_icon['rtf']     = "doc.png";
      $this->_mime['rtf']     = 'application/rtf';
      $this->_icon['ppt']     = "ppt.png";
      //Lassi-Dateien
      $this->_icon['lsi']     = "lassi_commsy.png";
      // open office
      $this->_mime['odf']     = 'application/smath';
      $this->_icon['odf']     = "ooo_formula_commsy.png";
      $this->_mime['odg']     = 'application/sdraw';
      $this->_icon['odg']     = "ooo_draw_commsy.png";
      $this->_mime['ods']     = 'application/scalc';
      $this->_icon['ods']     = "ooo_calc_commsy.png";
      //$this->_mime['odb']     = 'application/sbase';
      //$this->_icon['odb']     = "ooo_base_commsy.png";
      $this->_mime['odp']     = 'application/simpress';
      $this->_icon['odp']     = "ooo_impress_commsy.png";
      $this->_mime['odt']     = 'application/swriter';
      $this->_icon['odt']     = "ooo_writer_commsy.png";

      // Missing MIME-type for PowerPoint (TBD) mj 20.03.03
      $this->_icon['xls']     = "xls.png";
      // Missing MIME-type for Excel (TBD) mj 20.03.03

      // Flash / Shockwave
      $this->_icon['swf']      = "movie.png";
      $this->_mime['swf']      = 'application/x-shockwave-flash';


      $this->_icon['unknown'] = "unknown.png";

      $this->cs_item($environment);
      $this->_type = 'file';

   }

   function isOnDisk() {
      $disc_manager = $this->_environment->getDiscManager();
      $disc_manager->setContextID($this->getContextID());
      $portal_id = $this->getPortalID();
      if ( isset($portal_id) and !empty($portal_id) ) {
         $disc_manager->setPortalID($portal_id);
      } else {
         $context_item = $this->getContextItem();
         if ( isset($context_item) ) {
            $portal_item = $context_item->getContextItem();
            if ( isset($portal_item) ) {
               $disc_manager->setPortalID($portal_item->getItemID());
               unset($portal_item);
            }
            unset($context_item);
         }
      }
      $retour = $disc_manager->existsFile($this->getDiskFilenameWithoutFolder());
      $disc_manager->setContextID($this->_environment->getCurrentContextID());
      return $retour;
   }

   /* There was a bug in CommSy so context ID of an item were not
      saved correctly. This method is a workaround for file item db entries
      with context_id of 0. */
   function getContextID () {
      $context_id = parent::getContextID();
      if ( $context_id == 0 ) {
         $context_id = $this->_environment->getCurrentContextID();
      }
      return (int) $context_id;
   }

   function setPortalID ($value) {
      $this->_portal_id = (int)$value;
   }

   function getPortalID () {
      return $this->_portal_id;
   }

   function setPostFile($post_data) {
      $this->setTempName($post_data["tmp_name"]);
      $filename = rawurlencode(rawurldecode(basename($post_data["name"])));
      $filename = str_replace('%20','_',$filename);
      $this->setFileName($filename);
   }

   /** set file_id of the file
    * this method sets the file_id of the file
    *
    * @param integer value file_id of the file
    */
   function setFileID($value) {
      $this->_data['files_id'] = $value;
   }

   /** get file_id of the file
    * this method returns the file_id of the file
    *
    * @return integer file_id of the file
    */
   function getFileID () {
      return $this->_getValue('files_id');
   }

   public function getTitle () {
      return $this->getFileName();
   }

   function setFileName($value) {
      $this->_setValue('filename', $value);
   }

   function getFileName() {
      return $this->_getValue('filename');
   }

   function getDisplayName() {
      return rawurldecode($this->_getValue('filename'));
   }

   function setTempName($value) {
      $this->_data['tmp_name'] = $value;
   }

   function _getTempName() {
      return $this->_getValue('tmp_name');
   }

   function getMime() {
      $extension = cs_strtolower(substr(strrchr($this->getDisplayName(),'.'),1));
      return empty($this->_mime[$extension]) ? 'application/octetstream' : $this->_mime[$extension];
   }

   function getExtension () {
      return cs_strtolower(substr(strrchr($this->getDisplayName(),'.'),1));
   }

   function getUrl () {
      $params = array();
      $params['iid'] = $this->_data['files_id'];
      return curl($this->getContextID(),'material', 'getfile', $params,'',$this->_data['filename'],'commsy.php');
   }

   function getFileSize() {
      if ( $this->isOnDisk($this->getDiskFileName()) ) {
          if ($this->_getValue('size') > 0) {
         return round((($this->_getValue('size')+1023)/1024), 0);
          } else {
       $this->_data['size'] = filesize($this->getDiskFileName());
             return round((($this->_getValue('size')+1023)/1024), 0);
          }
      } else {
         return 0;
      }
   }

   function getFileIcon($title_of_image = '' ) {
      $ext = cs_strtolower(substr(strrchr($this->getFileName(),'.'),1));
      $img = '<img src="images/';
      if ( !empty($this->_icon[$ext]) ) {
         $img .= $this->_icon[$ext];
      } else {
         $img .= $this->_icon['unknown'];
      }

      $ftsearch_manager = $this->_environment->getFTSearchManager();
      // are we in search status? - set by cs_manager -> initFTSearch()
      if ($ftsearch_manager->getSearchStatus()) {
         // get fids from cs_ftsearch_manager
         $ft_file_ids = $ftsearch_manager->getFileIDs();
         if ( !empty($ft_file_ids) and in_array($this->getFileID(),$ft_file_ids) ) {
            $img = str_replace('.','_found.',$img);
         }
         unset($ft_file_ids);
      }
      unset($ftsearch_manager);
      $img .= '" style="border:0;" align="baseline"';

      if (!empty($title_of_image)) {
         $img .= ' title="'.$title_of_image.'"';
      }
      $img .= ' alt="'.$title_of_image.'"/>';
      return $img;
   }

   function getIconFilename() {
      $ext = cs_strtolower(substr(strrchr($this->getFileName(),'.'),1));
     if ( !empty($this->_icon[$ext]) ) {
         $img = $this->_icon[$ext];
      } else {
         $img = $this->_icon['unknown'];
      }
     return $img;
    }

   function getDiskFileName () {
      $disc_manager = $this->_environment->getDiscManager();
      $disc_manager->setContextID($this->getContextID());
      $portal_id = $this->getPortalID();
      if ( isset($portal_id) and !empty($portal_id) ) {
         $disc_manager->setPortalID($portal_id);
      } else {
         $context_item = $this->getContextItem();
         if ( isset($context_item) ) {
            $portal_item = $context_item->getContextItem();
            if ( isset($portal_item) ) {
               $disc_manager->setPortalID($portal_item->getItemID());
               unset($portal_item);
            }
            unset($context_item);
         }
      }
      $retour = $disc_manager->getFilePath().'cid'.$this->getContextID().'_'.$this->getFileID().'_'.$this->getFileName();
      $disc_manager->setContextID($this->_environment->getCurrentContextID());
      return $retour;
   }

    function getDiskFileNameWithoutFolder() {
      return 'cid'.$this->getContextID().'_'.$this->getFileID().'_'.$this->getFileName();
   }

   function save() {
      $saved = false;
      $manager = $this->_environment->getFileManager();
      $saved = $this->_save($manager);
      return $saved;
   }

   function saveHasHTML() {
      $saved = false;
      $manager = $this->_environment->getFileManager();
      $saved = $manager->updateHasHTML($this);
      return $saved;
   }

   function saveExtras() {
      $saved = false;
      $manager = $this->_environment->getFileManager();
      $saved = $manager->updateExtras($this);
      return $saved;
   }

   public function getDataAsXML ($with_file_data = false) {
      $retour  = '<file_item>'.LF;
      $retour .= $this->_getDataAsXML();
      if ($with_file_data) {
         $retour .= '<base64>'.$this->_getFileAsBase64().'</base64>'.LF;
      } else {
         $session_item = $this->_environment->getSessionItem();
         $file_md5_array = $session_item->getValue('file_md5_array');
         if ( isset($file_md5_array[$this->getFileID()]) and !empty($file_md5_array[$this->getFileID()]) ) {
            $retour .= '<md5>'.$file_md5_array[$this->getFileID()].'</md5>'.LF;
         } else {
            $md5 = md5($this->_getFileAsString());
            $retour .= '<md5>'.$md5.'</md5>'.LF;
            $file_md5_array[$this->getFileID()] = $md5;
            $session_item->setValue('file_md5_array',$file_md5_array);
            $session_manager = $this->_environment->getSessionManager();
            $session_manager->save($session_item);
            $this->_environment->setSessionItem($session_item);
         }
         $params = array();
         $params['iid'] = $this->getFileID();
         $params['SID'] = $session_item->getSessionID();
         global $c_commsy_domain,$c_commsy_url_path;
         include_once('functions/curl_functions.php');
         $url = _curl(false,$this->getContextID(),'material','getfile',$params);
         $url = str_replace('soap.php','commsy.php',$url);
         $retour .= '<resource_link><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'.$url.']]></resource_link>'.LF;
      }
      $retour .= '</file_item>'.LF;
      return $retour;
   }

   private function _getFileAsString () {
      $retour = '';
      $disc_manager = $this->_environment->getDiscManager();
      $portal_id = $this->getPortalID();
      if ( isset($portal_id) and !empty($portal_id) ) {
         $disc_manager->setPortalID($portal_id);
      }
      $retour = $disc_manager->getFileAsString($this->getDiskFileName());
      return $retour;
   }

   private function _getFileAsBase64 () {
      $retour = '';
      $disc_manager = $this->_environment->getDiscManager();
      $portal_id = $this->getPortalID();
      if ( isset($portal_id) and !empty($portal_id) ) {
         $disc_manager->setPortalID($portal_id);
      }
      $retour = $disc_manager->getFileAsBase64($this->getDiskFileName());
      return $retour;
   }

   protected function _getDataAsXML () {
      $retour = '';
      foreach ($this->_data as $key => $value) {
         if ($key == 'filename') {
            $retour .= '<'.$key.'>'.rawurldecode($value).'</'.$key.'>';
         } else {
            $retour .= '<'.$key.'>'.$value.'</'.$key.'>';
         }
      }
      return $retour;
   }

   public function getHasHTML() {
      return $this->_getValue('has_html');
   }

   public function setHasHTML($value) {
      $this->_data['has_html'] = (int)$value;
   }

   public function deleteReally () {
      $manager = $this->_environment->getFileManager();
      $manager->deleteReally($this);
   }

   ##################################################
   # virus scanning
   ##################################################

   public function isScanned () {
      $retour = false;
      if ( $this->_getValue('scan') == 1 ) {
         $retour = true;
      }
      return $retour;
   }

   public function setScanned () {
      $this->_data['scan'] = 1;
   }

   public function updateScanned () {
      $this->setScanned();
      $saved = false;
      $manager = $this->_environment->getFileManager();
      $saved = $manager->updateScanned($this);
      unset($manager);
      return $saved;
   }

   public function getScanValue () {
      $retour = -1;
      $temp = $this->_getValue('scan');
      if ( !empty($temp) ) {
         $retour = $temp;
      }
      return $retour;
   }

   public function hasVirus () {
      $retour = false;
      if ($this->isOnDisk()) {
         include_once('classes/cs_virus_scan.php');
         $virus_scanner = new cs_virus_scan($this->_environment);
         if (!$virus_scanner->isClean($this->getDiskFileName())) {
            $this->_virus_name = $virus_scanner->getVirusName();
            if ( !empty($this->_virus_name) ) {
               $retour = true;
            }
         }
         unset($virus_scanner);
      }
      return $retour;
   }

   public function getVirusName () {
      return $this->_virus_name;
   }

   function setScribdDocId($value) {
      $this->_setExtra('SCRIBD_DOC_ID', (string)$value);
   }
   function getScribdDocId() {
      return (string) $this->_getExtra('SCRIBD_DOC_ID');
   }

   function setScribdAccessKey($value) {
      $this->_setExtra('SCRIBD_ACCESS_KEY', (string)$value);
   }
   function getScribdAccessKey() {
      return (string) $this->_getExtra('SCRIBD_ACCESS_KEY');
   }
}
?>