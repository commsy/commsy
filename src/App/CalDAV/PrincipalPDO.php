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
class PrincipalPDO extends \Sabre\DAVACL\PrincipalBackend\AbstractBackend {

    private $container;
    private $portalId;

    /**
    * Reference to PDO connection
    *
    * @var AuthPDO
    */
    protected $pdo;


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
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
    function getPrincipalsByPrefix($prefixPath) {
        $result = [];

        $userItems = $this->getUserListFromPortal();

        foreach ($userItems as $userItem) {
            $result[] = [
                    'uri' => 'principals/'.$userItem->getUserId(),
                    '{DAV:}displayname' => $userItem->getFullname(),
                    '{http://sabredav.org/ns}email-address ' => $userItem->getEmail(),
                ];
        }

        return $result;
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
    function getPrincipalByPath($path) {
        $result = [];

        $pathArray = explode('/', $path);

        if (isset($pathArray[1])) {
            $userItem = $this->getUserFromPortal($pathArray[1]);
            $result = [
                'id'                                    => $userItem->getItemId(),
                'uri'                                   => $path,
                '{DAV:}displayname'                     => $userItem->getFullname(),
                '{http://sabredav.org/ns}email-address' => $userItem->getEmail(),
            ];
        }

        return $result;
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $path
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch) {

    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @param string $test
     * @return array
     */
    function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
        $result = [];

        return $result;
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     * @return string
     */
    function findByUri($uri, $principalPrefix) {
        $path = null;

        $userItems = $this->getUserListFromPortal();

        foreach ($userItems as $userItem) {
            if ($userItem->getEmail() == $uri) {
                $path = 'principals/'.$userItem->getUserId();
            }
        }

        return $path;
    }

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
    function getGroupMemberSet($principal) {
        $result = [];

        return $result;
    }

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
    function getGroupMembership($principal) {
        $result = [];

        return $result;
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
    function setGroupMemberSet($principal, array $members) {

    }


    // --- Helper ---

    private function getUserListFromPortal () {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $legacyEnvironment->setCurrentContextId($this->portalId);
        $legacyEnvironment->setCurrentPortalId($this->portalId);

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setPortalIDLimit($this->portalId);
        $userManager->select();
        return $userManager->get()->to_array();
    }

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
