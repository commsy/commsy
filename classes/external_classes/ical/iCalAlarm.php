<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//+----------------------------------------------------------------------+
//| WAMP (XP-SP1/1.3.24/4.0.12/5.0.0b2-dev)                                    |
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
* Container for an alarm (used in event and todo)
*
* Tested with WAMP (XP-SP1/1.3.24/4.0.4/4.3.0)
* Last Change: 2003-03-29
*
* @access public
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
* @version 2.001
*/
class iCalAlarm extends iCalBase {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**#@+
	* @var int
	*/
	/**
	* Kind of alarm (0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE))
	*/
	private $action;

	/**
	* Minutes the alarm goes off before the event/todo
	*/
	private $trigger = 0;

	/**
	* Duration between the alarms in minutes
	*/
	private $duration;

	/**
	* How often should the alarm be repeated
	*/
	private $repeat;
	/**#@-*/

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
	* @param int $action  0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
	* @param int $trigger  Minutes the alarm goes off before the event/todo
	* @param string $summary  Title for the alarm
	* @param string $description  Description
	* @param (array) $attendees  key = attendee name, value = e-mail, second value = role of the attendee
	* [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param int $duration  Duration between the alarms in minutes
	* @param int $repeat  How often should the alarm be repeated
	* @param string $lang  Language of the strings used in the event (iso code)
	* @uses setAction()
	* @uses setTrigger()
	* @uses iCalBase::setSummary()
	* @uses iCalBase::setDescription()
	* @uses iCalBase::setAttendees()
	* @uses setDuration()
	* @uses setRepeat()
	* @uses iCalBase::setLanguage()
	*/
	function __construct($action, $trigger, $summary, $description, $attendees,
					   $duration, $repeat, $lang) {
		parent::__construct();
		$this->setVar('action', $action);
		$this->setVar('trigger', $trigger);
		parent::setSummary($summary);
		parent::setDescription($description);
		parent::setAttendees($attendees);
		$this->setVar('duration', $duration);
		$this->setVar('repeat', $repeat);
		parent::setLanguage($lang);
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

	/**
	* Set class variable
	*
	* @param string $var class variable
	* @param mixed $value value
	* @since 2.000 - 2003-07-07
	*/
	private function setVar($var, $value) {
		$this->$var = (int) $value;
	} // end function

	/**#@+
	* @since 1.021 - 2002-12-24
	*/
	/**
	* Get $action variable
	*
	* @return string $action
	* @see setAction()
	* @see iCalAlarm::$action
	*/
	public function getAction() {
		$action_status = (array) array('DISPLAY', 'EMAIL', 'AUDIO', 'PROCEDURE');
		return (string) ((array_key_exists($this->action, $action_status)) ? $action_status[$this->action] : $action_status[0]);
	} // end function

	/**
	* Get $trigger variable
	*
	* @return int $trigger
	*/
	public function getTrigger() {
		return (int) $this->trigger;
	} // end function

	/**
	* Get $duration variable
	*
	* @return int $duration
	*/
	public function getDuration() {
		return (int) $this->duration;
	} // end function

	/**
	* Get $repeat variable
	*
	* @return int $repeat
	*/
	public function getRepeat() {
		return (int) $this->duration;
	} // end function
} // end class iCalAlarm
?>
