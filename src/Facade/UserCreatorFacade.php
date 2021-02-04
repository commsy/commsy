<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.07.18
 * Time: 19:54
 */

namespace App\Facade;


use App\Form\Model\Csv\CsvUserDataset;
use App\Services\LegacyEnvironment;

class UserCreatorFacade
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param CsvUserDataset[] $csvUserDatasets
     */
    public function createFromCsvDataset(\cs_auth_source_item $authSourceItem, array $csvUserDatasets)
    {
        foreach ($csvUserDatasets as $csvUserDataset) {
            /** CsvUserDataset $csvUserDataset */
            $userIdentifier = $this->findFreeIdentifier($csvUserDataset->getIdentifier(), $authSourceItem);
            $userPassword = $csvUserDataset->getPassword() ?? $this->generatePassword();

            $newUser = $this->createAuthAndUser(
                $userIdentifier,
                $userPassword,
                $csvUserDataset->getFirstname(),
                $csvUserDataset->getLastname(),
                $csvUserDataset->getEmail(),
                $this->legacyEnvironment->getCurrentPortalID(),
                $authSourceItem->getItemID());

            if ($csvUserDataset->getRooms()) {
                $this->addUserToRooms($newUser, $csvUserDataset->getRooms());
            }
        }
    }

    /**
     * Searches for a not yet used username or identifier for the given authentication source.
     * If the provided identifier is already used, search continues by appending a numeric
     * suffix until a free account ist found.
     *
     * @param string $identifier
     * @param \cs_auth_source_item $authSourceItem
     * @return string The free user identifier
     */
    private function findFreeIdentifier(string $identifier, \cs_auth_source_item $authSourceItem): string
    {
        $authentication = $this->legacyEnvironment->getAuthenticationObject();
        $lookup = $identifier;
        $suffix = null;

        while (!$authentication->is_free($lookup, $authSourceItem->getItemID())) {
            if ($suffix === null) {
                $suffix = 0;
            }

            $suffix++;
            $lookup = $identifier . (string)$suffix;
        }

        return $lookup;
    }

    /**
     * @param int $length
     * @return bool|string The password or false on error
     * @throws \Exception
     */
    private function generatePassword(int $length = 12): string
    {
        return substr(sha1(random_bytes(10)), 0, 10);
    }

    /**
     * Creates entries in auth and user table as needed. Only the local authentication persisted in the commsy
     * database needs to create an authentication item. See the different auth implementations for detail.
     *
     * @param string $identifier
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param int $portalId
     * @param int $authSourceId
     * @return \cs_user_item
     */
    private function createAuthAndUser(
        string $identifier,
        string $password,
        string $firstname,
        string $lastname,
        string $email,
        int $portalId,
        int $authSourceId
    ): \cs_user_item {
        $authentication = $this->legacyEnvironment->getAuthenticationObject();

        $newAccount = $authentication->getNewItem();
        $newAccount->setUserID($identifier);
        $newAccount->setPassword($password);
        $newAccount->setFirstname($firstname);
        $newAccount->setLastname($lastname);
        $newAccount->setEmail($email);
        $newAccount->setPortalID($portalId);
        $newAccount->setAuthSourceID($authSourceId);

        $authentication->save($newAccount);

        $newUser = $authentication->getUserItem();
        $newUser->makeUser();
        $newUser->save();

        return $newUser;
    }

    private function addUserToRooms(\cs_user_item $user, string $rooms)
    {
        $roomIds = explode(' ', trim($rooms));

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $privateRoomUser = $user->getRelatedPrivateRoomUserItem();

        foreach ($roomIds as $roomId) {
            $room = $roomManager->getItem($roomId);

            if ($room) {
                $userAlreadyExists = $user->getRelatedUserItemInContext($roomId) ? true : false;
                if (!$userAlreadyExists) {
                    // determine the source user to clone from
                    $sourceUser = $privateRoomUser ? $privateRoomUser : $user;

                    $newUserItem = $sourceUser->cloneData();
                    $newUserItem->setContextID($roomId);

                    if ($room->checkNewMembersNever()) {
                        $newUserItem->setStatus(2);
                    } else {
                        $newUserItem->setStatus(1);
                    }

                    $newUserItem->save();

                    // task
                    if (!$newUserItem->isUser()) {
                        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

                        $taskManager = $this->legacyEnvironment->getTaskManager();
                        $requestTask = $taskManager->getNewItem();
                        $requestTask->setCreatorItem($currentUser);
                        $requestTask->setContextID($room->getItemID());
                        $requestTask->setTitle('TASK_USER_REQUEST');
                        $requestTask->setStatus('REQUEST');
                        $requestTask->setItem($newUserItem);
                        $requestTask->save();
                    }

                    // TODO: write_email_to_moderators($user_item, $room);
                }
            }
        }
    }




//    function add_user_to_rooms($user, $room_array, $password_generated = false, $temp_account_password = '') {
//
//        if($_POST['autoaccount_send_email'] == 'autoaccount_send_email_form'){
//            include_once('classes/cs_mail.php');
//            $mail = new cs_mail();
//            $mail->set_to($user->getEmail());
//
//            global $symfonyContainer;
//            $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
//            $mail->set_from_email($emailFrom);
//
//            $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
//            $mail->set_subject($_POST['autoaccount_email_subject']);
//            $mail->set_message($_POST['autoaccount_email_text']);
//            $mail->send();
//        }
//        return $rooms_added_to;
//    }
}

/*
 *
 *


function auto_create_accounts($date_array){
   global $environment;
   $translator = $environment->getTranslationObject();

   $account_auth_source = $_POST['autoaccounts_auth_source'];
   $account_array = array();
   $allow_add_account = false;
   $auth_source_manager = $environment->getAuthSourceManager();
   $auth_source_item = $auth_source_manager->getItem($account_auth_source);
   if($auth_source_item->allowAddAccount()){
      $allow_add_account = true;
   }

   foreach($date_array as $account){
      if($allow_add_account){



      }

      // don not allow add accounts
      else {
         $temp_account_account = $account[$_POST['autoaccounts_account']];
         $account_length = strlen($temp_account_account);
         if($account_length == 0){
            $temp_account_array = array();
            $temp_account_array['lastname'] = $account[$_POST['autoaccounts_lastname']];
            $temp_account_array['firstname'] = $account[$_POST['autoaccounts_firstname']];
            $temp_account_array['email'] = $account[$_POST['autoaccounts_email']];
            $temp_account_array['account'] = $account[$_POST['autoaccounts_account']];
            $temp_account_array['account_changed'] = false;
            $temp_account_array['password'] = '';
            $temp_account_array['password_generated'] = false;
            $temp_account_array['found_account_by_email'] = false;
            $temp_account_array['account_not_created'] = true;
            $temp_account_array['rooms'] = array();
            $temp_account_array['has_comment'] = true;
            $temp_account_array['comment'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_NO_USER_ID');
            $account_array[] = $temp_account_array;
         } else {
            $user_manager = $environment->getUserManager();
            $user_item = $user_manager->getItemByUserIDAuthSourceID($account[$_POST['autoaccounts_account']],$auth_source_item->getItemID());
            $account_generated = false;
            if ( !isset($user_item) ) {
               $auth_connection = $auth_source_item->getAuthConnection();
               $new_account_data = $auth_connection->get_data_for_new_account($account[$_POST['autoaccounts_account']], $account[$_POST['autoaccounts_password']]);
               if ( !empty($new_account_data)
                    and !empty($new_account_data['firstname'])
                    and !empty($new_account_data['lastname'])
                  ) {
                  $user_manager = $environment->getUserManager();
                  $user_item = $user_manager->getNewItem();
                  $user_item->setUserID($account[$_POST['autoaccounts_account']]);
                  if ( !empty($account[$_POST['autoaccounts_firstname']]) ) {
                     $user_item->setFirstname($account[$_POST['autoaccounts_firstname']]);
                  } else {
                     $user_item->setFirstname($new_account_data['firstname']);
                  }
                  if ( !empty($account[$_POST['autoaccounts_lastname']]) ) {
                     $user_item->setLastname($account[$_POST['autoaccounts_lastname']]);
                  } else {
                     $user_item->setLastname($new_account_data['lastname']);
                  }
                  if ( !empty($account[$_POST['autoaccounts_email']]) ) {
                     $user_item->setEmail($account[$_POST['autoaccounts_email']]);
                  } elseif ( !empty($new_account_data['email']) ) {
                     $user_item->setEmail($new_account_data['email']);
                  } else {
                      global $symfonyContainer;
                      $email = $symfonyContainer->getParameter('commsy.email.from');

                     $user_item->setEmail($email);
                     $user_item->setHasToChangeEmail();
                  }
                  $user_item->setAuthSource($account_auth_source);
                  $user_item->makeUser();
                  $user_item->save();
                  $account_generated = true;
               }
            }
            if ( !empty($user_item) ) {
               $temp_account_rooms = $account[$_POST['autoaccounts_rooms']];
               $temp_account_rooms = trim($temp_account_rooms);
               if(stristr($temp_account_rooms, ' ')){
                  $temp_account_rooms_array = explode(' ', $temp_account_rooms);
               } else if(stristr($temp_account_rooms, ';')){
                  $temp_account_rooms_array = explode(';', $temp_account_rooms);
               } else if(stristr($temp_account_rooms, ',')){
                  $temp_account_rooms_array = explode(',', $temp_account_rooms);
               }
               $room_length = strlen($temp_account_rooms);
               if($room_length != 0 and empty($temp_account_rooms_array)){
                  $temp_account_rooms_array = array($temp_account_rooms);
               }

               $temp_account_array = array();
               $temp_account_array['lastname'] = $user_item->getFirstname();
               $temp_account_array['firstname'] = $user_item->getLastname();
               $temp_account_array['email'] = $user_item->getEmail();
               $temp_account_array['account'] = $user_item->getUserID();
               $temp_account_array['account_changed'] = false;
               $temp_account_array['password'] = '';
               $temp_account_array['password_generated'] = false;
               $temp_account_array['found_account_by_email'] = false;
               $rooms_added_to = add_user_to_rooms($user_item, $temp_account_rooms_array);
               $temp_account_array['rooms_added'] = $rooms_added_to;
               $temp_account_array['rooms'] = $temp_account_rooms_array;
               $temp_account_array['account_not_created'] = !$account_generated;
               if (!$account_generated) {
                  $temp_account_array['has_comment'] = true;
                  $temp_account_array['comment'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_FOUND_ACCOUNT');
               }
               $account_array[] = $temp_account_array;
            } else {
               $temp_account_array = array();
               $temp_account_array['lastname'] = $account[$_POST['autoaccounts_lastname']];
               $temp_account_array['firstname'] = $account[$_POST['autoaccounts_lastname']];
               $temp_account_array['email'] = $account[$_POST['autoaccounts_lastname']];
               $temp_account_array['account'] = $account[$_POST['autoaccounts_account']];
               $temp_account_array['account_changed'] = false;
               $temp_account_array['password'] = '';
               $temp_account_array['password_generated'] = false;
               $temp_account_array['found_account_by_email'] = false;
               $temp_account_array['account_not_created'] = true;
               $temp_account_array['rooms'] = array();
               $temp_account_array['has_comment'] = true;
               $temp_account_array['comment'] = $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_DID_NOT_GET_DATA');
               $account_array[] = $temp_account_array;
            }
         }
      }
   }
   return $account_array;
}

function write_email_to_moderators($user_item, $room){
   global $environment;
   $translator = $environment->getTranslationObject();

   $room_manager = $environment->getRoomManager();
   $room_item = $room_manager->getItem($room);

   $user_manager = $environment->getUserManager();
   $user_manager->resetLimits();
   $user_manager->setModeratorLimit();
   $user_manager->setContextLimit($room);
   $user_manager->select();
   $user_list = $user_manager->get();
   $email_addresses = array();
   $moderator_item = $user_list->getFirst();
   $recipients = '';
   $language = $room_item->getLanguage();
   while ($moderator_item) {
      $want_mail = $moderator_item->getAccountWantMail();
      if (!empty($want_mail) and $want_mail == 'yes') {
         if ($language == 'user' and $moderator_item->getLanguage() == 'browser') {
            $email_addresses[$environment->getSelectedLanguage()][] = $moderator_item->getEmail();
         } elseif ($language == 'user' and $moderator_item->getLanguage() != 'browser') {
            $email_addresses[$moderator_item->getLanguage()][] = $moderator_item->getEmail();
         } else {
            $email_addresses[$room_item->getLanguage()][] = $moderator_item->getEmail();
         }
         $recipients .= $moderator_item->getFullname().LF;
      }
       $moderator_item = $user_list->getNext();
   }
   if ( !$room_item->checkNewMembersNever() and !$room_item->checkNewMembersWithCode()) {
      $check_message = 'YES'; // for mail body
   } else {
      $check_message = 'NO';
   }
   foreach ($email_addresses as $language => $email_array) {
      if (count($email_array) > 0) {
         $old_lang = $translator->getSelectedLanguage();
         $translator->setSelectedLanguage($language);
         $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
         $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         if ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('USER_JOIN_COMMUNITY_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
         } else {
            $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
         }
         $body .= LF.LF;
         if ($check_message == 'YES') {
            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
         } else {
            $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
         $body .= LF;
         if ($check_message == 'YES') {
            $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
            $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room.'&mod=account&fct=index'.'&selstatus=1';
         } else {
            $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room;
         }
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_to(implode(',',$email_array));

          global $symfonyContainer;
          $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
          $mail->set_from_email($emailFrom);

         $current_context = $environment->getCurrentContextItem();
         $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
         $mail->set_from_name($room_item->getTitle());
         $mail->set_reply_to_name($user_item->getFullname());
         $mail->set_reply_to_email($user_item->getEmail());
         $mail->set_subject($subject);
         $mail->set_message($body);
         $mail->send();
         $translator->setSelectedLanguage($old_lang);
      }
   }
}

function write_email_to_user($user_item, $room, $password_generated = false, $temp_account_password = ''){
   global $environment;
   $room_manager = $environment->getRoomManager();
   $room_item = $room_manager->getItem($room);

   // get contact moderator (TBD) now first moderator
   $user_list = $room_item->getModeratorList();
   $contact_moderator = $user_list->getFirst();

   // change context
   $translator = $environment->getTranslationObject();
   $translator->setEmailTextArray($room_item->getEmailTextArray());
   if ($room_item->isProjectRoom()) {
      $translator->setContext('project');
   } else {
      $translator->setContext('community');
   }
   $save_language = $translator->getSelectedLanguage();
   $translator->setSelectedLanguage($room_item->getLanguage());

   // Datenschutz
   if($environment->getCurrentPortalItem()->getHideAccountname()){
   	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
   } else {
   	$userid = $user->getUserID();
   }

   // email texts
   $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
   $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
   $body .= LF.LF;
   $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
   $body .= LF.LF;
   $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room_item->getTitle());
   $body .= LF.LF;
   if($password_generated){
      $body .= $translator->getMessage('CONFIGURATION_AUTOACCOUNTS_PASSWORD_GENERATED',$temp_account_password);
      $body .= LF.LF;
   }
   $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
   $body .= LF.LF;
   $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();

   // send mail to user
   include_once('classes/cs_mail.php');
   $mail = new cs_mail();
   $mail->set_to($user_item->getEmail());
   $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));

    global $symfonyContainer;
    $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
    $mail->set_from_email($emailFrom);

   $mail->set_reply_to_email($contact_moderator->getEmail());
   $mail->set_reply_to_name($contact_moderator->getFullname());
   $mail->set_subject($subject);
   $mail->set_message($body);
   $mail->send();
}


 *
 *
 */