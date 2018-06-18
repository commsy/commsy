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

$this->includeClass(VIEW);
include_once('classes/cs_language.php');
include_once('functions/file_functions.php');
include_once('functions/misc_functions.php');
include_once('functions/curl_functions.php');

class cs_language_form_view extends cs_view {

   var $_title = NULL;
   var $_description = NULL;
   var $_text = NULL;
   var $_return_url= NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
   }

   function setAction ($value) {
      $this->_return_url = (string)$value;
   }

   function setTitle ($value) {
      $this->_title = (string)$value;
   }

   function setDescription ($value) {
      $this->_description = (string)$value;
   }

   function setText ($value) {
      $this->_text = (string)$value;
   }

function asHTML () {
   global $message, $_POST, $_GET, $translator;
   $lang = new cs_language('', $message);
   if (!isset($_POST['option']) and isset($_GET['MessageID'])) {
      $i=0;
      $MessageID = $_GET['MessageID'];
      foreach ($translator->getAvailableLanguages() as $item) {
         $languages[$i]['lang'] = $item;
         if ( !empty($message[$MessageID][$item]) ) {
            $languages[$i]['value'] = $message[$MessageID][$item];
         } else {
            $languages[$i]['value'] = '';
         }
         $i= $i+1;
      }
   } elseif(!isset($_POST['option'])and !isset($_GET['MessageID'])) {
      $i=0;
      foreach ($translator->getAvailableLanguages() as $item){
         $languages[$i]['lang'] = $item;
         $languages[$i]['value'] = '';
         $i=$i+1;
      }
      $MessageID="";
//      $MessageText = "Save speichert sowohl Änderungen als auch die Eingabe von neuen Messages mit deren Übersetzungen. Delete löscht die gerade angezeigte Message aus der Liste.";
   } elseif($_POST['option']== 'Go') {
      $MessageID=$_POST['MessageName'];
      $i=0;
      foreach ($translator->getAvailableLanguages() as $item){
         $languages[$i]['lang'] = $item;
         $languages[$i]['value'] = $message[$MessageID][$item];
         $i= $i+1;
      }
      //$MessageText = "Es wurde soeben folgende Message ausgewählt : ".$_POST['MessageName']." <br />. Sie können nun Änderungen vornehmen und mit Save speichern oder die Message ".$MessageName." mit Delete löschen.";
   } elseif($_POST['option']== 'Save') {
      $i=0;
      $MessageID=$_POST['MessageID'];
      foreach ($translator->getAvailableLanguages() as $item){
         $message[$MessageID][$item]= $_POST[$item];
         $languages[$i]['lang'] = $item;
         $languages[$i]['value'] = $_POST[$item];
         $i= $i+1;
      }
      ksort($message);
      reset($message);
      $lang->setLanguageSettings('',$message);
      #write2File($lang->getModifiedProperties(),'etc/cs_message.dat');
      $translator->setMessageArray($lang->getMessageArray());
      $translator->saveMessages();
//      header ("Location: ".$this->_return_url);
//      $MessageText = "Die neuen Übersetzungen wurden für die Message : ".$MessageID." wurden gespeichert.!";
   } elseif($_POST['option']== 'Delete') {
      $i=0;
      $lang->deleteMessage($_POST['MessageID']);
      #write2File($lang->getModifiedProperties(),'etc/cs_message.dat');
      $translator->setMessageArray($lang->getMessageArray());
      $translator->saveMessages();

//      $MessageText = "Die Message : ".$MessageID." wurde gelöscht.!";
      foreach ($translator->getAvailableLanguages() as $item){
         $languages[$i]['lang'] = $item;
         $languages[$i]['value'] = '';
         $i= $i+1;
      }
      $MessageID= '';
      #header ("Location: ".$this->_return_url);
   } elseif($_POST['option']== 'Add') {
      $i=0;
      $MessageID= '';
      $lang->addLanguage($_POST['newLanguage']);
      #write2File($lang->getModifiedProperties(),'etc/cs_message.dat');
      $translator->setMessageArray($lang->getMessageArray());
      $translator->saveMessages();
      foreach ($translator->getAvailableLanguages() as $item){
         $languages[$i]['lang'] = $item;
         $languages[$i]['value'] = '';
         $i= $i+1;
      }
      #header ("Location: ".$this->_return_url);
//      $MessageText = "Die Sprache : ".$newLanguage." wurde hinzugefügt!";
   } elseif ($_POST['option']== 'DeleteLang') {
      $i=0;
      $MessageID= '';
      $lang->deleteLanguage($_POST['newLanguage']);
      #write2File($lang->getModifiedProperties(),'etc/cs_message.dat');
      $translator->setMessageArray($lang->getMessageArray());
      $translator->saveMessages();
      foreach ($translator->getAvailableLanguages() as $item){
         $languages[$i]['lang'] = $item;
         $languages[$i]['value'] = '';
         $i= $i+1;
      }
      #header ("Location: ".$this->_return_url);
//      $MessageText = "Die Sprache : ".$newLanguage." wurde gelöscht!";
   }
      $html  = '';
      $html .= '<!-- BEGIN OF LANGUAGEMANAGERVIEW -->'."\n";
      $html .= '<table border="0" cellspacing="0" cellpadding="3" width="100%" summary="Layout">'."\n";
      if (!empty($this->_title)) {
         $html .= '   <tr><td class="tabletitle">'."\n";
         $html .= '      <b>'.$this->_title.'</b>'."\n";
         if (!empty($this->_description)) {
            $html .= '      <span class="small">('.$this->_description.')</span></td>'."\n";
         }
         $html .= '   <td class="tableactions">'.ahref_curl($this->_environment->getCurrentContextID(),'language','index','',$this->_translator->getMessage('MESSAGE_INDEX'),$this->_translator->getMessage('MESSAGE_INDEX_DESC'))."\n";
      }
      $html .= '   <tr>'."\n";
      $html .= '      <td class="detailsvalue" colspan="2">'.$this->_text.'</td>'."\n";
      $html .= '   </tr>'."\n";
      $html .= '</table>'."\n";
      $html .= '<center>'."\n";
      $html .= '<form enctype="multipart/form-data" method="post" action="'.$this->_return_url.'" name="f">'."\n";
      $html .= '<table border="0" cellpadding="2" cellspacing="4" summary="Layout">'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td valign="top" align="left" style="width:200px;">Vorhandene Einträge :<br />'."\n";
      $html .= '<select name="MessageName" style="width:400px; "size="20">'."\n";
      foreach ($message as $key => $value) {
         if ($key == $MessageID) {
            $html .= '<option value='.$key.' selected>'.$key.'</option>'."\n";
         } else {
            $html .= '<option value='.$key.'>'.$key.'</option>'."\n";
         }
      }
      $html .= '</select>'."\n";
      $html .= '<br />'."\n";
      $html .= 'Zum Editieren bitte eine MessageID auswählen und <br />'."\n";
      $html .= '<input type="submit" name="option" value="Go"/>'."\n";
      $html .= 'drücken.'."\n";
      $html .= '</td>'."\n";
      $html .= '<td style="width:600px;">'."\n";
      $html .= '<table border="0" cellpadding="2" cellspacing="2" width="100%" summary="Layout">'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td valign="top" align="left" style="width:200px;">MessageID :<br/>'."\n";
      $html .= '<input type="text" name="MessageID" maxlength="255" size="45" value="'.$MessageID.'"/>'."\n";
      $html .= '<br />(Message bearbeiten oder neue anlegen)<br/><br/></td>'."\n";
      $html .= '</tr>'."\n";
      foreach($languages as $item){
         $html .= '<tr>'."\n";
         $html .= '<td valign="top" align="left">'.$item['lang'].': <br/>'."\n";
         $html .= '<textarea name="'.$item['lang'].'" cols="36" rows="4">'.$item['value'].'</textarea>'."\n";
         $html .= '</td>'."\n";
         $html .= '</tr>'."\n";
      }
      $html .= '<tr>'."\n";
      $html .= '<td  width="100%">'."\n";
      $html .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" summary="Layout">'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td align="left" valign="top">'."\n";
      $html .= '<input type="submit" name="option" value="Save"/>'."\n";
      $html .= '<input type="reset" name="option" value="Reset"/>'."\n";
      $html .= '</td>'."\n";
      $html .= '<td align="right" valign="top">'."\n";
      $html .= '<input type="submit" name="option" value="Delete"/>'."\n";
      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '</table>'."\n";
      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td valign="top" align="left" style="width:200px; padding-top:50px;">Neue Sprache :<br/>'."\n";
      $html .= '<input type="text" name="newLanguage" maxlength="255" size="43" value=""/>'."\n";
      $html .= '<br />(es für beispielsweise Spanisch eingeben und Add drücken, falls die Sprache existiert und entfernt werden soll, bitte das Kürzel für die Sprache eingeben und DeleteLang drücken.)</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td width="100%">'."\n";
      $html .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" summary="Layout">'."\n";
      $html .= '<tr>'."\n";
      $html .= '<td align="left" valign="top">'."\n";
      $html .= '<input type="submit" name="option" value="Add"/>'."\n";
      $html .= '</td>'."\n";
      $html .= '<td align="right" valign="top">'."\n";
      $html .= '<input type="submit" name="option" value="DeleteLang"/>'."\n";
      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '</table>'."\n";
      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '</table>'."\n";
      $html .= '</td>'."\n";
      $html .= '<td>'."\n";

      $html .= '</td>'."\n";
      $html .= '</tr>'."\n";
      $html .= '</table>'."\n";
      $html .= '</form>'."\n";
      $html .= '</center>'."\n";

      $html .= '<!-- END OF LANGUAGEMANAGERVIEW -->'."\n\n";
      return $html;
   }
}
?>