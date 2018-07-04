<?php

namespace CommsyBundle\CalDAV;

use Sabre\DAV\Exception;
use Sabre\VObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is an authentication backend that uses a database to manage passwords.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalendarPDO extends \Sabre\CalDAV\Backend\AbstractBackend
{

    private $container;
    private $portalId;
    private $userId;

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
    function __construct(\PDO $pdo, ContainerInterface $container, $portalId, $userId)
    {
        $this->pdo = $pdo;
        $this->container = $container;
        $this->portalId = $portalId;
        $this->userId = $userId;
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
    function getCalendarsForUser($principalUri)
    {
        $userId = str_ireplace('principals/', '', $principalUri);

        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $calendarsService = $this->container->get('commsy.calendars_service');

        $userManager = $legacyEnvironment->getUserManager();
        $userArray = $userManager->getAllUserItemArray($userId);

        $contextTitlesArray = array();
        $calendarsArray = array();

        $calendarSelection = false;

        foreach ($userArray as $user) {
            if ($user->getContextItem()) {
                $contextTitlesArray[$user->getContextId()] = $user->getContextItem()->getTitle();
                $calendarsArray = array_merge($calendarsArray, $calendarsService->getListCalendars($user->getContextItem()->getItemId()));

                if ($calendarSelection === false && $user->getOwnRoom()) {
                    $calendarSelection = $user->getOwnRoom()->getCalendarSelection();
                }
            }
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
            if (in_array($calendar->getId(), $calendarSelection['calendarsCalDAV'])) {
                if (!$calendar->getExternalUrl()) {
                    $components = [
                        'VEVENT'
                    ];

                    $tempCalendar = [
                        'id' => [(int)$calendar->getId(), (int)$calendar->getId()],
                        'uri' => urlencode($calendar->getContextId() . $calendar->getId()),
                        'principaluri' => $principalUri,
                        '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . $calendar->getSynctoken(),
                        '{http://sabredav.org/ns}sync-token' => $calendar->getSynctoken(),
                        '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                        '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' => new \Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp('opaque'),
                        'share-resource-uri' => '/ns/share/' . $calendar->getId(),
                    ];

                    $tempCalendar['share-access'] = 1;

                    $tempCalendar['{DAV:}displayname'] = $contextTitlesArray[$calendar->getContextId()] . ' / ' . $calendar->getTitle();
                    $tempCalendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] = '';
                    $tempCalendar['{urn:ietf:params:xml:ns:caldav}calendar-timezone'] = 'BEGIN:VCALENDAR VERSION:2.0 PRODID:-//Apple Inc.//Mac OS X 10.12.5//EN CALSCALE:GREGORIAN BEGIN:VTIMEZONE TZID:Europe/Berlin BEGIN:DAYLIGHT TZOFFSETFROM:+0100 RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU DTSTART:19810329T020000 TZNAME:MESZ TZOFFSETTO:+0200 END:DAYLIGHT BEGIN:STANDARD TZOFFSETFROM:+0200 RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU DTSTART:19961027T030000 TZNAME:MEZ TZOFFSETTO:+0100 END:STANDARD END:VTIMEZONE END:VCALENDAR';
                    $tempCalendar['{http://apple.com/ns/ical/}calendar-order'] = '1';
                    $tempCalendar['{http://apple.com/ns/ical/}calendar-color'] = '';

                    $calendars[] = $tempCalendar;
                }
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
    function getCalendarObjects($calendarId)
    {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $calendarsService = $this->container->get('commsy.calendars_service');

        $calendars = $calendarsService->getCalendar($calendarId[0]);

        if ($calendars[0]) {
            $datesManager = $legacyEnvironment->getDatesManager();
            $datesManager->setContextArrayLimit([$calendars[0]->getcontextId()]);
            $datesManager->setCalendarArrayLimit([$calendars[0]->getId()]);
            $datesManager->setWithoutDateModeLimit();
            $datesManager->select();
            $datesArray = $datesManager->get()->to_array();

            $result = [];
            $recurringIds = [];

            foreach ($datesArray as $dateItem) {
                if ($dateItem->getRecurrenceId() == '' || (!in_array($dateItem->getRecurrenceId(), $recurringIds) && ($dateItem->getItemId() == $dateItem->getRecurrenceId()))) {
                    if ($dateItem->getRecurrenceId() != '') {
                        $recurringIds[] = $dateItem->getRecurrenceId();
                    }

                    $dateTime = new \DateTime($dateItem->getModificationDate());
                    $calendarObjectId = $legacyEnvironment->getCurrentPortalId() . '-' . $dateItem->getContextId() . '-' . $dateItem->getItemId();
                    $result[] = [
                        'id' => $calendarObjectId,
                        'uri' => $calendarObjectId . '.ics',
                        'lastmodified' => $dateTime->getTimestamp(),
                        'etag' => '"' . $calendarObjectId . '-' . $dateTime->getTimestamp() . '"',
                        'size' => $this->getCalendarDataSize($dateItem, $calendarObjectId),
                        'component' => strtolower('VEVENT'),
                    ];
                }
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
    function getCalendarObject($calendarId, $objectUri)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }

        $dateItem = $this->getDateItemFromObjectUri($objectUri);

        if ($dateItem) {
            $dateTime = new \DateTime($dateItem->getModificationDate());

            $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
            $calendarObjectId = $legacyEnvironment->getCurrentPortalId() . '-' . $dateItem->getContextId() . '-' . $dateItem->getItemId();

            return [
                'id' => $calendarObjectId,
                'uri' => $calendarObjectId . '.ics',
                'lastmodified' => $dateTime->getTimestamp(),
                'etag' => '"' . $calendarObjectId . '-' . $dateTime->getTimestamp() . '1"',
                'size' => $this->getCalendarDataSize($dateItem, $objectUri),
                'calendardata' => $this->getCalendarData($dateItem, $objectUri),
                'component' => strtolower('VEVENT'),
            ];
        }

        return [];
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
    function createCalendar($principalUri, $calendarUri, array $properties)
    {

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
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch)
    {

    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param mixed $calendarId
     * @return void
     */
    function deleteCalendar($calendarId)
    {

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
    function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $result = null;

        if ($calendarId[0]) {
            $calendarId = $calendarId[0];

            $dateItem = $this->transformVeventToDateItem($calendarId, $calendarData, null);
            $dateItem->save();

            $this->addChange($calendarId, $objectUri, 1);
        }

        return $result;
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
    function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $result = null;

        if ($calendarId[0]) {
            $calendarId = $calendarId[0];

            $dateItem = $this->transformVeventToDateItem($calendarId, $calendarData, $this->getDateItemFromObjectUri($objectUri));
            $dateItem->save();

            $this->addChange($calendarId, $objectUri, 2);
        }

        return $result;
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
    function deleteCalendarObject($calendarId, $objectUri)
    {
        $dateItem = $this->getDateItemFromObjectUri($objectUri);
        if ($dateItem) {
            $dateItem->delete();
            $this->addChange($calendarId, $objectUri, 3);
        }
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
    function calendarQuery($calendarId, array $filters)
    {
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
                $calendarObjectId = $legacyEnvironment->getCurrentPortalId() . '-' . $dateItem->getContextId() . '-' . $dateItem->getItemId();

                $result[] = $calendarObjectId . '.ics';
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
    protected function addChange($calendarId, $objectUri, $operation)
    {
        $this->container->get('commsy.calendars_service')->updateSynctoken($calendarId);
    }


    // ---- helper methods ---

    private function getCalendarData($dateItem, $objectUri)
    {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();

        $uid = str_ireplace('.ics', '', $objectUri);
        if ($dateItem->getRecurrenceId() != '') {
            $uid = $legacyEnvironment->getCurrentPortalId() . '-' . $dateItem->getContextId() . '-' . $dateItem->getRecurrenceId();
        }

        $eventDataArray = [
            'SUMMARY' => $dateItem->getTitle(),
            'DTSTART' => new \DateTime($dateItem->getDateTime_start()),
            'DTEND' => new \DateTime($dateItem->getDateTime_end()),
            'UID' => $uid,
            'LOCATION' => $dateItem->getPlace(),
            'DESCRIPTION' => $dateItem->getDescription(),
            'CLASS' => ($dateItem->isPublic() ? 'PUBLIC' : 'PRIVATE'),
        ];

        $recurringSubEvents = [];
        if ($dateItem->getRecurrenceId() != '') {
            $recurrencePattern = $this->translateRecurringPattern($dateItem->getRecurrencePattern(), 'CommSy');
            $eventDataArray['RRULE'] = $recurrencePattern;

            $datesManager = $legacyEnvironment->getDatesManager();
            $datesManager->setContextArrayLimit([$dateItem->getContextId()]);
            $datesManager->setWithoutDateModeLimit();
            $datesManager->setRecurrenceLimit($dateItem->getRecurrenceId());
            $datesManager->select();
            $recurringDatesArray = $datesManager->get()->to_array();

            foreach ($recurringDatesArray as $recurringDateItem) {
                //if ($recurringDateItem->getItemId() != $dateItem->getItemId()) {
                    $recurringSubEvents[] = [
                        'SUMMARY' => $recurringDateItem->getTitle(),
                        'DTSTART' => new \DateTime($recurringDateItem->getDateTime_start()),
                        'DTEND' => new \DateTime($recurringDateItem->getDateTime_end()),
                        'UID' => $uid,
                        'LOCATION' => $recurringDateItem->getPlace(),
                        'DESCRIPTION' => $recurringDateItem->getDescription(),
                        'CLASS' => ($recurringDateItem->isPublic() ? 'PUBLIC' : 'PRIVATE'),
                        'RECURRENCE-ID' => new \DateTime($recurringDateItem->getDateTime_start()),
                    ];
                //}
            }
        }

        $vDateItem = new VObject\Component\VCalendar([
            'VEVENT' => $eventDataArray,
        ]);

        foreach ($recurringSubEvents as $recurringSubEvent) {
            $vDateItem->add('VEVENT', $recurringSubEvent);
        }

        foreach ($dateItem->getParticipantsItemList()->to_array() as $attendee) {
            $vDateItem->add('ATTENDEE', 'mailto:' . $attendee->getEmail());
        }

        $vtimezone = $vDateItem->add('VTIMEZONE', [
            'TZID'           => 'Europe/Berlin'
        ]);

        $standardDateTime = (new \DateTime('1970-10-25 03:00:00', new \DateTimeZone('Europe/Berlin')))->format('Ymd\THis');
        $standard = $vDateItem->createComponent('STANDARD', [
            'TZOFFSETFROM' => '+0200',
            'TZOFFSETTO' => '+0100',
            'TZNAME' => 'MESZ',
            'DTSTART' => $standardDateTime,
            'RRULE' => 'FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
        ]);
        $vtimezone->add($standard);

        $daylightDateTime = (new \DateTime('1970-03-29 02:00:00', new \DateTimeZone('Europe/Berlin')))->format('Ymd\THis');
        $daylight = $vDateItem->createComponent('DAYLIGHT', [
            'TZOFFSETFROM' => '+0100',
            'TZOFFSETTO' => '+0200',
            'TZNAME' => 'MESZ',
            'DTSTART' => $daylightDateTime,
            'RRULE' => 'FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
        ]);
        $vtimezone->add($daylight);

        return $vDateItem->serialize();
    }

    private function getCalendarDataSize($dateItem, $objectUri)
    {
        return strlen($this->getCalendarData($dateItem, $objectUri));
    }

    private function getUserFromPortal($userId, $contextId)
    {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $legacyEnvironment->setCurrentContextId($contextId);
        $legacyEnvironment->setCurrentPortalId($this->portalId);

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($contextId);
        $userManager->setUserIDLimit($userId);
        $userManager->select();
        $userList = $userManager->get();
        return $userList->getFirst();
    }

    private function getDateItemFromObjectUri($objectUri)
    {
        $objectUriArray = explode('-', $objectUri);

        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $datesManager = $legacyEnvironment->getDatesManager();

        if (isset($objectUriArray[2])) {
            if ($datesManager->existsItem($objectUriArray[2])) {
                return $datesManager->getItem($objectUriArray[2]);
            }
        }

        return null;
    }

    private function transformVeventToDateItem($calendarId, $calendarData, $dateItem = null)
    {
        $calendarsService = $this->container->get('commsy.calendars_service');
        $dateService = $this->container->get('commsy_legacy.date_service');

        $calendarRead = VObject\Reader::read($calendarData);

        // insert new data into database
        if ($calendarRead->VEVENT) {
            foreach ($calendarRead->VEVENT as $event) {

                $title = '';
                if ($event->SUMMARY) {
                    $title = $event->SUMMARY->getValue();
                }

                $startDatetime = '';
                if ($event->DTSTART) {
                    $startDatetime = $event->DTSTART->getDateTime();
                }

                $endDatetime = '';
                if ($event->DTEND) {
                    $endDatetime = $event->DTEND->getDateTime();
                }

                $location = '';
                if ($event->LOCATION) {
                    $location = $event->LOCATION->getValue();
                }

                $description = '';
                if ($event->DESCRIPTION) {
                    $description = $event->DESCRIPTION->getValue();
                }

                $attendee = '';
                $attendeeArray = array();
                if ($event->ORGANIZER) {
                    $tempOrganizerString = '';
                    if (isset($event->ORGANIZER['CN'])) {
                        $tempOrganizerString .= $event->ORGANIZER['CN'];
                    }
                    $attendeeArray[] = $tempOrganizerString . ' (<a href="' . $event->ORGANIZER->getValue() . '">' . str_ireplace('MAILTO:', '', $event->ORGANIZER->getValue()) . '</a>)';
                }
                if ($event->ATTENDEE) {
                    foreach ($event->ATTENDEE as $tempAttendee) {
                        $tempAttendeeString = '';
                        if (isset($tempAttendee['CN'])) {
                            $tempAttendeeString .= $tempAttendee['CN'];
                        }
                        $attendeeArray[] = $tempAttendeeString . ' (<a href="' . $tempAttendee->getValue() . '">' . str_ireplace('MAILTO:', '', $tempAttendee->getValue()) . '</a>)';
                    }
                }
                if (!empty($attendeeArray)) {
                    $attendee = implode(", ", array_unique($attendeeArray));
                }

                $calendar = $calendarsService->getCalendar($calendarId)[0];
                if ($calendar) {
                    if (!$dateItem) {
                        $dateItem = $dateService->getNewDate();
                    } else {
                        $user = $this->getUserFromPortal($this->userId, $dateItem->getContextId());
                        if ($user->getContextId() == $dateItem->getContextId()) {
                            if (!$dateItem->mayEdit($user)) {
                                throw new Exception\Forbidden('Permission denied to edit date');
                            }
                        }
                    }
                    $dateItem->setContextId($calendar->getContextId());
                    $dateItem->setTitle($title);
                    $dateItem->setDateTime_start($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    $dateItem->setStartingDay($startDatetime->format('Y-m-d'));
                    $dateItem->setStartingTime($startDatetime->format('H:i'));
                    $dateItem->setDateTime_end($endDatetime->format('Ymd') . 'T' . $endDatetime->format('His'));
                    $dateItem->setEndingDay($endDatetime->format('Y-m-d'));
                    $dateItem->setEndingTime($endDatetime->format('H:i'));
                    $dateItem->setCalendarId($calendar->getId());
                    $dateItem->setPlace($location);
                    $dateItem->setDescription($description);

                    $userItem = $this->getUserFromPortal($this->userId, $calendar->getContextId());
                    $dateItem->setCreatorId($userItem->getItemId());
                    $dateItem->setModifierId($userItem->getItemId());

                    //$dateItem->setCreationDate($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    //$dateItem->setModificationDate($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    //$dateItem->setChangeModificationOnSave(false);
                    $dateItem->setExternal(false);
                }
            }
        }

        return $dateItem;
    }


    // ---- pattern translation for recurring events  ---

    /*
        VObject supports the following RRULE options:

        UNTIL           for an end date,

        INTERVAL        for for example "every 2 days",

        COUNT           to stop recurring after x items,

        FREQ=DAILY      to recur every day, and BYDAY to limit it to certain days,

        FREQ=WEEKLY     to recur every week, BYDAY to expand this to multiple weekdays
                        in every week and WKST to specify on which day the week starts,

        FREQ=MONTHLY    to recur every month, BYMONTHDAY to expand this to certain days in a month,
                        BYDAY to expand it to certain weekdays occuring in a month, and BYSETPOS
                        to limit the last two expansions,

        FREQ=YEARLY     to recur every year, BYMONTH to expand that to certain months in a year,
                        and BYDAY and BYWEEKDAY to expand the BYMONTH rule even further.
    */

    private function translateRecurringPattern ($pattern, $type) {
        $result = '';
        if ($type == "CommSy") {
            /*
                Array
                    (
                        [recurring_select] => RecurringDailyType
                        [recurring_sub] => Array
                            (
                                [recurrenceDay] => 1
                            )

                        [recurringStartDate] => 2018-07-02
                        [recurringEndDate] => 2018-07-06
                    )
             */

            if ($pattern['recurring_select'] == 'RecurringDailyType') {
                $result .= 'FREQ=DAILY;';

                if (isset($pattern['recurring_sub']['recurrenceDay'])) {
                    $result .= 'INTERVAL='.$pattern['recurring_sub']['recurrenceDay'].';';
                }
            }

            if (isset($pattern['recurringEndDate'])) {
                $recurringEndDate = new \DateTime($pattern['recurringEndDate']);
                $recurringEndDate->add(new \DateInterval('P1D'));
                $result .= 'UNTIL='.$recurringEndDate->format('Ymd\THis\Z');
            }

        } else if ($type == 'iCal') {

        }
        return $result;
    }
}