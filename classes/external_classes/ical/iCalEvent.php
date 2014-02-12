<?php
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
* We need the base class
*/
include_once 'iCalBase.php';

/**
* Container for a single event
*
* Tested with WAMP (XP-SP1/1.3.27/4.0.12/5.0.0b2-dev)
* Last Change: 2003-07-07
*
* @access public
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
* @version 2.001
*/
class iCalEvent extends iCalBase {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**
	* Timestamp of the start date
	*
	* @var int
	*/
	private $startdate_ts;

	/**
	* Timestamp of the end date
	*
	* @var int
	*/
	private $enddate_ts;

	/**
	* OPAQUE (1) or TRANSPARENT (1)
	*
	* @var int
	*/
	private $transp = 0;

	/**
	* start date in iCal format
	*
	* @var string
	*/
	private $startdate;

	/**
	* end date in iCal format
	*
	* @var string
	*/
	private $enddate;

	/**
	* Automaticaly created: md5 value of the start date + end date
	*
	* @var string
	*/
	private $uid;

	/**
	* '' = never, integer < 4 numbers = number of times, integer >= 4 = timestamp
	*
	* @var mixed
	*/
	private $rec_end;

	/**
	* If alarm is set, holds alarm object
	*
	* @var object
	*/
	private $alarm;

	/*-----------------------*/
	/* C O N S T R U C T O R */
	/*-----------------------*/

	/**#@+
	* @return void
	*/
	/**
	* Constructor
	*
	* Only job is to set all the variablesnames
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
	* @param int $prio  riority = 09
	* @param int $frequency  frequency: 0 = once, secoundly  yearly = 17
	* @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
	* @param int $interval  Interval for frequency (every 2,3,4 weeks)
	* @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	* @param string $weekstart  Startday of the Week ( 0 = Sunday - 6 = Saturday)
	* @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	* @param array $alarm  Array with all the alarm information, "''" for no alarm
	* @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
	* @param string $url  optional URL for that event
	* @param string $language  Language of the strings used in the event (iso code)
	* @param string $uid  Optional UID for the event
	* @uses iCalBase::setLanguage()
	* @uses iCalBase::setOrganizer()
	* @uses setStartDate()
	* @uses setEndDate()
	* @uses iCalBase::setLocation()
	* @uses setTransp()
	* @uses iCalBase::setSequence()
	* @uses iCalBase::setCategories()
	* @uses iCalBase::setDescription()
	* @uses iCalBase::setSummary()
	* @uses iCalBase::setPriority()
	* @uses iCalBase::setClass()
	* @uses setUID()
	* @uses iCalBase::setAttendees()
	* @uses iCalBase::setFrequency()
	* @uses setRecEnd()
	* @uses iCalBase::setInterval()
	* @uses iCalBase::setDays()
	* @uses iCalBase::setWeekStart()
	* @uses iCalBase::setExeptDates()
	* @uses iCalBase::setStatus()
	* @uses setAlarm()
	* @uses iCalBase::setURL()
	* @uses setUID()
	*/
	function __construct($organizer, $start, $end, $location, $transp, $categories,
					   $description, $summary, $class, $attendees, $prio, $frequency,
					   $rec_end, $interval, $days, $weekstart, $exept_dates,
					   $alarm, $status, $url, $language, $uid) {
		parent::__construct();
		parent::setLanguage($language);
		parent::setOrganizer($organizer);
		$this->setStartDate($start);
		$this->setEndDate($end);
		parent::setLocation($location);
		$this->setTransp($transp);
		parent::setSequence(0);
		parent::setCategories($categories);
		parent::setDescription($description);
		parent::setSummary($summary);
		parent::setPriority($prio);
		parent::setClass($class);
		parent::setAttendees($attendees);
		parent::setFrequency($frequency);
		$this->setRecEnd($rec_end);
		parent::setInterval($interval);
		parent::setDays($days);
		parent::setWeekStart($weekstart);
		parent::setExeptDates($exept_dates);
		parent::setStatus($status);
		$this->setAlarm($alarm);
		parent::setURL($url);
        $this->setUID($uid);
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

	/**
	* Sets the end for a recurring event (0 = never ending,
	* integer < 4 numbers = number of times, integer >= 4 enddate)
	*
	* @param int $freq
	* @see getRecEnd()
	* @uses iCalEvent::$rec_end
	* @since 1.010 - 2002-10-26
	*/
	private function setRecEnd($freq = '') {
		if (strlen(trim($freq)) < 1) {
			$this->rec_end = 0;
		} elseif (is_int($freq) && strlen(trim($freq)) < 4) {
			$this->rec_end = $freq;
		} else {
			$this->rec_end = (string) gmdate('Ymd\THi00\Z',$freq);
		} // end if
	} // end function

	/**
	* Set $startdate_ts variable
	*
	* @param int $timestamp
	* @see getStartDateTS()
	* @uses iCalEvent::$startdate_ts
	*/
	private function setStartDateTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->startdate_ts = (int) $timestamp;
		} else {
			$this->startdate_ts = (int) ((isset($this->enddate_ts) && is_numeric($this->enddate_ts) && $this->enddate_ts > 0) ? ($this->enddate_ts - 3600) : time());
		} // end if
	} // end function

	/**
	* Set $enddate_ts variable
	*
	* @param int $timestamp
	* @see getEndDateTS()
	* @uses iCalEvent::$enddate_ts
	*/
	private function setEndDateTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->enddate_ts = (int) $timestamp;
		} else {
			$this->enddate_ts = (int) ((isset($this->startdate_ts) && is_numeric($this->startdate_ts) && $this->startdate_ts > 0) ? ($this->startdate_ts + 3600) : (time() + 3600));
		} // end if
	} // end function

	/**
	* Set $startdate variable
	*
	* @param int $timestamp
	* @see getStartDate()
	* @uses setStartDateTS()
	* @uses iCalEvent::$startdate
	* @uses iCalEvent::$startdate_ts
	*/
	private function setStartDate($timestamp = 0) {
		$this->setStartDateTS($timestamp);
		if (date('H:i:s', $this->startdate_ts) == '00:00:00') {
			$this->startdate = (string) gmdate('Ymd',$this->startdate_ts);
		} else {
			$this->startdate = (string) gmdate('Ymd\THi00\Z',$this->startdate_ts);
		} // end if
	} // end function

	/**
	* Set $enddate variable
	*
	* @param (mixed) $timestamp or 'allday'
	* @see getEndDate()
	* @uses iCalEvent::$enddate
	* @uses iCalEvent::$enddate_ts
	* @uses setEndDateTS()
	*/
	private function setEndDate($timestamp = 0) {
		if (is_int($timestamp)) {
			$this->setEndDateTS($timestamp);
			$this->enddate = (string) gmdate('Ymd\THi00\Z',$this->enddate_ts);
		} else {
			$this->enddate = (string) '';
		} // end if

	} // end function

	/**
	* Set $transp variable
	*
	* @param int $int  0|1
	* @see getTransp()
	* @uses iCalEvent::$transp
	*/
	private function setTransp($int = 0) {
		$this->transp = (int) $int;
	} // end function

	/**
	* Set $uid variable
	*
    * @param int $uid
	* @see getUID()
	* @uses iCalEvent::$uid
	* @uses iCalEvent::$startdate
	* @uses iCalEvent::$enddate
	*/
	private function setUID($uid = 0) {
		if (strlen(trim($uid)) > 0) {
            $this->uid = (string) $uid;
        } else {
            $rawid = (string) $this->startdate . 'plus' .  $this->enddate;
            $this->uid = (string) md5($rawid);
        }
	} // end function

	/**
	* Set $alarm object
	*
	* @param array $alarm
	* @see getAttendees()
	* @uses iCalEvent::$alarm
	* @uses iCalAlarm
	* @since 1.001 - 2002-10-10
	*/
	private function setAlarm($alarm = '') {
		if (is_array($alarm) and !empty($alarm)
                     and isset($alarm[0])
                     and isset($alarm[1])
                     and isset($alarm[2])
                     and isset($alarm[3])
                     and isset($alarm[4])
                     and isset($alarm[5])
                     and isset($alarm[6])) {
		   $this->alarm = (object) new iCalAlarm($alarm[0], $alarm[1],
		   $alarm[2], $alarm[3], $alarm[4],
		   $alarm[5], $alarm[6], $this->getLanguage());
		} // end if
	} // end function
	/**#@-*/

	/**
	* Get $rec_end variable
	*
	* @return mixed $rec_end
	* @see setRecEnd()
	* @since 1.010 - 2002-10-26
	*/
	public function getRecEnd() {
		return $this->rec_end;
	} // end function

	/**
	* Get $startdate_ts variable
	*
	* @return int $startdate_ts
	* @see setStartDateTS()
	*/
	public function getStartDateTS() {
		return (int) $this->startdate_ts;
	} // end function

	/**
	* Get $enddate_ts variable
	*
	* @return int $enddate_ts
	* @see setEndDateTS()
	*/
	public function getEndDateTS() {
		return (int) $this->enddate_ts;
	} // end function

	/**
	* Get $startdate variable
	*
	* @return int $startdate
	* @see setStartDate()
	*/
	public function getStartDate() {
		return (string) $this->startdate;
	} // end function

	/**
	* Get $enddate variable
	*
	* @return string $enddate
	* @see setEndDate()
	*/
	public function getEndDate() {
		return (string) $this->enddate;
	} // end function

	/**
	* Get $transp variable
	*
	* @return int $transp
	* @see setTransp()
	*/
	public function getTransp() {
		$transps = (array) array('OPAQUE','TRANSPARENT');
		return (string) ((array_key_exists($this->transp, $transps)) ? $transps[$this->transp] : $transps[0]);
	} // end function

	/**
	* Get $uid variable
	*
	* @return string $uid
	* @see setUID()
	*/
	public function getUID() {
		return (string) $this->uid;
	} // end function

	/**
	* Get $alarm object
	*
	* @return string $alarm
	* @see setAlarm()
	* @since 1.001 - 2002-10-10
	*/
	public function getAlarm() {
		return ((is_object($this->alarm)) ? $this->alarm : FALSE);
	} // end function
} // end class iCalEvent
?>