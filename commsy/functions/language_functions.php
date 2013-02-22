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

/** gets the array which contains the messages
 */
include_once('functions/text_functions.php');

/** get a text of a message in a particular language
 * this method returns the translated MessageID
 *
 * @param string MsgID           The MessageID, which should be translated
 * @param string language        The particular language
 * @param string param1          The string %1 in the translated text is replaced by param1
 * @param string param2          see param1
 * @param string param3          see param1
 * @param string param4          see param1
 *
 * @return string the translation of MsgID
 * @author CommSy Development Group
 */
function getMessageInLang($language, $MsgID, $param1='', $param2='', $param3='', $param4='', $param5='') {
   global $environment;
	echo "<!-- Use of deprecated function: getMessageInLang -->";
   $translator = $environment->getTranslationObject();
   $text = $translator->getMessageInLang($language,$MsgID,$param1,$param2,$param3,$param4,$param5);
   return $text;
}

/** get the translation of the messageID in the right language
 * this method returns the translation of the messageID in the right language
 *
 *
 * @return string the translated text
 * @author CommSy Development Group
 */
function getMessage($MsgID, $param1='', $param2='', $param3='', $param4='', $param5='') {
   global $environment;
   $translator = $environment->getTranslationObject();
   return $translator->getMessage($MsgID,$param1,$param2,$param3,$param4,$param5);
}

// can be deleted after merge
function getSelectedLanguage () {
   global $environment;
   return $environment->getSelectedLanguage();
}
?>