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

use function Symfony\Component\String\u;

/** class for authentication items
 * this class implements authentication items.
 */
class cs_translator
{
    private array $messageArray = [];
    private array $timeMessageArray = [];

    private ?array $availableLanguages = null;

    /**
     * containing the path to the message.dats.
     */
    private string $_file_path = 'etc/messages/';

    /**
     * containing the selected language.
     */
    private string $_selected_language = '';

    /**
     * containing the selected language.
     */
    private string $_session_language = '';

    /**
     * containing the special rubric names, get from current room.
     */
    private array $_rubric_translation_array = [];

    /**
     * containing the special email texts, get from current room.
     */
    private array $_email_array = [];

    /**
     * containing the loaded message.dats to delete while saving.
     */
    private array $_loaded_message_dats = [];

    /**
     * containing the context: community or project or portal.
     */
    private ?string $_context = null;

    /**
     * containing the default language / default = de (german).
     */
    private string $_default_language = 'de';

    private array $_dat_folder_array = [];

    /** constructor
     * the only available constructor, initial values for internal variables.
     */
    public function __construct()
    {
        $this->_file_path = realpath(__DIR__).'/../'.$this->_file_path;
    }

    /** _loadAllMessages - INTERNAL
     * this methode loads all message.dats from commsy -> for language edit.
     */
    public function _loadAllMessages()
    {
        $directory = dir($this->_file_path);
        while ($entry = $directory->read()) {
            if (1 == mb_strpos($entry, 's_') and mb_strpos($entry, '.dat') and 'm' == $entry[0]) {
                if (file_exists($this->_file_path.$entry)) {
                    include_once $this->_file_path.$entry;
                    $this->_loaded_message_dats[] = $entry;
                    if (!empty($message)) {
                        $message = encode(FROM_FILE, $message);
                        $this->messageArray = multi_array_merge($this->messageArray, $message);
                        unset($message);
                    }
                }
            }
        }
    }

    /** _loadMessages - INTERNAL
     * this methode loads ms_$rubric_$language.dat.
     *
     * @param string $rubric   to load (first word of message tag)
     * @param string $language to load (de,en,...), if is empty -> all languages will be loaded
     */
    public function _loadMessages($rubric, $language)
    {
        $message = [];
        if (!empty($language)) {
            $entry = 'ms_'.$rubric.'_'.$language.'.dat';
            if (file_exists($this->_file_path.$entry)) {
                include_once $this->_file_path.$entry;
                $this->_loaded_message_dats[] = $entry;
                if (!empty($message)) {
                    $message = encode(FROM_FILE, $message);
                    $this->messageArray = multi_array_merge($this->messageArray, $message);
                    unset($message);
                }
            } else {
                foreach ($this->_dat_folder_array as $folder) {
                    if (file_exists($folder.'/'.$entry)) {
                        include_once $folder.'/'.$entry;
                        $this->_loaded_message_dats[] = $entry;
                        if (!empty($message)) {
                            $message = encode(FROM_FILE, $message);
                            $this->messageArray = multi_array_merge($this->messageArray, $message);
                            unset($message);
                        }
                        break;
                    }
                }
            }
        } else {
            $directory = dir($this->_file_path);
            while ($entry = $directory->read()) {
                if (mb_stristr($entry, $rubric)) {
                    if (file_exists($this->_file_path.$entry)) {
                        include_once $this->_file_path.$entry;
                        $this->_loaded_message_dats[] = $entry;
                        if (!empty($message)) {
                            $message = encode(FROM_FILE, $message);
                            $this->messageArray = multi_array_merge($this->messageArray, $message);
                            unset($message);
                        }
                    }
                }
            }
        }
    }

    /** saveMessages
     * save stored messages to the message.dats.
     */
    public function saveMessages()
    {
        $lang_array = [];
        foreach ($this->messageArray as $key => $value) {
            $rubric = $this->_getRubricOutMessageTag($key);
            foreach ($value as $language => $translation) {
                $lang_array[$language][$rubric][$key][$language] = $translation;
            }
        }
        $this->_deleteLoadedMessages();
        foreach ($lang_array as $language => $rubric_array) {
            foreach ($rubric_array as $rubric => $message_array) {
                $filename = $this->_file_path.'ms_'.$rubric.'_'.$language.'.dat';
                $messagefile = fopen($filename, 'w');
                fwrite($messagefile, $this->_translate2String(encode(AS_FILE, $message_array)));
                fclose($messagefile);
            }
        }
    }

    /** saveMessageBundles
     * save stored messages to java bundle files.
     */
    public function saveMessageBundles()
    {
        $lang_array = [];
        foreach ($this->messageArray as $key => $value) {
            $rubric = $this->_getRubricOutMessageTag($key);
            foreach ($value as $language => $translation) {
                $lang_array[$language][$rubric][$key][$language] = $translation;
            }
        }
        $this->_deleteLoadedMessageBundles();
        foreach ($lang_array as $language => $rubric_array) {
            $filename = mb_strtolower($this->_file_path.'c3p0_'.$language.'.properties', 'UTF-8');
            $messagefile = fopen($filename, 'a');
            fwrite($messagefile, "// \$Id\$\n// DO NOT EDIT, CHANGES WILL BE LOST! - This file is generated on the basis of a PHP file\n// To make changes to this file use the edit message function within the commsy system itself\n");
            foreach ($rubric_array as $rubric => $message_array) {
                echo "writing file '".$filename."'<br/>\n";
                flush();
                fwrite($messagefile, '// ### '.$filename." ###\n");
                fwrite($messagefile, $this->_translate2JavaString(encode(FROM_FILE, $message_array)));
            }
            fclose($messagefile);
        }
    }

    /** _deleteAllMessages - INTERNAL
     * this methode deletes all message.dats.
     */
    public function _deleteAllMessages()
    {
        $directory = dir($this->_file_path);
        while ($entry = $directory->read()) {
            if (1 == mb_strpos($entry, 's_') and mb_strpos($entry, '.dat') and 'm' == $entry[0]) {
                if (file_exists($this->_file_path.$entry)) {
                    unlink($this->_file_path.$entry);
                }
            }
        }
    }

    /** _deleteAllMessageBundles - INTERNAL
     * this methode deletes all message property files.
     */
    public function _deleteAllMessageBundles()
    {
        $directory = dir($this->_file_path);
        while ($entry = $directory->read()) {
            if (1 == mb_strpos($entry, 's_') and mb_strpos($entry, '.properties') and 'm' == $entry[0]) {
                if (file_exists($this->_file_path.$entry)) {
                    unlink($this->_file_path.$entry);
                }
            }
        }
    }

    /** _deleteLoadedMessages - INTERNAL
     * this methode deletes all loaded message.dats.
     */
    public function _deleteLoadedMessages()
    {
        foreach ($this->_loaded_message_dats as $entry) {
            if (file_exists($this->_file_path.$entry)) {
                unlink($this->_file_path.$entry);
            }
        }
    }

    /** _deleteLoadedMessageBundles - INTERNAL
     * this methode deletes all loaded message.dats.
     */
    public function _deleteLoadedMessageBundles()
    {
        foreach ($this->_loaded_message_dats as $entry) {
            if (file_exists($this->_file_path.$entry)) {
                // unlink($this->_file_path.$entry);
            }
        }
    }

    /** _translate2String - INTERNAL
     * this methode translate a message array to a string to write it into a file.
     *
     * @param array message array to translate
     *
     * @return string $message_text message array as string
     *
     * @author CommSy Development Group
     */
    public function _translate2String($message_array)
    {
        ksort($message_array);
        reset($message_array);
        $message_text = "<?php\n";
        foreach ($message_array as $key => $value) {
            foreach ($value as $key2 => $value2) {
                $message_text .= '$message["'.$key.'"]["'.$key2.'"] = "'.$value2.'";'."\n";
            }
        }
        $message_text .= '?>';

        return $message_text;
    }

    /** _translate2String - INTERNAL
     * this methode translate a message array to a string to write it into a file.
     *
     * @param array message array to translate
     *
     * @return string $message_text message array as string
     */
    public function _translate2JavaString($message_array)
    {
        ksort($message_array);
        reset($message_array);
        $message_text = '';
        foreach ($message_array as $key => $value) {
            foreach ($value as $key2 => $value2) {
                for ($i = 0; $i < 10; ++$i) {
                    $value2 = str_replace('%'.($i + 1), '{'.$i.'}', (string) $value2);
                }
                $value2 = strtr($value2, "\n", ' ');
                $key = strtr($key, ' ', '_');
                $message_text .= ''.$key.'='.$value2.''."\r\n";
            }
        }
        $message_text .= '';

        return $message_text;
    }

    /** _getRubricOutMessageTag - INTERNAL
     * this methode separate the rubric (first word) out of the messagetag-name.
     *
     * @param string message_tag
     *
     * @return string rubric (first word)
     */
    public function _getRubricOutMessageTag($messag_tag)
    {
        return mb_substr((string) $messag_tag, 0, mb_strpos((string) $messag_tag, '_'));
    }

    /** get an array of the available languages
     *  this method returns the available languages, which are used as indices in the array message.
     *
     * @return array of available languages
     */
    public function getAvailableLanguages(): array
    {
        if (!isset($this->availableLanguages)) {
            $filename_array = [];
            $language_array = [];
            $directory = dir($this->_file_path);
            while ($entry = $directory->read()) {
                if (mb_stristr($entry, 'COMMON_') and !mb_stristr($entry, '#')) {
                    $filename_array[] = $entry;
                }
            }
            foreach ($filename_array as $filename) {
                $pos1 = mb_strrpos($filename, '_') + 1;
                $pos2 = mb_strpos($filename, '.');
                $language = mb_substr($filename, $pos1, $pos2 - $pos1);
                $language_array[] = $language;
            }
            sort($language_array);
            $this->availableLanguages = $language_array;
        }

        return $this->availableLanguages;
    }

    public function isLanguageAvailable($lang): bool
    {
        return in_array($lang, $this->getAvailableLanguages());
    }

    public function setDefaultLanguage($value)
    {
        $this->_default_language = $value;
    }

    /** get the translation of the messageID in the right language
     * this method returns the translation of the messageID in the right language.
     *
     * @param string mode            mode of text encoding
     * @param string MsgID           The MessageID, which should be translated
     * @param string param1          The string %1 in the translated text is replaced by param1
     * @param string param2          see param1
     * @param string param3          see param1
     * @param string param4          see param1
     * @param string param5          see param1
     *
     * @return string the translated text
     */
    public function getMessage($MsgID, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '')
    {
        if (!empty($this->_selected_language)) {
            $retour = $this->getMessageInLang($this->_selected_language, $MsgID, $param1, $param2, $param3, $param4, $param5);
        } else {
            trigger_error('no selected language is set', E_USER_WARNING);
            $retour = $MsgID;
        }

        return $retour;
    }

    /**
     * get a text of a message in a particular language
     * this method returns the translated MessageID.
     *
     * @param string $language The particular language
     * @param string $MsgID The MessageID, which should be translated
     * @param string[] $params The string params %1...%5 in the translated text for replacement
     *
     * @return string the translation of MsgID
     */
    public function getMessageInLang(string $language, string $MsgID, ...$params): string
    {
        if ($this->_issetSessionLanguage()) {
            $language = $this->_getSessionLanguage();
        }

        if (!$this->isLanguageAvailable($language)) {
            $language = $this->_default_language;
        }

        // load message.dat
        if (!isset($this->messageArray[$MsgID][$language])) {
            $this->_loadMessages($this->_getRubricOutMessageTag($MsgID), $language);
        }

        if (isset($this->messageArray[$MsgID][$language])) {
            $text = $this->messageArray[$MsgID][$language];
            $text = $this->text_replace($text, ...$params);
        } else {
            $text = $MsgID;
        }

        return $text;
    }

    /**
     * Get the translation of the email message in the right language.
     * This method returns the translation of the email text in the right language
     * just from the current room or default.
     *
     * @param string $MsgID The MessageID, which should be translated
     * @param string[] $params parameters
     *
     * @return string the translated text
     */
    public function getEmailMessage(string $MsgID, ...$params): string
    {
        if (!empty($this->_selected_language)) {
            $retour = $this->getEmailMessageInLang($this->_selected_language, $MsgID, ...$params);

            /*
             * This is quite hacky... Content from CKEditor can contain multiple paragraphs. In the end we are trying
             * to trim all tags from start and end and preserve line breaks.
             */
            return u($retour)
                ->replace("\n", '<br/>')
                ->replace('<p>', '<br/><br/>')
                ->replace('</p>', '')
                ->trimPrefix('<br/><br/>')
                ->trimSuffix('<br/><br/>')
                ->toString();
        } else {
            trigger_error('no selected language is set', E_USER_WARNING);
            $retour = $MsgID;
        }

        return $retour;
    }

    public function getEmailMessageInLang(string $language, string $MsgID, ...$params): string
    {
        if ($this->_issetSessionLanguage()) {
            $language = $this->_getSessionLanguage();
        }
        if (!empty($this->_email_array[$MsgID][mb_strtoupper($language, 'UTF-8')])) {
            $retour = $this->text_replace($this->_email_array[$MsgID][mb_strtoupper($language, 'UTF-8')], ...$params);
        } elseif (!empty($this->_email_array[$MsgID][mb_strtolower($language, 'UTF-8')])) {
            $retour = $this->text_replace($this->_email_array[$MsgID][mb_strtolower($language, 'UTF-8')], ...$params);
        } else {
            if ($this->_inProjectRoom()) {
                $retour = match ($MsgID) {
                    'MAIL_BODY_CIAO' => $this->getMessageInLang($language, 'MAIL_BODY_CIAO_PR', ...$params),
                    'MAIL_BODY_HELLO' => $this->getMessageInLang($language, 'MAIL_BODY_HELLO_PR', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_DELETE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_PR', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_LOCK' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_PR', ...$params),
                    'MAIL_BODY_USER_MAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_PR', ...$params),
                    'MAIL_BODY_USER_STATUS_MODERATOR' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_PR', ...$params),
                    'MAIL_BODY_USER_STATUS_USER' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_PR', ...$params),
                    'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_PR', ...$params),
                    'EMAIL_BODY_PASSWORD_EXPIRATION' => $this->getMessageInLang($language, 'EMAIL_BODY_PASSWORD_EXPIRATION', ...$params),
                    default => $this->getMessageInLang($language, $MsgID, ...$params),
                };
            } elseif ($this->_inCommunityRoom()) {
                $retour = match ($MsgID) {
                    'MAIL_BODY_CIAO' => $this->getMessageInLang($language, 'MAIL_BODY_CIAO_GR', ...$params),
                    'MAIL_BODY_HELLO' => $this->getMessageInLang($language, 'MAIL_BODY_HELLO_GR', ...$params),
                    'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC' => $this->getMessageInLang($language, 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC_GR', ...$params),
                    'MAIL_BODY_MATERIAL_WORLDPUBLIC' => $this->getMessageInLang($language, 'MAIL_BODY_MATERIAL_WORLDPUBLIC_GR', ...$params),
                    'MAIL_BODY_ROOM_LOCK' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_LOCK_GR', ...$params),
                    'MAIL_BODY_ROOM_UNLINK' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNLINK_GR', ...$params),
                    'MAIL_BODY_ROOM_UNLOCK' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNLOCK_GR', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_DELETE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_GR', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_LOCK' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_GR', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_MERGE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_MERGE_GR', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_PASSWORD' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_PASSWORD_GR', ...$params),
                    'MAIL_BODY_USER_MAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_GR', ...$params),
                    'MAIL_BODY_USER_STATUS_MODERATOR' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_GR', ...$params),
                    'MAIL_BODY_USER_STATUS_USER' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_GR', ...$params),
                    'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_GR', ...$params),
                    'EMAIL_BODY_PASSWORD_EXPIRATION' => $this->getMessageInLang($language, 'EMAIL_BODY_PASSWORD_EXPIRATION', ...$params),
                    'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON' => $this->getMessageInLang($language, 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON', ...$params),
                    default => $this->getMessageInLang($language, $MsgID, ...$params),
                };
            } elseif ($this->_inGroupRoom()) {
                $retour = match ($MsgID) {
                    'MAIL_BODY_CIAO' => $this->getMessageInLang($language, 'MAIL_BODY_CIAO_GP', ...$params),
                    'MAIL_BODY_HELLO' => $this->getMessageInLang($language, 'MAIL_BODY_HELLO_GP', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_DELETE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_GP', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_LOCK' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_GP', ...$params),
                    'MAIL_BODY_USER_STATUS_MODERATOR' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_GP', ...$params),
                    'MAIL_BODY_USER_STATUS_USER' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_GP', ...$params),
                    'MAIL_BODY_USER_MAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_GP', ...$params),
                    'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_GP', ...$params),
                    'EMAIL_BODY_PASSWORD_EXPIRATION' => $this->getMessageInLang($language, 'EMAIL_BODY_PASSWORD_EXPIRATION', ...$params),
                    'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON' => $this->getMessageInLang($language, 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON', ...$params),
                    default => $this->getMessageInLang($language, $MsgID, ...$params),
                };
            } else {
                $retour = match ($MsgID) {
                    'MAIL_BODY_CIAO' => $this->getMessageInLang($language, 'MAIL_BODY_CIAO_PO', ...$params),
                    'MAIL_BODY_HELLO' => $this->getMessageInLang($language, 'MAIL_BODY_HELLO_PO', ...$params),
                    'MAIL_BODY_ROOM_DELETE' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_DELETE_PO', ...$params),
                    'MAIL_BODY_ROOM_LOCK' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_LOCK_PO', ...$params),
                    'MAIL_BODY_ROOM_OPEN' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_OPEN_PO', ...$params),
                    'MAIL_BODY_ROOM_UNDELETE' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNDELETE_PO', ...$params),
                    'MAIL_BODY_ROOM_UNLOCK' => $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNLOCK_PO', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_DELETE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_PO', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_LOCK' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_PO', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_MERGE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_MERGE_PO', ...$params),
                    'MAIL_BODY_USER_ACCOUNT_PASSWORD' => $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_PASSWORD_PO', ...$params),
                    'MAIL_BODY_USER_MAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_PO', ...$params),
                    'MAIL_BODY_USER_PASSWORD_CHANGE' => $this->getMessageInLang($language, 'MAIL_BODY_USER_PASSWORD_CHANGE_PO', ...$params),
                    'MAIL_BODY_USER_STATUS_MODERATOR' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_PO', ...$params),
                    'MAIL_BODY_USER_STATUS_USER' => $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_PO', ...$params),
                    'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON' => $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_PO', ...$params),
                    'EMAIL_BODY_PASSWORD_EXPIRATION_SOON' => $this->getMessageInLang($language, 'EMAIL_PASSWORD_EXPIRATION_SOON_BODY', ...$params),
                    'EMAIL_BODY_PASSWORD_EXPIRATION' => $this->getMessageInLang($language, 'EMAIL_PASSWORD_EXPIRATION_BODY', ...$params),
                    default => $this->getMessageInLang($language, $MsgID, ...$params),
                };
            }
        }

        return $retour;
    }

    public function getTimeMessage($MsgID)
    {
        if (!empty($this->_selected_language)) {
            $retour = $this->getTimeMessageInLang($this->_selected_language, $MsgID);
        } else {
            trigger_error('no selected language is set', E_USER_WARNING);
            $retour = $MsgID;
        }

        return $retour;
    }

    public function getTimeMessageInLang($language, $MsgID)
    {
        if ($this->_issetSessionLanguage()) {
            $language = $this->_getSessionLanguage();
        }
        $retour = $MsgID;
        if (!$this->isLanguageAvailable($language)) {
            $language = $this->_default_language;
        }
        $msg_array = explode('_', (string) $MsgID);
        $year_small_temp = $msg_array[0];
        $year_small = $year_small_temp[2].$year_small_temp[3];
        $year_small_plus = $year_small + 1;
        if (100 == $year_small_plus) {
            $year_small_plus = '00';
        }
        if ($year_small_plus < 10) {
            $year_small_plus = '0'.$year_small_plus;
        }
        $year_small_minus = $year_small - 1;
        if (-1 == $year_small_minus) {
            $year_small_minus = '99';
        }
        if ($year_small_minus < 10) {
            $year_small_minus = '0'.$year_small_minus;
        }
        if (isset($msg_array[1]) and !empty($this->timeMessageArray[$msg_array[1]][mb_strtoupper((string) $language, 'UTF-8')])) {
            $retour = $this->text_replace($this->timeMessageArray[$msg_array[1]][mb_strtoupper((string) $language, 'UTF-8')], $msg_array[0], $msg_array[0] + 1, $msg_array[0] - 1, $year_small, $year_small_plus, $year_small_minus);
        }

        return $retour;
    }

    /** setDBConnector
     * this methode set the class to connect the database.
     *
     * @param class for connecting the database
     */
    public function setDBConnector($value)
    {
        $this->_db_connector = $value;
    }

    /** setSelectedLanguage
     * this methode set the selected language, form environment.
     *
     * @param string $language (de,en,...)
     */
    public function setSelectedLanguage(string $language): void
    {
        if (!$this->isLanguageAvailable($language)) {
            $language = $this->_default_language;
        }
        $this->_selected_language = $language;
    }

    /** getSelectedLanguage
     * this methode get the selected language.
     *
     * @return string language (de,en,...)
     */
    public function getSelectedLanguage()
    {
        return $this->_selected_language;
    }

    /** setSessionLanguage
     * this methode set the session language, form environment.
     *
     * @param string language (de,en,...)
     */
    public function setSessionLanguage($value)
    {
        $this->_session_language = $value;
    }

    /** getSelectedLanguage
     * this methode get the selected language.
     *
     * @return string language (de,en,...)
     */
    private function _getSessionLanguage()
    {
        return $this->_session_language;
    }

    private function _issetSessionLanguage()
    {
        $retour = false;
        if (!empty($this->_session_language)) {
            $retour = true;
        }

        return $retour;
    }

    /**
     * Returns the context (community, project or grouproom).
     */
    public function getContext(): ?string
    {
        return $this->_context;
    }

    /** setContext
     * this methode set the context (community or project).
     *
     * @param string context
     */
    public function setContext($value): void
    {
        $this->_context = (string) $value;
    }

    public function _inCommunityRoom()
    {
        return isset($this->_context) && $this->_context == CS_COMMUNITY_TYPE;
    }

    public function _inProjectRoom()
    {
        return isset($this->_context) && $this->_context == CS_PROJECT_TYPE;
    }

    public function _inGroupRoom()
    {
        return isset($this->_context) && $this->_context == CS_GROUPROOM_TYPE;
    }

    /** setRubricTranslationArray
     * this methode set the special rubric names, get from current room.
     *
     * @param array special rubric names
     */
    public function setRubricTranslationArray($value)
    {
        $this->_rubric_translation_array = (array) $value;
    }

    /** setEmailTextArray
     * this methode set the special email text, get from current room.
     *
     * @param array email text
     */
    public function setEmailTextArray($value): void
    {
        $this->_email_array = (array) $value;
    }

    /** setTimeMessageArray
     * this methode set the special time messages, get from current portal.
     *
     * @param array time messages
     */
    public function setTimeMessageArray($value): void
    {
        $this->timeMessageArray = (array) $value;
    }

    /** setMessageArray
     * this methode set the message array, needed in language_edit.
     *
     * @param array message_array
     */
    public function setMessageArray($value)
    {
        $this->messageArray = (array) $value;
    }

    /** replace %x in text
     * this method returns the replaced text.
     *
     * @param string text            The MessageID, which should be translated
     * @param string param1          The string %1 in the translated text is replaced by param1
     * @param string param2          see param1
     * @param string param3          see param1
     * @param string param4          see param1
     * @param string param5          see param1
     * @param string param6          see param1
     *
     * @return string the replaced text
     */
    public function text_replace($text, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '', $param6 = '')
    {
        $text = $this->tag_replace($text);
        if ('' !== $param1) {
            $text = str_replace('%1', (string) $param1, $text);
        }
        if ('' !== $param2) {
            $text = str_replace('%2', (string) $param2, $text);
        }
        if ('' !== $param3) {
            $text = str_replace('%3', (string) $param3, $text);
        }
        if ('' !== $param4) {
            $text = str_replace('%4', (string) $param4, $text);
        }
        if ('' !== $param5) {
            $text = str_replace('%5', (string) $param5, $text);
        }
        if ('' !== $param6) {
            $text = str_replace('%6', (string) $param6, $text);
        }

        return $text;
    }

    /** replace placeholders for dynamic module-declarations
     * this method returns the replaced text.
     *
     * @param string text           The Messagetext, which is to be translated
     *
     * @return string the replaced text
     */
    public function tag_replace($text)
    {
        $tags = [];
        // filling the array $placeholders with the occurring placeholder strings
        preg_match_all('~%(?:_[A-Z0-9]+)+~u', (string) $text, $placeholders);

        // if placeholders were found, explode them into their sub-elements
        if ((is_countable($placeholders[0]) ? count($placeholders[0]) : 0) > 0) {
            $i = 0;

            foreach ($placeholders[0] as $placeholder) {
                $placeholder_elements = explode('_', (string) $placeholder);

                // get the replacement strings for the placeholders
                if ('ART' == $placeholder_elements[2]) {
                    $tags[$i++] = $this->_getRubricNameArticle(Module2Type($placeholder_elements[1]),
                        $placeholder_elements[3],
                        $placeholder_elements[4],
                        $placeholder_elements[5]);
                } else {
                    $tags[$i++] = $this->_getRubricName(Module2Type($placeholder_elements[1]),
                        $placeholder_elements[3],
                        $placeholder_elements[4]);
                    if (!empty($placeholder_elements[5])
                         and 'ADJ' == $placeholder_elements[5]
                         and !empty($placeholder_elements[1])
                    ) {
                        $upper_lower = '';
                        if (!empty($placeholder_elements[7])) {
                            $upper_lower = $placeholder_elements[7];
                        }
                        $tags[$i - 1] = $this->_getRubricAdjective(Module2Type($placeholder_elements[1]), $placeholder_elements[6], $upper_lower).$tags[$i - 1];
                    }
                }
            }

            // replace the placeholders with their corresponding replacement strings
            $new_text = str_replace($placeholders[0], $tags, (string) $text);

            return $new_text;
        }
        // if no placeholders were found, return the untouched text
        else {
            return $text;
        }
    }

    private function _getRubricGenus($rubric)
    {
        $retour = '';
        if (!empty($rubric)) {
            $rubric_array = $this->_getRubricArray($rubric);
            if (!empty($rubric_array)) {
                $language = '';
                if ($this->_issetSessionLanguage()) {
                    $language = $this->_getSessionLanguage();
                } else {
                    $language = $this->_selected_language;
                }
                if (!empty($language)
                     and !empty($rubric_array[mb_strtoupper($language)]['GENUS'])
                ) {
                    $retour = $rubric_array[mb_strtoupper($language)]['GENUS'];
                }
            }
        }

        return $retour;
    }

    private function _getRubricAdjective($rubric, $adjective, $upper_case = '')
    {
        $retour = '';
        if (!empty($rubric)
             and !empty($adjective)
        ) {
            $genus = $this->_getRubricGenus($rubric);
            $adjective_array = $this->_getAdjectiveArray();
            $language = '';
            if ($this->_issetSessionLanguage()) {
                $language = $this->_getSessionLanguage();
            } else {
                $language = $this->_selected_language;
            }
            if (!empty($genus)
                 and !empty($adjective_array)
                 and !empty($language)
                 and !empty($adjective_array[mb_strtoupper((string) $adjective)][mb_strtoupper($language)][mb_strtoupper((string) $genus)])
            ) {
                $adjective_tranlsation = $adjective_array[mb_strtoupper((string) $adjective)][mb_strtoupper($language)][mb_strtoupper((string) $genus)];
                if (!empty($adjective_tranlsation)) {
                    if ('BIG' == $upper_case) {
                        $adjective_tranlsation = ucfirst($adjective_tranlsation);
                    } elseif ('LOW' == $upper_case) {
                        $adjective_tranlsation = ucfirst($adjective_tranlsation);
                    }
                    $retour = $adjective_tranlsation.' ';
                }
            }
        }

        return $retour;
    }

    private function _getAdjectiveArray()
    {
        $retour = [];
        $retour['NEW']['DE']['F'] = $this->getMessageInLang('DE', 'COMMON_NEW_F');
        $retour['NEW']['DE']['M'] = $this->getMessageInLang('DE', 'COMMON_NEW_M');
        $retour['NEW']['DE']['N'] = $this->getMessageInLang('DE', 'COMMON_NEW_N');
        $retour['NEW']['EN']['F'] = $this->getMessageInLang('EN', 'COMMON_NEW_F');
        $retour['NEW']['EN']['M'] = $this->getMessageInLang('EN', 'COMMON_NEW_M');
        $retour['NEW']['EN']['N'] = $this->getMessageInLang('EN', 'COMMON_NEW_N');

        return $retour;
    }

    /** get _getRubricArray - INTERNAL
     * this method gets the stored rubric array for one rubric.
     *
     * @param string rubric
     * @param string mode for text encode
     *
     * @return array value name cases
     */
    public function _getRubricArray($rubric): array
    {
        $rubric_array = [];
        if (!empty($this->_rubric_translation_array)
             and !empty($rubric)
             and !empty($this->_rubric_translation_array[cs_strtoupper($rubric)])
        ) {
            $retour = $this->_rubric_translation_array[cs_strtoupper($rubric)];
        } else {
            $rubric_array['NAME'] = 'rubrics';
            $rubric_array['DE']['GENUS'] = 'F';
            $rubric_array['DE']['NOMS'] = 'Rubrik';
            $rubric_array['DE']['GENS'] = 'Rubrik';
            $rubric_array['DE']['AKKS'] = 'Rubrik';
            $rubric_array['DE']['DATS'] = 'Rubrik';
            $rubric_array['DE']['NOMPL'] = 'Rubriken';
            $rubric_array['DE']['GENPL'] = 'Rubriken';
            $rubric_array['DE']['AKKPL'] = 'Rubriken';
            $rubric_array['DE']['DATPL'] = 'Rubriken';
            $rubric_array['EN']['GENUS'] = 'F';
            $rubric_array['EN']['NOMS'] = 'rubric';
            $rubric_array['EN']['GENS'] = 'rubric';
            $rubric_array['EN']['AKKS'] = 'rubric';
            $rubric_array['EN']['DATS'] = 'rubric';
            $rubric_array['EN']['NOMPL'] = 'rubrics';
            $rubric_array['EN']['GENPL'] = 'rubrics';
            $rubric_array['EN']['AKKPL'] = 'rubrics';
            $rubric_array['EN']['DATPL'] = 'rubrics';
            $rubric_array['RU']['GENUS'] = 'F';
            $rubric_array['RU']['NOMS'] = 'rubrica';
            $rubric_array['RU']['GENS'] = 'rubricii';
            $rubric_array['RU']['AKKS'] = 'rubrica';
            $rubric_array['RU']['DATS'] = 'rubricii';
            $rubric_array['RU']['NOMPL'] = 'rubricile';
            $rubric_array['RU']['GENPL'] = 'rubricilor';
            $rubric_array['RU']['AKKPL'] = 'rubricile';
            $rubric_array['RU']['DATPL'] = 'rubricilor';
            $retour = $rubric_array;
        }

        return $retour;
    }

    /** get _getRubricName - INTERNAL
     * this method gets the rubric name.
     *
     * @param string rubric
     * @param string postion
     * @param string first letter BIG or not
     *
     * @return array value name cases
     */
    public function _getRubricName($rubric, $position, $upper_case)
    {
        $rubric_array = $this->_getRubricArray($rubric);
        if ($this->_issetSessionLanguage()) {
            $language = $this->_getSessionLanguage();
        } else {
            $language = $this->_selected_language;
        }
        if (isset($rubric_array[cs_strtoupper($language)][cs_strtoupper($position)])) {
            $text = $rubric_array[cs_strtoupper($language)][cs_strtoupper($position)];
        } else {
            $text = 'rubric';
        }
        if ('BIG' == $upper_case) {
            $text = ucfirst($text);
        }

        return $text;
    }

    /** get _getRubricNameArticle - INTERNAL
     * this method gets the rubric article.
     *
     * @param string rubric
     * @param string def or undef
     * @param string postion
     * @param string first letter BIG or not
     *
     * @return array value name cases
     */
    public function _getRubricNameArticle($rubric, $mode, $position, $upper_case)
    {
        $cs_article = [];
        // default article arrays
        $cs_article['DE']['DEF']['M']['NOMS'] = 'der';
        $cs_article['DE']['DEF']['M']['GENS'] = 'des';
        $cs_article['DE']['DEF']['M']['AKKS'] = 'den';
        $cs_article['DE']['DEF']['M']['DATS'] = 'dem';
        $cs_article['DE']['DEF']['M']['NOMPL'] = 'die';
        $cs_article['DE']['DEF']['M']['GENPL'] = 'der';
        $cs_article['DE']['DEF']['M']['AKKPL'] = 'die';
        $cs_article['DE']['DEF']['M']['DATPL'] = 'den';

        $cs_article['DE']['DEF']['F']['NOMS'] = 'die';
        $cs_article['DE']['DEF']['F']['GENS'] = 'der';
        $cs_article['DE']['DEF']['F']['AKKS'] = 'die';
        $cs_article['DE']['DEF']['F']['DATS'] = 'der';
        $cs_article['DE']['DEF']['F']['NOMPL'] = 'die';
        $cs_article['DE']['DEF']['F']['GENPL'] = 'der';
        $cs_article['DE']['DEF']['F']['AKKPL'] = 'die';
        $cs_article['DE']['DEF']['F']['DATPL'] = 'den';

        $cs_article['DE']['DEF']['N']['NOMS'] = 'das';
        $cs_article['DE']['DEF']['N']['GENS'] = 'des';
        $cs_article['DE']['DEF']['N']['AKKS'] = 'das';
        $cs_article['DE']['DEF']['N']['DATS'] = 'dem';
        $cs_article['DE']['DEF']['N']['NOMPL'] = 'die';
        $cs_article['DE']['DEF']['N']['GENPL'] = 'der';
        $cs_article['DE']['DEF']['N']['AKKPL'] = 'die';
        $cs_article['DE']['DEF']['N']['DATPL'] = 'den';

        $cs_article['DE']['UNDEF']['M']['NOMS'] = 'ein';
        $cs_article['DE']['UNDEF']['M']['GENS'] = 'eines';
        $cs_article['DE']['UNDEF']['M']['AKKS'] = 'einen';
        $cs_article['DE']['UNDEF']['M']['DATS'] = 'einem';

        $cs_article['DE']['UNDEF']['F']['NOMS'] = 'eine';
        $cs_article['DE']['UNDEF']['F']['GENS'] = 'einer';
        $cs_article['DE']['UNDEF']['F']['AKKS'] = 'eine';
        $cs_article['DE']['UNDEF']['F']['DATS'] = 'einer';

        $cs_article['DE']['UNDEF']['N']['NOMS'] = 'ein';
        $cs_article['DE']['UNDEF']['N']['GENS'] = 'eines';
        $cs_article['DE']['UNDEF']['N']['AKKS'] = 'ein';
        $cs_article['DE']['UNDEF']['N']['DATS'] = 'einem';

        $cs_article['EN'] = 'the';
        $rubric_array = $this->_getRubricArray($rubric);
        $language = cs_strtoupper($this->_selected_language);
        if ($this->_issetSessionLanguage()) {
            $language = cs_strtoupper($this->_getSessionLanguage());
        } else {
            $language = cs_strtoupper($this->_selected_language);
        }
        if ('EN' == $language) {
            $text = $cs_article[$language];
        } else {
            $text = $cs_article[$language][$mode][$rubric_array[$language]['GENUS']][cs_strtoupper($position)];
        }
        if ('BIG' == $upper_case) {
            $text = ucfirst($text);
        }

        return $text;
    }

    public function getDateTimeInLang($datetime, $oclock = true)
    {
        $date = $this->_getDateTimeInLang($datetime, $oclock);
        $date = mb_eregi_replace('/', ' ', (string) $date);

        return $date;
    }

    /** translate a Date and Time from a MYSQL-datetime depending on selectet language.
     */
    public function _getDateTimeInLang($datetime, $oclock = true)
    {
        $Datetime = [];
        $language = $this->_selected_language;
        if ($this->_issetSessionLanguage()) {
            $language = $this->_getSessionLanguage();
        }
        $length = mb_strlen((string) $datetime);

        if (2 == mb_substr_count((string) $datetime, '-')) {
            $year = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
            $month = $datetime[5].$datetime[6];
            $day = $datetime[8].$datetime[9];
            if ($length > 12) {
                $hour = $datetime[11].$datetime[12];
            } else {
                $hour = '00';
            }
            if ($length > 15) {
                $min = $datetime[14].$datetime[15];
            } else {
                $min = '00';
            }
        // $sec   = $datetime[17].$datetime[18];
        } elseif (!empty($datetime)) {
            $year = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
            $month = $datetime[4].$datetime[5];
            $day = $datetime[6].$datetime[7];
            $hour = $datetime[8].$datetime[9];
            $min = $datetime[10].$datetime[11];
        } else {
            $year = '';
            $month = '';
            $day = '';
            $hour = '';
            $min = '';
        }

        // create datetime depends on language
        if ('en' == $language) {
            $ampm = 'am';
            if ($hour > 12) {
                $hour = $hour - 12;
                $ampm = 'pm';
            } elseif (12 == $hour) {
                $ampm = 'pm';
            }
            if (1 == mb_strlen((string) $hour)) {
                $hour = '0'.$hour;
            }
            $Datetime = $month.'/'.$day.'/'.$year.' '.$hour.':'.$min.$ampm;
        } elseif ('de' == $language) {
            $Datetime = $day.'.'.$month.'.'.$year.' '.$hour.':'.$min; // .':'.$sec;
        } elseif ('ru' == $language) {
            $Datetime = $day.'.'.$month.'.'.$year.' '.$hour.':'.$min; // .':'.$sec;
        }

        $Datetime = mb_eregi_replace(' ', ', ', $Datetime);
        if ('en' != $language and $oclock) {
            $Datetime = $Datetime.' '.$this->getMessage('DATES_OCLOCK');
        }

        return $Datetime;
    }

    public function getShortMonthName($month)
    {
        $ret = match ($month) {
            '01' => $this->getMessage('COMMON_DATE_JANUARY_SHORT'),
            '02' => $this->getMessage('COMMON_DATE_FEBRUARY_SHORT'),
            '03' => $this->getMessage('COMMON_DATE_MARCH_SHORT'),
            '04' => $this->getMessage('COMMON_DATE_APRIL_SHORT'),
            '05' => $this->getMessage('COMMON_DATE_MAY_SHORT'),
            '06' => $this->getMessage('COMMON_DATE_JUNE_SHORT'),
            '07' => $this->getMessage('COMMON_DATE_JULY_SHORT'),
            '08' => $this->getMessage('COMMON_DATE_AUGUST_SHORT'),
            '09' => $this->getMessage('COMMON_DATE_SEPTEMBER_SHORT'),
            '10' => $this->getMessage('COMMON_DATE_OCTOBER_SHORT'),
            '11' => $this->getMessage('COMMON_DATE_NOVEMBER_SHORT'),
            '12' => $this->getMessage('COMMON_DATE_DECEMBER_SHORT'),
            default => '',
        };

        return $ret;
    }

    public function getShortMonthNameToInt($month)
    {
        $ret = match ($month) {
            $this->getMessage('COMMON_DATE_JANUARY_SHORT') => '01',
            $this->getMessage('COMMON_DATE_FEBRUARY_SHORT') => '02',
            $this->getMessage('COMMON_DATE_MARCH_SHORT') => '03',
            $this->getMessage('COMMON_DATE_APRIL_SHORT') => '04',
            $this->getMessage('COMMON_DATE_MAY_SHORT') => '05',
            $this->getMessage('COMMON_DATE_JUNE_SHORT') => '06',
            $this->getMessage('COMMON_DATE_JULY_SHORT') => '07',
            $this->getMessage('COMMON_DATE_AUGUST_SHORT') => '08',
            $this->getMessage('COMMON_DATE_SEPTEMBER_SHORT') => '09',
            $this->getMessage('COMMON_DATE_OCTOBER_SHORT') => '10',
            $this->getMessage('COMMON_DATE_NOVEMBER_SHORT') => '11',
            $this->getMessage('COMMON_DATE_DECEMBER_SHORT') => '12',
            $this->getMessage('COMMON_DATE_JANUARY_LONG') => '01',
            $this->getMessage('COMMON_DATE_FEBRUARY_LONG') => '02',
            $this->getMessage('COMMON_DATE_MARCH_LONG') => '03',
            $this->getMessage('COMMON_DATE_APRIL_LONG') => '04',
            $this->getMessage('COMMON_DATE_MAY_LONG') => '05',
            $this->getMessage('COMMON_DATE_JUNE_LONG') => '06',
            $this->getMessage('COMMON_DATE_JULY_LONG') => '07',
            $this->getMessage('COMMON_DATE_AUGUST_LONG') => '08',
            $this->getMessage('COMMON_DATE_SEPTEMBER_LONG') => '09',
            $this->getMessage('COMMON_DATE_OCTOBER_LONG') => '10',
            $this->getMessage('COMMON_DATE_NOVEMBER_LONG') => '11',
            $this->getMessage('COMMON_DATE_DECEMBER_LONG') => '12',
            default => $month,
        };

        return $ret;
    }

    /** translate a Time from a MYSQL-datetime depending on selectet language.
     */
    public function getTimeInLang($datetime)
    {
        $Time = explode(' ', (string) $this->_getDateTimeInLang($datetime));

        return $Time[1];
    }

    /** translate a Date from a MYSQL-datetime depending on selectet language.
     */
    public function getDateInLang($datetime)
    {
        $Date = explode(' ', (string) $this->_getDateTimeInLang($datetime));
        $Date[0] = mb_eregi_replace(',', '', $Date[0]);

        return $Date[0];
    }

    /** translate a Time from a time string depending on selectet language.
     */
    public function getTimeLanguage(string $timestring): ?string
    {
        $language = $this->_selected_language;
        if ($this->_issetSessionLanguage()) {
            $language = $this->_getSessionLanguage();
        }

        if (2 == mb_substr_count($timestring, ':')) {
            $hour = $timestring[0].$timestring[1];
            $min = $timestring[3].$timestring[4];
        } else {
            $hour = $timestring[0].$timestring[1];
            $min = $timestring[2].$timestring[3];
        }

        // create time depends on language
        if ('en' == $language) {
            $ampm = ' am';
            if ($hour > 12) {
                $hour = $hour - 12;
                $ampm = ' pm';
            } elseif (12 == $hour) {
                $ampm = ' pm';
            }
            if (1 == mb_strlen((string) $hour)) {
                $hour = '0'.$hour;
            }
            return $hour.':'.$min.$ampm;
        } elseif ('de' == $language) {
            return $hour.':'.$min;
        } elseif ('ru' == $language) {
            return $hour.':'.$min;
        }

        return null;
    }

    /** getMessageArray
     * this method gets the message array.
     *
     * @return array message array
     */
    public function getMessageArray()
    {
        ksort($this->messageArray);
        reset($this->messageArray);

        return $this->messageArray;
    }
}
