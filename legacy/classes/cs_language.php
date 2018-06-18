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

/**
 * class for language management
 *
 */

class cs_language {

   /**
     * Standard Constructor for cs_language_manager
     */
   function __construct($defaultLanguage, $messages) {
      if (!empty($defaultLanguage)) {
         $this->_defaultLanguage = $defaultLanguage;
      }
      $this->_messages = $messages;
   }

    /**
     *  Function addLanguage adds a new language
     *
     *  Function addLanguage adds a new language as a key to every messageid in the array in the file message.dat
     *
     * @param $newLanguage     the language which should be added
     */
   function addLanguage($newLanguage){
      $messageKeys = array_keys($this->_messages);
      foreach($messageKeys as $item){
        $this->_messages[$item][$newLanguage]= '';
      }
//      $this->setLanguageSettings($this->_defaultLanguage,$this->_messages);
   }

    /** Function deleteLanguage deletes a new language
     *
     * deletes a new language from the array in the file
     *                           message.dat
     *
     * @param $language          the language which should be deleted
     */
   function deleteLanguage($language){
      foreach($this->_messages as $key => $value){
         $key_index = array_keys(array_keys($value), $language);
         array_splice($value, $key_index[0], 1);
         foreach($value as $lang => $trans){
            $newMessageArray[$key][$lang]= $trans;
         }
      }
      $this->_messages = $newMessageArray;
   }

    /** Function deleteLanguage deletes a messageid
     *
     * Function deleteLanguage deletes a messageid from the array in the file
     *                           message.dat
     *
     * @param $MessageID          the messageid which should be deleted
     */
   function deleteMessage($MessageID){
      $key_index = array_keys(array_keys($this->_messages), $MessageID);
      array_splice($this->_messages, $key_index[0], 1);
   }

   /** Function setLanguageSettings
    *
    * sets the settings of the language and the message array
    *
    * @param $language
    * @param $messageArray
    */
   function setLanguageSettings($language,$messageArray){
      $this->_language = $language;
      $this->_messages = $messageArray;
      ksort($this->_messages);
      reset($this->_messages);
   }

   /** Function getModifiedProperties
    *
    *  puts together the new array of message for message.dat
    *  is used by function writeMessageFile()
    *
    * @return $this->_messageText     includes the new array for message.dat
    */
   function getModifiedProperties(){
      ksort($this->_messages);
      reset($this->_messages);
      $this->_messageText="<?php\n";
      foreach($this->_messages as $key => $value){
         foreach($value as $key2 => $value2){
               $this->_messageText .= "\$message['".$key."']['".$key2."'] = '".$value2."';\n";
          }
       }
     $this->_messageText .= "?>";
     return  $this->_messageText;
   }

   function getMessageArray () {
      ksort($this->_messages);
      reset($this->_messages);
      return $this->_messages;
   }

// End of class, do not remove
}
?>