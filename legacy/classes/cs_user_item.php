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

use App\Entity\Account;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** class for a user
 * this class implements a user item.
 */
class cs_user_item extends cs_item
{
    private string $oldStatus = 'new';

    private ?string $oldContact = null;

    private array $changedValues = [];

    private ?array $contextIdArray = null;

    /**
     * the user room associated with this user.
     */
    private ?cs_userroom_item $userroomItem = null;

    /**
     * for a user item in a user room, returns the project room user associated with this user.
     */
    private ?cs_user_item $projectUserItem = null;

    /** constructor: cs_user_item
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_USER_TYPE;
    }

    /** Checks and sets the data of the item.
     *
     * @param array $data_array The prepared array from "_buildItem($db_array)"
     */
    public function _setItemData($data_array): void
    {
        $this->_data = $data_array;
        if (isset($data_array['status']) and !empty($data_array['status'])) {
            $this->oldStatus = $data_array['status'];
            $this->oldContact = $data_array['is_contact'];
        }
    }

    /** get user id of the user
     * this method returns the user id (account or Benutzerkennung) of the user.
     *
     * @return string user id of the user
     */
    public function getUserID()
    {
        return $this->_getValue('user_id');
    }

    /** set user id of the user
     * this method sets the user id (account or Benutzerkennung) of the user.
     *
     * @param string value user id of the user
     */
    public function setUserID($value)
    {
        $this->_setValue('user_id', $value);
        $this->changedValues[] = 'user_id';
    }

    public function getAuthSource()
    {
        return $this->_getValue('auth_source');
    }

    public function setAuthSource($value)
    {
        $this->_setValue('auth_source', $value);
    }

    /** set groups of a news item by id
     * this method sets a list of group item_ids which are linked to the user.
     *
     * @param array of group ids, index of id must be 'iid'<br />
     * Example:<br />
     * array(array('iid' => value1), array('iid' => value2))
     */
    public function setGroupListByID($value)
    {
        $this->setLinkedItemsByID(CS_GROUP_TYPE, $value);
    }

    /** set one group of a user item by id
     * this method sets one group item id which is linked to the user.
     *
     * @param int group id
     */
    public function setGroupByID($value)
    {
        $value_array = [];
        $value_array[] = $value;
        $this->setGroupListByID($value_array);
    }

    /** set one group of a user item
     * this method sets one group which is linked to the user.
     *
     * @param object cs_label group
     */
    public function setGroup($value)
    {
        if (isset($value)
            and $value->isA(CS_LABEL_TYPE)
            and CS_GROUP_TYPE == $value->getLabelType()
            and $value->getItemID() > 0
        ) {
            $this->setGroupByID($value->getItemID());
            unset($value);
        }
    }

    /** get topics of a user
     * this method returns a list of topics which are linked to the user.
     *
     * @return object cs_list a list of topics (cs_label_item)
     */
    public function getTopicList()
    {
        $topic_manager = $this->_environment->getLabelManager();
        $topic_manager->setTypeLimit(CS_TOPIC_TYPE);

        return $this->_getLinkedItems($topic_manager, CS_TOPIC_TYPE);
    }

    /** set topics of a user
     * this method sets a list of topics which are linked to the user.
     *
     * @param cs_list list of topics (cs_label_item)
     */
    public function setTopicList($value)
    {
        $this->_setObject(CS_TOPIC_TYPE, $value, false);
    }

    /** set topics of a news item by id
     * this method sets a list of topic item_ids which are linked to the user.
     *
     * @param array of topic ids, index of id must be 'iid'<br />
     * Example:<br />
     * array(array('iid' => value1), array('iid' => value2))
     */
    public function setTopicListByID($value)
    {
        $this->setLinkedItemsByID(CS_TOPIC_TYPE, $value);
    }

    /** set one topic of a user item by id
     * this method sets one topic item id which is linked to the user.
     *
     * @param int topic id
     */
    public function setTopicByID($value)
    {
        $value_array = [];
        $value_array[] = $value;
        $this->setTopicListByID($value_array);
    }

    /**
     * For a user item in a project room, returns any user room associated with this user.
     *
     * @return cs_userroom_item|null the user room associated with this user
     */
    public function getLinkedUserroomItem(): ?cs_userroom_item
    {
        if (isset($this->userroomItem) && !$this->userroomItem->isDeleted()) {
            return $this->userroomItem;
        }

        $userroomItemId = $this->getLinkedUserroomItemID();
        if (isset($userroomItemId)) {
            $userroomManager = $this->_environment->getUserroomManager();
            $userroomItem = $userroomManager->getItem($userroomItemId);
            if (isset($userroomItem) and !$userroomItem->isDeleted()) {
                $this->userroomItem = $userroomItem;
            }

            return $this->userroomItem;
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
        $this->_setExtra('USERROOM_ITEM_ID', (int) $roomId);
    }

    public function unsetLinkedUserroomItemID()
    {
        $this->_unsetExtra('USERROOM_ITEM_ID');
    }

    /**
     * For a user item in a user room, returns the project room user who corresponds to this user.
     *
     * @return cs_user_item|null the project room user associated with this user
     */
    public function getLinkedProjectUserItem(): ?cs_user_item
    {
        if (isset($this->projectUserItem)) {
            return $this->projectUserItem;
        }

        $userItemId = $this->getLinkedProjectUserItemID();
        if (isset($userItemId)) {
            $userManager = $this->_environment->getUserManager();
            if ($userManager->existsItem($userItemId)) {
                $userItem = $userManager->getItem($userItemId);
                if (isset($userItem) and !$userItem->isDeleted()) {
                    $this->projectUserItem = $userItem;
                }

                return $this->projectUserItem;
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
        $this->_setExtra('PROJECT_USER_ITEM_ID', (int) $userId);
    }

    public function unsetLinkedProjectUserItemID()
    {
        $this->_unsetExtra('PROJECT_USER_ITEM_ID');
    }

    /** get firstname of the user
     * this method returns the firstname of the user.
     *
     * @return string firstname of the user
     */
    public function getFirstname()
    {
        return $this->_getValue('firstname');
    }

    /** set firstname of the user
     * this method sets the firstname of the user.
     *
     * @param string value firstname of the user
     */
    public function setFirstname($value)
    {
        $this->_setValue('firstname', $value);
        $this->changedValues[] = 'firstname';
    }

    /** get lastname of the user
     * this method returns the lastname of the user.
     *
     * @return string lastname of the user
     */
    public function getLastname()
    {
        return $this->_getValue('lastname');
    }

    /** set lastname of the user
     * this method sets the lastname of the user.
     *
     * @param string value lastname of the user
     */
    public function setLastname($value)
    {
        $this->_setValue('lastname', $value);
        $this->changedValues[] = 'lastname';
    }

    public function makeContactPerson()
    {
        $this->_setValue('is_contact', '1');
    }

    public function makeNoContactPerson()
    {
        $this->_setValue('is_contact', '0');
    }

    public function getContactStatus()
    {
        $status = $this->_getValue('is_contact');

        return $status;
    }

    public function isContact()
    {
        $retour = false;
        $status = $this->getContactStatus();
        if (1 == $status) {
            $retour = true;
        }

        return $retour;
    }

    /** get fullname of the user
     * this method returns the fullname (firstname + lastname) of the user.
     *
     * @return string fullname of the user
     */
    public function getFullName()
    {
        return ltrim($this->getFirstname().' '.$this->getLastname());
    }

    /** set title of the user
     * this method sets the title of the user.
     *
     * @param string value title of the user
     */
    public function setTitle($value)
    {
        $this->_addExtra('USERTITLE', (string) $value);
    }

    /** get title of the user
     * this method returns the title of the user.
     *
     * @return string title of the user
     */
    public function getTitle(): string
    {
        $retour = '';
        if ($this->_issetExtra('USERTITLE')) {
            $retour = $this->_getExtra('USERTITLE');
        }

        return $retour;
    }

    /** set birthday of the user
     * this method sets the birthday of the user.
     *
     * @param string value birthday of the user
     */
    public function setBirthday($value)
    {
        $this->_addExtra('USERBIRTHDAY', (string) $value);
    }

    /** get birthday of the user
     * this method returns the birthday of the user.
     *
     * @return string birthday of the user
     */
    public function getBirthday()
    {
        $retour = '';
        if ($this->_issetExtra('USERBIRTHDAY')) {
            $retour = $this->_getExtra('USERBIRTHDAY');
        }

        return $retour;
    }

    /** set birthday of the user
     * this method sets the birthday of the user.
     *
     * @param string value birthday of the user
     */
    public function setTelephone($value)
    {
        $this->_addExtra('USERTELEPHONE', (string) $value);
    }

    /** get birthday of the user
     * this method returns the birthday of the user.
     *
     * @return string birthday of the user
     */
    public function getTelephone()
    {
        $retour = '';
        if ($this->_issetExtra('USERTELEPHONE')) {
            $retour = $this->_getExtra('USERTELEPHONE');
        }

        return $retour;
    }

    /** set celluarphonenumber of the user
     * this method sets the celluarphonenumber of the user.
     *
     * @param string value celluarphonenumber of the user
     */
    public function setCellularphone($value)
    {
        $this->_addExtra('USERCELLULARPHONE', (string) $value);
    }

    /** get celluarphonenumber of the user
     * this method returns the celluarphonenumber of the user.
     *
     * @return string celluarphonenumber of the user
     */
    public function getCellularphone()
    {
        $retour = '';
        if ($this->_issetExtra('USERCELLULARPHONE')) {
            $retour = $this->_getExtra('USERCELLULARPHONE');
        }

        return $retour;
    }

    /** set homepage of the user
     * this method sets the homepage of the user.
     *
     * @param string value homepage of the user
     */
    public function setHomepage($value)
    {
        if (!empty($value) and '-1' != $value) {
            if (!mb_ereg('https?://([a-z0-9_./?&=#:@]|-)*', (string) $value)) {
                $value = 'http://'.$value;
            }
        }
        $this->_addExtra('USERHOMEPAGE', (string) $value);
    }

    /** get homepage of the user
     * this method returns the homepage of the user.
     *
     * @return string homepage of the user
     */
    public function getHomepage()
    {
        $retour = '';
        if ($this->_issetExtra('USERHOMEPAGE')) {
            $retour = $this->_getExtra('USERHOMEPAGE');
        }

        return $retour;
    }

    public function setOrganisation($value)
    {
        $this->_addExtra('USERORGANISATION', (string) $value);
    }

    public function getOrganisation()
    {
        $retour = '';
        if ($this->_issetExtra('USERORGANISATION')) {
            $retour = $this->_getExtra('USERORGANISATION');
        }

        return $retour;
    }

    public function setPosition($value)
    {
        $this->_addExtra('USERPOSITION', (string) $value);
    }

    public function getPosition()
    {
        $retour = '';
        if ($this->_issetExtra('USERPOSITION')) {
            $retour = $this->_getExtra('USERPOSITION');
        }

        return $retour;
    }

    /** set street of the user
     * this method sets the street of the user.
     *
     * @param string value street of the user
     */
    public function setStreet($value)
    {
        $this->_addExtra('USERSTREET', (string) $value);
    }

    /** get street of the user
     * this method returns the street of the user.
     *
     * @return string street of the user
     */
    public function getStreet()
    {
        $retour = '';
        if ($this->_issetExtra('USERSTREET')) {
            $retour = $this->_getExtra('USERSTREET');
        }

        return $retour;
    }

    /** set zipcode of the user
     * this method sets the zipcode of the user.
     *
     * @param string value zipcode of the user
     */
    public function setZipcode($value)
    {
        $this->_addExtra('USERZIPCODE', (string) $value);
    }

    /** get zipcode of the user
     * this method returns the zipcode of the user.
     *
     * @return string zipcode of the user
     */
    public function getZipcode()
    {
        $retour = '';
        if ($this->_issetExtra('USERZIPCODE')) {
            $retour = $this->_getExtra('USERZIPCODE');
        }

        return $retour;
    }

    /** set city of the user
     * this method sets the city of the user.
     *
     * @param string value city of the user
     */
    public function setCity($value)
    {
        $this->_setValue('city', $value);
    }

    /** get city of the user
     * this method returns the city of the user.
     *
     * @return string city of the user
     */
    public function getCity()
    {
        return $this->_getValue('city');
    }

    /** set room of the user
     * this method sets the room of the user.
     *
     * @param string value room of the user
     */
    public function setRoom($value)
    {
        $this->_addExtra('USERROOM', (string) $value);
    }

    /** get room of the user
     * this method returns the room of the user.
     *
     * @return string room of the user
     */
    public function getRoom()
    {
        $retour = '';
        if ($this->_issetExtra('USERROOM')) {
            $retour = $this->_getExtra('USERROOM');
        }

        return $retour;
    }

    /** set description of the user
     * this method sets the description of the user.
     *
     * @param string value description of the user
     */
    public function setDescription($value)
    {
        $this->_setValue('description', (string) $value);
    }

    /** get description of the user
     * this method returns the description of the user.
     *
     * @return string description of the user
     */
    public function getDescription()
    {
        return $this->_getValue('description');
    }

    /** set picture filename of the user
     * this method sets the picture filename of the user.
     *
     * @param string value picture filename of the user
     */
    public function setPicture($name)
    {
        $this->_addExtra('USERPICTURE', $name);
    }

    /** get description of the user
     * this method returns the description of the user.
     *
     * @return string description of the user
     */
    public function getPicture(): string
    {
        if ($this->_issetExtra('USERPICTURE')) {
            return $this->_getExtra('USERPICTURE');
        }

        return '';
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

    /** get room email of the user.
     *
     * @return string room email of user
     */
    public function getRoomEmail()
    {
        return $this->_getValue('email');
    }

    /** set email of the user
     * this method sets the email of the user.
     *
     * @param string value email of the user
     */
    public function setEmail($value)
    {
        $this->_setValue('email', (string) $value);
        $this->changedValues[] = 'email';
    }

    /** get deleter - do not use
     * this method is a warning for coders, because if you want an object cs_user_item here, you get into an endless loop.
     */
    public function getDeleter()
    {
        echo 'use getDeleterID()<br />';
    }

    /** get user comment
     * this method returns the users comment: why he or she wants an account.
     *
     * @return string user comment
     */
    public function getUserComment()
    {
        $retour = '';
        if ($this->_issetExtra('USERCOMMENT')) {
            $retour = $this->_getExtra('USERCOMMENT');
        }

        return $retour;
    }

    /** set user comment
     * this method sets the users comment why he or she wants an account.
     *
     * @param string value user comment
     */
    public function setUserComment($value)
    {
        $this->_addExtra('USERCOMMENT', (string) $value);
    }

    /** get flag, if moderator wants a mail at new accounts
     * this method returns the getaccountwantmail flag.
     *
     * @return string value no, moderator doesn't want an e-mail
     *             yes, moderator wants an e-mail
     */
    public function getAccountWantMail(): string
    {
        $retour = 'yes';
        if ($this->_issetExtra('ACCOUNTWANTMAIL')) {
            $retour = $this->_getExtra('ACCOUNTWANTMAIL');
        }

        return $retour;
    }

    /** set flag if moderator wants a mail at new accounts
     * this method sets the comment of the moderator.
     *
     * @param int value no, moderator doesn't want an e-mail
     *                      yes, moderator wants an e-mail
     */
    public function setAccountWantMail($value)
    {
        $this->_addExtra('ACCOUNTWANTMAIL', (string) $value);
    }

    /** get flag, if moderator wants a mail at opening rooms
     * this method returns the getopenroomwantmail flag.
     *
     * @return int value no, moderator doesn't want an e-mail
     *             yes, moderator wants an e-mail
     */
    public function getOpenRoomWantMail()
    {
        $retour = 'yes';
        if ($this->_issetExtra('ROOMWANTMAIL')) {
            $retour = $this->_getExtra('ROOMWANTMAIL');
        }

        return $retour;
    }

    /** set flag if moderator wants a mail at opening rooms
     * this method sets the getopneroomwantmail flag.
     *
     * @param int value no, moderator doesn't want an e-mail
     *                      yes, moderator wants an e-mail
     */
    public function setOpenRoomWantMail($value)
    {
        $this->_addExtra('ROOMWANTMAIL', (string) $value);
    }

    public function setDeleteEntryWantMail(bool $enabled)
    {
        if ($enabled) {
            $this->_addExtra('DELETEENTRYMAIL', 'yes');
        } elseif ($this->_issetExtra('DELETEENTRYMAIL')) {
            $this->_unsetExtra('DELETEENTRYMAIL');
        }
    }

    public function getDeleteEntryWantMail(): bool
    {
        return $this->_issetExtra('DELETEENTRYMAIL');
    }

    /** get last login time
     * this method returns the last login in datetime format.
     *
     * @return string last login
     */
    public function getLastLogin()
    {
        return $this->_getValue('lastlogin');
    }

    /** get user language
     * this method returns the users language: de or en or ...
     *
     * @return string user language
     */
    public function getLanguage(): string
    {
        return ($this->_issetExtra('LANGUAGE')) ? $this->_getExtra('LANGUAGE') : 'de';
    }

    /** set user language
     * this method sets the users language: de or en or ...
     *
     * @param string value user language
     */
    public function setLanguage($value): void
    {
        $this->_addExtra('LANGUAGE', (string) $value);
    }

    /** get Visible of the user
     * this method returns the visible Property of the user.
     *
     * @return string visible of the user
     */
    public function getVisible(): string
    {
        return $this->isVisibleForAll() ? '2' : '1';
    }

    /** set visible property of the user
     * this method sets the visible Property of the user.
     *
     * @param int value visible of the user
     */
    public function setVisible($value)
    {
        if ('2' == $value) {
            $this->_setValue('visible', $value);
        } else {
            $this->_setValue('visible', '1');
        }
    }

    /** set visible property of the user to LoggedIn.
     */
    public function setVisibleToLoggedIn()
    {
        $this->setVisible('1');
    }

    /** set visible property of the user to All
     * this method sets an order limit for the select statement to name.
     */
    public function setVisibleToAll()
    {
        $this->setVisible('2');
    }

    public function isEmailVisible()
    {
        $retour = true;
        $value = $this->_getEmailVisibility();
        if ('-1' == $value) {
            $retour = false;
        }

        return $retour;
    }

    public function setEmailNotVisible()
    {
        $this->_setEmailVisibility('-1');
    }

    public function setEmailVisible()
    {
        $this->_setEmailVisibility('1');
    }

    public function _setEmailVisibility($value)
    {
        $this->_addExtra('EMAIL_VISIBILITY', $value);
    }

    public function _getEmailVisibility()
    {
        $retour = '';
        if ($this->_issetExtra('EMAIL_VISIBILITY')) {
            $retour = $this->_getExtra('EMAIL_VISIBILITY');
        }

        return $retour;
    }

    /**
     * Sets the status of the user to rejected. As a result, the user will be blocked from its context.
     */
    public function reject(): void
    {
        $this->setStatus(0);
        $this->makeNoContactPerson();
    }

    /**
     * Sets the status of the user to request, a moderator must free the account.
     */
    public function request(): void
    {
        $this->setStatus(1);
    }

    /**
     * Sets the status of the user to normal.
     */
    public function makeUser(): void
    {
        $this->setStatus(2);
    }

    /**
     * Sets the status of the user to read-only.
     */
    public function makeReadOnlyUser(): void
    {
        $this->setStatus(4);
    }

    /**
     * Sets the status of the user to moderator.
     */
    public function makeModerator(): void
    {
        $this->setStatus(3);
    }

    /** get status of user
     * this method returns an integer value corresponding with the users status.
     *
     * @return int|string status
     */
    public function getStatus(): int|string
    {
        return $this->_getValue('status');
    }

    /** get status of user
     * this method returns an integer value corresponding with the users status.
     *
     * @return int status
     */
    public function getLastStatus(): false|int
    {
        return intval($this->_getValue('status_last'));
    }

    /** set user status last
     * this method sets the last status of the user, if status changed.
     *
     * @param int status
     */
    public function setLastStatus($value): void
    {
        $this->_setValue('status_last', (int) $value);
    }

    /** set user status
     * this method sets the status of the user.
     *
     * @param int status
     */
    public function setStatus($value): void
    {
        $this->setLastStatus($this->getStatus());
        $this->_setValue('status', (int) $value);
    }

    /** is user rejected ?
     * this method returns a boolean explaining if user is rejected or not.
     *
     * @return bool true, if user is rejected
     *              false, if user is not rejected
     */
    public function isRejected(): bool
    {
        return 0 == $this->_getValue('status');
    }

    /** is user a guest ?
     * this method returns a boolean explaining if user is a guest or not.
     *
     * @return bool true, if user is a guest
     *              false, if user is not a guest
     */
    public function isGuest(): bool
    {
        return 0 == $this->_getValue('status');
    }

    /** is user a guest ?
     * this method returns a boolean explaining if user is a guest or not.
     *
     * @return bool true, if user is a guest
     *              false, if user is not a guest
     */
    public function isReallyGuest(): bool
    {
        return 0 == $this->_getValue('status') and 'guest' == mb_strtolower((string) $this->_getValue('user_id'), 'UTF-8');
    }

    /** user has requested an account
     * this method returns a boolean explaining if user is still in request status.
     *
     * @return bool true, if user is in request status
     *              false, if user is not in request status
     */
    public function isRequested(): bool
    {
        return 1 == $this->_getValue('status');
    }

    /** is user a normal user ?
     * this method returns a boolean explaining if user is a normal user or not.
     *
     * @return bool true, if user is a normal user or moderator
     *              false, if user is not a normal user or moderator
     */
    public function isUser(): bool
    {
        return $this->_getValue('status') >= 2;
    }

    /** is user a moderator ?
     * this method returns a boolean explaining if user is a moderator or not.
     *
     * @return bool true, if user is a moderator
     *              false, if user is not a moderator
     */
    public function isModerator(): bool
    {
        return 3 == $this->_getValue('status');
    }

    public function isReadOnlyUser(): bool
    {
        return 4 == $this->_getValue('status');
    }

    public function getUserRelatedCommunityList(bool $withExtras = true): cs_list
    {
        $manager = $this->_environment->getCommunityManager();

        return $manager->getUserRelatedCommunityListForUser($this, $withExtras);
    }

    public function getRelatedCommunityList(): cs_list
    {
        $manager = $this->_environment->getCommunityManager();

        return $manager->getRelatedCommunityListForUser($this);
    }

    public function getRelatedCommunityListAllUserStatus(): cs_list
    {
        $manager = $this->_environment->getCommunityManager();

        return $manager->getRelatedCommunityListForUserAllUserStatus($this);
    }

    public function getRelatedUserroomsList(bool $withExtras = true): cs_list
    {
        $manager = $this->_environment->getRoomManager();

        return $manager->getUserRoomsUserIsMemberOf($this, $withExtras);
    }

    public function getUserRelatedProjectList(bool $withExtras = true): cs_list
    {
        $manager = $this->_environment->getProjectManager();

        return $manager->getUserRelatedProjectListForUser($this, $withExtras);
    }

    public function getRelatedProjectList(): cs_list
    {
        $manager = $this->_environment->getProjectManager();

        return $manager->getRelatedProjectListForUser($this, null);
    }

    public function getRelatedProjectListAllUserStatus(): cs_list
    {
        $manager = $this->_environment->getProjectManager();
        return $manager->getRelatedProjectListForUserAllUserStatus($this, null);
    }

    public function getUserRelatedGroupList(): cs_list
    {
        $manager = $this->_environment->getGrouproomManager();
        return $manager->getUserRelatedGroupListForUser($this);
    }

    public function getRelatedGroupList(): cs_list
    {
        $manager = $this->_environment->getGrouproomManager();
        return $manager->getRelatedGroupListForUser($this);
    }

    public function _getTaskList()
    {
        $task_manager = $this->_environment->getTaskManager();

        return $task_manager->getTaskListForItem($this);
    }

    /** is user root ?
     * this method returns a boolean explaining if user is root or not.
     *
     * @return bool true, if user is root
     *              false, if user is not root
     */
    public function isRoot(): bool
    {
        return (3 == $this->_getValue('status'))
            and ('root' == $this->getUserID())
            and ($this->getContextID() == $this->_environment->getServerID());
    }

    /** is user VisibleForAll ?
     * this method returns a boolean explaining if user is Visible for everyone or not.
     *
     * @return bool true, if user is Visible for the Public
     *              false, else
     */
    public function isVisibleForAll(): bool
    {
        return 2 == $this->_getValue('visible');
    }

    /** is user VisibleForLoggedIn ?
     * this method returns a boolean explaining if user is Visible for logged in members or not.
     *
     * @return bool true, if user is Visible for logged in members
     *              false, else
     */
    public function isVisibleForLoggedIn(): bool
    {
        return true;
    }

    public function save(): void
    {
        $user_manager = $this->_environment->getUserManager();
        $this->_save($user_manager);
        $item_id = $this->getItemID();
        if (empty($item_id)) {
            $this->setItemID($user_manager->getCreateID());
        }

        // NOTE: media upload in a user item's description field is currently disabled
        // $this->_saveFiles();     // this must be done before saveFileLinks
        // $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id

        // ContactPersonString
        $context_item = $this->getContextItem();
        // get grouproom
        if ($context_item && 'group' == $context_item->getType()) {
            $grouproom_array = $context_item->_getItemData();
            $grouproom_id = $grouproom_array['extras']['GROUP_ROOM_ID'];
            $room_manager = $this->_environment->getRoomManager();
            $context_item = $room_manager->getItem($grouproom_id);
        }

        if (isset($context_item)
            and !$context_item->isPortal()
            and !$context_item->isServer()
            and $this->getUserID()
            and 'GUEST' != mb_strtoupper($this->getUserID())
            and (!isset($this->oldStatus)
                or !isset($this->oldContact)
                or $this->oldStatus != $this->getStatus()
                or $this->oldContact != $this->getContactStatus()
            )
        ) {
            $context_item->renewContactPersonString();
            unset($context_item);
        }

        // set old status to current status
        $this->oldStatus = $this->getStatus();
        $this->oldContact = $this->getContactStatus();

        $this->updateElastic();
    }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_user');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(User::class);

        $this->replaceElasticItem($objectPersister, $repository);
    }

    /**
     * This method only updates the LastLogin Of the User.
     * Only the LastLoginField will be touched.
     */
    public function updateLastLogin()
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->updateLastLoginOf($this);
    }

    public function getOwnRoom($context_id = null)
    {
        if ($this->isRoot()) {
            return null;
        } else {
            $private_room_manager = $this->_environment->getPrivateRoomManager();
            if (!empty($context_id)) {
                return $private_room_manager->getRelatedOwnRoomForUser($this, $context_id);
            } else {
                return $private_room_manager->getRelatedOwnRoomForUser($this, $this->_environment->getCurrentPortalID());
            }
        }
    }

    public function delete()
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
            and (!isset($this->oldStatus)
                or !isset($this->oldContact)
                or $this->oldStatus != $this->getStatus()
                or $this->oldContact != $this->getContactStatus()
            )
        ) {
            $context_item->renewContactPersonString();
            unset($context_item);
        }

        // set old status to current status
        $this->oldStatus = $this->getStatus();
        $this->oldContact = $this->getContactStatus();

        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_user');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(User::class);

        $this->deleteElasticItem($objectPersister, $repository);
    }

    /**
     * Check if this user can be seen by $userItem.
     *
     * @see cs_item::mayPortfolioSee()
     */
    public function mayPortfolioSee(string $username): bool
    {
        $portfolioManager = $this->_environment->getPortfolioManager();

        $userArray = $portfolioManager->getPortfolioUserForExternalViewer($this->getItemId());

        return in_array($username, $userArray);
    }

    /**
     * @return bool
     */
    public function maySee(cs_user_item $userItem)
    {
        if ($this->_environment->inCommunityRoom()) {  // Community room
            if ($userItem->isRoot()
                or ($userItem->isGuest() and $this->isVisibleForAll())
                or (($userItem->getContextID() == $this->getContextID()
                        or $userItem->getContextID() == $this->_environment->getCurrentContextID()
                )
                and (($userItem->isUser()
                        and $this->isVisibleForLoggedIn()
                )
                or ($userItem->getUserID() == $this->getUserID()
                    and $userItem->getAuthSource() == $this->getAuthSource()
                )
                or $userItem->isModerator()
                ))) {
                $access = true;
            } else {
                $access = false;
            }
        } else {    // Project room, group room, private room, portal
            $access = parent::maySee($userItem);
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
                        if ($userItem->getUserID() == $this->getUserID() && $userItem->getAuthSource() == $this->getAuthSource()) {
                            $access = true;
                        } elseif ($userItem->isModerator()) {
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
     * Checks whether the current user is editable by the given one.
     *
     * @param cs_user_item $userItem the user object asking for permission
     *
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

    public function mayEditRegular($user_item)
    {
        $access = false;
        if (!$user_item->isOnlyReadUser()) {
            $access = $this->getUserID() == $user_item->getUserID() and $this->getAuthSource() == $user_item->getAuthSource();
        }

        return $access;
    }

    /**
     * Returns all users representing this user in other rooms this user is a member of. By default,
     * related users from community rooms, project rooms and the user's private room are returned.
     *
     * @param bool $includeUserroomUsers whether related users from user rooms shall be returned as well
     *
     * @return \cs_list list of user items connected to this item
     */
    public function getRelatedUserList(bool $includeUserroomUsers = false): cs_list
    {
        $roomIds = [];
        $emptyList = new cs_list();
        $currentContextId = $this->getContextID();
        $currentPortalId = $this->_environment->getCurrentPortalID();

        // current portal
        if (!empty($currentPortalId) && $currentPortalId != $currentContextId) {
            $roomIds[] = $currentPortalId;
        }

        // community rooms
        $communityManager = $this->_environment->getCommunityManager();
        $communityRooms = $communityManager->getRelatedCommunityListForUser($this, false);

        // project rooms
        $projectManager = $this->_environment->getProjectManager();
        $projectRooms = $projectManager->getRelatedProjectListForUser($this, null, false);

        // user rooms
        $userroomManager = $this->_environment->getUserRoomManager();
        $userRooms = ($includeUserroomUsers) ? $userroomManager->getRelatedUserroomListForUser($this) : $emptyList;

        // gather all room IDs sans the current context ID
        $roomIds = array_merge($communityRooms->getIDArray(), $projectRooms->getIDArray(), $userRooms->getIDArray());
        $roomIds = array_filter($roomIds, fn (int $roomId) => $roomId != $currentContextId);

        // NOTE: we reindex the $roomIds array (so that its array values start from 0) since cs_user_manager->_performQuery()
        //       for some reason requires a _context_array_limit array to start with index 0
        $roomIds = array_values($roomIds);

        // private room
        $privateRoomManager = $this->_environment->getPrivateRoomManager();
        $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($this, $currentPortalId);
        if ($privateRoom) {
            $roomIds[] = $privateRoom->getItemID();
        }

        if (empty($roomIds)) {
            return $emptyList;
        }

        // gather IDs of all related users
        $userManager = $this->_environment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextArrayLimit($roomIds);
        $userManager->setUserIDLimit($this->getUserID());
        $userManager->setAuthSourceLimit($this->getAuthSource());
        $userManager->select();
        /** @var \cs_list $relatedUsers */
        $relatedUsers = $userManager->get();

        return $relatedUsers;
    }

    public function getRelatedUserItemInContext($contextId): ?cs_user_item
    {
        $userManager = $this->_environment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($contextId);
        $userManager->setUserIDLimit($this->getUserID());
        $userManager->setAuthSourceLimit($this->getAuthSource());
        $userManager->select();
        $userList = $userManager->get();
        if (isset($userList) && 1 == $userList->getCount()) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $userList->getFirst();
        }

        return null;
    }

    /**
     * @return cs_user_item|null
     */
    public function getRelatedPrivateRoomUserItem(): ?cs_user_item
    {
        $private_room_manager = $this->_environment->getPrivateRoomManager();
        $own_room = $private_room_manager->getRelatedOwnRoomForUser($this, $this->_environment->getCurrentPortalID());
        if (isset($own_room)) {
            $own_cid = $own_room->getItemID();
            $user_manager = $this->_environment->getUserManager();
            $user_manager->resetLimits();
            $user_manager->setContextLimit($own_cid);
            $user_manager->setUserIDLimit($this->getUserID());
            $user_manager->setAuthSourceLimit($this->getAuthSource());
            $user_manager->select();
            $user_list = $user_manager->get();
            if (1 == $user_list->getCount()) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $user_list->getFirst();
            }
        }

        return null;
    }

    public function getRelatedPortalUserItem(): ?cs_user_item
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
        $user_manager->setUserIDLimit($this->getUserID());
        $user_manager->setAuthSourceLimit($this->getAuthSource());
        $user_manager->select();
        $user_list = $user_manager->get();
        if (1 == $user_list->getCount()) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $user_list->getFirst();
        }

        return null;
    }

    public function cloneData()
    {
        $new_room_user = clone $this;
        $new_room_user->unsetContextID();
        $new_room_user->unsetItemID();
        $new_room_user->unsetCreatorID();
        $new_room_user->unsetCreatorDate();
        $new_room_user->setAGBAcceptanceDate(null);
        $new_room_user->unsetLinkedUserroomItemID();
        $new_room_user->unsetLinkedProjectUserItemID();
        $new_room_user->_unsetValue('modifier_id');

        return $new_room_user;
    }

    public function unsetContextID()
    {
        $this->_unsetValue('context_id');
    }

    public function unsetItemID()
    {
        $this->_unsetValue('item_id');
    }

    public function unsetCreatorID()
    {
        $this->_unsetValue('creator_id');
    }

    public function unsetCreatorDate()
    {
        $this->_unsetValue('creator_date');
    }

    public function setCreatorID2ItemID()
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setCreatorID2ItemID($this);
    }

    public function deleteAllEntriesOfUser()
    {
        // datenschutz: overwrite or not (03.09.2012 IJ)
        $overwrite = true;
        global $symfonyContainer;
        $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
        if (!empty($disable_overwrite) and 'TRUE' === $disable_overwrite) {
            $overwrite = false;
        }

        if ($overwrite) {
            $announcement_manager = $this->_environment->getAnnouncementManager();
            $dates_manager = $this->_environment->getDatesManager();
            $discussion_manager = $this->_environment->getDiscussionManager();
            $discarticle_manager = $this->_environment->getDiscussionarticlesManager();
            $material_manager = $this->_environment->getMaterialManager();
            $section_manager = $this->_environment->getSectionManager();
            $annotation_manager = $this->_environment->getAnnotationManager();
            $label_manager = $this->_environment->getLabelManager();
            $tag_manager = $this->_environment->getTagManager();
            $todo_manager = $this->_environment->getTodosManager();
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

    public function setAGBAcceptanceDate(?DateTimeImmutable $agbAcceptanceDate): cs_user_item
    {
        $this->_addExtra(
            'AGB_ACCEPTANCE_DATE',
            $agbAcceptanceDate ? $agbAcceptanceDate->format('Y-m-d H:i:s') : ''
        );

        return $this;
    }

    public function getAGBAcceptanceDate(): ?DateTimeImmutable
    {
        if ($this->_issetExtra('AGB_ACCEPTANCE_DATE')) {
            $agbAcceptanceDate = $this->_getExtra('AGB_ACCEPTANCE_DATE') ?? '';

            return !empty($agbAcceptanceDate) ?
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $agbAcceptanceDate) :
                null;
        }

        return null;
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
     * }.
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
        if (!empty($value) and 1 == $value) {
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
        if (!empty($value) and -1 == $value) {
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

    public function isActiveDuringLast99Days(): bool
    {
        return $this->getLastLogin() > getCurrentDateTimeMinusDaysInMySQL(99);
    }

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

    public function isOnlyReadUser()
    {
        if ($this->isReadOnlyUser()) {
            return true;
        }

        $retour = false;
        global $c_read_account_array;
        if (isset($c_read_account_array)
            and !empty($c_read_account_array[mb_strtolower($this->getUserID(), 'UTF-8').'_'.$this->getAuthSource()])
        ) {
            $retour = true;
        }

        return $retour;
    }

    public function hasChanged($value)
    {
        $result = false;
        foreach ($this->changedValues as $changed_value) {
            if ($changed_value == $value) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    private function _setHasToChangeEmail($value)
    {
        $this->_addExtra('HASTOCHANGEEMAIL', (int) $value);
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
            and 1 == $temp
        ) {
            $retour = true;
        }

        return $retour;
    }

    public function setExternalID($value)
    {
        $this->_addExtra('EXTERNALID', (string) $value);
    }

    public function getExternalID()
    {
        $retour = '';
        if ($this->_issetExtra('EXTERNALID')) {
            $retour = $this->_getExtra('EXTERNALID');
        }

        return $retour;
    }

    /** get lastlogin from plugin
     * this method returns the users plugin lastlogin.
     *
     * @return string timestamp
     */
    public function getLastLoginPlugin($plugin)
    {
        $retour = '';
        if ($this->_issetExtra('LASTLOGIN_'.mb_strtoupper((string) $plugin))) {
            $retour = $this->_getExtra('LASTLOGIN_'.mb_strtoupper((string) $plugin));
        }

        return $retour;
    }

    /** set user lastlogin from plugin
     * this method sets the users users plugin lastlogin.
     *
     * @param string value timestamp
     * @param string plugin plugin identifier
     */
    public function setLastLoginPlugin($value, $plugin)
    {
        $this->_addExtra('LASTLOGIN_'.mb_strtoupper((string) $plugin), (string) $value);
    }

    public function isTemporaryLocked()
    {
        $retour = false;
        if ($this->_issetExtra('TEMPORARY_LOCK')) {
            $date = $this->_getExtra('TEMPORARY_LOCK');
            if (getCurrentDateTimeInMySQL() > $date) {
                $retour = false;
            } else {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setLock($days)
    {
        $this->_addExtra('LOCK', getCurrentDateTimePlusDaysInMySQL($days));
    }

    public function setTemporaryLock()
    {
        $lock_time = $this->_environment->getCurrentContextItem()->getLockTime();
        $this->_addExtra('TEMPORARY_LOCK', getCurrentDateTimePlusMinutesInMySQL($lock_time));
    }

    public function getTemporaryLock()
    {
        $retour = '';
        if ($this->_issetExtra('TEMPORARY_LOCK')) {
            $retour = $this->_getExtra('TEMPORARY_LOCK');
        }

        return $retour;
    }

    public function unsetTemporaryLock()
    {
        $this->_unsetExtra('TEMPORARY_LOCK');
    }

    /**
     * @return $this
     */
    public function setCanImpersonateAnotherUser(bool $enabled): self
    {
        if (true === $enabled) {
            if ($this->_issetExtra('DEACTIVATE_LOGIN_AS')) {
                $this->_unsetExtra('DEACTIVATE_LOGIN_AS');
            }
        } else {
            $this->_addExtra('DEACTIVATE_LOGIN_AS', true);
        }

        return $this;
    }

    public function getCanImpersonateAnotherUser(): bool
    {
        return !$this->_issetExtra('DEACTIVATE_LOGIN_AS');
    }

    public function setPasswordExpireDate($days)
    {
        if (0 == $days) {
            $this->_setValue('expire_date', 'NULL');
        } else {
            $this->_setValue('expire_date', getCurrentDateTimePlusDaysInMySQL($days, true));
        }
    }

    public function unsetPasswordExpireDate()
    {
        $this->_setValue('expire_date', '');
    }

    public function getPasswordExpireDate()
    {
        return $this->_getValue('expire_date');
    }

    public function isPasswordExpired()
    {
        $retour = false;
        if ($this->_getValue('expire_date') < getCurrentDateTimeInMySQL()) {
            $retour = true;
        }

        return $retour;
    }

    public function setImpersonateExpiryDate(?DateTimeImmutable $expiry): cs_user_item
    {
        if (null === $expiry) {
            $this->_unsetExtra('LOGIN_AS_TMSP');
        } else {
            $this->_addExtra('LOGIN_AS_TMSP', $expiry->format(DateTimeInterface::ATOM));
        }

        return $this;
    }

    public function getImpersonateExpiryDate(): ?DateTimeImmutable
    {
        if ($this->_issetExtra('LOGIN_AS_TMSP')) {
            if ($val = DateTimeImmutable::createFromFormat(
                DateTimeInterface::ATOM,
                $this->_getExtra('LOGIN_AS_TMSP'))
            ) {
                return $val;
            }
        }

        return null;
    }

    public function setIsAllowedToCreateContext($value)
    {
        $this->_addExtra('IS_ALLOWED_TO_CREATE_CONTEXT', $value);
    }

    public function getIsAllowedToCreateContext()
    {
        $retour = 'standard';
        if ($this->_issetExtra('IS_ALLOWED_TO_CREATE_CONTEXT')) {
            $retour = $this->_getExtra('IS_ALLOWED_TO_CREATE_CONTEXT');
        }

        return $retour;
    }

    public function getUsePortalEmail()
    {
        return 1 == $this->_getValue('use_portal_email');
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
        if ('standard' != $this->getIsAllowedToCreateContext()) {
            if (-1 == $this->getIsAllowedToCreateContext()) {
                return false;
            } else {
                return true;
            }
        } else {
            global $symfonyContainer;

            /** @var TokenStorageInterface $tokenStorage */
            $tokenStorage = $symfonyContainer->get('app.token_storage');

            /** @var Account $user */
            $user = $tokenStorage->getToken()->getUser();

            return $user->getAuthSource()->getCreateRoom();
        }
    }
}
