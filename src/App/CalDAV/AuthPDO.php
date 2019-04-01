<?php

namespace App\CalDAV;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* This is an authentication backend that uses a database to manage passwords.
*
* @copyright Copyright (C) fruux GmbH (https://fruux.com/)
* @author Evert Pot (http://evertpot.com/)
* @license http://sabre.io/license/ Modified BSD License
*/
class AuthPDO extends \Sabre\DAV\Auth\Backend\AbstractDigest {

    private $container;
    private $portalId;

    /**
    * Reference to PDO connection
    *
    * @var AuthPDO
    */
    protected $pdo;

    /**
    * PDO table name we'll be using
    *
    * @var string
    */
    public $tableName = 'hash';


    /**
    * Creates the backend object.
    *
    * If the filename argument is passed in, it will parse out the specified file fist.
    *
    * @param \PDO $pdo
    */
    function __construct(\PDO $pdo, ContainerInterface $container, $portalId) {
        $this->pdo = $pdo;
        $this->container = $container;
        $this->portalId = $portalId;
    }

    /**
    * Returns the digest hash for a user.
    *
    * @param string $realm
    * @param string $username
    * @return string|null
    */
    function getDigestHash($realm, $userId) {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();

        $userItem = $this->getUserFromPortal($userId);

        if ($userItem) {
            $legacyEnvironment->setCurrentUser($userItem);

            $portalUser = $userItem->getRelatedPrivateRoomUserItem();

            $stmt = $this->pdo->prepare('SELECT caldav FROM ' . $this->tableName . ' WHERE user_item_id = ?');
            $stmt->execute([$portalUser->getItemId()]);
            return $stmt->fetchColumn() ?: null;
        }

        return null;
    }

    /**
     * When this method is called, the backend must check if authentication was
     * successful.
     *
     * The returned value must be one of the following
     *
     * [true, "principals/username"]
     * [false, "reason for failure"]
     *
     * If authentication was successful, it's expected that the authentication
     * backend returns a so-called principal url.
     *
     * Examples of a principal url:
     *
     * principals/admin
     * principals/user1
     * principals/users/joe
     * principals/uid/123457
     *
     * If you don't use WebDAV ACL (RFC3744) we recommend that you simply
     * return a string such as:
     *
     * principals/users/[username]
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    function check(RequestInterface $request, ResponseInterface $response) {

        $digest = new Digest(
            $this->realm,
            $request,
            $response
        );
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            return [false, "No 'Authorization: Digest' header found. Either the client didn't send one, or the server is misconfigured"];
        }

        $userItem = $this->getUserFromPortal($username);

        if (!$userItem) {
            return [false, "No user could be created"];
        }

        if (!$userItem->isAllowedToUseCalDAV()) {
            return [false, "User is not allowed to use CalDAV"];
        }

        $hash = $this->getDigestHash($this->realm, $username);
        // If this was false, the user account didn't exist
        if ($hash === false || is_null($hash)) {
            return [false, "Username or password was incorrect"];
        }
        if (!is_string($hash)) {
            throw new DAV\Exception('The returned value from getDigestHash must be a string or null');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($hash)) {
            return [false, "Username or password was incorrect"];
        }

        return [true, $this->principalPrefix . $username];

    }

    // --- Helper ---

    private function getUserFromPortal ($userId) {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $legacyEnvironment->setCurrentContextId($this->portalId);
        $legacyEnvironment->setCurrentPortalId($this->portalId);

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setPortalIDLimit($this->portalId);
        $userManager->setUserIDLimit($userId);
        $userManager->select();
        $userList = $userManager->get();
        return $userList->getFirst();
    }
}
