<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

use Doctrine\Common\Collections;

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
   function __construct($environment) {

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
      $this->_icon['mp4']	   = "movie.png";
      $this->_mime['mp4']	   = "video/mp4";
      $this->_icon['avi']     = "movie.png";
      $this->_mime['avi']     = 'video/x-msvideo';
      $this->_icon['mov']     = "movie.png";
      $this->_mime['mov']     = 'video/quicktime';
      $this->_icon['moov']    = "movie.png";
      $this->_mime['moov']    = 'video/quicktime';
      $this->_icon['m4v']     = "movie.png";
      $this->_mime['m4v']     = 'video/quicktime';
      $this->_icon['mpg']     = "movie.png";
      $this->_mime['mpg']     = 'video/mpeg';
      $this->_icon['mpeg']    = "movie.png";
      $this->_mime['mpeg']    = 'video/mpeg';
      $this->_icon['dif']     = "movie.png";
      $this->_mime['dif']     = 'video/x-dv';
      $this->_icon['dv']      = "movie.png";
      $this->_mime['dv']      = 'video/x-dv';
      $this->_icon['flv']     = "movie.png";
      $this->_mime['flv']     = "video/flv"; // flv type for projekktor
      // Missing MIME-type for Flash Video File (TBD) ij 14.07.06

      // Vendor-specific
      // MIME types for Microsoft Office formats according to https://blogs.msdn.microsoft.com/vsofficedeveloper/2008/05/08/office-2007-file-format-mime-types-for-http-content-streaming-2/
      $this->_icon['pdf']     = "pdf.png";
      $this->_mime['pdf']     = 'application/pdf';
      $this->_icon['fdf']     = "pdf.png";
      $this->_mime['fdf']     = 'application/vnd.fdf';
      $this->_icon['doc']     = "doc.png";
      $this->_mime['doc']     = 'application/msword';
      $this->_icon['docx']    = "doc.png";
      $this->_mime['docx']    = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
      $this->_icon['dot']     = "doc.png";
      $this->_mime['dot']     = 'application/msword';
      $this->_icon['dotx']    = "doc.png";
      $this->_mime['dotx']    = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
      $this->_icon['rtf']     = "doc.png";
      $this->_mime['rtf']     = 'application/rtf';

      //Lassi-Dateien
      $this->_icon['lsi']     = "lassi_commsy.png";

      //smartboard
      $this->_icon['notebook']     = "notebook.png";
      $this->_mime['notebook']     = 'application/x-smarttech-notebook';

      $this->_icon['gallery']      = "notebook.png";

      //promethean
      $this->_icon['flp']     = "flipchart.png";
      $this->_mime['flp']     = 'application/x-asstudio';


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

      $this->_icon['pot']     = "ppt.png";
      $this->_mime['pot']     = 'application/vnd.ms-powerpoint';
      $this->_icon['potx']    = "ppt.png";
      $this->_mime['potx']    = 'application/vnd.openxmlformats-officedocument.presentationml.template';
      $this->_icon['pps']     = "ppt.png";
      $this->_mime['pps']     = 'application/vnd.ms-powerpoint';
      $this->_icon['ppsx']    = "ppt.png";
      $this->_mime['ppsx']    = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
      $this->_icon['ppt']     = "ppt.png";
      $this->_mime['ppt']     = 'application/vnd.ms-powerpoint';
      $this->_icon['pptx']    = "ppt.png";
      $this->_mime['pptx']    = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

      $this->_icon['xls']     = "xls.png";
      $this->_mime['xls']     = 'application/vnd.ms-excel';
      $this->_icon['xlsx']    = "xls.png";
      $this->_mime['xlsx']    = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      $this->_icon['xlt']     = "xls.png";
      $this->_mime['xlt']     = 'application/vnd.ms-excel';
      $this->_icon['xltx']    = "xls.png";
      $this->_mime['xltx']    = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';

      $this->_icon['vsd']      = "visio.png";
      $this->_mime['vsd']      = 'application/x-visio';


      // Flash / Shockwave
      $this->_icon['swf']      = "movie.png";
      $this->_mime['swf']      = 'application/x-shockwave-flash';

      // Consideo Modeler
      $this->_icon['cons']      = "consideo.png";
      $this->_mime['cons']      = 'application/consideo';

      // GeoGebra
      $this->_icon['ggb']      = "geogebra.png";
      $this->_mime['ggb']      = 'application/geogebra';

      // Scratch
      $this->_icon['sb']      = "scratch.png";
      $this->_mime['sb']      = 'application/scratch';

      $this->_icon['unknown'] = "unknown.png";

      cs_item::__construct($environment);
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
      $filename = rawurldecode(basename(rawurlencode($post_data["name"])));
      $filename = str_replace(' ','_',$filename);
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

   public function setFilePath($value)
   {
      $this->_setValue('filepath', $value);
   }

   public function getFilePath()
   {
      return $this->_getValue('filepath');
   }

   function getDisplayName() {
      $temp_display_name = rawurldecode($this->_getValue('filename'));
      include_once('functions/text_functions.php');
      return cs_utf8_encode($temp_display_name);
   }

   function setTempKey ($value) {
      $this->_setExtra('TEMP_KEY', (string)$value);
   }

   function setTempName($value) {
      $this->_data['tmp_name'] = $value;
   }

   function _getTempName() {
      return $this->_getValue('tmp_name');
   }

   function getMime() {
      $extension = cs_strtolower(mb_substr(strrchr($this->getDisplayName(),'.'),1));
      return empty($this->_mime[$extension]) ? 'application/octetstream' : $this->_mime[$extension];
   }

   function getExtension () {
      $display_name = $this->getDisplayName();
      if ( !empty($display_name) ) {
         return cs_strtolower(mb_substr(strrchr($display_name,'.'),1));
      }
   }

   function getUrl () {
      $params = array();
      $params['iid'] = $this->_data['files_id'];
      global $c_single_entry_point;
      return curl($this->getContextID(),'material', 'getfile', $params,'',$this->_data['filename'],$c_single_entry_point);
   }

    public function getFileSize()
    {
        if (!$this->isOnDisk()) {
            return 0;
        }

        if ($this->_getValue('size') == 0) {
            $diskFileName = $this->getDiskFileName();
            $filesize = filesize($diskFileName);
            $this->_data['size'] = $filesize ?: 0 ;
        }

        return round((($this->_getValue('size') + 1023) / 1024), 0);
    }

   function getFileIcon($title_of_image = '' ) {
      $ext = cs_strtolower(mb_substr(strrchr($this->getFileName(),'.'),1));
      $img = '<img src="images/';
      if ( !empty($this->_icon[$ext]) ) {
         $img .= $this->_icon[$ext];
      } else {
         $img .= $this->_icon['unknown'];
      }

      $ft_file_ids = array();

      // are we in search status? - set by cs_manager -> initFTSearch()
      $ftsearch_manager = $this->_environment->getFTSearchManager();
      if ($ftsearch_manager->getSearchStatus()) {
         // get fids from cs_ftsearch_manager
         $ft_file_ids = $ftsearch_manager->getFileIDs();
      }
      unset($ftsearch_manager);

      // are we in search status? - set by session
      $session = $this->_environment->getSessionItem();
      if(isset($session)){
	      if ($session->issetValue('cid'.$this->_environment->getCurrentContextID().'_campus_search_parameter_array')) {
	         $search_array = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_campus_search_parameter_array');
	         if ( !empty($search_array['file_id_array']) ) {
	            $ft_file_ids = $search_array['file_id_array'];
	         }
	      }
      }

      if ( !empty($ft_file_ids) and in_array($this->getFileID(),$ft_file_ids) ) {
         $img = str_replace('.','_found.',$img);
      }
      unset($ft_file_ids);
      $img .= '" style="border:0;" align="baseline"';

      if (!empty($title_of_image)) {
         $img .= ' title="'.$title_of_image.'"';
      }
      $img .= ' alt="'.$title_of_image.'"/>';
      return $img;
   }

   function getIconFilename() {
      $ext = cs_strtolower(mb_substr(strrchr($this->getFileName(),'.'),1));
      if ( !empty($this->_icon[$ext]) ) {
         $img = $this->_icon[$ext];
      } else {
         $img = $this->_icon['unknown'];
      }
      return $img;
   }

   public function getIconUrl () {
      global $c_commsy_domain;
      global $c_commsy_url_path;
      $retour = $c_commsy_domain.$c_commsy_url_path.'/images/'.$this->getIconFilename();
      return $retour;
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
      $retour = $disc_manager->getFilePath().$disc_manager->getCurrentFileName($this->getContextID(), $this->getFileID(), $this->getFileName(), $this->getExtension());
      $disc_manager->setContextID($this->_environment->getCurrentContextID());
      return $retour;
   }

    function getDiskFileNameWithoutFolder() {
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
      $retour = $disc_manager->getCurrentFileName($this->getContextID(), $this->getFileID(), $this->getFileName(), $this->getExtension());
      $disc_manager->setContextID($this->_environment->getCurrentContextID());
      return $retour;
   }

   function save() {
      $saved = false;
      $manager = $this->_environment->getFileManager();
      $saved = $this->_save($manager);
      return $saved;
   }

   function update() {
      $saved = false;
      $manager = $this->_environment->getFileManager();
      $saved = $manager->updateItem($this);
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
         global $c_single_entry_point;
         $url = str_replace('soap.php',$c_single_entry_point,$url);
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

   public function getBase64 () {
      return $this->_getFileAsBase64();
   }

   public function getString () {
      return $this->_getFileAsString();
   }

   public function _getDataAsXML () {
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

   /** Get the linked items of the file.
    *
    * @return Collections\ArrayCollection an array collection of \cs_item objects that are linked to this file
    */
   public function getLinkedItems(): Collections\ArrayCollection
   {
      // get a list of \cs_link_item_file objects
      $linkItemManager = $this->_environment->getLinkItemFileManager();
      $linkItemManager->resetLimits();
      $linkItemManager->setFileIDLimit($this->getFileID());
      $linkItemManager->select();
      /** @var \cs_list $linkItemList */
      $linkItemList = $linkItemManager->get();

      // assemble array collection of corresponding \cs_item objects
      $itemCollection = new Collections\ArrayCollection();
      if (isset($linkItemList) and $linkItemList->isNotEmpty()) {
         $linkItem = $linkItemList->getFirst();
         while ($linkItem) {
            $linkedItem = $linkItem->getLinkedItem();
            if ($linkedItem) {
               $itemCollection->add($linkedItem);
               $linkItem = $linkItemList->getNext();
            }
         }
      }

      return $itemCollection;
   }

   function _delete($manager) {
      $manager->delete($this->getFileID());
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

   /**
    * Returns true if the user represented by the given user item is allowed to edit the file,
    * otherwise returns false.
    *
    * @param \cs_user_item $user_item
    * @return bool
    */
   public function mayEdit(\cs_user_item $user_item) {
      $access = false;
      if ( !$user_item->isOnlyReadUser() ) {
         if ( $user_item->isRoot() or
              ( $user_item->getContextID() == $this->getContextID()
                and ( $user_item->isModerator()
                      or ( $user_item->isUser()
                           and ( $user_item->getItemID() == $this->getCreatorID()
                                 or $this->mayEditLinkedItem($user_item)
                               )
                         )
                    )
              )
            ) {
            $access = true;
         }
      }
      return $access;
   }

   /**
    * Returns true if the user represented by the given user item is allowed to edit any of 
    * the file's linked items, otherwise returns false.
    *
    * @param \cs_user_item $userItem
    * @return bool
    */
   public function mayEditLinkedItem(\cs_user_item $userItem): bool
   {
      $itemCollection = $this->getLinkedItems();
      if (!isset($itemCollection) or $itemCollection->isEmpty()) {
         return false;
      }

      foreach ($itemCollection as $item) {
         if ($item->mayEdit($userItem)) {
            return true;
         }
      }

      return false;
   }

    /**
     * Returns true if the user represented by the given user item is allowed to see the file,
     * otherwise returns false.
     *
     * @param \cs_user_item $userItem
     * @return bool
     */
    public function maySee($userItem)
    {
        // a user who's allowed to see any of this file's linked items may also see this file
        return $this->maySeeLinkedItem($userItem);
    }

   /**
    * Returns true if the user represented by the given user item is allowed to see any of 
    * the file's linked items, otherwise returns false.
    *
    * @param \cs_user_item $userItem
    * @return bool
    */
   public function maySeeLinkedItem(\cs_user_item $userItem): bool
   {
      $itemCollection = $this->getLinkedItems();
      if (!isset($itemCollection) or $itemCollection->isEmpty()) {
         return false;
      }

      foreach ($itemCollection as $item) {
         if (!$item->getHasOverwrittenContent() && $item->maySee($userItem)) {
            return true;
         }
      }

      return false;
   }

   public function mayPortfolioSeeLinkedItem(\cs_user_item $userItem)
   {
       $itemCollection = $this->getLinkedItems();
       if (!isset($itemCollection) or $itemCollection->isEmpty()) {
           return false;
       }

       foreach ($itemCollection as $item) {
           if ($item->mayPortfolioSee($userItem)) {
               return true;
           }
       }

       return false;
   }

    public function isImage () {
      $retour = false;
      $mime = $this->getMime();
      if ( mb_stristr($mime,'image') ) {
         $retour = true;
      }
      return $retour;
   }

   function setTempUploadFromEditorSessionID ($value) {
      $this->_setValue('temp_upload_session_id', $value);
   }

   function getTempUploadFromEditorSessionID(){
      return $this->_getValue('temp_upload_session_id');
   }

   function setWordpressPostId($value) {
      $this->_setExtra('WORDPRESS_POST_ID', (string)$value);
   }
   function getWordpressPostId() {
      return (string) $this->_getExtra('WORDPRESS_POST_ID');
   }

    /**
     * Get file content base64 encoded
     *
     * @return string (base64)
     */
    public function getContentBase64()
    {
        global $symfonyContainer;
        $projectDir = $symfonyContainer->get('kernel')->getProjectDir();

        $filePath = $projectDir . '/' . $this->getFilepath();

        if (file_exists($filePath)) {
            return base64_encode(file_get_contents(
                $filePath,
                'r'
            ));
        } else {
            return null;
        }
    }
}