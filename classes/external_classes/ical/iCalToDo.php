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
* Container for a single todo
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
class iCalToDo extends iCalBase {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**
	* end date in iCal format
	*
	* @var string
	*/
	private $completed;

	/**
	* Timestamp of the end date
	*
	* @var int
	*/
	private $completed_ts;

	/**
	* start date in iCal format
	*
	* @var string
	*/
	private $startdate;

	/**
	* Timestamp of the start date
	*
	* @var int
	*/
	private $startdate_ts;

	/**
	* The percent completion of a ToDo
	*
	* @var int
	*/
	private $percent;

	/**
	* Automaticaly created: md5 value of the start date + Summary
	*
	* @var string
	*/
	private $uid;

	/**
	* Duration of the ToDo in minutes
	*
	* @var int
	*/
	private $duration;

	/**
	* If alarm is set, holds alarm object
	*
	* @var object
	*/
	private $alarm;

	private $due;

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
	* @param array) $alarm  Array with all the alarm information, "''" for no alarm
	* @param int $frequency  frequency: 0 = once, secoundly  yearly = 17
	* @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
	* @param int $interval  Interval for frequency (every 2,3,4 weeks)
	* @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	* @param string $weekstart  Startday of the Week ( 0 = Sunday  6 = Saturday)
	* @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	* @param string $url  optional URL for that event
	* @param string $lang  Language of the strings used in the event (iso code)
	* @param string $uid  Optional UID for the ToDo
	* @uses iCalBase::setLanguage()
	* @uses iCalBase::setOrganizer()
	* @uses setStartDate()
	* @uses setDuration()
	* @uses setCompleted()
	* @uses iCalBase::setLastMod()
	* @uses setPercent()
	* @uses setLocation()
	* @uses iCalBase::setSequence()
	* @uses iCalBase::setCategories()
	* @uses iCalBase::setDescription()
	* @uses setSummary()
	* @uses iCalBase::setPriority()
	* @uses iCalBase::setClass()
	* @uses iCalBase::setAttendees()
	* @uses iCalBase::setStatus()
	* @uses iCalBase::setURL()
	* @uses iCalBase::setFrequency()
	* @uses setRecEnd()
	* @uses iCalBase::setInterval()
	* @uses iCalBase::setDays()
	* @uses iCalBase::setWeekStart()
	* @uses iCalBase::setExeptDates()
	* @uses setAlarm()
	* @uses setUID()
	* @since 1.020 - 2002-12-24
	*/
	function __construct($summary, $description, $location, $start, $duration, $end,
					  $percent, $prio, $status, $class, $organizer, $attendees,
					  $categories, $last_mod, $alarm, $frequency, $rec_end,
					  $interval, $days, $weekstart, $exept_dates, $url, $lang, $uid, $due) {

        parent::__construct();
		parent::setLanguage($lang);
		parent::setOrganizer($organizer);
		$this->setStartDate($start);
		$this->setDuration($duration);
		if($end != '') {
			$this->setCompleted($end);
		}
		parent::setLastMod($last_mod);
		$this->setPercent($percent);
		parent::setLocation($location);
		parent::setSequence(0);
		parent::setCategories($categories);
		parent::setDescription($description);
		parent::setSummary($summary);
		parent::setPriority($prio);
		parent::setClass($class);
		parent::setAttendees($attendees);
		parent::setStatus($status);
		parent::setURL($url);
		parent::setFrequency($frequency);
		$this->setRecEnd($rec_end);
		parent::setInterval($interval);
		parent::setDays($days);
		parent::setWeekStart($weekstart);
		parent::setExeptDates($exept_dates);
		$this->setAlarm($alarm);
        $this->setUID($uid);
        $this->setDue($due);
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

    /**
	* Set $uid variable
	*
    * @param int $uid
	* @see getUID()
	* @uses iCalToDo::$uid
	* @uses iCalToDo::$startdate
	* @uses iCalToDo::$summary
	*/
	private function setUID($uid = 0) {
		if (strlen(trim($uid)) > 0) {
            $this->uid = (string) $uid;
        } else {
            $rawid = (string) $this->startdate . 'plus' .  $this->summary;
            $this->uid = (string) md5($rawid);
        }
	} // end function

	/**
	* Set $startdate_ts variable
	*
	* @param int $timestamp
	* @see getStartDateTS()
	* @uses iCalToDo::$startdate_ts
	*/
	private function setStartDateTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->startdate_ts = (int) $timestamp;
		} // end if
	} // end function

	/**
	* Set $startdate variable
	*
	* @param int $timestamp
	* @see getStartDate()
	* @uses iCalToDo::$startdate
	* @uses iCalToDo::$startdate_ts
	* @uses setStartDateTS()
	*/
	private function setStartDate($timestamp = 0) {
		$this->setStartDateTS($timestamp);
		$this->startdate = (string) gmdate('Ymd\THi00\Z',$this->startdate_ts);
	} // end function

	/**
	* Set $due variable
	*
	* @param int $timestamp
	* @see getStartDate()
	* @uses iCalToDo::$due
	* @uses iCalToDo::$startdate_ts
	* @uses setStartDateTS()
	*/
	private function setDue($timestamp = '') {
		if(!empty($timestamp))
		{
			$this->due = (string) gmdate('Ymd\THi00\Z',(int)$timestamp);
		}
	} // end function
	/**
	* Set $completed_ts variable
	*
	* @param int $timestamp
	* @see getCompletedTS()
	* @uses iCalToDo::$completed_ts
	* @since 1.020 - 2002-12-24
	*/
	private function setCompletedTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->completed_ts = (int) $timestamp;
		} // end if
	} // end function

	/**
	* Set $completed variable
	*
	* @param int $completed
	* @see getCompleted()
	* @uses iCalToDo::$completed
	* @uses iCalToDo::$completed_ts
	* @uses setCompletedTS()
	* @since 1.020 - 2002-12-24
	*/
	private function setCompleted($timestamp = 0) {
		$this->setCompletedTS($timestamp);
		$this->completed = (string) gmdate('Ymd\THi00\Z',$this->completed_ts);
	} // end function

	/**
	* Set $duration variable
	*
	* @param int $int
	* @see getPercent()
	* @uses iCalToDo::$percent
	* @since 1.020 - 2002-12-24
	*/
	private function setPercent($int = 0) {
		$this->percent = (int) $int;
	} // end function

	/**
	* Set $duration variable
	*
	* @param int $int
	* @see getDuration()
	* @uses iCalToDo::$duration
	* @since 1.020 - 2002-12-24
	*/
	private function setDuration($int = 0) {
		$this->duration = (int) $int;
	} // end function

	/**
	* Sets the end for a recurring event
	*
	* 0 = never ending, integer < 4 numbers = number of times, integer >= 4 enddate
	*
	* @param int $freq
	* @see getRecEnd()
	* @uses iCalToDo::$rec_end
	* @since 1.010 - 2002-10-26
	*/
	private function setRecEnd($freq = '') {
		if (strlen(trim($freq)) < 1) {
			$this->rec_end = 0;
		}
		elseif (is_int($freq) && strlen(trim($freq)) < 4) {
			$this->rec_end = $freq;
		} else {
			$this->rec_end = (string) gmdate('Ymd\THi00\Z',$freq);
		} // end if
	} // end function

	/**
	* Set $alarm variable
	*
	* @param (array) $alarm
	* @see getAlarm()
	* @uses iCalToDo::$alarm
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
			$this->alarm = (object) new iCalAlarm($alarm[0], $alarm[1], $alarm[2],
												  $alarm[3], $alarm[4], $alarm[5],
												  $alarm[6], $this->lang);
		} // end if
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
	* Get $startdate_ts variable
	*
	* @return (int) $startdate_ts
	* @see setStartDateTS()
	*/
	public function getStartDateTS() {
		return (int) $this->startdate_ts;
	} // end function

	/**
	* Get $startdate variable
	*
	* @return (int) $startdate
	* @see setStartDate()
	*/
	public function getStartDate() {
		return (string) $this->startdate;
	} // end function

	/**
	* Get $completed_ts variable
	*
	* @return (int) $completed_ts
	* @see setCompletedTS()
	* @since 1.020 - 2002-12-24
	*/
	public function getCompletedTS() {
		return (int) $this->completed_ts;
	} // end function

	/**
	* Get $completed variable
	*
	* @return (int) $completed
	* @see setCompleted()
	* @since 1.020 - 2002-12-24
	*/
	public function getCompleted() {
		return (string) $this->completed;
	} // end function

	/**
	* Get $percent variable
	*
	* @return (int) $percent
	* @see setPercent()
	* @since 1.020 - 2002-12-24
	*/
	public function getPercent() {
		return (int) $this->percent;
	} // end function

	/**
	* Get $duration variable
	*
	* @return (int) $duration
	* @see setDuration()
	* @since 1.020 - 2002-12-24
	*/
	public function getDuration() {
		return (int) $this->duration;
	} // end function

	/**
	* Get $rec_end variable
	*
	* @return (mixed) $rec_end
	* @see setRecEnd()
	* @since 1.010 - 2002-10-26
	*/
	public function getRecEnd() {
		return $this->rec_end;
	} // end function

	public function getDue()
	{
		return $this->due;
	}

	/**
	* Get $attendees variable
	*
	* @return string $attendees
	* @see setAttendees()
	* @since 1.001 - 2002-10-10
	*/
	public function getAlarm() {
		return ((is_object($this->alarm)) ? $this->alarm : FALSE);
	} // end function
} // end class iCalToDo
?>