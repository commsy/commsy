<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** gets the array which contains the messages.
 */
include_once 'functions/text_functions.php';

/** get a text of a message in a particular language
 * this method returns the translated MessageID.
 *
 * @param string MsgID           The MessageID, which should be translated
 * @param string language        The particular language
 * @param string param1          The string %1 in the translated text is replaced by param1
 * @param string param2          see param1
 * @param string param3          see param1
 * @param string param4          see param1
 *
 * @return string the translation of MsgID
 *
 * @author CommSy Development Group
 */
function getMessageInLang($language, $MsgID, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '')
{
    global $environment;
    echo '<!-- Use of deprecated function: getMessageInLang -->';
    $translator = $environment->getTranslationObject();
    $text = $translator->getMessageInLang($language, $MsgID, $param1, $param2, $param3, $param4, $param5);

    return $text;
}

/** get the translation of the messageID in the right language
 * this method returns the translation of the messageID in the right language.
 *
 * @return string the translated text
 *
 * @author CommSy Development Group
 */
function getMessage($MsgID, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '')
{
    global $environment;
    $translator = $environment->getTranslationObject();

    return $translator->getMessage($MsgID, $param1, $param2, $param3, $param4, $param5);
}

// can be deleted after merge
function getSelectedLanguage()
{
    global $environment;

    return $environment->getSelectedLanguage();
}
