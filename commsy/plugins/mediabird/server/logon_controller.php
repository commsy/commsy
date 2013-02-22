<?php
/*
 * 	Copyright (C) 2008-2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

/**
 * Handles client sign-in, sign-up and sign-out requests
 * @author fabian
 *
 */
class MediabirdLogonController {
	/**
	 * Database object
	 * @var MediabirdDbo
	 */
	var $db;

	/**
	 * Id of current user
	 * @var int
	 */
	var $userId;

	/**
	 * User model
	 * @var MediabirdUser
	 */
	var $User;

	/**
	 * Auth interface
	 * @var MediabirdAuth
	 */
	var $auth;

	
	/**
	 * @param MediabirdDbo $db Database object
	 * @param MediabirdAuthManager $this->auth Auth interface to identify the current user
	 */
	function __construct($db,$auth) {
		$this->db = $db;
		$this->auth = $auth;
		$this->userId = $auth->userId;

		$this->User = new MediabirdUser($this);
	}

	var $validActions = array('signup','signin','signout','retrievepassword','confirmemail');

	/**
	 * Dispatches a logon request from the client
	 * @param $action Command that is to be performed
	 * @param $args Arguments for the given command
	 * @return stdClass Object that is supposed to be sent back to the client
	 */
	function dispatch($action, $args) {
		if(in_array($action,$this->validActions) && method_exists($this,$action)) {
			unset($args['action']);
			foreach($args as $key=>$arg) {
				$args[$key] = MediabirdUtility::getArgNoSlashes($args[$key]);
			}

			return $this->$action((object)$args);
		}
	}

	function signup($args) {
		$results = array();

		$name = MediabirdUtility::getArgNoSlashes($args->name);
		$password = MediabirdUtility::getArgNoSlashes($args->password);
		$password=sha1(MediabirdConfig::$security_salt.$password);
		$email = MediabirdUtility::getArgNoSlashes($args->email);
		$captcha = MediabirdUtility::getArgNoSlashes($args->captcha);

		if (!MediabirdConfig::$disable_signup) {
			if (!MediabirdUtility::checkEmail($email)) {
				$results['r'] = MediabirdConstants::invalidEmail;
			}
			else if (!$captcha || $this->auth->getSecurityCode() != $captcha) {
				$this->auth->restartSession();
				$results['r'] = MediabirdConstants::wrongCaptcha;
			}
			else {
				$checkIfUniqueQuery = "SELECT email,name FROM ".MediabirdConfig::tableName('User')." WHERE email='".$this->db->escape($email)."' OR name='".$this->db->escape($name)."'";
				if ($result = $this->db->getRecordSet($checkIfUniqueQuery)) {
					if ($this->db->recordLength($result) > 0) {
						//there is already a user with same email or user name

						$results = $this->db->recordToArray($this->db->fetchNextRecord($result));
						if ($results['email'] == $email) {
							$results['r'] = MediabirdConstants::emailNotUnique;
						}
						else {
							$results['r'] = MediabirdConstants::nameNotUnique;
						}
					}
					else {
						if (MediabirdConfig::$disable_mail) {
							$hash = 1;
						}
						else {
							$hash = rand(2, pow(2, 24));
						}

						$user=(object)null;
						$user->name=$name;
						$user->password=$password;
						$user->email=$email;
						$user->active=$hash;
						$user->created = $user->modified = $this->db->datetime(time());

						if ($newId = $this->db->insertRecord(MediabirdConfig::tableName('User',true),$user)) {
							if (!MediabirdConfig::$disable_mail) {
								$oldReporting = error_reporting(0);

								$link = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?confirmemail=".urlencode($hash);

								$host = $_SERVER['SERVER_NAME'];
								$body = "Please confirm that you have registered the account '$name' at $host by opening the following location in your browser: $link . Please ignore this email if you have not issued the registration of this account. \nThank you.\n";

								$okay = method_exists($this->auth,'sendMail') && $this->auth->sendMail($newId, "Email confirmation for account $name", $body);
								error_reporting($oldReporting);
								if($okay) {
									$results['mailsent'] = true;
									$results['r'] = MediabirdConstants::processed;

								}
								else {
									$results['r'] = MediabirdConstants::serverError;
								}


							}
							else {
								$results['mailsent'] = false;
								$results['r'] = MediabirdConstants::processed;
							}
						}
						else {
							$results['r'] = MediabirdConstants::serverError;
						}
					}
				}
				else {
					$results['r'] = MediabirdConstants::serverError;
				}
			}
		}
		else {
			//signup disabled
			$results['r'] = MediabirdConstants::disabled;
		}

		return $results;
	}

	function confirmemail($args) {
		$results = array();

		$hash = intval($_GET['confirmemail']);

		if ($user = $this->db->getRecord(MediabirdConfig::tableName('User',true),"active=$hash")) {
			$user->active=1;
			if ($this->db->updateRecord(MediabirdConfig::tableName('User',true),$user)) {
				//success
				header("Location: confirmed.php?q=enabled");
				return;
			}
		}
		header("Location: confirmed.php");
		exit();
	}


	function retrievepassword($args) {
		$results = array();

		$email = MediabirdUtility::getArgNoSlashes($args->email);
		$captcha = MediabirdUtility::getArgNoSlashes($args->captcha);

		if (!MediabirdConfig::$disable_mail) {
			if (!MediabirdUtility::checkEmail($email)) {
				$results['r'] = MediabirdConstants::invalidEmail;
			}
			else if (!$captcha || $this->auth->getSecurityCode() != $captcha) {
				$this->auth->restartSession();
				$results['r'] = MediabirdConstants::wrongCaptcha;
			}
			else {
				$retrievePasswordQuery = "SELECT * FROM ".MediabirdConfig::tableName('User')." WHERE email='".$this->db->escape($email)."'";
				if (($result = $this->db->getRecordSet($retrievePasswordQuery)) && ($results = $this->db->recordToArray($this->db->fetchNextRecord($result)))) {
					$name = $results['name'];
					$id = intval($results['id']);
					$password = $results['password'];

					$body = "You have requested a password notification.\n\nYour account is '$name' and the new password is '$password', both without the quotation marks.";

					$oldReporting = error_reporting(0);
					$okay = method_exists($this->auth,'sendMail') && $this->auth->sendMail($id, "Password retrieval for Mediabird", $body);
					error_reporting($oldReporting);
					if($okay) {
						$results['r'] = MediabirdConstants::processed;
					}
					else {
						$results['r'] = MediabirdConstants::serverError;
					}
				}
				else {
					$results['r'] = MediabirdConstants::invalidEmail;
				}
			}
		}
		else {
			//mail disabled
			$results['r'] = MediabirdConstants::disabled;
		}

		return $results;
	}

	function signin ($args) {
		$results = array();

		//check user and password

		$name = MediabirdUtility::getArgNoSlashes($args->name);
		$password = MediabirdUtility::getArgNoSlashes($args->password);
		$password=sha1(MediabirdConfig::$security_salt.$password);

		if ($userRecord = $this->db->getRecord(MediabirdConfig::tableName('User',true)," name='".$this->db->escape($name)."' AND password='".$this->db->escape($password)."'")) {

			if ($userRecord->active == 1) {
				$user = $this->User->userFromRecord($userRecord);

				//save session time
				$_SESSION['mb_session_time'] = $user['lastLogin'];
				
				//update last login
				$time = time();
				$userRecord->last_login = $this->db->datetime($time);
				$this->db->updateRecord(MediabirdConfig::tableName('User',true),$userRecord);

				//save the session info for subsequent requests
				$this->auth->createSession($user['id']);

				$results['user'] = $user;
				$results['r'] = MediabirdConstants::processed;
			}
			else {
				$results['r'] = MediabirdConstants::disabled;
			}
		}
		else {
			$results['r'] = MediabirdConstants::wrongPass;
		}

		return $results;
	}


	function signout($args) {
		$results = array();

		//delete card locks associated with this user
		if ($this->auth->isAuthorized()) {
			$query="SELECT id,locked_by FROM ".MediabirdConfig::tableName('Content')." WHERE locked_by=$this->userId";
			if ($result = $this->db->getRecordSet($query)) {
				while($record = $this->db->fetchNextRecord($result)) {
					$record->locked_by = 0;
					$this->db->updateRecord(MediabirdConfig::tableName('Content',true),$record);
				}
			}

			if ( property_exists($args,'settings')) {
				$settings = MediabirdUtility::getArgNoSlashes($args->settings);

				if ($settingsJson = json_decode($settings)) {
					$settings = json_encode($settingsJson);

					$user = $this->db->getRecord(MediabirdConfig::tableName('User',true),"id=$this->userId");
					$user->settings = $settings;
					$this->db->updateRecord(MediabirdConfig::tableName('User',true),$user);
				}
			}

			$this->auth->restartSession();

			//notify back
			$results['r'] = MediabirdConstants::processed;
		}

		return $results;
	}
}
?>
