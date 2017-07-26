<?php

namespace CommsyBundle\Services;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

require_once('../legacy/classes/cs_session_item.php');
require_once('../legacy/classes/cs_session_item.php');

class SoapService
{
    private $legacyEnvironment;

    private $sessionIdArray = [];

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Returns a new guest session id
     * 
     * @param  int $portalId
     * 
     * @return string session id
     */
    public function getGuestSession($portalId)
    {
        if (!$portalId) {
            return new \SoapFault('ERROR', 'portalId not set!');
        }

        $this->legacyEnvironment->setCurrentContextID($portalId);

        // create guest session
        $sessionItem = new \cs_session_item();
        $sessionItem->createSessionID('guest');
        $sessionItem->setValue('portalId', $portalId);
        $sessionItem->setSoapSession();

        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionManager->save($sessionItem);

        return $sessionItem->getSessionID();
    }

    /**
     * Authenticates a user
     *
     * @param string $userId
     * @param string $password
     * @param int $portalId
     * @param int $authSourceId
     * 
     * @return string session id
     * 
     */
    public function authenticate($userId, $password, $portalId = 99, $authSourceId = 0)
    {
        if (!$userId) {
            return new \SoapFault('ERROR', 'userId not set!');
        }

        if (!$password) {
            return new \SoapFault('ERROR', 'password not set!');
        }

        $this->legacyEnvironment->setCurrentContextID($portalId);

        $authentication = $this->legacyEnvironment->getAuthenticationObject();
        if (!isset($authentication)) {
            return new \SoapFault('ERROR', 'no authentication found!');
        }

        if ($authentication->isAccountGranted($userId, $password, $authSourceId)) {
            if ($this->isSessionActive($userId, $portalId)) {
                $sessionId = $this->getActiveSessionId($userId, $portalId);
                if (!$sessionId) {
                    return new \SoapFault('ERROR', 'no session found!');
                }

                return $sessionId;
            } else {
                $sessionItem = new \cs_session_item();
                $sessionItem->createSessionID($userId);

                // save portal id in session to be sure, that user didn't
                // switch between portals
                $sessionItem->setValue('user_id', $userId);
                $sessionItem->setValue('commsy_id', $portalId);

                if (!$authSourceId) {
                    $authSourceId = $authentication->getAuthSourceItemID();
                }

                $sessionItem->setValue('auth_source', $authSourceId);
                $sessionItem->setValue('cookie', '3');
                $sessionItem->setSoapSession();

                // save session
                $sessionManager = $this->legacyEnvironment->getSessionManager();
                $sessionManager->save($sessionItem);

                return $sessionItem->getSessionID();
            }
        } else {
            return new \SoapFault('ERROR', 'permission denied!');
        }
    }

    /**
     * Creates a new wiki
     * 
     * @param  string $sessionId
     * @param  string $contextId
     * 
     * @return bool success
     */
    public function createWiki($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new \SoapFault('ERROR', 'session invalid!');
        }

//          $room_manager = $this->_environment->getRoomManager();
//          $room_item = $room_manager->getItem($context_id);

//          $item->setWikiSkin();
//          $item->setWikiEditPW();
//          $item->setWikiAdminPW();
//          $item->setWikiEditPW();
//          $item->setWikiReadPW();
//          $item->setWikiTitle();
//          $item->setWikiShowCommSyLogin();
//          $item->setWikiWithSectionEdit();
//          $item->setWikiWithHeaderForSectionEdit();
//          $item->setWikiEnableFCKEditor();
//          $item->setWikiEnableSearch();
//          $item->setWikiEnableSitemap();
//          $item->setWikiEnableStatistic();
//          $item->setWikiEnableRss();
//          $item->setWikiEnableCalendar();
//          $item->setWikiEnableNotice();
//          $item->setWikiEnableGallery();
//          $item->setWikiEnablePdf();
//          $item->setWikiEnableSwf();
//          $item->setWikiEnableWmplayer();
//          $item->setWikiEnableQuicktime();
//          $item->setWikiEnableYoutubeGoogleVimeo();
//          $item->setWikiEnableDiscussion();
//          //$item->setWikiDiscussionArray();
//          $item->setWikiEnableDiscussionNotification();
//          $item->setWikiEnableDiscussionNotificationGroups();

//          $wiki_manager = $this->_environment->getWikiManager();
//          $wiki_manager->deleteWiki($room_item);
    }

    /**
     * Deletes a wiki
     * 
     * @param  string $sessionId
     * @param  string $contextId
     * 
     * @return bool success
     */
    public function deleteWiki($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new \SoapFault('ERROR', 'session invalid!');
        }

//          $room_manager = $this->_environment->getRoomManager();
//          $room_item = $room_manager->getItem($context_id);
//          $wiki_manager = $this->_environment->getWikiManager();
//          $wiki_manager->deleteWiki($room_item);
    }

    /**
     * Checks valid session
     * 
     * @param  string $sessionId
     * 
     * @return bool success
     */
    public function isSessionValid($sessionId)
    {
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionItem = $sessionManager->get($sessionId);

        if (isset($sessionItem) && $sessionItem->issetValue('user_id')) {
            return true;
        }

        return false;
    }

    /**
     * Returns a userId
     *
     * @param string $sessionId
     *
     * @return string user_id
     */
    public function getUserIdBySessionId($sessionId)
    {
        if ($this->isSessionValid($sessionId)) {
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionItem = $sessionManager->get($sessionId);

            return $sessionItem->getValue('user_id');
        }
        return false;
    }

    /**
     * Returns information about an the user identified by the session id
     *
     * @param string $sessionId The session id
     * @param int $contextId The context id
     *
     * @throws SoapFault
     *
     * @return string | null
     */
    public function getUserInfo($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new \SoapFault('ERROR', 'given session id is invalid!');
        }

        // grep the session
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionItem = $sessionManager->get($sessionId);

        // extract information from session object
        $userId = $sessionItem->getValue('user_id');
        $authSource = $sessionItem->getValue('auth_source');

        // get the user object
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($contextId);
        $userManager->setUserIDLimit($userId);
        $userManager->setAuthSourceLimit($authSource);
        $userManager->select();

        $userList = $userManager->get();
        if ($userList->getCount() == 1) {
            return $userList->getFirst()->getDataAsXML();
        }

        return new \SoapFault('ERROR', 'no user found!');
    }

    private function isSessionActive($userId, $portalId)
    {
        if (!empty($this->sessionIdArray[$portalId][$userId])) {
            return true;
        } else {
            $sessionId = $this->getActiveSessionId($userId, $portalId);
            if ($sessionId) {
                return true;
            }
        }

        return false;
    }

    private function getActiveSessionId($userId, $portalId)
    {
        if (!empty($this->sessionIdArray[$portalId][$userId])) {
            return $this->sessionIdArray[$portalId][$userId];
        } else {
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionId = $sessionManager->getActiveSOAPSessionID($userId, $portalId);

            if (!empty($sessionId)) {
                $this->sessionIdArray[$portalId][$userId] = $sessionId;
                $this->updateSessionCreationDate($sessionId);

                return $sessionId;
            }
        }

        return null;
    }

    private function updateSessionCreationDate($sessionId)
    {
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionManager->updateSessionCreationDate($sessionId);
    }
}