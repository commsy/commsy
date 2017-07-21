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
class CalendarPDO extends \Sabre\CalDAV\Backend\AbstractBackend {

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
                    '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/'.$calendar->getSynctoken(),
                    '{http://sabredav.org/ns}sync-token' => $calendar->getSynctoken(),
                    '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                    '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('opaque'),
                    'share-resource-uri' => '/ns/share/' . $calendar->getId(),
                ];

                $tempCalendar['share-access'] = 1;

                $tempCalendar['{DAV:}displayname'] = $contextTitlesArray[$calendar->getContextId()].' / '.$calendar->getTitle();
                $tempCalendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] = '';
                $tempCalendar['{urn:ietf:params:xml:ns:caldav}calendar-timezone'] = 'BEGIN:VCALENDAR VERSION:2.0 PRODID:-//Apple Inc.//Mac OS X 10.12.5//EN CALSCALE:GREGORIAN BEGIN:VTIMEZONE TZID:Europe/Berlin BEGIN:DAYLIGHT TZOFFSETFROM:+0100 RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU DTSTART:19810329T020000 TZNAME:MESZ TZOFFSETTO:+0200 END:DAYLIGHT BEGIN:STANDARD TZOFFSETFROM:+0200 RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU DTSTART:19961027T030000 TZNAME:MEZ TZOFFSETTO:+0100 END:STANDARD END:VTIMEZONE END:VCALENDAR';
                $tempCalendar['{http://apple.com/ns/ical/}calendar-order'] = '1';
                $tempCalendar['{http://apple.com/ns/ical/}calendar-color'] = '';

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
                    'size' => $this->getCalendarDataSize($dateItem, $calendarObjectId),
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
                [size] => 1
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
            'etag'         => '"' . $calendarObjectId.'-'.$dateTime->getTimestamp() . '1"',
            'size'         => $this->getCalendarDataSize($dateItem, $objectUri),
            'calendardata' => $this->getCalendarData($dateItem, $objectUri),
            'component'    => strtolower('VEVENT'),
        ];
    }


    // --- calendars ---

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used
     * to reference this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string
     */
    function createCalendar($principalUri, $calendarUri, array $properties) {

    }

    /**
     * Updates properties for a calendar.
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
     * @param mixed $calendarId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch) {

    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param mixed $calendarId
     * @return void
     */
    function deleteCalendar($calendarId) {

    }


    // --- calendar objects ---

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    function createCalendarObject($calendarId, $objectUri, $calendarData) {

    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    function updateCalendarObject($calendarId, $objectUri, $calendarData) {

    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return void
     */
    function deleteCalendarObject($calendarId, $objectUri) {

    }


    // --- calendar query ---

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * Note that it is extremely likely that getCalendarObject for every path
     * returned from this method will be called almost immediately after. You
     * may want to anticipate this to speed up these requests.
     *
     * This method provides a default implementation, which parses *all* the
     * iCalendar objects in the specified calendar.
     *
     * This default may well be good enough for personal use, and calendars
     * that aren't very large. But if you anticipate high usage, big calendars
     * or high loads, you are strongly adviced to optimize certain paths.
     *
     * The best way to do so is override this method and to optimize
     * specifically for 'common filters'.
     *
     * Requests that are extremely common are:
     *   * requests for just VEVENTS
     *   * requests for just VTODO
     *   * requests with a time-range-filter on a VEVENT.
     *
     * ..and combinations of these requests. It may not be worth it to try to
     * handle every possible situation and just rely on the (relatively
     * easy to use) CalendarQueryValidator to handle the rest.
     *
     * Note that especially time-range-filters may be difficult to parse. A
     * time-range filter specified on a VEVENT must for instance also handle
     * recurrence rules correctly.
     * A good example of how to interpret all these filters can also simply
     * be found in \Sabre\CalDAV\CalendarQueryFilter. This class is as correct
     * as possible, so it gives you a good idea on what type of stuff you need
     * to think of.
     *
     * This specific implementation (for the PDO) backend optimizes filters on
     * specific components, and VEVENT time-ranges.
     *
     * @param mixed $calendarId
     * @param array $filters
     * @return array
     */
    function calendarQuery($calendarId, array $filters) {
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
                $calendarObjectId = $legacyEnvironment->getCurrentPortalId().'-'.$dateItem->getContextId().'-'.$dateItem->getItemId();

                $result[] = $calendarObjectId.'.ics';
            }

            return $result;
        }

        return [];
    }


    /**
     * Adds a change record to the calendarchanges table.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param int $operation 1 = add, 2 = modify, 3 = delete.
     * @return void
     */
    protected function addChange($calendarId, $objectUri, $operation) {
        $this->container->get('commsy.calendars_service')->updateSynctoken($calendarId);
    }


    // ---- helper methods ---

    private function getCalendarData ($dateItem, $objectUri) {
        $vDateItem = new VObject\Component\VCalendar([
            'VEVENT' => [
                'SUMMARY' => $dateItem->getTitle(),
                'DTSTART' => new \DateTime($dateItem->getDateTime_start()),
                'DTEND'   => new \DateTime($dateItem->getDateTime_end()),
                'UID'     => str_ireplace('.ics', '', $objectUri),
            ]
        ]);
        return $vDateItem->serialize();
        //return 'BEGIN:VCALENDAR PRODID:-//Mozilla.org/NONSGML Mozilla Calendar V1.1//EN VERSION:2.0 BEGIN:VTIMEZONE TZID:Europe/Berlin BEGIN:DAYLIGHT TZOFFSETFROM:+0100 TZOFFSETTO:+0200 TZNAME:CEST DTSTART:19700329T020000 RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3 END:DAYLIGHT BEGIN:STANDARD TZOFFSETFROM:+0200 TZOFFSETTO:+0100 TZNAME:CET DTSTART:19701025T030000 RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10 END:STANDARD END:VTIMEZONE BEGIN:VEVENT CREATED:20170717T185816Z LAST-MODIFIED:20170717T185833Z DTSTAMP:20170717T185833Z UID:101-2024-14828 SUMMARY:Neuer Termin DTSTART;TZID=Europe/Berlin:20170717T103000 DTEND;TZID=Europe/Berlin:20170717T113000 TRANSP:OPAQUE CLASS:PRIVATE END:VEVENT END:VCALENDAR';
    }

    private function getCalendarDataSize ($dateItem, $objectUri) {
        return strlen($this->getCalendarData($dateItem, $objectUri));
    }

}