<?php
error_reporting(E_ALL);
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//+----------------------------------------------------------------------+
//| WAMP (XP-SP1/1.3.27/4.0.12/5.0.0b2-dev)                                    |
//+----------------------------------------------------------------------+
//| Copyright (c) 1992-2003 Michael Wimmer                               |
//+----------------------------------------------------------------------+
//| I don't have the time to read through all the licences to find out   |
//| what the exactly say. But it's simple. It's free for non commercial  |
//| projects, but as soon as you make money with it, i want my share :-) |
//| (License : Free for non-commercial use)                              |
//+----------------------------------------------------------------------+
//| Authors: Michael Wimmer <flaimo@gmx.net>                             |
//+----------------------------------------------------------------------+
//
// $Id$

/**
* @package iCalendar Everything to generate simple iCal files
*/

/**
* We need the child classes
*/
function autoload($class)
{
    require_once('classes/external_classes/ical/' . $class.'.php');
}
spl_autoload_register("autoload");

/**
* Create a iCalendar file for download
*
* $iCal = new iCal('', 0, '');
* $iCal->addEvent();
* $iCal->addToDo();
* 
* $iCal->outputFile('ics'); // output file as isc (xcs and rdf possible)
*
* Date/Time is stored with an absolute z value, which means that the
* calendar programm should import the time 1:1 not regarding timezones and
* Daylight Saving Time. MS Outlook imports z dates wrong, so you have to
* correct the dates BEFORE you add a new event.
* Also if you have an event series and not a single event, you have to use
* File >> Import in Outlook to import the whole series and not just the
* first date.
*
* Last Change: 2003-07-07
* Tested with WAMP (XP-SP1/1.3.27/4.0.12/5.0.0b2-dev)
*
* @access public
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
* @abstract
* @example sample_ical.php Sample script
* @version 2.001
*/
class iCal {

   /*-------------------*/
   /* V A R I A B L E S */
   /*-------------------*/

   /**#@+
   * @var array
   */
   /**
   * Array with all the iCalEvent objects
   */
   private $icalevents = array();

   /**
   * Array with all the iCalToDo objects
   */
   private $icaltodos = array();

   /**
   * Array with all the freebusy objects
   */
   private $icalfbs = array();

   /**
   * Array with all the journal objects
   */
   private $icaljournals = array();
   /**#@-*/

   /**#@+
   * @var string
   */
   /**
   * Programm ID for the File
   */
   private $prodid = '-//flaimo.com//iCal Class MIMEDIR//EN';

   /**
   * Output string to be written in the iCal file
   */
   private $output;

   /**
   * Format of the output (ics, xcs, rdf)
   */
   private $output_format;

   /**
   * Filename for the iCal file to be saved
   */
   private $events_filename;

   /**
   * Time the entry was created (iCal format)
   */
   private $ical_timestamp;
   /**#@-*/

   /**#@+
   * @var int
   */
   /**
   * ID number for the event array
   */
   private $eventid = 0;

   /**
   * ID number for the todo array
   */
   private $todoid = 0;

   /**
   * ID number for the freebusy array
   */
   private $fbid = 0;

   /**
   * ID number for the journal array
   */
   private $journalid = 0;

   /**
   * Method: PUBLISH (1) or REQUEST (0)
   */
   private $method = 1;

   /*-----------------------*/
   /* C O N S T R U C T O R */
   /*-----------------------*/

   /**
   * Constructor
   *
   * Only job is to set all the variablesnames
   *
   * @param string $prodid  ID code for the iCal file (see setProdID)
   * @param int $method  PUBLISH (1) or REQUEST (0)
   * @param string $downloaddir
   * @return void
   * @uses setiCalTimestamp()
   * @uses setProdID()
   * @uses setMethod()
   * @uses checkClass()
   * @uses iCal::$events_filename
   */
   function __construct($prodid = '', $method = 1) {
      $this->setiCalTimestamp();
      $this->setProdID($prodid);
        $this->setMethod($method);
      $this->events_filename  = (string) time() . '.ics';
   } // end constructor

   /*-------------------*/
   /* F U N C T I O N S */
   /*-------------------*/

   /**
   * Encodes a string for QUOTE-PRINTABLE
   *
   * @param string $quotprint  String to be encoded
   * @return string  Encodes string
   * @since 1.001 - 2002-10-19
   * @author Harald Huemer <harald.huemer@liwest.at>
   */
   private function quotedPrintableEncode($quotprint = '') {
      /*
      //beim Mac Umlaute nicht kodieren !!!! sonst Fehler beim Import
      if ($progid == 3)
        {
        $quotprintenc = preg_replace("~([\x01-\x1F\x3D\x7F-\xBF])~e", "sprintf('=%02X', ord('\\1'))", $quotprint);
        return($quotprintenc);
        }
      //bei Windows und Linux alle Sonderzeichen kodieren
      else
        {*/
      //if (!extension_loaded('mbstring')) {
         $quotprint = (string) str_replace('\r\n',chr(13) . chr(10),$quotprint);
         $quotprint = (string) str_replace('\n',chr(13) . chr(10),$quotprint);
        return (string) $quotprint;
      //} else {
      //	return (string) mb_encode_mimeheader($quotprint, 'iso-8859-1', 'Q');
      //} // end if
   } // end function

   /**
   * Returns string with the status of an attendee
   *
   * @param int $role
   * @return string $roles Status
   * @since 1.001 - 2002-10-10
   */
   public static final function getAttendeeRole($role = 2) {
      $roles = (array) array('CHAIR','REQ-PARTICIPANT','OPT-PARTICIPANT','NON-PARTICIPANT');
      return (string) ((array_key_exists($role, $roles)) ? $roles[$role] : $roles[2]);
   } // end function

   /**#@+
   * @return void
   */
   /**
   * Set $prodid variable
   *
   * @param string $prodid
   * @see getProdID()
   * @uses iCal::$prodid
   */
   private function setProdID($prodid = '') {
      if (strlen(trim($prodid)) > 0) {
         $this->prodid = (string) $prodid;
      } // end if
   } // end function

   /**
   * Set $method variable
   *
   * @param int $method
   * @see getMethod()
   * @uses iCal::$method
   * @since 1.001 - 2002-10-10
   */
   private function setMethod($method = 1) {
      if (is_int($method) && preg_match('(^([0-1]{1})$)', $method)) {
         $this->method = (int) $method;
      } // end if
   } // end function

   /**
   * Set $ical_timestamp variable
   *
   * @see getiCalTimestamp()
   * @uses iCal::$ical_timestamp
   */
   private function setiCalTimestamp() {
      $this->ical_timestamp = (string) gmdate('Ymd\THi00\Z',time());
   } // end function
   /**#@-*/

   /**
   * Get $prodid variable
   *
   * @desc Get $prodid variable
   * @return string $prodid
   * @see setProdID()
   * @uses iCal::$prodid
   */
   public function getProdID() {
      return (string) $this->prodid;
   } // end function

   /**
   * Get $method variable
   *
   * @return string $method
   * @see setMethod()
   * @uses iCal::$method
   * @since 1.001 - 2002-10-10
   */
   public final function getMethod() {
      $methods = (array) array('REQUEST','PUBLISH');
      return (string) ((array_key_exists($this->method, $methods)) ? $methods[$this->method] : $methods[1]);
   } // end function

   /**
   * Get $ical_timestamp variable
   *
   * @return string $ical_timestamp
   * @see setiCalTimestamp()
   * @uses iCal::$ical_timestamp
   */
   public function getiCalTimestamp() {
      return (string) $this->ical_timestamp;
   } // end function

   /**
   * Get class name
   *
   * @param int $int
   * @return string $classes
   */
   public static final function getClassName($int = 0) {
      $classes = (array) array('PRIVATE','PUBLIC','CONFIDENTIAL');
      return (string) ((array_key_exists($int, $classes)) ? $classes[$int] : $classes[0]);
   } // end function

   /**
   * Get status name
   *
   * @param int $int
   * @return string $statuscode
   * @since 1.011 - 2002-12-22
   */
   public static final function getStatusName($int = 0,$type) {
      if($type == 'EVENT') {
         $statuscode = (array) array('TENTATIVE','CONFIRMED','CANCELLED');
      } elseif($type == 'TODO') {
         $statuscode = (array) array('NEEDS-ACTION', 'COMPLETED', 'IN-PROCESS', 'CANCELED');
      } elseif($type == 'JOURNAL') {
         $statuscode = (array) array('DRAFT', 'FINAL', 'CANCELLED');
      }
      return (string) ((array_key_exists($int, $statuscode)) ? $statuscode[$int] : $statuscode[0]);
   } // end function

   /**
   * Get frequency name
   *
   * @return string $frequencies
   * @see setFrequency()
   * @since 1.010 - 2002-10-26
   */
   public static final function getFrequencyName($int = 0) {
      $frequencies = (array) array('ONCE','SECONDLY','MINUTELY','HOURLY','DAILY','WEEKLY','MONTHLY','YEARLY');
      return (string) ((array_key_exists($int, $frequencies)) ? $frequencies[$int] : $frequencies[0]);
   } // end function

   /**#@+
   * @return void
   */
   /**
   * Adds a new Event Object to the Events Array
   *
   * @param array $organizer  The organizer - use array('Name', 'name@domain.com')
   * @param int $start  Start time for the event (timestamp; if you want an allday event the startdate has to start at 00:00:00)
   * @param int $end  Start time for the event (timestamp or write 'allday' for an allday event)
   * @param string $location  Location
   * @param int $transp  Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
   * @param array $categories  Array with Strings (example: array('Freetime','Party'))
   * @param string $description  Description
   * @param string $summary  Title for the event
   * @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
   * @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
   * @param int $prio  riority = 0-9
   * @param int $frequency  frequency: 0 = once, secoundly  yearly = 17
   * @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
   * @param int $interval  Interval for frequency (every 2,3,4 weeks)
   * @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
   * @param string $weekstart  Startday of the Week ( 0 = Sunday  6 = Saturday)
   * @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
   * @param int $alarm  Array with all the alarm information, '' for no alarm
   * @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
   * @param string $url  optional URL for that event
   * @param string $language  Language of the strings used in the event (iso code)
   * @param string $uid  Optional UID for the event
   * @see getEvent()
   * @uses iCalEvent
   */
   public function addEvent($organizer, $start, $end, $location, $transp, $categories,
                 $description, $summary, $class, $attendees, $prio,
                 $frequency, $rec_end, $interval, $days, $weekstart,
                 $exept_dates, $alarm, $status, $url, $language, $uid) {

      $event = (object) new iCalEvent($organizer, $start, $end, $location,
                              $transp, $categories, $description,
                              $summary, $class, $attendees, $prio,
                              $frequency, $rec_end, $interval, $days,
                              $weekstart, $exept_dates, $alarm,
                              $status, $url, $language, $uid);

      $this->icalevents[$this->eventid++] = $event;
      unset($event);
   } // end function

   /**
   * Adds a new ToDo Object to the ToDo Array
   *
   * @param string $summary  Title for the event
   * @param string $description  Description
   * @param string $location  Location
   * @param int $start  Start time for the event (timestamp)
   * @param int $duration  Duration of the todo in minutes
   * @param int $end  Start time for the event (timestamp)
   * @param int $percent  The percent completion of the ToDo
   * @param int $prio  riority = 09
   * @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
   * @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
   * @param array $organizer  The organizer  use array('Name', 'name@domain.com')
   * @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
   * @param array $categories  Array with Strings (example: array('Freetime','Party'))
   * @param int $last_mod  Last modification of the to-to (timestamp)
   * @param array $alarm  Array with all the alarm information, '' for no alarm
   * @param int $frequency  frequency: 0 = once, secoundly  yearly = 17
   * @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
   * @param int $interval  Interval for frequency (every 2,3,4 weeks)
   * @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
   * @param string $weekstart  Startday of the Week ( 0 = Sunday  6 = Saturday)
   * @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
   * @param string $url  optional URL for that event
   * @param string $lang  Language of the strings used in the event (iso code)
   * @param string $uid  Optional UID for the ToDo
   * @uses iCalToDo
   * @since 1.020 - 2002-12-24
   */
   public function addToDo($summary, $description, $location, $start, $duration, $end,
                $percent, $prio, $status, $class, $organizer, $attendees,
                $categories, $last_mod, $alarm, $frequency, $rec_end,
                $interval, $days, $weekstart, $exept_dates, $url, $lang, $uid, $due) {

      $todo = (object) new iCalToDo($summary, $description, $location, $start,
                             $duration, $end, $percent, $prio, $status,
                             $class, $organizer, $attendees, $categories,
                             $last_mod, $alarm, $frequency, $rec_end,
                             $interval, $days, $weekstart, $exept_dates,
                             $url, $lang, $uid, $due);

      $this->icaltodos[$this->todoid++] = $todo;
      unset($todo);
   } // end function

   /**
   * Adds a new FreeBusy Object to the ToDo Array
   *
   * @param int $start  Start time for fb (timestamp)
   * @param int $end  Start time for fb (timestamp)
   * @param int $duration  Duration of the fb in minutes
   * @param array $organizer  The organizer - use array('Name', 'name@domain.com')
   * @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
   * @param array $fb_times  key = timestamp (starting point), value = minutes, secound value = status (0 = FREE, 1 = BUSY, 2 = BUSY-UNAVAILABLE, 3 = BUSY-TENTATIVE)
   * @param string $url  optional URL for that FreeBusy
   * @param string $uid  Optional UID for the FreeBusy
   * @uses iCalFreeBusy
   */
   public function addFreeBusy($start, $end, $duration, $organizer, $attendees,
                   $fb_times, $url, $uid) {

      $fb = (object) new iCalFreeBusy($start, $end, $duration, $organizer,
                              $attendees, $fb_times, $url, $uid);

      $this->icalfbs[$this->fbid++] = $fb;
      unset($fb);
   } // end function

   /**
   * Adds a new Journal Object to the ToDo Array
   *
   * @param string $summary  Title for the event
   * @param string $description  Description
   * @param int $start  Start time for the event (timestamp)
   * @param int $created  Creation date for the event (timestamp)
   * @param int $last_mod  Last modification date for the event (timestamp)
   * @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
   * @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
   * @param array $organizer  The organizer  use array('Name', 'name@domain.com')
   * @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
   * @param array $categories  Array with Strings (example: array('Freetime','Party'))
   * @param int $frequency  frequency: 0 = once, secoundly  yearly = 17
   * @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
   * @param int $interval  Interval for frequency (every 2,3,4 weeks)
   * @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
   * @param string $weekstart  Startday of the Week ( 0 = Sunday  6 = Saturday)
   * @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
   * @param string $url  optional URL for that event
   * @param string $lang  Language of the strings used in the event (iso code)
   * @param string $uid  Optional UID for the Journal
   * @uses iCalJournal
   */
   public function addJournal($summary, $description, $start, $created, $last_mod,
                  $status, $class, $organizer, $attendees, $categories,
                  $frequency, $rec_end, $interval, $days, $weekstart,
                  $exept_dates, $url, $lang, $uid) {

      $journal = (object) new iCalJournal($summary, $description, $start,
                                 $created, $last_mod, $status, $class,
                                 $organizer, $attendees, $categories,
                                 $frequency, $rec_end, $interval,
                                 $days, $weekstart, $exept_dates,
                                 $url, $lang, $uid);

      $this->icaljournals[$this->journalid++] = $journal;
      unset($journal);
   } // end function
   /**#@-*/

   /**#@+
   * @return mixed
   * @param int $id
   */
   /**
   * Fetches an event from the array by the ID number
   *
   * @see addEvent()
   * @see iCalEvent::iCalEvent()
   */
   public function getEvent($id = 0) {
      if (count($this->icalevents) < 1) {
         return (string) 'No Dates found';
      } elseif (is_int($id) && array_key_exists($id, $this->icalevents)) {
         return (object) $this->icalevents[$id];
      } else {
         return (object) $this->icalevents[0];
      } // end if
   } // end function

   /**
   * Fetches an event from the array by the ID number
   *
   * @see addToDo()
   * @see iCalToDo::iCalToDo()
   * @since 1.020 - 2002-12-24
   */
   public function getToDo($id = 0) {
      if (count($this->icaltodos) < 1) {
         return (string) 'No ToDos found';
      } elseif (is_int($id) && array_key_exists($id, $this->icaltodos)) {
         return (object) $this->icaltodos[$id];
      } else {
         return (object) $this->icaltodos[0];
      } // end if
   } // end function

   /**
   * Fetches an freebusy from the array by the ID number
   *
   * @see addFreeBusy()
   * @see iCalFreeBusy::iCalFreeBusy()
   * @since 1.020 - 2002-12-24
   */
   public function getFreeBusy($id = 0) {
      if (count($this->icalfbs) < 1) {
         return (string) 'No FreeBusys found';
      } elseif (is_int($id) && array_key_exists($id, $this->icalfbs)) {
         return (object) $this->icalfbs[$id];
      } else {
         return (object) $this->icalfbs[0];
      } // end if
   } // end function

   /**
   * Fetches an journal from the array by the ID number
   *
   * @see addJournal()
   * @see iCalJournal::iCalJournal()
   * @since 1.020 - 2002-12-24
   */
   public function getJournal($id = 0) {
      if (count($this->icaljournals) < 1) {
         return (string) 'No Journals found';
      } elseif (is_int($id) && array_key_exists($id, $this->icaljournals)) {
         return (object) $this->icaljournals[$id];
      } else {
         return (object) $this->icaljournals[0];
      } // end if
   } // end function
   /**#@-*/

   /**
   * Returns the array with the iCal Event Objects
   *
   * @return (array) $icalevents
   * @see addEvent()
   * @see getEvent()
   */
   public function getEvents() {
      return (array) $this->icalevents;
   } // end function

   /**
   * Returns the array with the iCal ToDo Objects
   *
   * @return array $icaltodos
   * @see addToDo()
   * @see getToDo()
   * @since 1.020 - 2002-12-24
   */
   public function getToDos() {
      return (array) $this->icaltodos;
   } // end function

   /**
   * Returns the array with the iCal ToDo Objects
   *
   * @return array $icaltodos
   * @see addFreeBusy()
   * @see getFreeBusy()
   * @since 1.020 - 2002-12-24
   */
   public function getFreeBusys() {
      return (array) $this->icalfbs;
   } // end function

   /**
   * Returns the array with the iCal journal objects
   *
   * @return array $icaljournals
   * @see addJournal()
   * @see getJournal()
   * @since 1.020 - 2002-12-24
   */
   public function getJournals() {
      return (array) $this->icaljournals;
   } // end function

   /**#@+
   * @since 1.031 - 2002-02-08
   */
   /**
   * Returns the number of created events
   *
   * @return int
   * @uses iCal::$icalevents
   */
   public function countEvents() {
      return (int) count($this->icalevents);
   } // end function

   /**
   * Returns the number of created ToDos
   *
   * @return int
   * @uses iCal::$icaltodos
   */
   public function countToDos() {
      return (int) count($this->icaltodos);
   } // end function

   /**
   * Returns the number of created FreeBusys
   *
   * @return int
   * @uses iCal::$icalfbs
   */
   public function countFreeBusys() {
      return (int) count($this->icalfbs);
   } // end function

   /**
   * Returns the number of created Journals
   *
   * @return int
   * @uses iCal::$icaljournals
   */
   public function countJournals() {
      return (int) count($this->icaljournals);
   } // end function
   /**#@-*/

   /**#@+
   * @return boolean
   * @since 1.020 - 2002-12-24
   */
   /**
   * Deletes an event-object from the event-array
   *
   * @see addEvent()
   * @since 1.011 - 2002-12-21
   */
   public function deleteEvent($id = 0) {
      if (array_key_exists($id, $this->icalevents)) {
         $this->icalevents[$id] = '';
         $this->icalevents = (array) array_filter($this->icalevents, 'strlen');
         /*
         $event_keys = (array) array_keys($this->getEvents());

         foreach ($event_keys as $key) {
            if (strlen(trim($this->icalevents[$key])) > 0) {
               $temp_array[$key] = $this->icalevents[$key];
            } // end if
         } // end foreach

         $this->icalevents = (array) $temp_array;
         unset($temp_array);
         unset($event_keys);
         */
         return (boolean) TRUE;
      } else {
         return (boolean) FALSE;
      } // end if
   } // end function

   /**
   * Deletes an todo-object from the todo-array
   *
   * @see addToDo()
   */
   public function deleteToDo($id = 0) {
      if (array_key_exists($id, $this->icaltodos)) {
         $this->icaltodos[$id] = '';
         $this->icaltodos = (array) array_filter($this->icaltodos, 'strlen');
         /*
         $todo_keys = (array) array_keys($this->getToDos());

         foreach ($todo_keys as $key) {
            if (strlen(trim($this->icaltodos[$key])) > 0) {
               $temp_array[$key] = $this->icaltodos[$key];
            } // end if
         } // end foreach

         $this->icaltodos = (array) $temp_array;
         unset($temp_array);
         unset($todo_keys);
         */
         return (boolean) TRUE;
      } else {
         return (boolean) FALSE;
      } // end if
   } // end function

   /**
   * Deletes an todo-object from the todo-array
   *
   * @see addFreeBusy()
   */
   public function deleteFreeBusy($id = 0) {
      if (array_key_exists($id, $this->icalfbs)) {
         $this->icalfbs[$id] = '';
         $this->icalfbs = (array) array_filter($this->icalfbs, 'strlen');
         /*
         $fb_keys = (array) array_keys($this->getFreeBusys());

         foreach ($fb_keys as $key) {
            if (strlen(trim($this->icalfbs[$key])) > 0) {
               $temp_array[$key] = $this->icalfbs[$key];
            } // end if
         } // end foreach

         $this->icalfbs = (array) $temp_array;
         unset($temp_array);
         unset($fb_keys);
         */
         return (boolean) TRUE;
      } else {
         return (boolean) FALSE;
      } // end if
   } // end function

   /**
   * Deletes an journal object from the journal-array
   *
   * @see addJournal()
   */
   public function deleteJournal($id = 0) {
      if (array_key_exists($id, $this->icaljournals)) {
         $this->icaljournals[$id] = '';
         $this->icaljournals = (array) array_filter($this->icaljournals, 'strlen');
         /*
         $journal_keys = (array) array_keys($this->getJournals());

         foreach ($journal_keys as $key) {
            if (strlen(trim($this->icaljournals[$key])) > 0) {
               $temp_array[$key] = $this->icaljournals[$key];
            } // end if
         } // end foreach

         $this->icaljournals = (array) $temp_array;
         unset($temp_array);
         unset($journal_keys);
         */
         return (boolean) TRUE;
      } else {
         return (boolean) FALSE;
      } // end if
   } // end function
   /**#@-*/

   /**
   * Returns the number of iCal-Objects which would be returned when generating the iCal file
   *
   * @desc Returns the number of iCal-Objects which would be returned when generating the iCal file
   * @return int
   * @uses iCal::$countEvents
   * @uses countToDos()
   * @uses countFreeBusys()
   * @uses countJournals()
   * @since 1.031 - 2002-02-08
   */
   public function countiCalObjects() {
      return (int) ($this->countEvents() + $this->countToDos() + $this->countFreeBusys() + $this->countJournals());
   } // end function

   /**#@+
   * @return void
   */
   /**
   * Generates the string for the alarm
   *
   * @param object $alarm
   * @param string $format  ics | xcs
   * @see generateOutput()
   * @uses generateAttendeesOutput()
   * @uses iCalAlarm::getTrigger()
   * @uses iCalAlarm::getAction()
   * @uses iCalAlarm::getLanguage()
   * @uses iCalAlarm::getDescription()
   * @uses iCalAlarm::getSummary()
   * @uses iCalAlarm::getRepeat()
   * @uses iCalAlarm::getDuration()
   * @uses iCalAlarm::getAttendees()
   * @since 1.021 - 2002-12-24
   */
   private function generateAlarmOutput(iCalAlarm $alarm, $format = 'ics') {
      $output = (string) '';
      if ($format === 'ics' && $alarm->getTrigger() > 0) {
         $output .= (string) "BEGIN:VALARM\r\n";
         $output .= (string) 'ACTION:' . $alarm->getAction() . "\r\n";
         $output .= (string) 'TRIGGER:-PT' . $alarm->getTrigger() . "M\r\n";

         if ($alarm->getAction() == 'DISPLAY' || $alarm->getAction() == 'EMAIL') {
            $output .= (string) 'DESCRIPTION' . $alarm->getLanguage() . ':' . $alarm->getDescription() . "\r\n";
            $output .= (string) 'SUMMARY' . $alarm->getLanguage() . ':' . $alarm->getSummary() . "\r\n";
         } // end if

         if ($alarm->getDuration() != 0 && $alarm->getRepeat() != 0) {
            $output .= (string) 'DURATION:' . $alarm->getDuration() . "\r\n";
            $output .= (string) 'REPEAT:' . $alarm->getRepeat() . "\r\n";
         } // end if

         $output .= (string) $this->generateAttendeesOutput($alarm->getAttendees(), $format);
         $output .= (string) "END:VALARM\r\n";
      } elseif ($format === 'xcs' && $alarm->getTrigger() > 0) {
         $output .= (string) '<valarm>';
         $output .= (string) '<action>' . $alarm->getAction() . '</action>';
         $output .= (string) '<trigger>-PT' . $alarm->getTrigger() . '</trigger>';

         if ($alarm->getAction() == 'DISPLAY' || $alarm->getAction() == 'EMAIL') {
            $output .= (string) '<description>' . $alarm->getDescription() . '</description>';
            $output .= (string) '<summary>' . $alarm->getSummary() . '</summary>';
         } // end if

         if ($alarm->getDuration() != 0 && $alarm->getRepeat() != 0) {
            $output .= (string) '<duration>' . $alarm->getDuration() . '</duration>';
            $output .= (string) '<repeat>' . $alarm->getRepeat() . '</repeat>';
         } // end if

         $output .= (string) $this->generateAttendeesOutput($alarm->getAttendees(), $format);
         $output .= (string) '</valarm>';
      } // end if
      return (string) $output;
   } // end function

   /**
   * Generates the string for the attendees
   *
   * @param array $attendees
   * @param string $format  ics | xcs
   * @see generateOutput()
   * @uses getAttendeeRole()
   * @since 1.021 - 2002-12-24
   */
   private function generateAttendeesOutput($attendees, $format = 'ics') {
      $output = (string) '';
      if ($this->method != 0 || count($attendees) == 0) {
         return (string) $output;
      } // end if

      if ($format === 'ics' && count($attendees) > 0) {
         foreach ($attendees as $attendee) {
               $output .= (string) 'ATTENDEE;ROLE=' . $this->getAttendeeRole($attendee['role']) . ';CN=' . $attendee['name'] . ':MAILTO:' . $attendee['email'] . "\r\n";
         } // end foreach
      } elseif ($format === 'xcs' && count($attendees) > 0) {
         foreach ($attendees as $attendee ) {
               $output .= (string) '<attendee cn="' . $attendee['name'] . '" role="' . $this->getAttendeeRole($attendee['role']) . '">MAILTO:' . $attendee['email'] . '</attendee>';
         } // end foreach
      } // end if
      return (string) $output;
   } // end function

   /**
   * Generates the string to be written in the file later on
   *
   * you can choose between ics, xcs or rdf format.
   * only ics has been tested; the other two are not, or are not
   * finished coded yet
   *
   * @param string $format  ics | xcs | rdf
   * @see getOutput()
   * @see writeFile()
   * @uses iCalEvent
   * @uses iCalToDo
   * @uses iCalFreeBusy
   * @uses iCalJournal
   * @uses quotedPrintableEncode()
   * @uses getClassName()
   * @uses getStatusName()
   * @uses getFrequencyName()
   * @since 1.001 - 2002-10-10
   */
   private function generateOutput($format = 'ics') {
      function isEmpty($variable) {
            return (boolean) ((strlen(trim($variable)) > 0) ? FALSE : TRUE);
        }

        $this->output_format = (string) $format;
      if ($this->output_format == 'ics') {
         $this->output  = (string) "BEGIN:VCALENDAR\r\n";
         $this->output .= (string) 'PRODID:' . $this->prodid . "\r\n";
         $this->output .= (string) "VERSION:2.0\r\n";
         $this->output .= (string) 'METHOD:' . $this->getMethod() . "\r\n";
         /* EWW */
         $this->output .= (string) 'X-WR-TIMEZONE:' . date_default_timezone_get() . "\r\n";
         /* ~EWW */
         $eventkeys = (array) array_keys($this->icalevents);
         foreach ($eventkeys as $id) {
            $this->output .= (string) "BEGIN:VEVENT\r\n";
            $event =& $this->icalevents[$id];
            $this->output .= (string) $this->generateAttendeesOutput($event->getAttendees(), $format);
            if (!isEmpty($event->getOrganizerMail())) {
               $name = '';
               if (!isEmpty($event->getOrganizerName())) {
                  $name = (string) ';CN=' . $event->getOrganizerName();
               } // end if
               $this->output .= (string) 'ORGANIZER' . $name . ':MAILTO:' . $event->getOrganizerMail() . "\r\n";
            } // end if
            if(strlen($event->getStartDate()) < 9)
            {
               $this->output .= (string) 'DTSTART;VALUE=DATE:' .$event->getStartDate(). "\r\n";
            } else {
               $this->output .= (string) 'DTSTART:'. $event->getStartDate() . "\r\n";
            }
            if (strlen(trim($event->getEndDate())) > 0) {
               $this->output .= (string) 'DTEND:' . $event->getEndDate() . "\r\n";
            }

            if ($event->getFrequency() > 0) {
               $this->output .= (string) 'RRULE:FREQ=' . $this->getFrequencyName($event->getFrequency());
               if (is_string($event->getRecEnd())) {
                  $this->output .= (string) ';UNTIL=' . $event->getRecEnd();
               } elseif (is_int($event->getRecEnd())) {
                  $this->output .= (string) ';COUNT=' . $event->getRecEnd();
               } // end if
               $this->output .= (string) ';INTERVAL=' . $event->getInterval() . ';BYDAY=' . $event->getDays() . ';WKST=' . $event->getWeekStart() . "\r\n";
            } // end if
            if (!isEmpty($event->getExeptDates())) {
               $this->output .= (string) 'EXDATE:' . $event->getExeptDates() . "\r\n";
            } // end if
            if (!isEmpty($event->getLocation())) {
               $this->output .= (string) 'LOCATION' . $event->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($event->getLocation()) . "\r\n";
            } // end if
            $this->output .= (string) 'TRANSP:' . $event->getTransp() . "\r\n";
            $this->output .= (string) 'SEQUENCE:' . $event->getSequence() . "\r\n";
            $this->output .= (string) 'UID:' . $event->getUID() . "\r\n";
            $this->output .= (string) 'DTSTAMP:' . $this->ical_timestamp . "\r\n";
            if (!isEmpty($event->getCategories())) {
               $this->output .= (string) 'CATEGORIES' . $event->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($event->getCategories()) . "\r\n";
            } // end if
            if (!isEmpty($event->getDescription())) {
//					$this->output .= (string) 'DESCRIPTION:' . str_replace('\n', '=0D=0A=',str_replace('\r', '=0D=0A=', $this->quotedPrintableEncode($event->getDescription()))) . "\r\n";
               $this->output .= (string) 'DESCRIPTION:'.str_replace(',','\,',str_replace(chr(10),'\n',str_replace(chr(13),'\n',$event->getDescription()))) . "\r\n";
            } // end if
            $this->output .= (string) 'SUMMARY:' . $this->quotedPrintableEncode($event->getSummary()) . "\r\n";
            #$this->output .= (string) 'PRIORITY:' . $event->getPriority() . "\r\n";
            $this->output .= (string) 'CLASS:' . $this->getClassName($event->getClass()) . "\r\n";
            if (!isEmpty($event->getURL())) {
               $this->output .= (string) 'URL:' . $event->getURL() . "\r\n";
            } // end if
            if (!isEmpty($event->getStatus())) {
               $this->output .= (string) 'STATUS:' . $this->getStatusName($event->getStatus(), 'EVENT') . "\r\n";
            } // end if
            $alarm = $event->getAlarm();
            if(!empty($alarm)) {
               $this->output .= (string) $this->generateAlarmOutput($alarm, $format);
            }
            $this->output .= (string) "END:VEVENT\r\n";
         } // end foreach
         $todokeys = (array) array_keys($this->icaltodos);
         foreach ($todokeys as $id) {
            $this->output .= (string) "BEGIN:VTODO\r\n";
            $todo =& $this->icaltodos[$id];
            $this->output .= (string) $this->generateAttendeesOutput($todo->getAttendees(), $format);
            if (!isEmpty($todo->getOrganizerMail())) {
               $name = '';
               if (!isEmpty($todo->getOrganizerName())) {
                  $name = (string) ';CN=' . $todo->getOrganizerName();
               } // end if
               $this->output .= (string) 'ORGANIZER' . $name . ':MAILTO:' . $todo->getOrganizerMail() . "\r\n";
            } // end if
            $this->output .= (string) 'SEQUENCE:' . $todo->getSequence() . "\r\n";
            $this->output .= (string) 'UID:' . $todo->getUID() . "\r\n";
            $this->output .= (string) 'DTSTAMP:' . $this->ical_timestamp . "\r\n";
            if (!isEmpty($todo->getCategories())) {
               $this->output .= (string) 'CATEGORIES' . $todo->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($todo->getCategories()) . "\r\n";
            } // end if
            if (!isEmpty($todo->getDescription())) {
               $this->output .= (string) 'DESCRIPTION:'.$this->quotedPrintableEncode($todo->getDescription()). "\r\n";;
               #$this->output .= (string) 'DESCRIPTION' . $todo->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . str_replace('\n', '=0D=0A=',str_replace('\r', '=0D=0A=', $this->quotedPrintableEncode($todo->getDescription()))) . "\r\n";
            } // end if
            $this->output .= (string) 'SUMMARY' . $todo->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($todo->getSummary()) . "\r\n";
            #$this->output .= (string) 'PRIORITY:' . $todo->getPriority() . "\r\n";
            $this->output .= (string) 'CLASS:' . $this->getClassName($todo->getClass()) . "\r\n";
            if (!isEmpty($todo->getLocation())) {
               $this->output .= (string) 'LOCATION' . $todo->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($todo->getLocation()) . "\r\n";
            } // end if
            if (!isEmpty($todo->getURL())) {
               $this->output .= (string) 'URL:' . $todo->getURL() . "\r\n";
            } // end if
            if (!isEmpty($todo->getStatus())) {
               $this->output .= (string) 'STATUS:' . $this->getStatusName($todo->getStatus(),'TODO') . "\r\n";
            } // end if
            if (!isEmpty($todo->getPercent()) && $todo->getPercent() > 0) {
               $this->output .= (string) 'PERCENT-COMPLETE:' . $todo->getPercent() . "\r\n";
            } // end if
            if (!isEmpty($todo->getDuration()) && $todo->getDuration() > 0) {
               $this->output .= (string) 'DURATION:PT' . $todo->getDuration() . "M\r\n";
            } // end if
            if (!isEmpty($todo->getLastMod())) {
               $this->output .= (string) 'LAST-MODIFIED:' . $todo->getLastMod() . "\r\n";
            } // end if
                    if (!isEmpty($todo->getStartDate())) {
               $this->output .= (string) 'DTSTART;VALUE=DATE:' . $todo->getStartDate() . "\r\n";
            } // end if
                                 if (!isEmpty($todo->getCompleted())) {
               $this->output .= (string) 'COMPLETED:' . $todo->getCompleted() . "\r\n";
            } // end if
            if ($todo->getFrequency() != 'ONCE') {
               $this->output .= (string) 'RRULE:FREQ=' . $todo->getFrequency();
               if (is_string($todo->getRecEnd())) {
                  $this->output .= (string) ';UNTIL=' . $todo->getRecEnd();
               } elseif (is_int($todo->getRecEnd())) {
                  $this->output .= (string) ';COUNT=' . $todo->getRecEnd();
               } // end if
               $this->output .= (string) ';INTERVAL=' . $todo->getInterval() . ';BYDAY=' . $todo->getDays() . ';WKST=' . $todo->getWeekStart() . "\r\n";
            } // end if
            if (!isEmpty($todo->getExeptDates())) {
               $this->output .= (string) 'EXDATE:' . $todo->getExeptDates() . "\r\n";
            } // end if
                if(!isEmpty($todo->getDue()))
                {
               $this->output .= (string) 'DUE:' . $todo->getDue() . "\r\n";
                }

                $alarm = $todo->getAlarm();
            if(!empty($alarm)) {
               $this->output .= (string) $this->generateAlarmOutput($alarm, $format);
            }
                        $this->output .= (string) "END:VTODO\r\n";
         } // end foreach
         $journalkeys = (array) array_keys($this->icaljournals);
         foreach ($journalkeys as $id) {
            $this->output .= (string) "BEGIN:VJOURNAL\r\n";
            $journal =& $this->icaljournals[$id];
            $this->output .= (string) $this->generateAttendeesOutput($journal->getAttendees(), $format);
            if (!isEmpty($journal->getOrganizerMail())) {
               $name = '';
               if (!isEmpty($journal->getOrganizerName())) {
                  $name = (string) ';CN=' . $journal->getOrganizerName();
               } // end if
               $this->output .= (string) 'ORGANIZER' . $name . ':MAILTO:' . $journal->getOrganizerMail() . "\r\n";
            } // end if
            $this->output .= (string) 'SEQUENCE:' . $journal->getSequence() . "\r\n";
            $this->output .= (string) 'UID:' . $journal->getUID() . "\r\n";
            $this->output .= (string) 'DTSTAMP:' . $this->ical_timestamp . "\r\n";
            if (!isEmpty($journal->getCategories())) {
               $this->output .= (string) 'CATEGORIES' . $journal->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($journal->getCategories()) . "\r\n";
            } // end if
            if (!isEmpty($journal->getDescription())) {
               $this->output .= (string) 'DESCRIPTION' . $journal->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . str_replace('\n', '=0D=0A=',str_replace('\r', '=0D=0A=', $this->quotedPrintableEncode($journal->getDescription()))) . "\r\n";
            } // end if
            $this->output .= (string) 'SUMMARY' . $journal->getLanguage() . ';ENCODING=QUOTED-PRINTABLE:' . $this->quotedPrintableEncode($journal->getSummary()) . "\r\n";
            $this->output .= (string) 'CLASS:' . $this->getClassName($journal->getClass()) . "\r\n";
            if (!isEmpty($journal->getURL())) {
               $this->output .= (string) 'URL:' . $journal->getURL() . "\r\n";
            } // end if
            if (!isEmpty($journal->getStatus())) {
               $this->output .= (string) 'STATUS:' . $this->getStatusName($journal->getStatus(), 'JOURNAL') . "\r\n";
            } // end if
            if (!isEmpty($journal->getLastMod())) {
               $this->output .= (string) 'LAST-MODIFIED:' . $journal->getLastMod() . "\r\n";
            } // end if
            if (!isEmpty($journal->getStartDate())) {
               $this->output .= (string) 'DTSTART;VALUE=DATE:' . $journal->getStartDate() . "\r\n";
            } // end if
            if (!isEmpty($journal->getCreated())) {
               $this->output .= (string) 'CREATED:' . $journal->getCreated() . "\r\n";
            } // end if
            if ($journal->getFrequency() > 0) {
               $this->output .= (string) 'RRULE:FREQ=' . $this->getFrequencyName($journal->getFrequency());
               if (is_string($journal->getRecEnd())) {
                  $this->output .= (string) ';UNTIL=' . $journal->getRecEnd();
               } elseif (is_int($journal->getRecEnd())) {
                  $this->output .= (string) ';COUNT=' . $journal->getRecEnd();
               } // end if
               $this->output .= (string) ';INTERVAL=' . $journal->getInterval() . ';BYDAY=' . $journal->getDays() . ';WKST=' . $journal->getWeekStart() . "\r\n";
            } // end if
            if (!isEmpty($journal->getExeptDates())) {
               $this->output .= (string) 'EXDATE:' . $journal->getExeptDates() . "\r\n";
            } // end if
            $this->output .= (string) "END:VJOURNAL\r\n";
         } // end foreach
         $fbkeys = (array) array_keys($this->icalfbs);
         foreach ($fbkeys as $id) {
            $this->output .= (string) "BEGIN:VFREEBUSY\r\n";
            $fb =& $this->icalfbs[$id];
            $this->output .= (string) $this->generateAttendeesOutput($fb->getAttendees(), $format);
            if (!isEmpty($fb->getOrganizerMail())) {
               $name = '';
               if (!isEmpty($fb->getOrganizerName())) {
                  $name = (string) ';CN=' . $fb->getOrganizerName();
               } // end if
               $this->output .= (string) 'ORGANIZER' . $name . ':MAILTO:' . $fb->getOrganizerMail() . "\r\n";
            } // end if
            $this->output .= (string) 'UID:' . $fb->getUID() . "\r\n";
            $this->output .= (string) 'DTSTAMP:' . $this->ical_timestamp . "\r\n";
            if (!isEmpty($fb->getURL())) {
               $this->output .= (string) 'URL:' . $fb->getURL() . "\r\n";
            } // end if
            if (!isEmpty($fb->getDuration()) && $fb->getDuration() > 0) {
               $this->output .= (string) 'DURATION:PT' . $fb->getDuration() . "M\r\n";
            } // end if
            if (!isEmpty($fb->getStartDate())) {
               $this->output .= (string) 'DTSTART;VALUE=DATE:' . $fb->getStartDate() . "\r\n";
            } // end if
            if (!isEmpty($fb->getEndDate())) {
               $this->output .= (string) 'DTEND:' . $fb->getEndDate() . "\r\n";
            } // end if
            if (count($fb->getFBTimes()) > 0) {
               foreach ($fb->getFBTimes() as $timestamp => $data) {
                  $values = (array) explode(',',$data);
                  $this->output .= (string) 'FREEBUSY;FBTYPE=' . $values[1] . ':' . $timestamp . '/' . $values[0] . "\r\n";
               } // end foreach
               unset($values);
            } // end if
            $this->output .= (string) "END:VFREEBUSY\r\n";
         } // end foreach
         $this->output .= (string) "END:VCALENDAR\r\n";
      } // end if ics
      elseif ($this->output_format == 'xcs') {
         $this->output  = (string) '<?xml version="1.0" encoding="UTF-8"?>';
         //$this->output  = (string) '<!DOCTYPE iCalendar PUBLIC "-//IETF//DTD iCalendar//EN" "http://www.ietf.org/internet-drafts/draft-dawson-ical-xml-dtd-02.txt">';
         $this->output .= (string) '<iCalendar>';
         if (count($this->icalevents) > 0) {
            $this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
            $eventkeys = (array) array_keys($this->icalevents);
            foreach ($eventkeys as $id) {
               $this->output .= (string) '<vevent>';
               $event =& $this->icalevents[$id];
               $this->output .= (string) $this->generateAttendeesOutput($event->getAttendees(), $format);
               if (!isEmpty($event->getOrganizerMail())) {
                  $name = '';
                  if (!isEmpty($event->getOrganizerName())) {
                     $name = (string) ' cn="' . $event->getOrganizerName() . '"';
                  } // end if
                  $this->output .= (string) '<organizer' . $name . '>MAILTO:' . $event->getOrganizerMail() . '</organizer>';
               } // end if
               $this->output .= (string) '<dtstart>' . $event->getStartDate() . '</dtstart>';

               if (strlen(trim($event->getEndDate())) > 0) {
                  $this->output .= (string) '<dtend>' . $event->getEndDate() . '</dtend>';
               } // end if
               if ($event->getFrequency() > 0) {
                  $this->output .= (string) '<rrule>FREQ=' . $this->getFrequencyName($event->getFrequency());
                  if (is_string($event->getRecEnd())) {
                     $this->output .= (string) ";UNTIL=" . $event->getRecEnd();
                  } elseif (is_int($event->getRecEnd())) {
                     $this->output .= (string) ";COUNT=" . $event->getRecEnd();
                  } // end if
                  $this->output .= (string) ";INTERVAL=" . $event->getInterval() . ";BYDAY=" . $event->getDays() . ";WKST=" . $event->getWeekStart() . '</rrule>';
               } // end if
               if (!isEmpty($event->getExeptDates())) {
                  $this->output .= (string) '<exdate>' . $event->getExeptDates() . '</exdate>';
               } // end if
               $this->output .= (string) '<location>' . $event->getLocation() . '</location>';
               $this->output .= (string) '<transp>' . $event->getTransp() . '</transp>';
               $this->output .= (string) '<sequence>' . $event->getSequence() . '</sequence>';
               $this->output .= (string) '<uid>' . $event->getUID() . '</uid>';
               $this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
               if (!isEmpty($event->getCategories())) {
                  $this->output .= (string) '<categories>';
                  foreach ($event->getCategoriesArray() as $item) {
                     $this->output .= (string) '<item>' . $item . '</item>';
                  } // end foreach
                  $this->output .= (string) '</categories>';
               } // end if
               if (!isEmpty($event->getDescription())) {
                  $this->output .= (string) '<description>' . $event->getDescription() . '</description>';
               } // end if
               $this->output .= (string) '<summary>' . $event->getSummary() . '</summary>';
               #$this->output .= (string) '<priority>' . $event->getPriority() . '</priority>';
               $this->output .= (string) '<class>' . $this->getClassName($event->getClass()) . '</class>';
               if (!isEmpty($event->getURL())) {
                  $this->output .= (string) '<url>' . $event->getURL() . '</url>';
               } // end if
               if (!isEmpty($event->getStatus())) {
                  $this->output .= (string) '<status>' . $this->getStatusName($event->getStatus(),'EVENT') . '</status>';
               } // end if
               $this->output .= (string) $this->generateAlarmOutput($event->getAlarm(), $format);
               $this->output .= (string) '</vevent>';
            } // end foreach event
            $this->output .= (string) '</vCalendar>';
         } // end if count($this->icalevents) > 0
         if (count($this->icaltodos) > 0) {
            $this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
            $todokeys = (array) array_keys($this->icaltodos);
            foreach ($todokeys as $id) {
               $this->output .= (string) '<vtodo>';
               $todo =& $this->icaltodos[$id];
               $this->output .= (string) $this->generateAttendeesOutput($todo->getAttendees(), $format);
               if (!isEmpty($todo->getOrganizerMail())) {
                  $name = '';
                  if (!isEmpty($todo->getOrganizerName())) {
                     $name = (string) ' cn="' . $todo->getOrganizerName() . '"';
                  } // end if
                  $this->output .= (string) '<organizer' . $name . '>MAILTO:' . $todo->getOrganizerMail() . '</organizer>';
               } // end if
               if (!isEmpty($todo->getStartDate())) {
                  $this->output .= (string) '<dtstart>' . $todo->getStartDate() . '</dtstart>';
               } // end if
               if (!isEmpty($todo->getCompleted())) {
                  $this->output .= (string) '<completed>' . $todo->getCompleted() . '</completed>';
               } // end if
               if (!isEmpty($todo->getDuration()) && $todo->getDuration() > 0) {
                  $this->output .= (string) '<duration>PT' . $todo->getDuration() . 'M</duration>';
               } // end if
               if (!isEmpty($todo->getLocation())) {
                  $this->output .= (string) '<location>' . $todo->getLocation() . '</location>';
               } // end if
               $this->output .= (string) '<sequence>' . $todo->getSequence() . '</sequence>';
               $this->output .= (string) '<uid>' . $todo->getUID() . '</uid>';
               $this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
               if (!isEmpty($todo->getCategories())) {
                  $this->output .= (string) '<categories>';
                  foreach ($todo->getCategoriesArray() as $item) {
                     $this->output .= (string) '<item>' . $item . '</item>';
                  } // end foreach
                  $this->output .= (string) '</categories>';
               } // end if
               if (!isEmpty($todo->getDescription())) {
                  $this->output .= (string) '<description>' . $todo->getDescription() . '</description>';
               } // end if
               $this->output .= (string) '<summary>' . $todo->getSummary() . '</summary>';
               #$this->output .= (string) '<priority>' . $todo->getPriority() . '</priority>';
               $this->output .= (string) '<class>' . $this->getClassName($todo->getClass()) . '</class>';
               if (!isEmpty($todo->getURL())) {
                  $this->output .= (string) '<url>' . $todo->getURL() . '</url>';
               } // end if
               if (!isEmpty($todo->getStatus())) {
                  $this->output .= (string) '<status>' . $this->getStatusName($todo->getStatus(),'TODO') . '</status>';
               } // end if
               if (!isEmpty($todo->getPercent()) && $todo->getPercent() > 0) {
                  $this->output .= (string) '<percent>' . $todo->getPercent() . '</percent>';
               } // end if
               if (!isEmpty($todo->getLastMod())) {
                  $this->output .= (string) '<last-modified>' . $todo->getLastMod() . '</last-modified>';
               } // end if
               if ($todo->getFrequency() > 0) {
                  $this->output .= (string) '<rrule>FREQ=' . $this->getFrequencyName($todo->getFrequency());
                  if (is_string($todo->getRecEnd())) {
                     $this->output .= (string) ";UNTIL=" . $todo->getRecEnd();
                  } elseif (is_int($todo->getRecEnd())) {
                     $this->output .= (string) ";COUNT=" . $todo->getRecEnd();
                  } // end if
                  $this->output .= (string) ";INTERVAL=" . $todo->getInterval() . ";BYDAY=" . $todo->getDays() . ";WKST=" . $todo->getWeekStart() . '</rrule>';
               } // end if
               if (!isEmpty($todo->getExeptDates())) {
                  $this->output .= (string) '<exdate>' . $todo->getExeptDates() . '</exdate>';
               } // end if
               $this->output .= (string) $this->generateAlarmOutput($todo->getAlarm(), $format);
               $this->output .= (string) '</vtodo>';
            } // end foreach event
            $this->output .= (string) '</vCalendar>';
         } // end if count($this->icaljournals) > 0
         if (count($this->icaljournals) > 0) {
            $this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
            $journalkeys = (array) array_keys($this->icaljournals);
            foreach ($journalkeys as $id) {
               $this->output .= (string) '<vjournal>';
               $journal =& $this->icaljournals[$id];
               $this->output .= (string) $this->generateAttendeesOutput($journal->getAttendees(), $format);
               if (!isEmpty($journal->getOrganizerMail())) {
                  $name = '';
                  if (!isEmpty($journal->getOrganizerName())) {
                     $name = (string) ' cn="' . $journal->getOrganizerName() . '"';
                  } // end if
                  $this->output .= (string) '<organizer' . $name . '>MAILTO:' . $journal->getOrganizerMail() . '</organizer>';
               } // end if
               if (!isEmpty($journal->getStartDate())) {
                  $this->output .= (string) '<dtstart>' . $journal->getStartDate() . '</dtstart>';
               } // end if
               if (!isEmpty($journal->getCreated()) && $journal->getCreated() > 0) {
                  $this->output .= (string) '<created>' . $journal->getCreated() . '</created>';
               } // end if
               if (!isEmpty($journal->getLastMod()) && $journal->getLastMod() > 0) {
                  $this->output .= (string) '<last-modified>' . $journal->getLastMod() . '</last-modified>';
               } // end if
               $this->output .= (string) '<sequence>' . $journal->getSequence() . '</sequence>';
               $this->output .= (string) '<uid>' . $journal->getUID() . '</uid>';
               $this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
               if (!isEmpty($journal->getCategories())) {
                  $this->output .= (string) '<categories>';
                  foreach ($journal->getCategoriesArray() as $item) {
                     $this->output .= (string) '<item>' . $item . '</item>';
                  } // end foreach
                  $this->output .= (string) '</categories>';
               } // end if
               if (!isEmpty($journal->getDescription())) {
                  $this->output .= (string) '<description>' . $journal->getDescription() . '</description>';
               } // end if
               $this->output .= (string) '<summary>' . $journal->getSummary() . '</summary>';
               $this->output .= (string) '<class>' . $this->getClassName($journal->getClass()) . '</class>';
               if (!isEmpty($journal->getURL())) {
                  $this->output .= (string) '<url>' . $journal->getURL() . '</url>';
               } // end if
               if (!isEmpty($journal->getStatus())) {
                  $this->output .= (string) '<status>' . $this->getStatusName($journal->getStatus(),'JOURNAL') . '</status>';
               } // end if
               if ($journal->getFrequency() != 'ONCE') {
                  $this->output .= (string) '<rrule>FREQ=' . $journal->getFrequency();
                  if (is_string($journal->getRecEnd())) {
                     $this->output .= (string) ";UNTIL=" . $journal->getRecEnd();
                  } elseif (is_int($journal->getRecEnd())) {
                     $this->output .= (string) ";COUNT=" . $journal->getRecEnd();
                  } // end if
                  $this->output .= (string) ";INTERVAL=" . $journal->getInterval() . ";BYDAY=" . $journal->getDays() . ";WKST=" . $journal->getWeekStart() . '</rrule>';
               } // end if
               if (!isEmpty($journal->getExeptDates())) {
                  $this->output .= (string) '<exdate>' . $journal->getExeptDates() . '</exdate>';
               } // end if
               $this->output .= (string) '</vjournal>';
            } // end foreach event
            $this->output .= (string) '</vCalendar>';
         } // end if count($this->icaltodos) > 0
         if (count($this->icalfbs) > 0) {
            $this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
            $fbkeys = (array) array_keys($this->icalfbs);
            foreach ($fbkeys as $id) {
               $this->output .= (string) '<vfreebusy>';
               $fb =& $this->icalfbs[$id];
               $this->output .= (string) $this->generateAttendeesOutput($fb->getAttendees(), $format);
               if (!isEmpty($fb->getOrganizerMail())) {
                  $name = '';
                  if (!isEmpty($fb->getOrganizerName())) {
                     $name = (string) ' cn="' . $fb->getOrganizerName() . '"';
                  } // end if
                  $this->output .= (string) '<organizer' . $name . '>MAILTO:' . $fb->getOrganizerMail() . '</organizer>';
               } // end if
               if (!isEmpty($fb->getStartDate())) {
                  $this->output .= (string) '<dtstart>' . $fb->getStartDate() . '</dtstart>';
               } // end if
               if (!isEmpty($fb->getEndDate())) {
                  $this->output .= (string) '<dtend>' . $fb->getEndDate() . '</dtend>';
               } // end if
               if (!isEmpty($fb->getDuration()) && $fb->getDuration() > 0) {
                  $this->output .= (string) '<duration>PT' . $fb->getDuration() . 'M</duration>';
               } // end if
               $this->output .= (string) '<uid>' . $fb->getUID() . '</uid>';
               $this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
               if (!isEmpty($fb->getURL())) {
                  $this->output .= (string) '<url>' . $fb->getURL() . '</url>';
               } // end if
               if (count($fb->getFBTimes()) > 0) {
                  foreach ($fb->getFBTimes() as $timestamp => $data) {
                     $values = (array) explode(',',$data);
                     $this->output .= (string) '<freebusy fbtype="' . $values[1] . '">' . $timestamp . '/' . $values[0] . '</freebusy>';
                  } // end foreach
                  unset($values);
               } // end if
               $this->output .= (string) '</vfreebusy>';
            } // end foreach event
            $this->output .= (string) '</vCalendar>';
         } // end if count($this->icaltodos) > 0
         $this->output .= (string) '</iCalendar>';
      } // end if xcs
      elseif ($this->output_format == 'rdf') {
         $this->output  = (string) '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
         $this->output .= (string) '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/10/swap/pim/ical#" xmlns:i="http://www.w3.org/2000/10/swap/pim/ical#">';
         $this->output .= (string) '<Vcalendar rdf:about="">';
         $this->output .= (string) '<version>2.0</version>';
         $this->output .= (string) '<prodid>' . $this->prodid . '</prodid>';
         $this->output .= (string) '</Vcalendar>';
         $eventkeys = (array) array_keys($this->icalevents);
         foreach ($eventkeys as $id) {
            $event =& $this->icalevents[$id];
            $this->output .= (string) '<Vevent>';
            $this->output .= (string) '<uid>' . $event->getUID() . '</uid>';
            $this->output .= (string) '<summary>' . $event->getSummary() . '</summary>';
            if (!isEmpty($event->getDescription())) {
               $this->output .= (string) '<description>' . $event->getDescription() . '</description>';
            } // end if
            if (!isEmpty($event->getCategories())) {
               $this->output .= (string) '<categories>' . $event->getCategories() . '</categories>';
            } // end if
            $this->output .= (string) '<status/>';
            $this->output .= (string) '<class resource="http://www.w3.org/2000/10/swap/pim/ical#private"/>';
            $this->output .= (string) '<dtstart parseType="Resource">';
            $this->output .= (string) '<value>' . $event->getStartDate() . '</value>';
            $this->output .= (string) '</dtstart>';
            $this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
            $this->output .= (string) '<due/>';
            $this->output .= (string) '</Vevent>';
         } // end foreach event
         $this->output .= (string) '</rdf:RDF>';
      } // end if rdf
      if (isset($event)) {
         unset($event);
      }
   } // end function
   /**#@-*/

   /**
   * Loads the string into the variable if it hasn't been set before
   *
   * @param string $format  ics | xcs | rdf
   * @return string $output
   * @see generateOutput()
   * @see writeFile()
   */
   public function getOutput($format = 'ics') {
      if (!isset($this->output) || $this->output_format != $format) {
         $this->generateOutput($format);
      } // end if
      return (string) $this->output;
   } // end function

   /**
   * Sends the right header information and outputs the generated content to
   * the browser
   *
   * @param string $format  ics | xcs | rdf (only Events)
   * @return void
   * @uses getOutput()
   * @since 1.011 - 2002-12-22
   */
   public function outputFile($filename, $format = 'ics') {
      if ($format == 'ics') {
         header('Content-Type: text/Calendar; charset=utf-8');
         header('Content-Disposition: attachment; filename='.$filename.'.ics');
         // -------------
         // --->UTF8<----
         //echo utf8_encode($this->getOutput('ics'));
         echo $this->getOutput('ics');
         // --->UTF8<----
         // -------------
      } elseif ($format == 'xcs') {
         header('Content-Type: text/Calendar; charset=utf-8');
         header('Content-Disposition: attachment; filename='.$filename.'.xcs');
         echo utf8_encode($this->getOutput('xcs'));
      } elseif ($format == 'rdf') {
         header('Content-Type: text/xml:; charset=utf-8');
         header('Content-Disposition: attachment; filename='.$filename.'.rdf');
         echo utf8_encode($this->getOutput('rdf'));
      } // end if
   } // end function
} // end class iCal
?>