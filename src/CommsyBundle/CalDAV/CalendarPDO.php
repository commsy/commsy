<?php

namespace CommsyBundle\CalDAV;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;

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
        $calendarsService = $this->container->get('commsy.calendars_service');

        $userManager = $legacyEnvironment->getUserManager();
        $userArray = $userManager->getAllUserItemArray($userId);

        $contextTitlesArray = array();
        $calendarsArray = array();
        foreach ($userArray as $user) {
            $contextTitlesArray[$user->getContextId()] = $user->getContextItem()->getTitle();
            $calendarsArray = array_merge($calendarsArray, $calendarsService->getListCalendars($user->getContextItem()->getItemId()));
        }


        /*

        Structure of calendars

        1) sabre/dav demo
        Array
        (
            [0] => Array
                (
                    [id] => Array
                        (
                            [0] => 1
                            [1] => 1
                        )

                    [uri] => calendarAdmin
                    [principaluri] => principals/admin
                    [{http://calendarserver.org/ns/}getctag] => http://sabre.io/ns/sync/1
                    [{http://sabredav.org/ns}sync-token] => 1
                    [{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set] => Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet Object
                        (
                            [components:protected] => Array
                                (
                                    [0] => VEVENT
                                    [1] => VTODO
                                )

                        )

                    [{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp] => Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp Object
                        (
                            [value:protected] => opaque
                        )

                    [share-resource-uri] => /ns/share/1
                    [share-access] => 1
                    [{DAV:}displayname] => calendarAdmin
                    [{urn:ietf:params:xml:ns:caldav}calendar-description] =>
                    [{urn:ietf:params:xml:ns:caldav}calendar-timezone] =>
                    [{http://apple.com/ns/ical/}calendar-order] => 0
                    [{http://apple.com/ns/ical/}calendar-color] =>
                )

            2) current CommSy
            Array
                (
                    [id] => Array
                        (
                            [0] => 110
                            [1] => 110
                        )

                    [uri] => 110
                    [principaluri] => principals/solth
                    [{http://calendarserver.org/ns/}getctag] => http://sabre.io/ns/sync/1
                    [{http://sabredav.org/ns}sync-token] => 1
                    [{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set] => Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet Object
                        (
                            [components:protected] => Array
                                (
                                    [0] => VEVENT
                                )

                        )

                    [{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp] => Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp Object
                        (
                            [value:protected] => opaque
                        )

                    [share-resource-uri] => /ns/share/110
                    [share-access] => 1
                    [{DAV:}displayname] => Mein persönlicher Raum-110-Kalender
                    [{urn:ietf:params:xml:ns:caldav}calendar-description] =>
                    [{urn:ietf:params:xml:ns:caldav}calendar-timezone] =>
                    [{http://apple.com/ns/ical/}calendar-order] => 1
                    [{http://apple.com/ns/ical/}calendar-color] =>
                )

        )
        */


        $calendars = [];
        foreach ($calendarsArray as $calendar) {

            if (!$calendar->getExternalUrl()) {

                $components = [
                    'VEVENT'
                ];

                $tempCalendar = [
                    'id' => [(int)$calendar->getId(), (int)$calendar->getId()],
                    'uri' => urlencode($contextTitlesArray[$calendar->getContextId()].$calendar->getTitle()),
                    'principaluri' => $principalUri,
                    '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/1', // Allow sync
                    '{http://sabredav.org/ns}sync-token' => '1', // Allow sync
                    '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                    '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('opaque'),
                    'share-resource-uri' => '/ns/share/' . $calendar->getId(),
                ];

                $tempCalendar['share-access'] = 1;

                $tempCalendar['{DAV:}displayname'] = $contextTitlesArray[$calendar->getContextId()].' / '.$calendar->getTitle();
                $tempCalendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] = '';
                $tempCalendar['{urn:ietf:params:xml:ns:caldav}calendar-timezone'] = '';
                $tempCalendar['{http://apple.com/ns/ical/}calendar-order'] = '1';
                $tempCalendar['{http://apple.com/ns/ical/}calendar-color'] = '';

                foreach ($this->propertyMap as $xmlName => $dbName) {
                }

                $calendars[] = $tempCalendar;
            }
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
        $calendarsService = $this->container->get('commsy.calendars_service');

        $calendars = $calendarsService->getCalendar($calendarId[0]);

        if ($calendars[0]) {
            $datesManager = $legacyEnvironment->getDatesManager();
            $datesManager->setContextArrayLimit([$calendars[0]->getcontextId()]);
            $datesManager->setWithoutDateModeLimit();
            $datesManager->select();
            $datesArray = $datesManager->get()->to_array();

            $result = [];
            foreach ($datesArray as $dateItem) {
                $dateTime = new \DateTime($dateItem->getModificationDate());

                $calendarObjectId = $legacyEnvironment->getCurrentPortalId().'-'.$dateItem->getContextId().'-'.$dateItem->getItemId();

                $result[] = [
                    'id' => $calendarObjectId,
                    'uri' => $calendarObjectId.'.ics',
                    'lastmodified' => $dateTime->getTimestamp(),
                    'etag' => '"' . $calendarObjectId.'-'.$dateTime->getTimestamp() . '"',
                    'size' => $this->getCalendarDataSize($dateItem),
                    'component' => strtolower('VEVENT'),
                ];
            }

            /*

            Entry in result should hold this information:

            - sabre/dav
            (
                [id] => 1
                [uri] => 79b60720-7b23-5b4d-b8be-67ed7e8e29ac.ics
                [lastmodified] => 1500119848
                [etag] => "460e0690b3095cfbf9bef5d20873b379"
                [size] => 733
                [component] => vevent
            )

            - CommSy
            Array
            (
                [id] => 14829
                [uri] => 101-2024-14828.ics
                [lastmodified] => 1508356800
                [etag] => "101-2024-14828-1508356800"
                [size] => ToDo
                [component] => vevent
            )

            */

            return $result;
        }

        return [];
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    function getCalendarObject($calendarId, $objectUri) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }

        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $datesManager = $legacyEnvironment->getDatesManager();

        $objectUriArray = explode('-', $objectUri);
        $dateItem = $datesManager->getItem($objectUriArray[2]);

        $dateTime = new \DateTime($dateItem->getModificationDate());

        $calendarObjectId = $legacyEnvironment->getCurrentPortalId().'-'.$dateItem->getContextId().'-'.$dateItem->getItemId();

        return [
            'id'           => $calendarObjectId,
            'uri'          => $calendarObjectId.'.ics',
            'lastmodified' => $dateTime->getTimestamp(),
            'etag'         => '"' . $calendarObjectId.'-'.$dateTime->getTimestamp() . '"',
            'size'         => $this->getCalendarDataSize($dateItem),
            'calendardata' => $this->getCalendarData($dateItem),
            'component'    => strtolower('VEVENT'),
        ];
    }

    private function getCalendarData ($dateItem) {
        $vDateItem = new VObject\Component\VCalendar([
            'VEVENT' => [
                'SUMMARY' => $dateItem->getTitle(),
                'DTSTART' => new \DateTime($dateItem->getDateTime_start()),
                'DTEND'   => new \DateTime($dateItem->getDateTime_end())
            ]
        ]);
        return $vDateItem->serialize();
    }

    private function getCalendarDataSize ($dateItem) {
        return strlen($this->getCalendarData($dateItem));
    }

}