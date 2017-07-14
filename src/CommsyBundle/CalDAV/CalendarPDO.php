<?php

namespace CommsyBundle\CalDAV;

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
class CalendarPDO extends \Sabre\CalDAV\Backend\PDO {

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
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the calendar.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     * @return array
     */
    function getCalendarsForUser($principalUri) {
        $userId = str_ireplace('principals/', '', $principalUri);

        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();

        $userManager = $legacyEnvironment->getUserManager();
        $userArray = $userManager->getAllUserItemArray($userId);

        $contextArray = array();
        foreach ($userArray as $user) {
            $contextArray[] = $user->getContextItem();
        }

        $calendars = [];
        foreach ($contextArray as $context) {

            $components = [];

            $calendar = [
                'id'                                                                 => [(int)$context->getItemId(), (int)$context->getItemId()],
                'uri'                                                                => $context->getTitle(),
                'principaluri'                                                       => $principalUri,
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag'                  => 'http://sabre.io/ns/sync/0',
                '{http://sabredav.org/ns}sync-token'                                 => '0',
                '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp'         => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('opaque'),
                'share-resource-uri'                                                 => '/ns/share/' . $context->getItemId(),
            ];

            $calendar['share-access'] = 1;

            $calendar['{DAV:}displayname']                                   = $context->getTitle();
            $calendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] = $context->getTitle();
            $calendar['{urn:ietf:params:xml:ns:caldav}calendar-timezone']    = 'Hamburg';
            $calendar['{http://apple.com/ns/ical/}calendar-order']           = '1';
            $calendar['{http://apple.com/ns/ical/}calendar-color']           = '';

            foreach ($this->propertyMap as $xmlName => $dbName) {
            }

            $calendars[] = $calendar;
        }

        return $calendars;

    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param mixed $calendarId
     * @return array
     */
    function getCalendarObjects($calendarId) {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $datesManager = $legacyEnvironment->getDatesManager();
        $datesManager->setContextArrayLimit([$calendarId]);
        $datesManager->setWithoutDateModeLimit();
        $datesManager->select();
        $datesArray = $datesManager->get()->to_array();

        $result = [];
        foreach ($datesArray as $date) {
            $result[] = [
                'id'           => $date->getItemId(),
                'uri'          => $date->getItemId(),
                'lastmodified' => 1,
                'etag'         => '"' . $date->getTitle() . '"',
                'size'         => 1,
                'component'    => strtolower(''),
            ];
        }

        return $result;

    }

}
