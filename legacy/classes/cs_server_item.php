<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** upper class of the context item
 */

use App\Account\AccountManager;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

include_once 'classes/cs_guide_item.php';

/** class for a context
 * this class implements a context item
 */
class cs_server_item extends cs_guide_item
{
    /** constructor: cs_server_item
     * the only available constructor, initial values for internal variables
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        cs_guide_item::__construct($environment);
        $this->_type = CS_SERVER_TYPE;
    }

    public function isServer()
    {
        return true;
    }

    /** get default portal item id
     *
     * @return string portal item id
     */
    public function getDefaultPortalItemID()
    {
        $retour = '';
        if ($this->_issetExtra('DEFAULT_PORTAL_ID')) {
            $retour = $this->_getExtra('DEFAULT_PORTAL_ID');
        }

        return $retour;
    }

    /** set default portal item id
     *
     * @param default portal item id
     */
    public function setDefaultPortalItemID($value)
    {
        $this->_addExtra('DEFAULT_PORTAL_ID', $value);
    }

    /** get default email sender address
     *
     * @return string default email sender address
     */
    public function getDefaultSenderAddress()
    {
        $retour = '@';
        if ($this->_issetExtra('DEFAULT_SENDER_ADDRESS')) {
            $retour = $this->_getExtra('DEFAULT_SENDER_ADDRESS');
        }

        return $retour;
    }

    /** set default email sender address
     *
     * @param default email sender address
     */
    public function setDefaultSenderAddress($value)
    {
        $this->_addExtra('DEFAULT_SENDER_ADDRESS', $value);
    }

    public function getPortalIDArray()
    {
        $retour = array();
        $portal_manager = $this->_environment->getPortalManager();
        $portal_manager->setContextLimit($this->getItemID());
        $portal_manager->select();
        $portal_id_array = $portal_manager->getIDArray();
        unset($portal_manager);
        if (is_array($portal_id_array)) {
            $retour = $portal_id_array;
        }

        return $retour;
    }

    /** get portal list
     * this function returns a list of all portals
     * existing on this commsy server
     *
     * @return list of portals
     */
    public function getPortalListByActivity()
    {
        $portal_manager = $this->_environment->getPortalManager();
        $portal_manager->setContextLimit($this->getItemID());
        $portal_manager->setOrder('activity_rev');
        $portal_manager->select();
        $portal_list = $portal_manager->get();

        return $portal_list;
    }

    /** get contact moderator of a room
     * this method returns a list of contact moderator which are linked to the room
     *
     * @return object cs_list a list of contact moderator (cs_label_item)
     */
    public function getContactModeratorList()
    {
        $user_manager = $this->_environment->getUserManager();
        $mod_list = new cs_list();
        $mod_list->add($user_manager->getRootUser());

        return $mod_list;
    }

    /** get UsageInfos
     * this method returns the usage infos
     *
     * @return array
     */
    public function getUsageInfoArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO')) {
            $retour = $this->_getExtra('USAGE_INFO');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    /** set UsageInfos
     * this method sets the usage infos
     *
     * @param array
     */
    public function setUsageInfoArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO', $value_array);
        }
    }

    /** set UsageInfos
     * this method sets the usage infos
     *
     * @param array
     */
    public function setUsageInfoFormArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM', $value_array);
        }
    }

    /** get UsageInfos
     * this method returns the usage infos
     *
     * @return array
     */
    public function getUsageInfoFormArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function getUsageInfoHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_HEADER', $value_array);
        }
    }

    public function getUsageInfoFormHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoFormHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
        }
    }

    public function getUsageInfoTextArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_TEXT', $value_array);
        }
    }

    public function getUsageInfoFormTextArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }

        return $retour;
    }

    public function setUsageInfoFormTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
        }
    }

    public function getUsageInfoHeaderForRubric($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = $translator->getMessage('USAGE_INFO_HEADER');
        }

        return $retour;
    }

    public function setUsageInfoHeaderForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $value_array = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_HEADER', $value_array);
    }

    public function getUsageInfoHeaderForRubricForm($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = $translator->getMessage('USAGE_INFO_HEADER');
        }

        return $retour;
    }

    public function setUsageInfoHeaderForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
    }

    public function setUsageInfoTextForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_TEXT', $value_array);
    }

    public function setUsageInfoTextForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
        }
        $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
    }

    public function getUsageInfoTextForRubricForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $translator = $this->_environment->getTranslationObject();
            $temp = mb_strtoupper($rubric, 'UTF-8') . '_' . mb_strtoupper($funct, 'UTF-8');
            $tempMessage = "";
            switch ($temp) {
                case 'CONFIGURATION_BACKUP':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_BACKUP_FORM');
                    break;

                case 'CONFIGURATION_COLOR':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_COLOR_FORM');
                    break;

                case 'CONFIGURATION_EXTRA':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_EXTRA_FORM');
                    break;

                case 'CONFIGURATION_IMS':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_IMS_FORM');
                    break;

                case 'CONFIGURATION_LANGUAGE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_LANGUAGE_FORM');
                    break;

                case 'CONFIGURATION_NEWS':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_NEWS_FORM');
                    break;

                case 'CONFIGURATION_PREFERENCES':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_PREFERENCES_FORM');
                    break;

                case 'CONFIGURATION_SERVICE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_SERVICE_FORM');
                    break;

                case 'CONFIGURATION_OUTOFSERVICE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_OUTOFSERVICE_FORM');
                    break;

                case 'CONFIGURATION_SCRIBD':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_SCRIBD_FORM');
                    break;

                case 'CONFIGURATION_UPDATE':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_UPDATE_FORM');
                    break;

                case 'CONFIGURATION_HTMLTEXTAREA':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_HTMLTEXTAREA_FORM');
                    break;

                case 'CONFIGURATION_CONNECTION':
                    $tempMessage = $translator->getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_CONNECTION_FORM');
                    break;

                case 'CONFIGURATION_DATASECURITY':
                    $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
                    break;

                case 'CONFIGURATION_PLUGINS':
                    $tempMessage = $translator->getMessage('USAGE_INFO_COMING_SOON');
                    break;

                default:
                    $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR') . " cs_server_item (" . __LINE__ . ")";
                    break;

            }

            $retour = $tempMessage;
            if ($retour == 'USAGE_INFO_TEXT_SERVER_FOR_' . $temp . '_FORM' or $retour == 'tbd') {
                $retour = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
            }
        }

        return $retour;
    }

    ################################################################
    # Authentication
    ################################################################

    public function setAuthDefault($value)
    {
        $this->_addExtra('DEFAULT_AUTH', $value);
    }

    public function getAuthDefault()
    {
        $retour = '';
        if ($this->_issetExtra('DEFAULT_AUTH')) {
            $value = $this->_getExtra('DEFAULT_AUTH');
            if (!empty($value)) {
                $retour = $value;
            }
        }

        return $retour;
    }

    public function getDefaultAuthSourceItem()
    {
        $retour = null;
        $default_auth_item_id = $this->getAuthDefault();
        if (!empty($default_auth_item_id)) {
            $manager = $this->_environment->getAuthSourceManager();
            $item = $manager->getItem($default_auth_item_id);
            if (isset($item)) {
                $retour = $item;
            }
            unset($item);
            unset($manager);
        }

        return $retour;
    }

    public function getAuthSourceList()
    {
        $manager = $this->_environment->getAuthSourceManager();
        $manager->setContextLimit($this->getItemID());
        $manager->select();
        $retour = $manager->get();
        unset($manager);

        return $retour;
    }

    public function getAuthSource($item_id)
    {
        $manager = $this->_environment->getAuthSourceManager();
        $retour = $manager->getItem($item_id);
        unset($manager);

        return $retour;
    }

    public function getCurrentCommSyVersion()
    {
        $retour = '';
        $version = trim(file_get_contents('version'));
        if (!empty($version)) {
            $retour = $version;
        }

        return $retour;
    }

    /** get out of service text
     *
     * @return array out of service text in different languages
     */
    public function getOutOfServiceArray()
    {
        $retour = array();
        if ($this->_issetExtra('OUTOFSERVICE')) {
            $retour = $this->_getExtra('OUTOFSERVICE');
        }

        return $retour;
    }

    /** set out of service array
     *
     * @param array value out of service text in different languages
     */
    public function setOutOfServiceArray($value)
    {
        $this->_addExtra('OUTOFSERVICE', (array)$value);
    }

    /** get out of service of a context
     * this method returns the out of service of the context
     *
     * @return string out of service of a context
     */
    public function getOutOfServiceByLanguage($language)
    {
        $retour = '';
        if ($language == 'browser') {
            $language = $this->_environment->getSelectedLanguage();
        }
        $desc_array = $this->getOutOfServiceArray();
        if (!empty($desc_array[cs_strtoupper($language)])) {
            $retour = $desc_array[cs_strtoupper($language)];
        }

        return $retour;
    }

    public function getOutOfService()
    {
        $retour = '';
        $retour = $this->getOutOfServiceByLanguage($this->_environment->getSelectedLanguage());
        if (empty($retour)) {
            $retour = $this->getOutOfServiceByLanguage($this->_environment->getUserLanguage());
        }
        if (empty($retour)) {
            $retour = $this->getOutOfServiceByLanguage($this->getLanguage());
        }
        if (empty($retour)) {
            $desc_array = $this->getOutOfServiceArray();
            foreach ($desc_array as $desc) {
                if (!empty($desc)) {
                    $retour = $desc;
                    break;
                }
            }
        }

        return $retour;
    }

    /** set OutOfService of a context
     * this method sets the OutOfService of the context
     *
     * @param string value OutOfService of the context
     * @param string value lanugage of the OutOfService
     */
    public function setOutOfServiceByLanguage($value, $language)
    {
        $desc_array = $this->getOutOfServiceArray();
        $desc_array[mb_strtoupper($language, 'UTF-8')] = $value;
        $this->setOutOfServiceArray($desc_array);
    }

    public function _getOutOfServiceShow()
    {
        return $this->_getExtra('OUTOFSERVICE_SHOW');
    }

    public function showOutOfService()
    {
        $retour = false;
        $show_oos = $this->_getOutOfServiceShow();
        if ($show_oos == 1) {
            $retour = true;
        }

        return $retour;
    }

    public function _setOutOfServiceShow($value)
    {
        $this->_setExtra('OUTOFSERVICE_SHOW', $value);
    }

    public function setDontShowOutOfService()
    {
        $this->_setOutOfServiceShow(-1);
    }

    public function setShowOutOfService()
    {
        $this->_setOutOfServiceShow(1);
    }

    public function getDBVersion()
    {
        $retour = '';
        if ($this->_issetExtra('VERSION')) {
            $retour = $this->_getExtra('VERSION');
        }

        return $retour;
    }

    public function setDBVersion($value)
    {
        $this->_addExtra('VERSION', $value);
    }

    public function getScribdApiKey()
    {
        $retour = '';
        if ($this->_issetExtra('SCRIBD_API_KEY')) {
            $retour = $this->_getExtra('SCRIBD_API_KEY');
        }

        return $retour;
    }

    public function setScribdApiKey($value)
    {
        $this->_addExtra('SCRIBD_API_KEY', $value);
    }

    public function getScribdSecret()
    {
        $retour = '';
        if ($this->_issetExtra('SCRIBD_SECRET')) {
            $retour = $this->_getExtra('SCRIBD_SECRET');
        }

        return $retour;
    }

    public function setScribdSecret($value)
    {
        $this->_addExtra('SCRIBD_SECRET', $value);
    }

    public function isPluginActive($plugin)
    {
        $retour = false;
        #if ( $this->isPluginOn($plugin) ) {
        #   $retour = true;
        #}
        return $retour;
    }

    public function getStatistics($date_start, $date_end)
    {
        $manager = $this->_environment->getServerManager();

        return $manager->getStatistics($this, $date_start, $date_end);
    }

    public function withLogIPCover()
    {
        $retour = false;
        $value = $this->_getExtraConfig('LOGIPCOVER');
        if ($value == 1) {
            $retour = true;
        }

        return $retour;
    }

    public function setWithLogIPCover()
    {
        $this->_setExtraConfig('LOGIPCOVER', 1);
    }

    public function setWithoutLogIPCover()
    {
        $this->_setExtraConfig('LOGIPCOVER', -1);
    }

    ## commsy server connections: portal2portal
    public function getOwnConnectionKey()
    {
        $retour = '';
        $value = $this->_getExtraConfig('CONNECTION_OWNKEY');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    public function setOwnConnectionKey($value)
    {
        $this->_setExtraConfig('CONNECTION_OWNKEY', $value);
    }

    public function setNewServerConnection($title, $url, $key, $proxy = CS_NO)
    {
        if (!empty($title)
            and !empty($url)
            and !empty($key)
            and !empty($proxy)
        ) {
            $connection_array = $this->getServerConnectionArray();
            $temp_array = array();
            $temp_array['title'] = $title;
            $temp_array['url'] = $url;
            $temp_array['key'] = $key;
            $temp_array['proxy'] = $proxy;

            $key = '';
            $key .= $title;
            $key .= rand(0, 9);
            $key .= $url;
            $key .= rand(0, 9);
            $key .= $key;
            $key .= rand(0, 9);
            include_once 'functions/date_functions.php';
            $key .= getCurrentDateTimeInMySQL();
            $key = md5($key);
            $temp_array['id'] = $key;

            $connection_array[(count($connection_array) + 1)] = $temp_array;
            $this->setServerConnectionArray($connection_array);
        }
    }

    public function setOldServerConnection($id, $title, $url, $key, $proxy = CS_NO)
    {
        if (!empty($title)
            and !empty($url)
            and !empty($key)
            and !empty($proxy)
            and !empty($id)
        ) {
            $connection_array = $this->getServerConnectionArray();
            $temp_array = array();
            $temp_array['title'] = $title;
            $temp_array['url'] = $url;
            $temp_array['key'] = $key;
            $temp_array['proxy'] = $proxy;
            if (!empty($connection_array[$id]['id'])) {
                $temp_array['id'] = $connection_array[$id]['id'];
            } else {
                $key = '';
                $key .= $title;
                $key .= rand(0, 9);
                $key .= $url;
                $key .= rand(0, 9);
                $key .= $key;
                $key .= rand(0, 9);
                include_once 'functions/date_functions.php';
                $key .= getCurrentDateTimeInMySQL();
                $key = md5($key);
                $temp_array['id'] = $key;
            }
            $connection_array[$id] = $temp_array;
            $this->setServerConnectionArray($connection_array);
        }
    }

    public function getServerConnectionArray()
    {
        $retour = array();
        $value = $this->_getExtraConfig('CONNECTION_ARRAY');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    public function getServerConnectionInfo($id)
    {
        $retour = array();
        $connection_array = $this->getServerConnectionArray();
        if (!empty($connection_array)) {
            foreach ($connection_array as $connection_info) {
                if ($connection_info['id'] == $id) {
                    $retour = $connection_info;
                    break;
                }
            }
        }

        return $retour;
    }

    public function getServerConnectionInfoByKey($key)
    {
        $retour = array();
        $connection_array = $this->getServerConnectionArray();
        if (!empty($connection_array)) {
            foreach ($connection_array as $connection_info) {
                if ($connection_info['key'] == $key) {
                    $retour = $connection_info;
                    break;
                }
            }
        }

        return $retour;
    }

    public function setServerConnectionArray($value)
    {
        $this->_setExtraConfig('CONNECTION_ARRAY', $value);
    }

    public function deleteServerConnection($key)
    {
        if (!empty($key)
            or $key == 0
        ) {
            $connection_array = $this->getServerConnectionArray();
            if (!empty($connection_array[$key])) {
                // delete all tabs on this server
                $server_to_delete = $connection_array[$key];
                $portal_id_array = $this->getPortalIDArray();

                if (!empty($server_to_delete['id'])
                    and !empty($portal_id_array)
                ) {
                    $portal_id_array = $this->getPortalIDArray();

                    $user_manager = $this->_environment->getUserManager();
                    $user_manager->setContextArrayLimit($portal_id_array);
                    $user_manager->setExternalConnectionServerKeyLimit($server_to_delete['id']);
                    $user_manager->select();
                    $user_list = $user_manager->get();
                    if (!empty($user_list)
                        and $user_list->isNotEmpty()
                    ) {
                        $user_item = $user_list->getFirst();
                        while ($user_item) {
                            // delete tabs from server
                            $user_item->deletePortalConnectionFromServer($server_to_delete['id']);
                            $user_item->save();
                            $user_item = $user_list->getNext();
                        }
                    }
                }

                // delete server
                unset($connection_array[$key]);

                // reset keys
                if (!empty($connection_array)) {
                    $key_array = array_keys($connection_array);
                    $temp_array = array();
                    $i = 0;
                    foreach ($key_array as $key) {
                        $i++;
                        $temp_array[$i] = $connection_array[$key];
                    }
                    $connection_array = $temp_array;
                    unset($i);
                    unset($temp_array);
                    unset($key_array);
                    unset($key);
                }

                $this->setServerConnectionArray($connection_array);
            }
        }
    }

    public function isServerConnectionAvailable()
    {
        $retour = false;
        $server_array = $this->getServerConnectionArray();
        if (!empty($server_array)
            and is_array($server_array)
            and count($server_array) > 0
        ) {
            $retour = true;
        }

        return $retour;
    }
}