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
                if ($dateItem->getRecurrenceId() == '' || !in_array($dateItem->getRecurrenceId(), $recurringIds)) {
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
        error_log(print_r($calendarData, true));
        error_log(print_r($objectUri, true));
        error_log(print_r('*********************', true));

        return $this->updateCalendarObject($calendarId, $objectUri, $calendarData);
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
            $this->transformVeventToDateItem($calendarId, $calendarData, $objectUri);
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
            // ToDo: handle recurring events. Deleted CommSy items need to be stored to work as exclusions from the series.
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
        if (is_array($calendarId)) {
            if (isset($calendarId[0])) {
                $calendarId = $calendarId[0];
            } else {
                return false;
            }
        }
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

        $dateTimeStart = new \DateTime($dateItem->getDateTime_start());
        $dateTimeStartString = $dateTimeStart->format('Ymd') . 'T' . $dateTimeStart->format('His');
        if ($dateItem->isWholeDay()) {
            $dateTimeStartString = $dateTimeStart->format('Ymd');
        }
        $dateTimeEnd = new \DateTime($dateItem->getDateTime_end());
        $dateTimeEndString = $dateTimeEnd->format('Ymd') . 'T' . $dateTimeEnd->format('His');
        if ($dateItem->isWholeDay()) {
            $diff = $dateTimeStart->diff($dateTimeEnd);
            if ($diff->d > 0) {
                $dateTimeEnd->modify('+1 days');
            }
            $dateTimeEndString = $dateTimeEnd->format('Ymd');
        }

        $class = $dateItem->isPublic() ? 'PUBLIC' : 'PRIVATE';
        $user = $this->getUserFromPortal($this->userId, $dateItem->getContextId());
        if ($dateItem->mayEdit($user)) {
            $class = 'PUBLIC';
        }

        $eventDataArray = [
            'SUMMARY' => $dateItem->getTitle(),
            'DTSTART' => $dateTimeStartString,
            'DTEND' => $dateTimeEndString,
            'UID' => $uid,
            'LOCATION' => $dateItem->getPlace(),
            'DESCRIPTION' => $dateItem->getDescription(),
            'CLASS' => $class,
            'X-COMMSY-ITEM-ID' => $dateItem->getItemId(),
        ];

        $recurringSubEvents = [];
        if ($dateItem->getRecurrenceId() != '') {
            $excludeRecurrencePattern = $dateItem->getRecurrencePattern();
            if (isset($excludeRecurrencePattern['recurringExclude'])) {
                $eventDataArray['EXDATE'] = implode(',', $excludeRecurrencePattern['recurringExclude']);
            }

            $recurrencePattern = $this->translateRecurringPattern($dateItem->getRecurrencePattern(), 'CommSy');
            $eventDataArray['RRULE'] = $recurrencePattern;

            $datesManager = $legacyEnvironment->getDatesManager();
            $datesManager->setContextArrayLimit([$dateItem->getContextId()]);
            $datesManager->setWithoutDateModeLimit();
            $datesManager->setRecurrenceLimit($dateItem->getRecurrenceId());
            $datesManager->select();
            $recurringDatesArray = $datesManager->get()->to_array();

            foreach ($recurringDatesArray as $recurringDateItem) {
                $dateTimeRecurrence = $recurringDateItem->getDateTime_start();
                if ($recurringDateItem->getDateTime_recurrence()) {
                    $dateTimeRecurrence = $recurringDateItem->getDateTime_recurrence();
                }

                $dateTimeStart = new \DateTime($recurringDateItem->getDateTime_start());
                $dateTimeStartString = $dateTimeStart->format('Ymd') . 'T' . $dateTimeStart->format('His');
                if ($recurringDateItem->isWholeDay()) {
                    $dateTimeStartString = $dateTimeStart->format('Ymd');
                }
                $dateTimeEnd = new \DateTime($recurringDateItem->getDateTime_end());
                $dateTimeEndString = $dateTimeEnd->format('Ymd') . 'T' . $dateTimeEnd->format('His');
                if ($recurringDateItem->isWholeDay()) {
                    $dateTimeEndString = $dateTimeEnd->format('Ymd');
                }
                $recurringSubEvents[] = [
                    'SUMMARY' => $recurringDateItem->getTitle(),
                    'DTSTART' => $dateTimeStartString,
                    'DTEND' => $dateTimeEndString,
                    'UID' => $uid,
                    'LOCATION' => $recurringDateItem->getPlace(),
                    'DESCRIPTION' => $recurringDateItem->getDescription(),
                    'CLASS' => ($recurringDateItem->isPublic() ? 'PUBLIC' : 'PRIVATE'),
                    'RECURRENCE-ID' => new \DateTime($dateTimeRecurrence),
                    'X-COMMSY-ITEM-ID' => $recurringDateItem->getItemId(),
                ];
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
            } else if ($date = $datesManager->getDateByUid($objectUri)) {
                return $date;
            }
        }

        return null;
    }

    private function transformVeventToDateItem($calendarId, $calendarData, $objectUri = null)
    {
        $calendarsService = $this->container->get('commsy.calendars_service');
        $dateService = $this->container->get('commsy_legacy.date_service');

        $calendar = $calendarsService->getCalendar($calendarId)[0];

        $calendarRead = VObject\Reader::read($calendarData);

        // Use expanded calendar, to work with all dates.
        // CommSy itself does not have to do the calculations.
        $expandDateTimeStart = new \DateTime();
        $expandDateTimeStart->modify('-2 years');
        $expandDateTimeEnd = new \DateTime();
        $expandDateTimeEnd->modify('+2 years');
        /* if ($dateItem) {
            $expandDateTimeStart = new \DateTime($dateItem->getDateTime_start());
            $expandDateTimeEnd = new \DateTime($dateItem->getDateTime_end());
        } */
        $calendarReadExpanded = $calendarRead->expand($expandDateTimeStart, $expandDateTimeEnd);

        global $symfonyContainer;
        $commsyTimeZone = new \DateTimeZone($symfonyContainer->getParameter('commsy.dates.timezone'));
        $utcTimeZone = new \DateTimeZone('UTC');

        $commsyDateTime = new \DateTime('now', $commsyTimeZone);
        $utcDateTime = new \DateTime('now', $utcTimeZone);

        $commsyDateTime = new \DateTime($commsyDateTime->format('Y-m-d H:i:s'));
        $utcDateTime = new \DateTime($utcDateTime->format('Y-m-d H:i:s'));
        $timeZoneDiff = $commsyDateTime->diff($utcDateTime);

        $recurrenceCount = null;
        $recurrencePattern = null;
        $recurrenceEndDateTime = null;
        if ($calendarRead->VEVENT->RRULE) {
            $rrule = $calendarRead->VEVENT->RRULE;
            $rrulePartsArray = $rrule->getParts();
            if (isset($rrulePartsArray['COUNT'])) {
                $recurrenceCount = $rrulePartsArray['COUNT'];

                $calendarReadExpandedChildren = $calendarReadExpanded->children();
                $counter = 0;
                foreach ($calendarReadExpandedChildren as $event) {
                    if ($event->name == 'VEVENT' && (!$recurrenceCount || $counter < $recurrenceCount)) {
                        $wholeday = 0;
                        if ($event->DTSTART) {
                            if (strlen($event->DTSTART->getValue()) == 8) {
                                $wholeday = 1;
                            }
                        }

                        if ($event->DTEND) {
                            $recurrenceEndDateTime = $event->DTEND->getDateTime();
                            if (!$wholeday) {
                                $recurrenceEndDateTime = $recurrenceEndDateTime->modify('+'.$timeZoneDiff->h.' hours');
                            } else {
                                $recurrenceEndDateTime = $recurrenceEndDateTime->modify('-1 days');     // use returned object, as this is of class DateTimeImmutable.
                                $recurrenceEndDateTime = $recurrenceEndDateTime->modify('+23 hours');
                                $recurrenceEndDateTime = $recurrenceEndDateTime->modify('+59 minutes');
                                $recurrenceEndDateTime = $recurrenceEndDateTime->modify('+59 seconds');
                            }
                        }
                        $counter++;
                    }
                }

            }
            $recurrencePattern = $this->translateRecurringPattern($calendarRead->VEVENT->RRULE, 'iCal', $calendarRead->VEVENT->DTSTART->getDateTime(), $recurrenceEndDateTime);
        }

        $newItem = false;
        $recurrenceId = null;

        // insert new data into database
        $calendarReadExpandedChildren = $calendarReadExpanded->children();
        $counter = 0;
        foreach ($calendarReadExpandedChildren as $event) {
            if ($event->name == 'VEVENT' && (!$recurrenceCount || $counter < $recurrenceCount)) {
                if ($event->{'X-COMMSY-ITEM-ID'}) {
                    $dateItem = $dateService->getDate($event->{'X-COMMSY-ITEM-ID'}->getValue());
                } else if ($objectUri) {
                    $dateItem = $this->getDateItemFromObjectUri($objectUri);
                    if (!$dateItem) {
                        $dateItem = $dateService->getNewDate();
                        $dateItem->setContextId($calendar->getContextId());
                        $dateItem->setUid(str_ireplace('.isc', '', $objectUri));
                    }
                } else {
                    $newItem = true;
                    $dateItem = $dateService->getNewDate();
                    $dateItem->setContextId($calendar->getContextId());
                }

                $title = '';
                if ($event->SUMMARY) {
                    $title = $event->SUMMARY->getValue();
                }

                $wholeday = 0;
                $startDatetime = '';
                if ($event->DTSTART) {
                    $startDatetime = $event->DTSTART->getDateTime();
                    if (strlen($event->DTSTART->getValue()) == 8) {
                        $wholeday = 1;
                    } else {
                        $startDatetime = $startDatetime->modify('+'.$timeZoneDiff->h.' hours');
                    }
                }

                $endDatetime = '';
                if ($event->DTEND) {
                    $endDatetime = $event->DTEND->getDateTime();
                    if (!$wholeday) {
                        $endDatetime = $endDatetime->modify('+'.$timeZoneDiff->h.' hours');
                    } else {
                        $endDatetime = $endDatetime->modify('-1 days');     // use returned object, as this is of class DateTimeImmutable.
                        $endDatetime = $endDatetime->modify('+23 hours');
                        $endDatetime = $endDatetime->modify('+59 minutes');
                        $endDatetime = $endDatetime->modify('+59 seconds');
                    }
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

                if ($calendar) {
                    $user = $this->getUserFromPortal($this->userId, $dateItem->getContextId());
                    if ($user->getContextId() == $dateItem->getContextId()) {
                        if (!$dateItem->mayEdit($user)) {
                            throw new Exception\Forbidden('Permission denied to edit date');
                        }
                    }

                    // ToDo: set datetime_recurrence on items if recurring date is created or changed in client
                    // if (!$dateItem->getDateTime_recurrence()) {
                    //     $dateItem->setDateTime_recurrence($dateItem->getDateTime_start());
                    // }

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
                    $dateItem->setWholeDay($wholeday);

                    $userItem = $this->getUserFromPortal($this->userId, $calendar->getContextId());
                    $dateItem->setCreatorId($userItem->getItemId());
                    $dateItem->setModifierId($userItem->getItemId());

                    //$dateItem->setCreationDate($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    //$dateItem->setModificationDate($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    //$dateItem->setChangeModificationOnSave(false);
                    $dateItem->setExternal(false);

                    // iCal CLASS = 'PUBLIC' is used as default value.
                    $dateItem->setPublic(1);
                    if ($event->CLASS) {
                        if ($event->CLASS->getValue() == 'PRIVATE' || $event->CLASS->getValue() == 'CONFIDENTIAL') {
                            $dateItem->setPublic(0);
                        }
                    }

                    $dateItem->save();
                    if ($newItem && !$recurrenceId) {
                        $recurrenceId = $dateItem->getItemId();
                    }

                    if ($event->{'RECURRENCE-ID'} && $recurrenceId) {
                        if ($newItem) {
                            $dateItem->setRecurrencePattern($recurrencePattern);
                        }
                        $dateItem->setRecurrenceId($recurrenceId);
                        $dateItem->save();
                    }
                }
                $counter++;
            }
        }
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

    private function translateRecurringPattern ($pattern, $type, $startDate = null, $endDate = null) {
        $result = '';
        if ($type == "CommSy") {
            if ($pattern['recurring_select'] == 'RecurringDailyType') {
                /*
                CommSy:
                [recurring_sub] => Array
                    (
                        [recurrenceDay] => 1
                    )
                 */
                $result .= 'FREQ=DAILY;';
                if (isset($pattern['recurring_sub']['recurrenceDay'])) {
                    $result .= 'INTERVAL='.$pattern['recurring_sub']['recurrenceDay'].';';
                }
            } else if ($pattern['recurring_select'] == 'RecurringWeeklyType') {
                /*
                CommSy:
                [recurring_sub] => Array
                    (
                        [recurrenceDaysOfWeek] => Array
                            (
                                [0] => monday
                                [1] => tuesday
                                [2] => thursday
                            )

                        [recurrenceWeek] => 2
                    )
                */
                $result .= 'FREQ=WEEKLY;';
                if (isset($pattern['recurring_sub']['recurrenceWeek'])) {
                    $result .= 'INTERVAL='.$pattern['recurring_sub']['recurrenceWeek'].';';
                }

                $result .= 'WKST=MO;';
                if (isset($pattern['recurring_sub']['recurrenceDaysOfWeek'])) {
                    $daysOfWeek = [];
                    foreach ($pattern['recurring_sub']['recurrenceDaysOfWeek'] as $day) {
                        $daysOfWeek[] = mb_strtoupper(substr($day, 0, 2));
                    }

                    $result .= 'BYDAY='.implode($daysOfWeek, ',').';';
                }
            } else if ($pattern['recurring_select'] == 'RecurringMonthlyType') {
                /*
                CommSy:
                [recurring_sub] => Array
                    (
                        [recurrenceMonth] => 2
                        [recurrenceDayOfMonth] => tuesday
                        [recurrenceDayOfMonthInterval] => 3
                    )
                */
                $result .= 'FREQ=MONTHLY;';
                if (isset($pattern['recurring_sub']['recurrenceMonth'])) {
                    $result .= 'INTERVAL='.$pattern['recurring_sub']['recurrenceMonth'].';';
                }
                if (isset($pattern['recurring_sub']['recurrenceDayOfMonthInterval']) && isset($pattern['recurring_sub']['recurrenceDayOfMonth'])) {
                    $result .= 'BYDAY='.$pattern['recurring_sub']['recurrenceDayOfMonthInterval'].mb_strtoupper(substr($pattern['recurring_sub']['recurrenceDayOfMonth'], 0, 2)).';';
                }
            } else if ($pattern['recurring_select'] == 'RecurringYearlyType') {
                /*
                CommSy:
                [recurring_sub] => Array
                    (
                        [recurrenceDayOfMonth] => 2
                        [recurrenceMonthOfYear] => march
                    )
                */
                $result .= 'FREQ=YEARLY;';
                if (isset($pattern['recurring_sub']['recurrenceMonthOfYear'])) {
                    $months = [
                        'january'   => '1',
                        'february'  => '2',
                        'march'     => '3',
                        'april'     => '4',
                        'may'       => '5',
                        'june'      => '6',
                        'july'      => '7',
                        'august'    => '8',
                        'september' => '9',
                        'october'   => '10',
                        'november'  => '11',
                        'december'  => '12',
                    ];
                    $result .= 'BYMONTH='.$months[$pattern['recurring_sub']['recurrenceMonthOfYear']].';';
                }
                if (isset($pattern['recurring_sub']['recurrenceDayOfMonth'])) {
                    $result .= 'BYDAY='.$pattern['recurring_sub']['recurrenceDayOfMonth'].';';
                }
            }

            if (isset($pattern['recurringEndDate'])) {
                $recurringEndDate = new \DateTime($pattern['recurringEndDate']);
                //$recurringEndDate->add(new \DateInterval('P1D'));
                $result .= 'UNTIL='.$recurringEndDate->format('Ymd\THis\Z');
            }

        } else if ($type == 'iCal') {
            $result = [];

            $patternArray = $pattern->getParts();
            if ($patternArray['FREQ'] == 'DAILY') {
                /*
                $patternArray:
                Array
                    (
                        [FREQ] => DAILY
                        [INTERVAL] => 1
                        [UNTIL] => 20180727T215959Z
                    )

                CommSy:
                Array
                    (
                        'recurring_select' => 'RecurringDailyType',
                        'recurring_sub' =>
                            Array (
                                'recurrenceDay' => 1,
                            ),
                        'recurringStartDate' => '2018-07-16',
                        'recurringEndDate' => '2018-07-20',
                    )
                */
                $result['recurring_select'] = 'RecurringDailyType';
                if (isset($patternArray['INTERVAL'])) {
                    $result['recurring_sub']['recurrenceDay'] = $patternArray['INTERVAL'];
                }
            } else if ($patternArray['FREQ'] == 'WEEKLY') {
                /*
                 $patternArray:
                 Array
                 (
                     [FREQ] => WEEKLY
                     [INTERVAL] => 1
                     [UNTIL] => 20180813T215959Z
                 )
                */
                $result['recurring_select'] = 'RecurringWeeklyType';
                if (isset($patternArray['INTERVAL'])) {
                    $result['recurring_sub']['recurrenceWeek'] = $patternArray['INTERVAL'];
                }
                $daysOfWeek = [];
                if ($startDate) {
                    $daysOfWeek[] = mb_strtolower($startDate->format('l'));
                }
                $result['recurring_sub']['recurrenceDaysOfWeek'] = $daysOfWeek;
            } else if ($patternArray['FREQ'] == 'MONTHLY') {
                /*
                $patternArray:
                Array
                    (
                        [FREQ] => MONTHLY
                        [INTERVAL] => 1
                        [UNTIL] => 20181031T225959Z
                    )
                CommSy:
                Array
                    (
                          'recurring_select' => 'RecurringMonthlyType',
                          'recurring_sub' =>
                          Array
                          (
                                'recurrenceMonth' => '1',
                                'recurrenceDayOfMonth' => 'monday',
                                'recurrenceDayOfMonthInterval' => '1',
                          ),
                          'recurringStartDate' => '2018-08-30',
                          'recurringEndDate' => '2018-12-31',
                    )
                */
                $result['recurring_select'] = 'RecurringMonthlyType';
                if (isset($patternArray['INTERVAL'])) {
                    $result['recurring_sub']['recurrenceMonth'] = $patternArray['INTERVAL'];
                }
                $recurrenceDayOfMonth = '';
                if ($startDate) {
                    $recurrenceDayOfMonth = mb_strtolower($startDate->format('l'));
                }
                $result['recurring_sub']['recurrenceDayOfMonth'] = $recurrenceDayOfMonth;
                $result['recurring_sub']['recurrenceDayOfMonthInterval'] = 1;
            } else if ($patternArray['FREQ'] == 'YEARLY') {
                /*
                $patternArray:
                Array
                    (
                        [FREQ] => YEARLY
                        [INTERVAL] => 1
                        [UNTIL] => 20180723T215959Z
                    )
                CommSy:
                Array
                    (
                        'recurring_select' => 'RecurringYearlyType',
                        'recurring_sub' =>
                        Array
                            (
                                'recurrenceDayOfMonth' => '23',
                                'recurrenceMonthOfYear' => 'july',
                            ),
                        'recurringStartDate' => '2018-07-23',
                        'recurringEndDate' => '2020-09-30',
                    )
                */
                $result['recurring_select'] = 'RecurringYearlyType';
                $recurrenceDayOfMonth = '';
                $recurrenceMonthOfYear = '';
                if ($startDate) {
                    $recurrenceDayOfMonth = $startDate->format('j');
                    $recurrenceMonthOfYear = mb_strtolower($startDate->format('F'));
                }
                $result['recurring_sub']['recurrenceDayOfMonth'] = $recurrenceDayOfMonth;
                $result['recurring_sub']['recurrenceMonthOfYear'] = $recurrenceMonthOfYear;
            }

            $result['recurringStartDate'] = $startDate->format('Y-m-d');

            if (isset($patternArray['UNTIL'])) {
                $recurringEndDate = new \DateTime($patternArray['UNTIL']);
                $result['recurringEndDate'] = $recurringEndDate->format('Y-m-d');
            } else if (isset($patternArray['COUNT']) && $endDate) {
                $result['recurringEndDate'] = $endDate->format('Y-m-d');
            } else {
                $recurringEndDate = new \DateTime();
                $recurringEndDate->modify('+2 years');
                $result['recurringEndDate'] = $recurringEndDate->format('Y-m-d');
            }
        }
        return $result;
    }
}