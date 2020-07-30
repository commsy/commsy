<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez
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

/** upper class of the news item
 */
include_once('classes/cs_item.php');

/** class for a user
 * this class implements a user item
 */
class cs_user_item extends cs_item
{
    /**
     * array - is this needed??
     */
    var $_temp_picture_array = array();

    var $_picture_delete = false;

    var $_old_status = NULL;
    var $_old_contact = NULL;

    var $_changed_values = array();

    private $_context_id_array = NULL;

    /**
     * the user room associated with this user
     * @var \cs_userroom_item|null
     */
    private $_userroomItem = NULL;

    /**
     * for a user item in a user room, returns the project room user associated with this user
     * @var \cs_user_item|null
     */
    private $_projectUserItem = NULL;

    /** constructor: cs_user_item
     * the only available constructor, initial values for internal variables
     */
    function __construct($environment)
    {
        cs_item::__construct($environment);
        $this->_type = CS_USER_TYPE;
        $this->_old_status = 'new';
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array Is the prepared array from "_buildItem($db_array)"
     */
    function _setItemData($data_array)
    {
        $this->_data = $data_array;
        if (isset($data_array['status']) and !empty($data_array['status'])) {
            $this->_old_status = $data_array['status'];
            $this->_old_contact = $data_array['is_contact'];
        }
    }

    /** get user id of the user
     * this method returns the user id (account or Benutzerkennung) of the user
     *
     * @return string user id of the user
     */
    function getUserID()
    {
        return $this->_getValue('user_id');
    }

    /** set user id of the user
     * this method sets the user id (account or Benutzerkennung) of the user
     *
     * @param string value user id of the user
     */
    function setUserID($value)
    {
        $this->_setValue('user_id', $value);
        $this->_changed_values[] = 'user_id';
    }

    function getAuthSource()
    {
        return $this->_getValue('auth_source');
    }

    function setAuthSource($value)
    {
        $this->_setValue('auth_source', $value);
    }

    /** set groups of a news item by id
     * this method sets a list of group item_ids which are linked to the user
     *
     * @param array of group ids, index of id must be 'iid'<br />
     * Example:<br />
     * array(array('iid' => value1), array('iid' => value2))
     */
    function setGroupListByID($value)
    {
        $this->setLinkedItemsByID(CS_GROUP_TYPE, $value);
    }

    /** set one group of a user item by id
     * this method sets one group item id which is linked to the user
     *
     * @param integer group id
     */
    function setGroupByID($value)
    {
        $value_array = array();
        $value_array[] = $value;
        $this->setGroupListByID($value_array);
    }

    /** set one group of a user item
     * this method sets one group which is linked to the user
     *
     * @param object cs_label group
     */
    function setGroup($value)
    {
        if (isset($value)
            and $value->isA(CS_LABEL_TYPE)
            and $value->getLabelType() == CS_GROUP_TYPE
            and $value->getItemID() > 0
        ) {
            $this->setGroupByID($value->getItemID());
            unset($value);
        }
    }

    /** get topics of a user
     * this method returns a list of topics which are linked to the user
     *
     * @return object cs_list a list of topics (cs_label_item)
     */
    function getTopicList()
    {
        $topic_manager = $this->_environment->getLabelManager();
        $topic_manager->setTypeLimit(CS_TOPIC_TYPE);
        return $this->_getLinkedItems($topic_manager, CS_TOPIC_TYPE);
    }

    /** set topics of a user
     * this method sets a list of topics which are linked to the user
     *
     * @param cs_list list of topics (cs_label_item)
     */
    function setTopicList($value)
    {
        $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
    }

    /** set topics of a news item by id
     * this method sets a list of topic item_ids which are linked to the user
     *
     * @param array of topic ids, index of id must be 'iid'<br />
     * Example:<br />
     * array(array('iid' => value1), array('iid' => value2))
     */
    function setTopicListByID($value)
    {
        $this->setLinkedItemsByID(CS_TOPIC_TYPE, $value);
    }

    /** set one topic of a user item by id
     * this method sets one topic item id which is linked to the user
     *
     * @param integer topic id
     */
    function setTopicByID($value)
    {
        $value_array = array();
        $value_array[] = $value;
        $this->setTopicListByID($value_array);
    }

    /** set one topic of a user item
     * this method sets one topic which is linked to the user
     *
     * @param object cs_label topic
     */
    function setTopic($value)
    {
        $this->setTopicByID($value->getItemID());
    }

    /**
     * For a user item in a project room, returns any user room associated with this user
     * @return \cs_userroom_item|null the user room associated with this user
     */
    public function getLinkedUserroomItem(): ?\cs_userroom_item
    {
        if (isset($this->_userroomItem)) {
            return $this->_userroomItem;
        }

        $userroomItemId = $this->getLinkedUserroomItemID();
        if (isset($userroomItemId)) {
            $userroomManager = $this->_environment->getUserroomManager();
            $userroomItem = $userroomManager->getItem($userroomItemId);
            if (isset($userroomItem) and !$userroomItem->isDeleted()) {
                $this->_userroomItem = $userroomItem;
            }
            return $this->_userroomItem;
        }

        return null;
    }

    public function getLinkedUserroomItemID(): ?int
    {
        if ($this->_issetExtra('USERROOM_ITEM_ID')) {
            return $this->_getExtra('USERROOM_ITEM_ID');
        }
        return null;
    }

    public function setLinkedUserroomItemID($roomId)
    {
        $this->_setExtra('USERROOM_ITEM_ID', (int)$roomId);
    }

    public function unsetLinkedUserroomItemID()
    {
        $this->_unsetExtra('USERROOM_ITEM_ID');
    }

    /**
     * For a user item in a user room, returns the project room user who corresponds to this user
     * @return \cs_user_item|null the project room user associated with this user
     */
    public function getLinkedProjectUserItem(): ?\cs_user_item
    {
        if (isset($this->_projectUserItem)) {
            return $this->_projectUserItem;
        }

        $userItemId = $this->getLinkedProjectUserItemID();
        if (isset($userItemId)) {
            $userManager = $this->_environment->getUserManager();
            if ($userManager->existsItem($userItemId)) {
                $userItem = $userManager->getItem($userItemId);
                if (isset($userItem) and !$userItem->isDeleted()) {
                    $this->_projectUserItem = $userItem;
                }
                return $this->_projectUserItem;
            }
        }

        return null;
    }

    public function getLinkedProjectUserItemID(): ?int
    {
        if ($this->_issetExtra('PROJECT_USER_ITEM_ID')) {
            return $this->_getExtra('PROJECT_USER_ITEM_ID');
        }
        return null;
    }

    public function setLinkedProjectUserItemID($userId)
    {
        $this->_setExtra('PROJECT_USER_ITEM_ID', (int)$userId);
    }

    public function unsetLinkedProjectUserItemID()
    {
        $this->_unsetExtra('PROJECT_USER_ITEM_ID');
    }

    /** get firstname of the user
     * this method returns the firstname of the user
     *
     * @return string firstname of the user
     */
    function getFirstname()
    {
        return $this->_getValue("firstname");
    }

    /** set firstname of the user
     * this method sets the firstname of the user
     *
     * @param string value firstname of the user
     */
    function setFirstname($value)
    {
        $this->_setValue("firstname", $value);
        $this->_changed_values[] = 'firstname';
    }

    /** get lastname of the user
     * this method returns the lastname of the user
     *
     * @return string lastname of the user
     */
    function getLastname()
    {
        return $this->_getValue("lastname");
    }

    /** set lastname of the user
     * this method sets the lastname of the user
     *
     * @param string value lastname of the user
     */
    function setLastname($value)
    {
        $this->_setValue("lastname", $value);
        $this->_changed_values[] = 'lastname';
    }

    function makeContactPerson()
    {
        $this->_setValue("is_contact", '1');
    }

    function makeNoContactPerson()
    {
        $this->_setValue("is_contact", '0');
    }

    function getContactStatus()
    {
        $status = $this->_getValue("is_contact");
        return $status;
    }

    function isContact()
    {
        $retour = false;
        $status = $this->getContactStatus();
        if ($status == 1) {
            $retour = true;
        }
        return $retour;
    }

    /** get fullname of the user
     * this method returns the fullname (firstname + lastname) of the user
     *
     * @return string fullname of the user
     */
    function getFullName()
    {
        return ltrim($this->getFirstname() . ' ' . $this->getLastname());
    }

    /** set title of the user
     * this method sets the title of the user
     *
     * @param string value title of the user
     */
    function setTitle($value)
    {
        $this->_addExtra('USERTITLE', (string)$value);
    }

    /** get title of the user
     * this method returns the title of the user
     *
     * @return string title of the user
     */
    function getTitle()
    {
        $retour = '';
        if ($this->_issetExtra('USERTITLE')) {
            $retour = $this->_getExtra('USERTITLE');
        }
        return $retour;
    }

    /** set birthday of the user
     * this method sets the birthday of the user
     *
     * @param string value birthday of the user
     */
    function setBirthday($value)
    {
        $this->_addExtra('USERBIRTHDAY', (string)$value);
    }

    /** get birthday of the user
     * this method returns the birthday of the user
     *
     * @return string birthday of the user
     */
    function getBirthday()
    {
        $retour = '';
        if ($this->_issetExtra('USERBIRTHDAY')) {
            $retour = $this->_getExtra('USERBIRTHDAY');
        }
        return $retour;
    }

    /** set birthday of the user
     * this method sets the birthday of the user
     *
     * @param string value birthday of the user
     */
    function setTelephone($value)
    {
        $this->_addExtra('USERTELEPHONE', (string)$value);
    }

    /** get birthday of the user
     * this method returns the birthday of the user
     *
     * @return string birthday of the user
     */
    function getTelephone()
    {
        $retour = '';
        if ($this->_issetExtra('USERTELEPHONE')) {
            $retour = $this->_getExtra('USERTELEPHONE');
        }
        return $retour;
    }

    /** set celluarphonenumber of the user
     * this method sets the celluarphonenumber of the user
     *
     * @param string value celluarphonenumber of the user
     */
    function setCellularphone($value)
    {
        $this->_addExtra('USERCELLULARPHONE', (string)$value);
    }

    /** get celluarphonenumber of the user
     * this method returns the celluarphonenumber of the user
     *
     * @return string celluarphonenumber of the user
     */
    function getCellularphone()
    {
        $retour = '';
        if ($this->_issetExtra('USERCELLULARPHONE')) {
            $retour = $this->_getExtra('USERCELLULARPHONE');
        }
        return $retour;
    }

    /** set homepage of the user
     * this method sets the homepage of the user
     *
     * @param string value homepage of the user
     */
    function setHomepage($value)
    {
        if (!empty($value) and $value != '-1') {
            if (!mb_ereg("https?://([a-z0-9_./?&=#:@]|-)*", $value)) {
                $value = "http://" . $value;
            }
        }
        $this->_addExtra('USERHOMEPAGE', (string)$value);
    }

    /** get homepage of the user
     * this method returns the homepage of the user
     *
     * @return string homepage of the user
     */
    function getHomepage()
    {
        $retour = '';
        if ($this->_issetExtra('USERHOMEPAGE')) {
            $retour = $this->_getExtra('USERHOMEPAGE');
        }
        return $retour;
    }

    function setOrganisation($value)
    {
        $this->_addExtra('USERORGANISATION', (string)$value);
    }

    function getOrganisation()
    {
        $retour = '';
        if ($this->_issetExtra('USERORGANISATION')) {
            $retour = $this->_getExtra('USERORGANISATION');
        }
        return $retour;
    }

    function setPosition($value)
    {
        $this->_addExtra('USERPOSITION', (string)$value);
    }

    function getPosition()
    {
        $retour = '';
        if ($this->_issetExtra('USERPOSITION')) {
            $retour = $this->_getExtra('USERPOSITION');
        }
        return $retour;
    }

    /** set street of the user
     * this method sets the street of the user
     *
     * @param string value street of the user
     */
    function setStreet($value)
    {
        $this->_addExtra('USERSTREET', (string)$value);
    }

    /** get street of the user
     * this method returns the street of the user
     *
     * @return string street of the user
     */
    function getStreet()
    {
        $retour = '';
        if ($this->_issetExtra('USERSTREET')) {
            $retour = $this->_getExtra('USERSTREET');
        }
        return $retour;
    }

    /** set zipcode of the user
     * this method sets the zipcode of the user
     *
     * @param string value zipcode of the user
     */
    function setZipcode($value)
    {
        $this->_addExtra('USERZIPCODE', (string)$value);
    }

    /** get zipcode of the user
     * this method returns the zipcode of the user
     *
     * @return string zipcode of the user
     */
    function getZipcode()
    {
        $retour = '';
        if ($this->_issetExtra('USERZIPCODE')) {
            $retour = $this->_getExtra('USERZIPCODE');
        }
        return $retour;
    }

    /** set city of the user
     * this method sets the city of the user
     *
     * @param string value city of the user
     */
    function setCity($value)
    {
        $this->_setValue('city', $value);
    }

    /** get city of the user
     * this method returns the city of the user
     *
     * @return string city of the user
     */
    function getCity()
    {
        return $this->_getValue('city');
    }

    /** set room of the user
     * this method sets the room of the user
     *
     * @param string value room of the user
     */
    function setRoom($value)
    {
        $this->_addExtra('USERROOM', (string)$value);
    }

    /** get room of the user
     * this method returns the room of the user
     *
     * @return string room of the user
     */
    function getRoom()
    {
        $retour = '';
        if ($this->_issetExtra('USERROOM')) {
            $retour = $this->_getExtra('USERROOM');
        }
        return $retour;
    }

    /** set description of the user
     * this method sets the description of the user
     *
     * @param string value description of the user
     */
    function setDescription($value)
    {
        $this->_setValue('description', (string)$value);
    }

    /** get description of the user
     * this method returns the description of the user
     *
     * @return string description of the user
     */
    function getDescription()
    {
        return $this->_getValue('description');
    }

    /** set picture filename of the user
     * this method sets the picture filename of the user
     *
     * @param string value picture filename of the user
     */
    function setPicture($name)
    {
        // $this->_temp_picture_array = $value;
        $this->_addExtra('USERPICTURE', $name);
    }

    /** get description of the user
     * this method returns the description of the user
     *
     * @return string description of the user
     */
    function getPicture()
    {
        $retour = '';
        if ($this->_issetExtra('USERPICTURE')) {
            $retour = $this->_getExtra('USERPICTURE');
        }
        return $retour;
    }

    public function getPictureUrl($full = false, $amp = true)
    {
        $retour = '';
        $params['picture'] = $this->getPicture();
        if (!empty($params['picture'])) {
            $retour = curl($this->getContextID(), 'picture', 'getfile', $params, '');
            if (!$amp and strstr($retour, '&amp;')) {
                $retour = str_replace('&amp;', '&', $retour);
            }
            unset($params);
            if ($full) {
                $domain_and_path = '';
                global $c_commsy_domain;
                global $c_commsy_url_path;
                if (!empty($c_commsy_domain)) {
                    $domain_and_path .= $c_commsy_domain;
                }
                if (!empty($c_commsy_url_path)) {
                    $domain_and_path .= $c_commsy_url_path . '/';
                }
                if (substr($domain_and_path, strlen($domain_and_path) - 1) != '/') {
                    $domain_and_path .= '/';
                }
                if (!empty($domain_and_path)) {
                    $retour = $domain_and_path . $retour;
                }
            }
        }
        return $retour;
    }

    /** get email of the user
     * this method returns either the room email
     * of the user or the account email of the
     * user, depending on whether the correponding
     * flag in the database is set or not.
     *
     * @return string email of the user
     */
    public function getEmail()
    {
        if ($this->getUsePortalEmail() || empty($this->_getValue('email'))) {
            if (!($this->getContextItem()->isPortal() && $this->getContextItem()->isServer())) {
                if ($this->getRelatedPortalUserItem()) {
                    return $this->getRelatedPortalUserItem()->getRoomEmail();
                }
            }
        }

        return $this->getRoomEmail();
    }

    /** get room email of the user
     *
     * @return string room email of user
     */
    function getRoomEmail()
    {
        return $this->_getValue('email');
    }

    /** set email of the user
     * this method sets the email of the user
     *
     * @param string value email of the user
     */
    function setEmail($value)
    {
        $this->_setValue('email', (string)$value);
        $this->_changed_values[] = 'email';
    }


    /** set creator of the user - overwritting parent method - do not use
     *
     * @param object cs_user_item value creator of the user
     */
    function setCreator($value)
    {
        echo('use setCreatorID( xxx )<br />');
    }

    /** get deleter - do not use
     * this method is a warning for coders, because if you want an object cs_user_item here, you get into an endless loop
     */
    function getDeleter()
    {
        echo('use getDeleterID()<br />');
    }

    /** set deleter of the user - overwritting parent method - do not use
     *
     * @param object cs_user_item value deleter of the user
     */
    function setDeleter($value)
    {
        echo('use setDeleterID( xxx )<br />');
    }

    /** get user comment
     * this method returns the users comment: why he or she wants an account
     *
     * @return string user comment
     */
    function getUserComment()
    {
        $retour = '';
        if ($this->_issetExtra('USERCOMMENT')) {
            $retour = $this->_getExtra('USERCOMMENT');
        }
        return $retour;
    }

    /** set user comment
     * this method sets the users comment why he or she wants an account
     *
     * @param string value user comment
     */
    function setUserComment($value)
    {
        $this->_addExtra('USERCOMMENT', (string)$value);
    }

    /** get comment of the moderators
     * this method returns the comment of the moderators
     *
     * @return string comment of the moderators
     */
    function getAdminComment()
    {
        $retour = '';
        if ($this->_issetExtra('ADMINCOMMENT')) {
            $retour = $this->_getExtra('ADMINCOMMENT');
        }
        return $retour;
    }

    /** set comment of the moderators
     * this method sets the comment of the moderators
     *
     * @param string value comment
     */
    function setAdminComment($value)
    {
        $this->_addExtra('ADMINCOMMENT', $value);
    }

    /** get flag, if moderator wants a mail at new accounts
     * this method returns the getaccountwantmail flag
     *
     * @return integer value no, moderator doesn't want an e-mail
     *                       yes, moderator wants an e-mail
     */
    function getAccountWantMail()
    {
        $retour = 'yes';
        if ($this->_issetExtra('ACCOUNTWANTMAIL')) {
            $retour = $this->_getExtra('ACCOUNTWANTMAIL');
        }
        return $retour;
    }

    /** set flag if moderator wants a mail at new accounts
     * this method sets the comment of the moderator
     *
     * @param integer value no, moderator doesn't want an e-mail
     *                      yes, moderator wants an e-mail
     */
    function setAccountWantMail($value)
    {
        $this->_addExtra('ACCOUNTWANTMAIL', (string)$value);
    }

    /** get flag, if moderator wants a mail at opening rooms
     * this method returns the getopenroomwantmail flag
     *
     * @return integer value no, moderator doesn't want an e-mail
     *                       yes, moderator wants an e-mail
     */
    function getOpenRoomWantMail()
    {
        $retour = 'yes';
        if ($this->_issetExtra('ROOMWANTMAIL')) {
            $retour = $this->_getExtra('ROOMWANTMAIL');
        }
        return $retour;
    }

    /** set flag if moderator wants a mail at opening rooms
     * this method sets the getopneroomwantmail flag
     *
     * @param integer value no, moderator doesn't want an e-mail
     *                      yes, moderator wants an e-mail
     */
    function setOpenRoomWantMail($value)
    {
        $this->_addExtra('ROOMWANTMAIL', (string)$value);
    }

    function getRoomWantMail()
    {
        return $this->getOpenRoomWantMail();
    }


    public function setDeleteEntryWantMail(bool $enabled)
    {
        if ($enabled) {
            $this->_addExtra('DELETEENTRYMAIL', 'yes');
        } else if ($this->_issetExtra('DELETEENTRYMAIL')) {
            $this->_unsetExtra('DELETEENTRYMAIL');
        }
    }

    public function getDeleteEntryWantMail(): bool
    {
        return $this->_issetExtra('DELETEENTRYMAIL');
    }

    /** get flag, if moderator wants a mail if he has to publish a material
     * this method returns the getaccountwantmail flag
     *
     * @return integer value 0, moderator doesn't want an e-mail
     *                       1, moderator wants an e-mail
     */
    function getPublishMaterialWantMail()
    {
        $retour = 'yes';
        if ($this->_issetExtra('PUBLISHWANTMAIL')) {
            $retour = $this->_getExtra('PUBLISHWANTMAIL');
        }
        return $retour;
    }

    /** set flag if moderator wants a mail if he has to publish a material
     * this method sets the comment of the moderator
     *
     * @param integer value no, moderator doesn't want an e-mail
     *                      yes, moderator wants an e-mail
     */
    function setPublishMaterialWantMail($value)
    {
        $this->_addExtra('PUBLISHWANTMAIL', (string)$value);
    }

    /** get last login time
     * this method returns the last login in datetime format
     *
     * @return string last login
     */
    function getLastLogin()
    {
        return $this->_getValue('lastlogin');
    }

    /** get user language
     * this method returns the users language: de or en or ...
     *
     * @return string user language
     */
    function getLanguage()
    {
        $retour = 'de';
        if ($this->_issetExtra('LANGUAGE')) {
            $retour = $this->_getExtra('LANGUAGE');
        }
        return $retour;
    }

    /** set user language
     * this method sets the users language: de or en or ...
     *
     * @param string value user language
     */
    function setLanguage($value)
    {
        $this->_addExtra('LANGUAGE', (string)$value);
    }

    /** get Visible of the user
     * this method returns the visible Property of the user
     *
     * @return integer visible of the user
     */
    function getVisible()
    {
        if ($this->isVisibleForAll()) {
            return '2';
        } else {
            return '1';
        }
    }

    /** set visible property of the user
     * this method sets the visible Property of the user
     *
     * @param integer value visible of the user
     */
    function setVisible($value)
    {
        if ($value == '2') {
            $this->_setValue('visible', $value);
        } else {
            $this->_setValue('visible', '1');
        }
    }

    /** set visible property of the user to LoggedIn
     */
    function setVisibleToLoggedIn()
    {
        $this->setVisible('1');
    }

    /** set visible property of the user to All
     * this method sets an order limit for the select statement to name
     */
    function setVisibleToAll()
    {
        $this->setVisible('2');
    }

    function isEmailVisible()
    {
        $retour = true;
        $value = $this->_getEmailVisibility();
        if ($value == '-1') {
            $retour = false;
        }
        return $retour;
    }

    function setEmailNotVisible()
    {
        $this->_setEmailVisibility('-1');
    }

    function setEmailVisible()
    {
        $this->_setEmailVisibility('1');
    }

    function _setEmailVisibility($value)
    {
        $this->_addExtra('EMAIL_VISIBILITY', $value);
    }

    function _getEmailVisibility()
    {
        $retour = '';
        if ($this->_issetExtra('EMAIL_VISIBILITY')) {
            $retour = $this->_getExtra('EMAIL_VISIBILITY');
        }
        return $retour;
    }

    public function setDefaultMailNotVisible()
    {
        $this->_setExtra('DEFAULT_MAIL_VISIBILITY', '-1');
    }

    public function setDefaultMailVisible()
    {
        $this->_setExtra('DEFAULT_MAIL_VISIBILITY', '1');
    }

    public function getDefaultIsMailVisible()
    {
        $retour = '';
        if ($this->_issetExtra('DEFAULT_MAIL_VISIBILITY')) {
            $retour = $this->_getExtra('DEFAULT_MAIL_VISIBILITY');
        }

        if ($retour == '1') {
            $retour = true;
        } else {
            $retour = false;
        }
        return $retour;
    }

    // need anymore ??? (TBD)
    function isCommSyContact()
    {
        $retour = false;
        if ($this->getVisible() == 1) {
            $retour = true;
        }
        return $retour;
    }

    // need anymore ??? (TBD)
    function isWorldContact()
    {
        $retour = false;
        if ($this->getVisible() == 2) {
            $retour = true;
        }
        return $retour;
    }

    /** reject a user
     * this method sets the status of the user to rejected
     */
    function reject()
    {
        $this->_setValue('status', 0);
        $this->makeNoContactPerson();
    }

    /** request a user
     * this method sets the status of the user to request, an moderator must free the account
     */
    function request()
    {
        $this->_setValue('status', 1);
    }

    /** make a user normal user
     * this method sets the status of the user to normal
     */
    function makeUser()
    {
        $this->_setValue('status', 2);
    }

    function makeReadOnlyUser()
    {
        $this->_setValue('status', 4);
    }

    /** make a user moderator
     * this method sets the status of the user to moderator
     */
    function makeModerator()
    {
        $this->_setValue('status', 3);
    }

    /** get status of user
     * this method returns an integer value corresponding with the users status
     *
     * @return int status
     */
    function getStatus()
    {
        return $this->_getValue('status');
    }

    /** get status of user
     * this method returns an integer value corresponding with the users status
     *
     * @return int status
     */
    function getLastStatus()
    {
        return $this->_getValue('status_last');
    }

    /** set user status last
     * this method sets the last status of the user, if status changed
     *
     * @param int status
     */
    function setLastStatus($value)
    {
        $this->_setValue('status_last', (int)$value);
    }

    /** set user status
     * this method sets the status of the user
     *
     * @param int status
     */
    function setStatus($value)
    {
        $this->setLastStatus($this->getStatus());
        $this->_setValue('status', (int)$value);
    }

    /** is user rejected ?
     * this method returns a boolean explaining if user is rejected or not
     *
     * @return boolean true, if user is rejected
     *                 false, if user is not rejected
     */
    function isRejected()
    {
        return $this->_getValue('status') == 0;
    }

    /** is user a guest ?
     * this method returns a boolean explaining if user is a guest or not
     *
     * @return boolean true, if user is a guest
     *                 false, if user is not a guest
     */
    function isGuest()
    {
        return $this->_getValue('status') == 0;
    }

    /** is user a guest ?
     * this method returns a boolean explaining if user is a guest or not
     *
     * @return boolean true, if user is a guest
     *                 false, if user is not a guest
     */
    function isReallyGuest()
    {
        return $this->_getValue('status') == 0 and mb_strtolower($this->_getValue('user_id'), 'UTF-8') == 'guest';
    }

    /** user has requested an account
     * this method returns a boolean explaining if user is still in request status
     *
     * @return boolean true, if user is in request status
     *                 false, if user is not in request status
     */
    function isRequested()
    {
        return $this->_getValue('status') == 1;
    }

    /** is user a normal user ?
     * this method returns a boolean explaining if user is a normal user or not
     *
     * @return boolean true, if user is a normal user or moderator
     *                 false, if user is not a normal user or moderator
     */
    function isUser()
    {
        return $this->_getValue('status') >= 2;
    }

    /** is user a moderator ?
     * this method returns a boolean explaining if user is a moderator or not
     *
     * @return boolean true, if user is a moderator
     *                 false, if user is not a moderator
     */
    function isModerator()
    {
        return $this->_getValue('status') == 3;
    }

    function isReadOnlyUser()
    {
        return $this->_getValue('status') == 4;
    }

    function getRelatedRoomItem()
    {
        $room_manager = $this->_environment->getProjectManager();
        return $room_manager->getItem($this->getRoomID());
    }

    function getUserRelatedCommunityList()
    {
        $manager = $this->_environment->getCommunityManager();
        $list = $manager->getUserRelatedCommunityListForUser($this);
        return $list;
    }

    function getRelatedCommunityList()
    {
        $manager = $this->_environment->getCommunityManager();
        $list = $manager->getRelatedCommunityListForUser($this);
        return $list;
    }

    function getRelatedCommunityListAllUserStatus()
    {
        $manager = $this->_environment->getCommunityManager();
        $list = $manager->getRelatedCommunityListForUserAllUserStatus($this);
        return $list;
    }

    function getUserRelatedProjectList()
    {
        $manager = $this->_environment->getProjectManager();
        $list = $manager->getUserRelatedProjectListForUser($this);
        return $list;
    }

    function getRelatedProjectList()
    {
        $manager = $this->_environment->getProjectManager();
        $list = $manager->getRelatedProjectListForUser($this, null);
        return $list;
    }

    function getRelatedProjectListAllUserStatus()
    {
        $manager = $this->_environment->getProjectManager();
        $list = $manager->getRelatedProjectListForUserAllUserStatus($this, null);
        return $list;
    }

    function getUserRelatedGroupList()
    {
        $manager = $this->_environment->getGrouproomManager();
        $list = $manager->getUserRelatedGroupListForUser($this);
        return $list;
    }

    function getRelatedGroupList()
    {
        $manager = $this->_environment->getGrouproomManager();
        $list = $manager->getRelatedGroupListForUser($this);
        return $list;
    }

    function getRelatedProjectListSortByTime()
    {
        $manager = $this->_environment->getProjectManager();
        $list = $manager->getRelatedProjectListForUserSortByTime($this);
        return $list;
    }

    function getRelatedProjectListForMyArea()
    {
        $manager = $this->_environment->getProjectManager();
        $list = $manager->getRelatedProjectListForUserForMyArea($this);
        return $list;
    }

    function getRelatedProjectListSortByTimeForMyArea()
    {
        $manager = $this->_environment->getProjectManager();
        $list = $manager->getRelatedProjectListForUserSortByTimeForMyArea($this);
        return $list;
    }

    public function getContextIDArray()
    {
        if (!isset($this->_context_id_array)) {
            $retour = array();
            $manager = $this->_environment->getRoomManager();
            $manager->setUserIDLimit($this->getUserID());
            $manager->setAuthSourceLimit($this->getAuthSource());
            $manager->setContextLimit('');
            $manager->setWithGrouproom();
            $manager->select();
            $array = $manager->getIDArray();
            if (!empty($array)) {
                $retour = array_merge($retour, $array);
            }
            $own_room = $this->getOwnRoom();
            if (isset($own_room)) {
                $retour[] = $own_room->getItemID();
            }
            $this->_context_id_array = $retour;
        }
        return $this->_context_id_array;
    }

    function _getTaskList()
    {
        $task_manager = $this->_environment->getTaskManager();
        return $task_manager->getTaskListForItem($this);
    }

    /** is user root ?
     * this method returns a boolean explaining if user is root or not
     *
     * @return boolean true, if user is root
     *                 false, if user is not root
     */
    function isRoot()
    {
        return ($this->_getValue('status') == 3)
            and ($this->getUserID() == 'root')
            and ($this->getContextID() == $this->_environment->getServerID());
    }

    /** is user VisibleForAll ?
     * this method returns a boolean explaining if user is Visible for everyone or not
     *
     * @return boolean true, if user is Visible for the Public
     *                 false, else
     */
    function isVisibleForAll()
    {
        return $this->_getValue('visible') == 2;
    }

    /** is user VisibleForLoggedIn ?
     * this method returns a boolean explaining if user is Visible for logged in members or not
     *
     * @return boolean true, if user is Visible for logged in members
     *                 false, else
     */
    function isVisibleForLoggedIn()
    {
        return true;
    }

    function save()
    {
        $user_manager = $this->_environment->getUserManager();
        $this->_save($user_manager);
        $item_id = $this->getItemID();
        if (empty($item_id)) {
            $this->setItemID($user_manager->getCreateID());
        }

        plugin_hook('user_save', $this);

        // ContactPersonString
        $context_item = $this->getContextItem();
        // get grouproom
        if ($context_item->getType() == 'group') {
            $grouproom_array = $context_item->_getItemData();
            $grouproom_id = $grouproom_array['extras']['GROUP_ROOM_ID'];
            $room_manager = $this->_environment->getRoomManager();
            $context_item = $room_manager->getItem($grouproom_id);
        }

        if (isset($context_item)
            and !$context_item->isPortal()
            and !$context_item->isServer()
            and $this->getUserID()
            and mb_strtoupper($this->getUserID()) != 'GUEST'
            and (!isset($this->_old_status)
                or !isset($this->_old_contact)
                or $this->_old_status != $this->getStatus()
                or $this->_old_contact != $this->getContactStatus()
            )
        ) {
            $context_item->renewContactPersonString();
            unset($context_item);
        }

        // set old status to current status
        $this->_old_status = $this->getStatus();
        $this->_old_contact = $this->getContactStatus();

        if (($this->getStatus() == 2) or ($this->getStatus() == 3)) {
            // wenn $this->getStatus() einen freigeschalteten Benutzer angibt
            // 2 = normaler Benutzer
            // 3 = Moderator
            //if($this->_environment->getCurrentContextItem()->WikiEnableDiscussion() == "1"){
            //  $this->updateWikiProfile();
            //}

            //if($this->_environment->getCurrentContextItem()->WikiEnableDiscussionNotification() == "1"){
            //  $this->updateWikiNotification();
            //}
        } else {
            // Wenn der Benutzer gesperrt oder geloescht ist, müssen Profile und
            // Notification entsprechend angepasst werden
            // 0 = gesperrt & geloescht (+ deletion_date)
            //
            // Entscheidung 30.09.2008 - Eintraege bleiben unveraendert im Forum
            //$this->updateWikiRemoveUser();
        }

        $this->updateElastic();
    }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.user');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:User');

        $this->replaceElasticItem($objectPersister, $repository);
    }

    /**
     * This method only updates the LastLogin Of the User.
     * Only the LastLoginField will be touched.
     */
    function updateLastLogin()
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->updateLastLoginOf($this);
    }

    function getOwnRoom($context_id = NULL)
    {
        if ($this->isRoot()) {
            return NULL;
        } else {
            $private_room_manager = $this->_environment->getPrivateRoomManager();
            if (!empty($context_id)) {
                return $private_room_manager->getRelatedOwnRoomForUser($this, $context_id);
            } else {
                return $private_room_manager->getRelatedOwnRoomForUser($this, $this->_environment->getCurrentPortalID());
            }

        }
    }

    function delete()
    {
        // delete associated tasks
        $task_list = $this->_getTaskList();
        if (isset($task_list)) {
            $current_task = $task_list->getFirst();
            while ($current_task) {
                $current_task->delete();
                $current_task = $task_list->getNext();
            }
        }

        // in case of portal user, delete own room
        if ($this->_environment->getCurrentPortalID() == $this->getContextID()) {
            $own_room = $this->getOwnRoom();
            if (isset($own_room)) {
                $own_room->delete();
            }
        }

        // delete any associated user room
        $userroom = $this->getLinkedUserroomItem();
        if ($userroom) {
            $userroom->delete();
        }

        $this->makeNoContactPerson();
        $user_manager = $this->_environment->getUserManager();
        $this->_delete($user_manager);

        // ContactPersonString
        $context_item = $this->getContextItem();
        if (isset($context_item)
            and !$context_item->isPortal()
            and !$context_item->isServer()
            and (!isset($this->_old_status)
                or !isset($this->_old_contact)
                or $this->_old_status != $this->getStatus()
                or $this->_old_contact != $this->getContactStatus()
            )
        ) {
            $context_item->renewContactPersonString();
            unset($context_item);
        }

        // set old status to current status
        $this->_old_status = $this->getStatus();
        $this->_old_contact = $this->getContactStatus();

        if ($this->_environment->getCurrentPortalID() == $this->getContextID()) {
            $id_manager = $this->_environment->getExternalIdManager();
            $id_manager->deleteByCommSyID($this->getItemID());
            unset($id_manager);
        }

        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.user');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:User');

        $this->deleteElasticItem($objectPersister, $repository);
    }

    /**
     * Check if this user can be seen by $userItem
     *
     * @see cs_item::mayPortfolioSee()
     */
    public function mayPortfolioSee($userItem)
    {
        $portfolioManager = $this->_environment->getPortfolioManager();

        $userArray = $portfolioManager->getPortfolioUserForExternalViewer($this->getItemId());

        return in_array($userItem->getUserId(), $userArray);
    }

    function maySee($user_item)
    {
        if ($this->_environment->inCommunityRoom()) {  // Community room
            if ($user_item->isRoot()
                or ($user_item->isGuest() and $this->isVisibleForAll())
                or (($user_item->getContextID() == $this->getContextID()
                        or $user_item->getContextID() == $this->_environment->getCurrentContextID()
                    )
                    and (($user_item->isUser()
                            and $this->isVisibleForLoggedIn()
                        )
                        or ($user_item->getUserID() == $this->getUserID()
                            and $user_item->getAuthSource == $this->getAuthSource()
                        )
                        or ($user_item->isModerator()
                        )
                    ))) {
                $access = true;
            } else {
                $access = false;
            }
        } else {    // Project room, group room, private room, portal
            $access = parent::maySee($user_item);
            if ($access) {
                $room = $this->_environment->getCurrentContextItem();
                if ($room->isPrivateRoom()
                    or $room->isPortal()
                    or $room->withRubric(CS_USER_TYPE)
                ) {
                    $access = true;
                } else {
                    // if user rubric is not active, user can always see himself
                    if (!$room->withRubric(CS_USER_TYPE)) {
                        if ($user_item->getUserID() == $this->getUserID() && $user_item->getAuthSource() == $this->getAuthSource()) {
                            $access = true;
                        } elseif ($user_item->isModerator()){
                            $access = true;
                        } else {
                            $access = false;
                        }
                    } else {
                        $access = false;
                    }
                }
            }
        }
        return $access;
    }

    /**
     * Checks whether the current user is editable by the given one
     *
     * @param cs_user_item $userItem the user object asking for permission
     * @return bool
     */

    public function mayEdit(cs_user_item $userItem)
    {
        // readonly users aren't allowed to edit anyone
        if ($userItem->isOnlyReadUser()) {
            return false;
        }

        // root is always allowed to edit
        if ($userItem->isRoot()) {
            return true;
        }

        // if both live in the same context
        if ($userItem->getContextID() == $this->getContextID()) {

            // Moderators are only allowed to edit user in portal context
            if ($userItem->isModerator()) { // && $userItem->getContextItem()->isPortal()
                return true;
            }

            // Allow users to edit themselves
            if ($userItem->isUser() &&
                $this->getUserID() == $userItem->getUserID() &&
                $this->getAuthSource() == $userItem->getAuthSource()
            ) {
                return true;
            }
        }

        return false;
    }

    function mayEditRegular($user_item)
    {
        $access = false;
        if (!$user_item->isOnlyReadUser()) {
            $access = $this->getUserID() == $user_item->getUserID() and $this->getAuthSource() == $user_item->getAuthSource();
        }
        return $access;
    }

    /**
     * @param object cs_user User-Item with changed information
     */
    function changeRelatedUser($dummy_item)
    {
        $related_user = $this->getRelatedUserList();
        if (!$related_user->isEmpty()) {
            $user_item = $related_user->getFirst();
            while ($user_item) {
                $old_fullname = $user_item->getFullName();
                $value = $dummy_item->getFirstName();
                if (!empty($value)) {
                    $user_item->setFirstName($value);
                }
                $value = $dummy_item->getLastName();
                if (!empty($value)) {
                    $user_item->setLastName($value);
                }
                $value = $dummy_item->getTitle();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setTitle($value);
                }
                $value = $dummy_item->getTelephone();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setTelephone($value);
                }
                $value = $dummy_item->getBirthday();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setBirthday($value);
                }
                $value = $dummy_item->getCellularphone();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setCellularphone($value);
                }
                $value = $dummy_item->getHomepage();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setHomepage($value);
                }
                $value = $dummy_item->getOrganisation();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setOrganisation($value);
                }
                $value = $dummy_item->getPosition();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setPosition($value);
                }
                $value = $dummy_item->getStreet();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setStreet($value);
                }
                $value = $dummy_item->getZipCode();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setZipCode($value);
                }
                $value = $dummy_item->getCity();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setCity($value);
                }
                $value = $dummy_item->getDescription();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setDescription($value);
                }
                $value = $dummy_item->getPicture();
                if (!empty($value)) {
                    if ($value == -1) {
                        $new_picture_name = '';
                    } else {
                        $value_array = explode('_', $value);
                        $value_array[0] = 'cid' . $user_item->getContextID();
                        $new_picture_name = implode('_', $value_array);
                        $disc_manager = $this->_environment->getDiscManager();
                        $disc_manager->copyImageFromRoomToRoom($value, $user_item->getContextID());
                    }
                    $user_item->setPicture($new_picture_name);
                }
                $value = $dummy_item->getEmail();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setEmail($value);

                    if (!$dummy_item->isEmailVisible()) {
                        $user_item->setEmailNotVisible();
                    } else {
                        $user_item->setEmailVisible();
                    }
                }
                $value = $dummy_item->getRoom();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setRoom($value);
                }
                $value = $dummy_item->getICQ();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setICQ($value);
                }
                $value = $dummy_item->getJabber();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setJabber($value);
                }
                $value = $dummy_item->getMSN();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setMSN($value);
                }
                $value = $dummy_item->getSkype();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setSkype($value);
                }
                $value = $dummy_item->getYahoo();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setYahoo($value);
                }
                $value = $dummy_item->getExternalID();
                if (!empty($value)) {
                    if ($value == -1) {
                        $value = '';
                    }
                    $user_item->setExternalID($value);
                }
                $value = $dummy_item->getIsAllowedToCreateContext();
                if (!empty($value)) {
                    $user_item->setIsAllowedToCreateContext($value);
                }

                $current_user_item = $this->_environment->getCurrentUserItem();
                if (!$current_user_item->isRoot()
                    and $current_user_item->getItemID() != $user_item->getContextID()
                    and $current_user_item->getUserID() == $user_item->getUserID()
                    and $current_user_item->getAuthSource() == $user_item->getAuthSource()
                ) {
                    $user_item->setModificatorItem($user_item);
                }

                $user_item->save();
                if ($old_fullname != $user_item->getFullName()
                    and $user_item->isContact()
                ) {
                    $room_id = $user_item->getContextID();
                    $room_manager = $this->_environment->getRoomManager();
                    $room_item = $room_manager->getItem($room_id);
                    $room_item->renewContactPersonString();
                }

                $user_item = $related_user->getNext();
            }
        }
    }

    /**
     * @return object cs_list list of User-Items connected to this item
     */
    function getRelatedUserList(): \cs_list
    {

        $current_context_id = $this->getContextID();

        $room_id_array = array();
        if ($this->_environment->getCurrentPortalID() != $current_context_id) {
            $portalID = $this->_environment->getCurrentPortalID();
            if (!empty($portalID)) {
                $room_id_array[] = $this->_environment->getCurrentPortalID();
            }
        }

        $community_manager = $this->_environment->getCommunityManager();
        $community_list = $community_manager->getRelatedCommunityListForUser($this);
        if ($community_list->isNotEmpty()) {
            $community_room = $community_list->getFirst();
            while ($community_room) {
                if ($community_room->getItemID() != $current_context_id) {
                    $room_id_array[] = $community_room->getItemID();
                }
                unset($community_room);
                $community_room = $community_list->getNext();
            }
            unset($community_list);
        }
        unset($community_manager);

        $project_manager = $this->_environment->getProjectManager();
        $project_list = $project_manager->getRelatedProjectListForUser($this, $current_context_id);
        if ($project_list->isNotEmpty()) {
            $project_room = $project_list->getFirst();
            while ($project_room) {
                if ($project_room->getItemID() != $current_context_id) {
                    $room_id_array[] = $project_room->getItemID();
                }
                unset($project_room);
                $project_room = $project_list->getNext();
            }
            unset($project_list);
        }
        unset($project_manager);

        $private_room_manager = $this->_environment->getPrivateRoomManager();
        $own_room = $private_room_manager->getRelatedOwnRoomForUser($this, $this->_environment->getCurrentPortalID());
        if (isset($own_room) and !empty($own_room)) {
            $room_id = $own_room->getItemID();
            if (!empty($room_id)) {
                $room_id_array[] = $room_id;
            }
            unset($own_room);
        }
        unset($private_room_manager);

        if (!empty($room_id_array)) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->resetLimits();
            $user_manager->setContextArrayLimit($room_id_array);
            $user_manager->setUserIDLimit($this->getUserID());
            $user_manager->setAuthSourceLimit($this->getAuthSource());
            $user_manager->select();
            $user_list = $user_manager->get();
            unset($user_manager);
        } else {
            include_once('classes/cs_list.php');
            $user_list = new cs_list();
        }

        return $user_list;
    }

    public function getRelatedUserItemInContext($value):? \cs_user_item
    {
        $retour = NULL;
        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($value);
        $user_manager->setUserIDLimit($this->getUserID());
        $user_manager->setAuthSourceLimit($this->getAuthSource());
        $user_manager->select();
        $user_list = $user_manager->get();
        if (isset($user_list)
            and $user_list->isNotEmpty()
            and $user_list->getCount() == 1
        ) {
            $retour = $user_list->getFirst();
        }
        unset($user_manager);
        unset($user_list);
        return $retour;
    }

    /**
     * @return \cs_user_item|null User-Item from the community room
     */
    function getRelatedCommSyUserItem()
    {
        if (mb_strtoupper($this->getUserID(), 'UTF-8') == 'ROOT') {
            $retour = $this;
        } else {
            $item_manager = $this->_environment->getItemManager();
            $item = $item_manager->getItem($this->getContextID());

            if (isset($item)) {
                if ($item->getItemType() == CS_COMMUNITY_TYPE
                    or $item->getItemType() == CS_PROJECT_TYPE
                    or $item->getItemType() == CS_GROUPROOM_TYPE
                ) {
                    $room_manager = $this->_environment->getManager(CS_ROOM_TYPE);
                    $room = $room_manager->getItem($this->getContextID());
                    if (!empty($room)) {
                        $portal_id = $room->getContextID();
                    } else {
                        $portal_id = $item->getContextID();
                    }
                } elseif ($item->getItemType() == CS_PRIVATEROOM_TYPE) {
                    $room_manager = $this->_environment->getManager(CS_PRIVATEROOM_TYPE);
                    $room = $room_manager->getItem($this->getContextID());
                    $portal_id = $room->getContextID();
                } elseif ($item->getItemType() == CS_PORTAL_TYPE) {
                    $portal_id = $this->getContextID();
                }
            }

            $retour = NULL;
            $user_manager = $this->_environment->getUserManager();
            $user_manager->resetLimits();
            if (!isset($portal_id)) {
                $portal_id = $this->getContextID();
            }
            $user_manager->setContextLimit($portal_id);
            $user_manager->setAuthSourceLimit($this->getAuthSource());
            $user_manager->setUserIDLimit($this->getUserID());
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->getCount() == 1) {
                $retour = $user_list->getFirst();
            } // archive
            elseif ($user_list->getCount() == 0
                and $this->_environment->isArchiveMode()
            ) {
                $this->_environment->deactivateArchiveMode();
                $retour = $this->getRelatedCommSyUserItem();
                $this->_environment->activateArchiveMode();
            }
            // archive

        }
        return $retour;
    }

    /**
     * @return \cs_user_item
     */
    public function getRelatedPrivateRoomUserItem()
    {
        $retour = NULL;

        // archive
        $toggle_archive = false;
        if ($this->_environment->isArchiveMode()) {
            $toggle_archive = true;
            $this->_environment->deactivateArchiveMode();
        }
        // archive

        $private_room_manager = $this->_environment->getPrivateRoomManager();
        $own_room = $private_room_manager->getRelatedOwnRoomForUser($this, $this->_environment->getCurrentPortalID());
        unset($private_room_manager);
        if (isset($own_room)) {
            $own_cid = $own_room->getItemID();
            $user_manager = $this->_environment->getUserManager();
            $user_manager->resetLimits();
            $user_manager->setContextLimit($own_cid);
            $user_manager->setUserIDLimit($this->getUserID());
            $user_manager->setAuthSourceLimit($this->getAuthSource());
            $user_manager->select();
            $user_list = $user_manager->get();
            unset($user_manager);
            if ($user_list->getCount() == 1) {
                $retour = $user_list->getFirst();
            }
            unset($user_list);
        }
        unset($own_room);

        // archive
        if ($toggle_archive) {
            $this->_environment->activateArchiveMode();
        }
        // archive

        return $retour;
    }

    function getRelatedPortalUserItem():? \cs_user_item
    {
        $retour = NULL;

        // archive
        $toggle_archive = false;
        if ($this->_environment->isArchiveMode()) {
            $toggle_archive = true;
            $this->_environment->deactivateArchiveMode();
        }
        // archive

        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
        $user_manager->setUserIDLimit($this->getUserID());
        $user_manager->setAuthSourceLimit($this->getAuthSource());
        $user_manager->select();
        $user_list = $user_manager->get();
        unset($user_manager);
        if ($user_list->getCount() == 1) {
            $retour = $user_list->getFirst();
        }
        unset($user_list);

        // archive
        if ($toggle_archive) {
            $this->_environment->activateArchiveMode();
        }
        // archive

        return $retour;
    }

    function getModifiedItemIDArray($type, $creator_id)
    {
        $id_array = array();
        $link_manager = $this->_environment->getLinkItemManager();
        $context_item = $this->_environment->getCurrentContextItem();
        $link_ids = $link_manager->getModiefiedItemIDArray($type, $creator_id);
        foreach ($link_ids as $id) {
            $id_array[] = $id;
        }
        return $id_array;
    }

    function cloneData()
    {
        $new_room_user = clone $this;
        $new_room_user->unsetContextID();
        $new_room_user->unsetItemID();
        $new_room_user->unsetCreatorID();
        $new_room_user->unsetCreatorDate();
        $new_room_user->unsetAGBAcceptanceDate();
        $new_room_user->unsetLinkedUserroomItemID();
        $new_room_user->unsetLinkedProjectUserItemID();
        $new_room_user->_unsetValue('modifier_id');
        return $new_room_user;
    }

    function unsetContextID()
    {
        $this->_unsetValue('context_id');
    }

    function unsetItemID()
    {
        $this->_unsetValue('item_id');
    }

    function unsetCreatorID()
    {
        $this->_unsetValue('creator_id');
    }

    function unsetCreatorDate()
    {
        $this->_unsetValue('creator_date');
    }

    function setCreatorID2ItemID()
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setCreatorID2ItemID($this);
    }

    function isDeletable()
    {
        $value = false;
        $item_manager = $this->_environment->getItemManager();
        $annotation_manager = $this->_environment->getAnnotationManager();
        $link_manager = $this->_environment->getLinkItemManager();
        $result1 = $item_manager->getCountExistingItemsOfUser($this->getItemID());
        $result2 = $annotation_manager->getCountExistingAnnotationsOfUser($this->getItemID());
        $result3 = $link_manager->getCountExistingLinkItemsOfUser($this->getItemID());
        if ($result1 == 0 and $result2 == 0 and $result3 == 0) {
            $value = true;
        }
        return $value;
    }

    function deleteAllEntriesOfUser()
    {

        // datenschutz: overwrite or not (03.09.2012 IJ)
        $overwrite = true;
        global $symfonyContainer;
        $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
        if (!empty($disable_overwrite) and $disable_overwrite === 'TRUE') {
            $overwrite = false;
        }

        if ($overwrite) {
            $announcement_manager = $this->_environment->getAnnouncementManager();
            $dates_manager = $this->_environment->getDatesManager();
            $discussion_manager = $this->_environment->getDiscussionManager();
            $discarticle_manager = $this->_environment->getDiscussionarticleManager();
            $material_manager = $this->_environment->getMaterialManager();
            $section_manager = $this->_environment->getSectionManager();
            $annotation_manager = $this->_environment->getAnnotationManager();
            $label_manager = $this->_environment->getLabelManager();
            $tag_manager = $this->_environment->getTagManager();
            $todo_manager = $this->_environment->getToDoManager();
            $step_manager = $this->_environment->getStepManager();


            // replace users entries with the standard message for deleted entries
            $announcement_manager->deleteAnnouncementsofUser($this->getItemID());
            $dates_manager->deleteDatesOfUser($this->getItemID());
            $discussion_manager->deleteDiscussionsOfUser($this->getItemID());
            $discarticle_manager->deleteDiscarticlesOfUser($this->getItemID());
            $material_manager->deleteMaterialsOfUser($this->getItemID());
            $section_manager->deleteSectionsOfUser($this->getItemID());
            $annotation_manager->deleteAnnotationsOfUser($this->getItemID());
            $todo_manager->deleteTodosOfUser($this->getItemID());
            $step_manager->deleteStepsOfUser($this->getItemID());


            // NOTE: we don't replace hashtags (aka buzzwords) and categories (aka tags) with the standard message for
            // deleted entries since these are structural elements benefitting all room users, and which have no direct
            // association in the UI to the user who created them.
            // However note that, even with these lines uncommented, buzzwords currently won't get overwritten in the UI
            // if the server option `security.privacy_disable_overwriting` (in parameters.yml) is set to `flag`.
//          $label_manager->deleteLabelsOfUser($this->getItemID());
//          $tag_manager->deleteTagsOfUser($this->getItemID());
        }
    }

    function setAGBAcceptance()
    {
        include_once('functions/date_functions.php');
        $this->_setAGBAcceptanceDate(getCurrentDateTimeInMySQL());
    }

    function unsetAGBAcceptanceDate()
    {
        $this->_setAGBAcceptanceDate('');
    }

    function _setAGBAcceptanceDate($value)
    {
        $this->_addExtra('AGB_ACCEPTANCE_DATE', $value);
    }

    function getAGBAcceptanceDate()
    {
        $retour = '';
        if ($this->_issetExtra('AGB_ACCEPTANCE_DATE')) {
            $retour = $this->_getExtra('AGB_ACCEPTANCE_DATE');
        }
        return $retour;
    }

    /** OLD FUNCTION
     * public function isAutoSaveOn () {
     * $retour = false;
     * if ( $this->_environment->inPrivateRoom() ) {
     * $value = $this->getAutoSaveStatus();
     * } else {
     * $priv_user = $this->getRelatedPrivateRoomUserItem();
     * if ( isset($priv_user) and !empty($priv_user) ) {
     * $value = $priv_user->getAutoSaveStatus();
     * unset($priv_user);
     * } else {
     * $value = -1;
     * }
     * }
     * if ( !empty($value) and $value == 1 ) {
     * $retour = true;
     * }
     * return $retour;
     * }
     **/
    public function isAutoSaveOn()
    {
        $retour = false;
        $portal_user = $this->getRelatedPortalUserItem();
        if (isset($portal_user) and !empty($portal_user)) {
            $value = $portal_user->getAutoSaveStatus();
            unset($portal_user);
        } else {
            $value = -1;
        }
        if (!empty($value) and $value == 1) {
            $retour = true;
        }
        return $retour;
    }

    public function getAutoSaveStatus()
    {
        $retour = '';
        if ($this->_issetExtra('CONFIG_AUTOSAVE_STATUS')) {
            $retour = $this->_getExtra('CONFIG_AUTOSAVE_STATUS');
        }
        return $retour;
    }

    public function _setAutoSaveStatus($value)
    {
        $this->_addExtra('CONFIG_AUTOSAVE_STATUS', $value);
    }

    public function turnAutoSaveOn()
    {
        $this->_setAutoSaveStatus(1);
    }

    public function turnAutoSaveOff()
    {
        $this->_setAutoSaveStatus(-1);
    }

    public function isNewUploadOn()
    {
        $retour = true;
        $portal_user = $this->getRelatedPortalUserItem();
        if (isset($portal_user) and !empty($portal_user)) {
            $value = $portal_user->getNewUploadStatus();
            unset($portal_user);
        } else {
            $value = 1;
        }
        if (!empty($value) and $value == -1) {
            $retour = false;
        }
        return $retour;
    }

    public function getNewUploadStatus()
    {
        $retour = '';
        if ($this->_issetExtra('CONFIG_NEW_UPLOAD_STATUS')) {
            $retour = $this->_getExtra('CONFIG_NEW_UPLOAD_STATUS');
        }
        return $retour;
    }

    public function _setNewUploadStatus($value)
    {
        $this->_addExtra('CONFIG_NEW_UPLOAD_STATUS', $value);
    }

    public function turnNewUploadOn()
    {
        $this->_setNewUploadStatus(1);
    }

    public function turnNewUploadOff()
    {
        $this->_setNewUploadStatus(-1);
    }


    public function setICQ($number)
    {
        if ($this->_issetExtra('ICQ')) {
            $this->_setExtra('ICQ', $number);
        } else {
            $this->_addExtra('ICQ', $number);
        }
    }

    public function getICQ()
    {
        $result = '';
        if ($this->_issetExtra('ICQ')) {
            $result = $this->_getExtra('ICQ');
        }
        return $result;
    }

    public function setMSN($number)
    {
        if ($this->_issetExtra('MSN')) {
            $this->_setExtra('MSN', $number);
        } else {
            $this->_addExtra('MSN', $number);
        }
    }

    public function getMSN()
    {
        $result = '';
        if ($this->_issetExtra('MSN')) {
            $result = $this->_getExtra('MSN');
        }
        return $result;
    }

    public function setSkype($number)
    {
        if ($this->_issetExtra('SKYPE')) {
            $this->_setExtra('SKYPE', $number);
        } else {
            $this->_addExtra('SKYPE', $number);
        }
    }

    public function getSkype()
    {
        $result = '';
        if ($this->_issetExtra('SKYPE')) {
            $result = $this->_getExtra('SKYPE');
        }
        return $result;
    }

    public function setJabber($number)
    {
        if ($this->_issetExtra('JABBER')) {
            $this->_setExtra('JABBER', $number);
        } else {
            $this->_addExtra('JABBER', $number);
        }
    }

    public function getJabber()
    {
        $result = '';
        if ($this->_issetExtra('JABBER')) {
            $result = $this->_getExtra('JABBER');
        }
        return $result;
    }

    public function setYahoo($number)
    {
        if ($this->_issetExtra('YAHOO')) {
            $this->_setExtra('YAHOO', $number);
        } else {
            $this->_addExtra('YAHOO', $number);
        }
    }

    public function getYahoo()
    {
        $result = '';
        if ($this->_issetExtra('YAHOO')) {
            $result = $this->_getExtra('YAHOO');
        }
        return $result;
    }

    public function isInGroup($group_item)
    {
        $retour = false;
        if (isset($group_item)
            and $group_item->getItemID() > 0
        ) {
            $group_list = $this->getGroupList();
            $retour = $group_list->inList($group_item);
            unset($group_list);
            unset($group_item);
        }
        return $retour;
    }


    public function isActiveDuringLast99Days()
    {
        include_once('functions/date_functions.php');
        return $this->getLastLogin() > getCurrentDateTimeMinusDaysInMySQL(99);
    }

    public function updateWikiProfile()
    {
        $wiki_manager = $this->_environment->getWikiManager();
        $wiki_manager->updateWikiProfileFile($this);
        //$wiki_manager->updateWikiProfileFile_soap($this);
    }

    public function updateWikiNotification()
    {
        $wiki_manager = $this->_environment->getWikiManager();
        $wiki_manager->updateNotification();
    }

    // Entscheidung 30.09.2008 - Eintraege bleiben unveraendert im Forum
    //public function updateWikiRemoveUser(){
    //     $wiki_manager = $this->_environment->getWikiManager();
    //     $wiki_manager->updateWikiRemoveUser($this);
    //}

    public function isRoomMember()
    {
        $retour = false;

        // project rooms
        $list = $this->getRelatedProjectList();
        if (isset($list) and $list->isNotEmpty()) {
            $count = $list->getCount();
            if ($count > 0) {
                $retour = true;
            }
        }
        unset($list);

        // community rooms
        if (!$retour) {
            $list = $this->getRelatedCommunityList();
            if (isset($list) and $list->isNotEmpty()) {
                $count = $list->getCount();
                if ($count > 0) {
                    $retour = true;
                }
            }
            unset($list);
        }

        // group room
        if (!$retour) {
            $list = $this->getRelatedGroupList();
            if (isset($list) and $list->isNotEmpty()) {
                $count = $list->getCount();
                if ($count > 0) {
                    $retour = true;
                }
            }
            unset($list);
        }

        return $retour;
    }

    function getDataAsXML()
    {
        return $this->_getDataAsXML();
    }

    public function isOnlyReadUser()
    {
        if ($this->isReadOnlyUser()) {
            return true;
        }

        $retour = false;
        global $c_read_account_array;
        if (isset($c_read_account_array)
            and !empty($c_read_account_array[mb_strtolower($this->getUserID(), 'UTF-8') . '_' . $this->getAuthSource()])
        ) {
            $retour = true;
        }
        return $retour;
    }

    public function hasChanged($value)
    {
        $result = false;
        foreach ($this->_changed_values as $changed_value) {
            if ($changed_value == $value) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    private function _setHasToChangeEmail($value)
    {
        $this->_addExtra('HASTOCHANGEEMAIL', (int)$value);
    }

    public function setHasToChangeEmail()
    {
        $this->_setHasToChangeEmail(1);
    }

    public function unsetHasToChangeEmail()
    {
        $this->_setHasToChangeEmail(-1);
    }

    private function _getHasToChangeEmail()
    {
        $retour = '';
        if ($this->_issetExtra('HASTOCHANGEEMAIL')) {
            $retour = $this->_getExtra('HASTOCHANGEEMAIL');
        }
        return $retour;
    }

    public function hasToChangeEmail()
    {
        $retour = false;
        $temp = $this->_getHasToChangeEmail();
        if (!empty($temp)
            and $temp == 1
        ) {
            $retour = true;
        }
        return $retour;
    }

    function setExternalID($value)
    {
        $this->_addExtra('EXTERNALID', (string)$value);
    }

    function getExternalID()
    {
        $retour = '';
        if ($this->_issetExtra('EXTERNALID')) {
            $retour = $this->_getExtra('EXTERNALID');
        }
        return $retour;
    }

    /** get lastlogin from plugin
     * this method returns the users plugin lastlogin
     *
     * @return string timestamp
     */
    function getLastLoginPlugin($plugin)
    {
        $retour = '';
        if ($this->_issetExtra('LASTLOGIN_' . mb_strtoupper($plugin))) {
            $retour = $this->_getExtra('LASTLOGIN_' . mb_strtoupper($plugin));
        }
        return $retour;
    }

    /** set user lastlogin from plugin
     * this method sets the users users plugin lastlogin
     *
     * @param string value timestamp
     * @param string plugin plugin identifier
     */
    function setLastLoginPlugin($value, $plugin)
    {
        $this->_addExtra('LASTLOGIN_' . mb_strtoupper($plugin), (string)$value);
    }

    function isTemporaryLocked()
    {
        $retour = false;
        if ($this->_issetExtra('TEMPORARY_LOCK')) {
            include_once('functions/date_functions.php');
            $date = $this->_getExtra('TEMPORARY_LOCK');
            if (getCurrentDateTimeInMySQL() > $date) {
                $retour = false;
            } else {
                $retour = true;
            }
        }
        return $retour;
    }

    function setLock($days)
    {
        include_once('functions/date_functions.php');
        $this->_addExtra('LOCK', getCurrentDateTimePlusDaysInMySQL($days));
    }

    function getLock()
    {
        $retour = '';
        if ($this->_issetExtra('LOCK')) {
            $retour = $this->_getExtra('LOCK');
        }
        return $retour;
    }

    function isLocked()
    {
        $retour = false;
        if ($this->_issetExtra('LOCK')) {
            include_once('functions/date_functions.php');
            $date = $this->_getExtra('LOCK');
            if (getCurrentDateTimeInMySQL() > $date) {
                $retour = false;
            } else {
                $retour = true;
            }
        }
        return $retour;
    }

    function unsetLock()
    {
        $this->_unsetExtra('LOCK');
    }

    function setTemporaryLock()
    {
        include_once('functions/date_functions.php');
        $lock_time = $this->_environment->getCurrentContextItem()->getLockTime();
        $this->_addExtra('TEMPORARY_LOCK', getCurrentDateTimePlusMinutesInMySQL($lock_time));
    }

    function getTemporaryLock()
    {
        $retour = '';
        if ($this->_issetExtra('TEMPORARY_LOCK')) {
            $retour = $this->_getExtra('TEMPORARY_LOCK');
        }
        return $retour;
    }

    function unsetTemporaryLock()
    {
        $this->_unsetExtra('TEMPORARY_LOCK');
    }

    // save last used passwords
    function setGenerationPassword($generation, $password)
    {
        $this->_addExtra('PW_GENERATION_' . $generation, $password);
    }

    function getGenerationPassword($generation)
    {
        if ($this->_issetExtra('PW_GENERATION_' . $generation)) {
            $retour = $this->_getExtra('PW_GENERATION_' . $generation);
        }
    }

    function setNewGenerationPassword($password)
    {
        $portal_item = $this->_environment->getCurrentPortalItem();

        $i = $portal_item->getPasswordGeneration();
        if ($i != 0) {
            // shift hashes for a new generation password
            for ($i; $i > 0; $i--) {
                if ($this->_issetExtra('PW_GENERATION_' . ($i - 1)) AND $i != 1) {
                    $this->_addExtra('PW_GENERATION_' . $i, $this->_getExtra('PW_GENERATION_' . ($i - 1)));
                }
            }
            $this->_addExtra('PW_GENERATION_1', $password);
        }
        unset($portal_item);
    }

    function isPasswordInGeneration($password)
    {
        $portal_item = $this->_environment->getCurrentPortalItem();

        $retour = false;
        $i = $portal_item->getPasswordGeneration();
        if ($i == 0) {
            $authentication = $this->_environment->getAuthenticationObject();

            $authManager = $authentication->getAuthManager($this->getAuthSource());
            $auth_item = $authManager->getItem($this->getUserID());
            if ($auth_item->getPasswordMD5() == $password) {
                $retour = true;
            }
        } else {
            for ($i; $i > 0; $i--) {
                if ($this->_issetExtra('PW_GENERATION_' . ($i))) {
                    if ($this->_getExtra('PW_GENERATION_' . $i) == $password) {
                        $retour = true;
                    }
                }
            }
        }

        unset($portal_item);
        return $retour;
    }

    function deactivateLoginAsAnotherUser()
    {
        $this->_addExtra('DEACTIVATE_LOGIN_AS', '1');
    }

    function unsetDeactivateLoginAsAnotherUser()
    {
        if ($this->_issetExtra('DEACTIVATE_LOGIN_AS')) {
            $this->_unsetExtra('DEACTIVATE_LOGIN_AS');
        }
        #$this->_unsetExtra('DEACTIVATE_LOGIN_AS');
    }

    function isDeactivatedLoginAsAnotherUser()
    {
        $retour = '';
        if ($this->_issetExtra('DEACTIVATE_LOGIN_AS')) {
            $flag = $this->_getExtra('DEACTIVATE_LOGIN_AS');
            $retour = $flag;
        }

        return $retour;

    }

    function setPasswordExpireDate($days)
    {
        if ($days == 0) {
            $this->_setValue('expire_date', 'NULL');
        } else {
            $this->_setValue('expire_date', getCurrentDateTimePlusDaysInMySQL($days, true));
        }
        $this->unsetPasswordExpiredEmailSend();
    }

    function unsetPasswordExpireDate()
    {
        $this->_setValue('expire_date', '');
    }

    function getPasswordExpireDate()
    {
        return $this->_getValue('expire_date');
//    	$retour = '';
//    	if($this->_issetExtra('PW_EXPIRE_DATE')){
//    		$retour = $this->_getExtra('PW_EXPIRE_DATE');
//    	}
//    	return $retour;
    }

    function isPasswordExpired()
    {
        $retour = false;
        if ($this->_getValue('expire_date') < getCurrentDateTimeInMySQL()) {
            $retour = true;
        }
        return $retour;

    }

    function isPasswordExpiredEmailSend()
    {
        $retour = false;
        if ($this->_issetExtra('PASSWORD_EXPIRED_EMAIL')) {
            $retour = true;
        }
        return $retour;
    }

    function setPasswordExpiredEmailSend()
    {
        $this->_addExtra('PASSWORD_EXPIRED_EMAIL', '1');
    }

    function unsetPasswordExpiredEmailSend()
    {
        $this->_unsetExtra('PASSWORD_EXPIRED_EMAIL');
    }

    function setDaysForLoginAs($days)
    {
        $this->_addExtra('LOGIN_AS_TMSP', getCurrentDateTimePlusDaysInMySQL($days));
    }

    function unsetDaysForLoginAs()
    {
        $this->_unsetExtra('LOGIN_AS_TMSP');
    }

    function getTimestampForLoginAs()
    {
        $return = false;
        if ($this->_issetExtra('LOGIN_AS_TMSP')) {
            $return = $this->_getExtra('LOGIN_AS_TMSP');
        }
        return $return;
    }

    function isTemporaryAllowedToLoginAs()
    {
        $return = false;
        if ($this->_issetExtra('LOGIN_AS_TMSP')) {
            if ($this->_getExtra('LOGIN_AS_TMSP') >= getCurrentDateTimeInMySQL()) {
                $return = true;
            }
        }
        return $return;
    }

    function setMailSendLocked()
    {
        $this->_addExtra('MAIL_SEND_LOCKED', '1');
    }

    function unsetMailSendLocked()
    {
        $this->_unsetExtra('MAIL_SEND_LOCKED');
    }

    function getMailSendLocked()
    {
        $retour = false;
        if ($this->_issetExtra('MAIL_SEND_LOCKED')) {
            $retour = $this->_getExtra('MAIL_SEND_LOCKED');
        }
        return $retour;
    }


    function setMailSendBeforeLock()
    {
        $this->_addExtra('MAIL_SEND_LOCK', '1');
    }

    function unsetMailSendBeforeLock()
    {
        $this->_unsetExtra('MAIL_SEND_LOCK');
    }

    function getMailSendBeforeLock()
    {
        $retour = false;
        if ($this->_issetExtra('MAIL_SEND_LOCK')) {
            $retour = $this->_getExtra('MAIL_SEND_LOCK');
        }
        return $retour;
    }

    function getMailSendNextLock()
    {
        $retour = false;
        if ($this->_issetExtra('MAIL_SEND_NEXT_LOCK')) {
            $retour = $this->_getExtra('MAIL_SEND_NEXT_LOCK');
        }
        return $retour;
    }

    function setMailSendBeforeDelete()
    {
        $this->_addExtra('MAIL_SEND_DELETE', '1');
    }

    function unsetMailSendBeforeDelete()
    {
        $this->_unsetExtra('MAIL_SEND_DELETE');
    }

    function getMailSendBeforeDelete()
    {
        $retour = false;
        if ($this->_issetExtra('MAIL_SEND_DELETE')) {
            $retour = $this->_getExtra('MAIL_SEND_DELETE');
        }
        return $retour;
    }

    function setMailSendNextDelete()
    {
        $this->_addExtra('MAIL_SEND_NEXT_DELETE', '1');
    }

    function unsetMailSendNextDelete()
    {
        $this->_unsetExtra('MAIL_SEND_NEXT_DELETE');
    }

    function getMailSendNextDelete()
    {
        $retour = false;
        if ($this->_issetExtra('MAIL_SEND_NEXT_DELETE')) {
            $retour = $this->_getExtra('MAIL_SEND_NEXT_DELETE');
        }
        return $retour;
    }

    function setLockSendMailDate()
    {
        $this->_addExtra('LOCK_SEND_MAIL_DATE', getCurrentDateTimeInMySQL());
    }

    function getLockSendMailDate()
    {
        return $this->_getExtra('LOCK_SEND_MAIL_DATE');
    }

    function unsetLockSendMailDate()
    {
        $this->_unsetExtra('LOCK_SEND_MAIL_DATE');
    }

    function setNotifyLockDate()
    {
        $this->_addExtra('NOTIFY_LOCK_DATE', getCurrentDateTimeInMySQL());
    }

    function getNotifyLockDate()
    {
        return $this->_getExtra('NOTIFY_LOCK_DATE');
    }

    function unsetNotifyLockDate()
    {
        $this->_unsetExtra('NOTIFY_LOCK_DATE');
    }

    function setNotifyDeleteDate()
    {
        $this->_addExtra('NOTIFY_DELETE_DATE', getCurrentDateTimeInMySQL());
    }

    function getNotifyDeleteDate()
    {
        return $this->_getExtra('NOTIFY_DELETE_DATE');
    }

    function unsetNotifyDeleteDate()
    {
        $this->_unsetExtra('NOTIFY_DELETE_DATE');
    }

    function resetInactivity()
    {
        $this->unsetMailSendBeforeLock();
        $this->unsetMailSendLocked();
        $this->unsetMailSendBeforeDelete();
        $this->unsetMailSendNextDelete();
        $this->unsetLockSendMailDate();
        $this->unsetLock();
        $this->unsetNotifyLockDate();
        $this->unsetNotifyDeleteDate();

        $this->save();
    }

    ## commsy user connections: portal2portal
    public function getOwnConnectionKey()
    {
        $retour = '';
        $value = $this->_getExtra('CONNECTION_OWNKEY');
        if (!empty($value)) {
            $retour = $value;
        } else {
            $this->_generateOwnConnectionKey();
            $retour = $this->_getExtra('CONNECTION_OWNKEY');
        }
        return $retour;
    }

    private function _setOwnConnectionKey($value)
    {
        $this->_setExtra('CONNECTION_OWNKEY', $value);
    }

    private function _generateOwnConnectionKey()
    {
        $key = '';
        $key .= $this->getItemID();
        $key .= rand(0, 9);
        $key .= $this->getFullName();
        $key .= rand(0, 9);
        $key .= $this->getEmail();
        $key .= rand(0, 9);
        include_once('functions/date_functions.php');
        $key .= getCurrentDateTimeInMySQL();
        $this->_setOwnConnectionKey(md5($key));
        $this->save();
    }

    public function addExternalConnectionKey($key)
    {
        $key_array = $this->_getExternalConnectionKeyArray();
        if (!in_array($key, $key_array)) {
            $key_array[] = $key;
            $this->_setExternalConnectionKeyArray($key_array);
        }
    }

    private function _getExternalConnectionKeyArray()
    {
        $retour = array();

        $value = $this->_getExtra('CONNECTION_EXTERNAL_KEY_ARRAY');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    private function _setExternalConnectionKeyArray($value)
    {
        $this->_setExtra('CONNECTION_EXTERNAL_KEY_ARRAY', $value);
    }

    public function getPortalConnectionArrayDB()
    {
        $retour = array();
        $value = $this->_getExtra('CONNECTION_ARRAY');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    public function getPortalConnectionArray()
    {
        $retour = $this->getPortalConnectionArrayDB();

        // add infos
        if (!empty($retour)) {
            $server_item = $this->_environment->getServerItem();
            foreach ($retour as $key => $row) {
                $retour[$key]['server_info'] = $server_item->getServerConnectionInfo($row['server_connection_id']);
            }
        }

        return $retour;
    }

    public function getPortalConnectionInfo($id)
    {
        $retour = array();
        $connection_array = $this->getPortalConnectionArray();
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

    public function setPortalConnectionInfoDB($value)
    {
        $this->_setExtra('CONNECTION_ARRAY', $value);
    }

    public function deletePortalConnectionFromServer($id)
    {
        $tab_array = $this->getPortalConnectionArray();
        $tab_new_array = array();
        if (!empty($tab_array)) {
            foreach ($tab_array as $tab_info) {
                if ($tab_info['server_connection_id'] != $id) {
                    $tab_new_array[] = $tab_info;
                }
            }
            $this->setPortalConnectionInfoDB($tab_new_array);
        }
    }

    function setIsAllowedToCreateContext($value)
    {
        $this->_addExtra('IS_ALLOWED_TO_CREATE_CONTEXT', $value);
    }

    function getIsAllowedToCreateContext()
    {
        $retour = 'standard';
        if ($this->_issetExtra('IS_ALLOWED_TO_CREATE_CONTEXT')) {
            $retour = $this->_getExtra('IS_ALLOWED_TO_CREATE_CONTEXT');
        }
        return $retour;
    }

    public function getUsePortalEmail()
    {
        return ($this->_getValue('use_portal_email') == 1);
    }

    public function setUsePortalEmail($value)
    {
        $this->_setValue('use_portal_email', $value);
    }

    public function isAllowedToCreateContext()
    {
        if ($this->isGuest()) {
            return false;
        }

        if ($this->isRoot() || ($this->getContextItem()->isPortal() && $this->isModerator())) {
            return true;
        }
        if ($this->getIsAllowedToCreateContext() != 'standard') {
            if ($this->getIsAllowedToCreateContext() == -1) {
                return false;
            } else {
                return true;
            }
        } else {
            $auth_source_manager = $this->_environment->getAuthSourceManager();
            $auth_source_item = $auth_source_manager->getItem($this->getAuthSource());
            return $auth_source_item->isUserAllowedToCreateContext();
        }
    }

    function setIsAllowedToUseCalDAV($value)
    {
        $this->_addExtra('IS_ALLOWED_TO_USE_CALDAV', $value);
    }

    function getIsAllowedToUseCalDAV()
    {
        $retour = 'standard';
        if ($this->_issetExtra('IS_ALLOWED_TO_USE_CALDAV')) {
            $retour = $this->_getExtra('IS_ALLOWED_TO_USE_CALDAV');
        }
        return $retour;
    }

    public function isAllowedToUseCalDAV()
    {
        if ($this->getIsAllowedToUseCalDAV() != 'standard') {
            if ($this->getIsAllowedToUseCalDAV() == -1) {
                return false;
            } else {
                return true;
            }
        } else {
            if ($this->_environment->getCurrentPortalItem()->getConfigurationCalDAV() == 'CONFIGURATION_CALDAV_DISABLE') {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Deletes a user caused by inactivity, expects a portal user
     *
     * @throws Exception
     */
    public function deleteUserCausedByInactivity()
    {
        $userContext = $this->getContextItem();
        if (!$userContext->isPortal()) {
            throw new \Exception('expecting portal user');
        }

        $this->delete();

        $ownRoom = $this->getOwnRoom($userContext->getItemID());
        if (isset($ownRoom)) {
            $ownRoom->delete();
        }

        $authentication = $this->_environment->getAuthenticationObject();
        $authentication->delete($this->getItemID());
    }
}