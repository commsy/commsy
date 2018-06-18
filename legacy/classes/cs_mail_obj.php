<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/**
 * You use this object, if you like to send a mail.
 * You can prepare your mail, with this object, and you can save this object to session
 */
class cs_mail_obj {

	/**
	 * This constant is the man of the mail_obj in the session.
	 */
	var $SESSION_NAME = 'mailman';

	/**
	 * Is the type of the object
	 */
	var $_type = 'object';

	/**
	 * Is the subject of the mail
	 */
	var $_subject = NULL;

	/**
	 * Is the content/body of the mail
	 */
	var $_content = NULL;

	/**
	 * Is a array of the sender
	 * WHY AN ARRAY? -->  $sender[TheNameOfTheSender] = TheEmailOfTheSender;
	 */
	var $_sender = NULL;

	/**
	 * is an array of the receivers with the names of the receivers as keys and
	 * the email-addresses as values
	 */
	var $_receivers = NULL;

	/**
	 * Is an array of the needed parts for the backlink (context,module,function,parameter)
	 */
	var $_backLink = NULL;

	/**
	 * Is an other cs_mail_obj. This is set if there is a other mail to send.
	 * The nextMail(with form) will be prozessed right after this mail.
	 * The backLink will be copied from this mail to the nextmail.
	 */
	var $_nextMail = NULL;

    /**
     * Is the headline for the mail_form
     */
    var $_mailFormHeadLine = NULL;

    /**
     * Is the hintline for the mail form
     */
    var $_mailFormHints = NULL;

    /**
     * Should the email send automaticliy without the mailform?
     */
    var $_sendMailAuto = false;

    /**
     * Constuctor
     */
	function __construct() {
	}

   /** is the type of the item = $type ?
    * this method returns a boolean expressing if type of the item is $type or not
    *
    * @param string type string to compare with type of the item (_type)
    *
    * @return boolean   true - type of this item is $type
    *                   false - type of this item is not $type
    *
    * @author CommSy Development Group
    */
   function isA ($type) {
      return $this->_type == $type;
   }

	/**
	 * Returns an array, witch contains the name of the sender as key
	 * and the e-mail-address as value
	 *
	 * @return The Array of the sender
	 * @author Commsy Developer Team
	 */
	function getSender() {
		return $this->_sender;
	}

	/**
	 * Sets the sender of the mail.
	 * This has to be an array, with the sendername as key
	 * and the senders e-mail-address as value;
	 *
	 * @param $senderArray Is the sender in array-form. array[sender-name] = email-addr;
	 * @author Commsy Developer Team
	 */
	function setSender($senderArray) {
		if ( !is_array($senderArray) ) {
           include_once('functions/error_functions.php');trigger_error('cs_mail_obj.setSender(): Sorry. The sender is not legal. It has to be an array',E_USER_ERROR);
		}
		$this->_sender = $senderArray;
	}

	/**
	 * Returns the array of the receivers. with the names of them as keys and
	 * the e-mail addresses as values.
	 * @return The receiverarray
	 */
	function getReceivers() {
		return $this->_receivers;
	}

	/**
	 * Add or more receivers to the mail.
	 * The parameter $receiverArray muss be an array in this form:
	 * $receiverArray[receiverName] = receiverEmaiAddress
	 * @param Is the array of receivers
	 */
	function addReceivers($receiverArray) {
		if ( $this->_receivers == NULL ) $this->_receivers = array();
		$this->_receivers = array_merge($this->_receivers,$receiverArray);
	}

	/**
	 * Returns the subject of the mail
	 *
	 * @return The Subject
	 * @author Commsy Developer Team
	 */
	function getSubject() {
		return $this->_subject;
	}

	/**
	 * Sets the subject of the mail
	 *
	 * @author Commsy Developer Team
	 */
	function setSubject($subject) {
		$this->_subject = (string)$subject;
	}

	/**
	 * Returns the content of the mail
	 *
	 * @author Commsy Developer Team
	 */
	function getContent() {
		return $this->_content;
	}

	/**
	 * Sets the subject of the mail
	 *
	 * @author Commsy Developer Team
	 */
	function setContent($content) {
		$this->_content = (string)$content;
	}

	/**
	 * Set the backLink.
	 * The backLink is the link where to go after the mail form
	 * If there is an other mail (NextMail) the backLink will be set to the
	 * nextMail. The backLink of this mail will be set to the next mail form
	 *
	 * @param $context is the current context to link to
	 * @param $module is the module to link to
	 * @param $function is the functionality to link to
	 * @param $param are the GET_parameter of the link
	 * @author Commsy Developer Team
	 */
	function setBackLink($context,$module,$function,$param) {
      if ( !empty($param) and !is_array($param) ) {
         include_once('functions/error_functions.php');trigger_error('params must be set in an array',E_USER_WARNING);
      }
		global $session;
		$this->_backLink = array();
		if ( $this->_nextMail == NULL  ) {
 		   $this->_backLink['context'] = $context;
  		   $this->_backLink['module'] = $module;
		   $this->_backLink['function'] = $function;
 		   $this->_backLink['param'] = $param;
 		   $session->unsetValue($this->SESSION_NAME);
		} else {
			$this->_backLink['context'] = $context;
			$this->_backLink['module'] = 'mail';
			$this->_backLink['function'] = 'process';
			$this->_backLink['param'] = '';
    		$this->_nextMail->setBackLink($context,$module,$function,$param);
		}
	}

	/**
	 * Do the redirect to the next page.
	 * Normaly this will go to the settet backLink
	 * If there is an other mail (NextMail) to send it will
    * replace this mail in the session by the next mail and recall the mail procedure
	 */
	function goBackLink() {
		global $session;
		if ( $this->_nextMail != NULL ) {
			$session->unsetValue($this->SESSION_NAME);
			$session->setValue($this->SESSION_NAME,$this->_nextMail);
		} else {
			$session->unsetValue($this->SESSION_NAME);
		}

		redirect($this->_backLink['context'],
 				   $this->_backLink['module'],
				   $this->_backLink['function'],
 				   $this->_backLink['param']);
	}

    /**
     * Sets the nextMail.
     * If you have to send two differend mail in succession.
     * So you can put the next mail in the mail before.
     *
     * @param $mail_obj is a complete mail_obj
     * NOTE: There is always only one backLink witch is always set to the last mail
     *       in the row.
     */
	function setNextMail($mail_obj) {
		if ( $this->_nextMail == null ) {
   		$this->_nextMail = $mail_obj;
			if ( $this->_backLink != NULL ) {
			   $mail_obj->setBackLink($this->_backLink['context'],
 	         				           $this->_backLink['module'],
	  				                    $this->_backLink['function'],
	 				                    $this->_backLink['param']);
			}
			$this->_backLink['module'] = 'mail';
   	   $this->_backLink['function'] = 'process';
	 	   $this->_backLink['param'] = '';
		} else {
			$this->_nextMail->setNextMail($mail_obj);
		}
	}

	/**
	 * Returns the nextMailObj
	 * @return cs_mail_obj The naext mail to send
	 */
	function getNextMail() {
		return $this->_nextMail;
	}

	/**
	 * Returns the headline for the mail form
	 * @return the headline for the mail form
	 */
	function getMailFormHeadLine() {
		return $this->_mailFormHeadLine;
	}

	/**
	 * Sets the headline for the mail form
	 *
	 * @param the headline for the mail form
	 */
	function setMailFormHeadLine($headLine) {
		$this->_mailFormHeadLine = $headLine;
	}

	/**
	 * Returns the hint-field-entry for the mail form
	 * @return the hint-field-entry for the mail from
	 */
	function getMailFormHints() {
		return $this->_mailFormHints;
	}

	/**
	 * Sets the hint-field-entry for the mail for
	 * If this is not set the will be diplayed no hints
	 * @param $hints Is the hint text
	 */
	function setMailFormHints($hints) {
		$this->_mailFormHints = $hints;
	}

	function setSendMailAuto($value = true) {
		$this->_sendMailAuto = $value;
	}

	function isSendMailAuto() {
		return $this->_sendMailAuto;
	}

	/**
	 * Save this object to current session
	 */
	function toSession() {
		global $session;
		$session->setValue($this->SESSION_NAME,$this);
	}

	/**
	 * Returns the mail_obj from the current session
	 * @return the mail_obj from the current session
	 */
	function fromSession() {
		global $session;
		return $session->getValue($this->SESSION_NAME);
	}

   /**
    * Checks if the mail_obj is valid
    * @return true if the mail_obj is valid - else false
    */
   function isValid() {
      if ( $this->getSubject() == NULL ) return false;
      if ( $this->getContent() == NULL ) return false;
      if ( $this->getSender() == NULL ) return false;
      if ( $this->getReceivers() == NULL ) return false;
      if ( $this->_backLink == NULL ) return false;
      return true;
   }
}
?>