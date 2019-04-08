<?php

namespace App\Controller;

use App\CalDAV\AuthPDO;
use App\CalDAV\CalendarPDO;
use App\CalDAV\PrincipalPDO;
use App\CalDAV\Server;
use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CalDAVController extends Controller
{
    /**
     * @Route("/{portalId}/")
     */
    public function caldavAction($portalId)
    {
        return $this->caldavServer($portalId)->exec();
    }

    /**
     * @Route("/{portalId}/calendars/")
     */
    public function caldavCalendarsAction($portalId)
    {
        return $this->caldavServer($portalId)->exec();
    }

    /**
     * @Route("/{portalId}/calendars/{userId}/")
     */
    public function caldavCalendarsUserAction($portalId, $userId)
    {
        return $this->caldavServer($portalId, $userId)->exec();
    }

    /**
     * @Route("/{portalId}/calendars/{userId}/{calendarId}/")
     */
    public function caldavCalendarAction($portalId, $userId, $calendarId)
    {
        return $this->caldavServer($portalId, $userId)->exec();
    }

    /**
     * @Route("/{portalId}/calendars/{userId}/{calendarId}/{objectId}")
     */
    public function caldavCalendarObjectAction($portalId, $userId, $calendarId, $objectId)
    {
        return $this->caldavServer($portalId, $userId)->exec();
    }

    /**
     * @Route("/{portalId}/principals/")
     */
    public function caldavPrincipalsAction($portalId)
    {
        return $this->caldavServer($portalId)->exec();
    }

    /**
     * @Route("/{portalId}/principals/{userId}/")
     */
    public function caldavPrincipalAction($portalId, $userId)
    {
        return $this->caldavServer($portalId, $userId)->exec();
    }

    private function caldavServer($portalId, $userId = '')
    {
        $dbHost = $this->container->getParameter('database_host');
        $dbName = $this->container->getParameter('database_name');
        $dbUser = $this->container->getParameter('database_user');
        $dbPassword = $this->container->getParameter('database_password');

        $commsyPdo = new \PDO('mysql:dbname=' . $dbName . ';host=' . $dbHost, $dbUser, $dbPassword);

        // Backends
        $authBackend = new AuthPDO($commsyPdo, $this->container, $portalId);
        $authBackend->setRealm('CommSy');
        $principalBackend = new PrincipalPDO($commsyPdo, $this->container, $portalId);
        $calendarBackend = new CalendarPDO($commsyPdo, $this->container, $portalId, $userId);

        // Directory tree
        $tree = array(
            new DAVACL\PrincipalCollection($principalBackend),
            new CalDAV\CalendarRoot($principalBackend, $calendarBackend)
        );

        // The object tree needs in turn to be passed to the server class
        $server = new Server($tree);

        // You are highly encouraged to set your WebDAV server base url. Without it,
        // SabreDAV will guess, but the guess is not always correct. Putting the
        // server on the root of the domain will improve compatibility.
        $prefix = '';
        $server->setBaseUri($prefix . '/' . $portalId . '/');

        // Authentication plugin
        $authPlugin = new DAV\Auth\Plugin($authBackend, 'SabreDAV');
        $server->addPlugin($authPlugin);

        // CalDAV plugin
        $caldavPlugin = new CalDAV\Plugin();
        $server->addPlugin($caldavPlugin);

        // ACL plugin
        $aclPlugin = new DAVACL\Plugin();
        $server->addPlugin($aclPlugin);

        // Support for html frontend
        $browser = new DAV\Browser\Plugin();
        $server->addPlugin($browser);

        return $server;
    }
}