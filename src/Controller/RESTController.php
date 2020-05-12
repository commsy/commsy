<?php

namespace App\Controller;

use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Swagger\Annotations as SWG;

class RESTController extends AbstractFOSRestController
{
    /**
     * List portals
     *
     * Top level portals.
     *
     * @Rest\Get("/api/v2/portal/list")
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of portals",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Portal::class, groups={"api"}))
     *     )
     * )
     * @SWG\Tag(name="portals")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function portalList(EntityManagerInterface $entityManager)
    {
        return $entityManager->getRepository(Portal::class)
            ->findActivePortals();
    }

    /**
     * List auth sources
     *
     * Auth sources for a given portal.
     *
     * @Rest\Get("/api/v2/portal/{id}/auth")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of auth sources",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=AuthSource::class, groups={"api"}))
     *     )
     * )
     * @SWG\Tag(name="portals")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function authList(EntityManagerInterface $entityManager, int $id)
    {
        return $entityManager->getRepository(AuthSource::class)
            ->findByPortal($id);
    }

    /**
     * List rooms
     *
     * Rooms in a portal, either of type project or community.
     *
     * @Rest\Get("/api/v2/portal/{id}/rooms")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of rooms",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Room::class, groups={"api_read"}))
     *     )
     * )
     * @SWG\Tag(name="rooms")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api_read"}
     * )
     */
    public function roomList(EntityManagerInterface $entityManager, int $id)
    {
        return $entityManager->getRepository(Room::class)
            ->getMainRoomQueryBuilder($id)
            ->getQuery()
            ->getResult();
    }

    /**
     * List users
     *
     * Users in a portal.
     *
     * @Rest\Get("/api/v2/portal/{id}/users")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of users",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"api_read"}))
     *     )
     * )
     * @SWG\Tag(name="users")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api_read"}
     * )
     */
    public function userList(EntityManagerInterface $entityManager, int $id)
    {
        return $entityManager->getRepository(User::class)
            ->findActiveUsers($id);
    }

    /**
     * Create a room
     *
     * Creates a new project or community room
     *
     * @Rest\Post("/api/v2/portal/{id}/rooms")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(ref=@Model(type=Room::class, groups={"api_write"}))
     * )
     * @SWG\Response(
     *     response="201",
     *     description="The created room",
     *     @SWG\Schema(ref=@Model(type=Room::class, groups={"api_read"}))
     * )
     * @SWG\Tag(name="rooms")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=201,
     *     serializerGroups={"api_write"}
     * )
     */
    public function roomCreate()
    {





//            $this->_environment->setCurrentContextID($context_id);
//            $contextItem = $this->_environment->getCurrentContextItem();
//            $textConverter = $this->_environment->getTextConverter();
//
//            // get user item
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $userId = $session->getValue('user_id');
//            $authSourceId = $session->getValue('auth_source');
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//            $this->_environment->setCurrentUserItem($userItem);
//
//            // check form?
//            $isValid = true;
//
//            if ($isValid) {
//                $manager = $this->_environment->getManager($type);
//                if ($type == "project") {
//                    $projectOpeningStatus = $contextItem->getProjectRoomCreationStatus();
//                    if (!$userItem->isUser() || $projectOpeningStatus != "portal") {
//                        return new SoapFault('ERROR','Insufficent rights!');
//                    }
//                } else if($type == "community") {
//                    $communityOpeningStatus = $contextItem->getCommunityRoomCreationStatus();
//                    if (!($userItem->isUser() && $communityOpeningStatus == "all") && !$userItem->isModerator()) {
//                        return new SoapFault('ERROR','Insufficent rights!');
//                    }
//                } else {
//                    return new SoapFault('ERROR','Wrong type!');
//                }
//
//                $item = $manager->getNewItem();
//                $item->setCreatorItem($userItem);
//                $item->setCreationDate(getCurrentDateTimeInMySQL());
//
//                if ($this->_environment->inCommunityRoom()) {
//                    $item->setContextID($this->_environment->getCurrentPortalID());
//                } else {
//                    $item->setContextID($this->_environment->getCurrentContextID());
//                }
//
//                $item->open();
//
//                if ($this->_environment->inPortal() || $this->_environment->inCommunityRoom()) {
//                    $item->setRoomContext($contextItem->getRoomContext());
//                }
//
//                // modifactor and date
//                $item->setModificatorItem($userItem);
//                $item->setModificationDate(getCurrentDateTimeInMySQL());
//
//                // title
//                $item->setTitle($textConverter->_htmlentities_cleanbadcode($title));
//
//                // intervals
//                if (trim($intervals)) {
//                    $timeIntervals = explode(',', $intervals);
//
//                    if (in_array('cont', $timeIntervals)) {
//                        $item->setContinuous();
//                    } else {
//                        $item->setTimeListByID2($timeIntervals);
//                        $item->setNotContinuous();
//                    }
//                } else if ($item->isProjectRoom()) {
//                    $item->setTimeListByID2(array());
//                    $item->setNotContinuous();
//                }
//
//                // language
//                $item->setLanguage($language);
//
//                // description
//                if (trim($description)) {
//                    $item->setDescription(trim($description));
//                }
//
//                // community rooms
//                if (trim($assignments)) {
//                    $communityRooms = explode(',', $assignments);
//
//                    if ($item->isProjectRoom()) {
//                        $item->setCommunityListByID($communityRooms);
//                    }
//                }
//
//                if ($isNew) {
//                    if ($contextItem->withHtmlTextArea()) {
//                        $item->setHtmlTextAreaStatus($contextItem->getHtmlTextAreaStatus());
//                    }
//                }
//
//                $item->save();
//
//                if (trim($template)) {
//                    $template = trim($template);
//
//                    if ($isNew && $template > 99 && $template != 'disabled') {
//                        // copy all entries from the template into the new room
//                        $roomManager = $this->_environment->getRoomManager();
//                        $templateRoom = $roomManager->getItem($template);
//                        $creator = $userManager->getItem($item->getCreatorID());
//                        if ($creator->getContextID() == $room->getItemID()) {
//                            $creatorId = $creator->getItemID();
//                        } else {
//                            $userManager->resetLimits();
//                            $userManager->setContextLimit($room->getItemID());
//                            $userManager->setUserIDLimit($creator->getUserID());
//                            $userManager->setAuthSourceLimit($creator->getAuthSource());
//                            $userManager->setModeratorLimit();
//                            $userManager->select();
//                            $userList = $userManager->get();
//
//                            if ($userList->isNotEmpty() && $userList->getCount() == 1) {
//                                $creator = $userList->getFirst();
//                                $creatorId = $creator->getItemID();
//                            } else {
//                                return new SoapFault('ERROR','Unable to create room from template!');
//                            }
//                        }
//
//                        $creator->setAccountWantMail('yes');
//                        $creator->setOpenRoomWantMail('yes');
//                        $creator->setPublishMaterialWantMail('yes');
//                        $creator->save();
//
//                        // copy room settings
//                        $environment =& $this->_environment;
//                        $oldRoom =& $templateRoom;
//                        $newRoom =& $item;
//                        include_once('include/inc_room_copy_config.php');
//
//                        $item->save();
//
//                        // copy data
//                        include_once('include/inc_room_copy_data.php');
//                    }
//                }
//            } else {
//                return new SoapFault('ERROR','Invalid data!');
//            }
    }
}

//    public function deleteRoom($session_id, $portal_id, $room_id)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($portal_id);
//            $contextItem = $this->_environment->getCurrentContextItem();
//
//            $roomManager = $this->_environment->getRoomManager();
//            $room = $roomManager->getItem($room_id);
//
//            // get user item
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $userId = $session->getValue('user_id');
//            $authSourceId = $session->getValue('auth_source');
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//
//            // check rights
//            if (!$room->mayEdit($userItem)) {
//                return new SoapFault('ERROR','Insufficent rights!');
//            } else {
//                $room->delete();
//            }
//
//            $xml .= "<room>\n";
//            $xml .= "</room>";
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }





//    public function createMembershipBySession ( $session_id, $context_id, $agb = false ) {
//        $session_id = $this->_encode_input($session_id);
//        $context_id = $this->_encode_input($context_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//
//            // root or guest -> NO
//            if ( mb_strtoupper($user_id, 'UTF-8') != 'GUEST'
//                and mb_strtoupper($user_id, 'UTF-8') != 'ROOT'
//            ) {
//                $portal_id = $session->getValue('commsy_id');
//                $this->_environment->setCurrentPortalID($portal_id);
//                $auth_source = $session->getValue('auth_source');
//
//                // portal: is user valid
//                $user_manager = $this->_environment->getUserManager();
//                $user_manager->setContextLimit($portal_id);
//                $user_manager->setUserIDLimit($user_id);
//                $user_manager->setAuthSourceLimit($auth_source);
//                $user_manager->select();
//                $user_list = $user_manager->get();
//                if ($user_list->getCount() == 1) {
//                    $current_user = $user_list->getFirst();
//                    $this->_environment->setCurrentUserItem($current_user);
//
//                    // room: user allready exist?
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !empty($room_item) ) {
//                        $room_user_item = $room_item->getUserByUserID($current_user->getUserID(),$current_user->getAuthSource());
//                        if ( !isset($room_user_item) ) {
//
//                            // now create membership
//                            $private_room_user_item = $current_user->getRelatedPrivateRoomUserItem();
//                            if ( isset($private_room_user_item) ) {
//                                $user_item = $private_room_user_item->cloneData();
//                                $picture = $private_room_user_item->getPicture();
//                            } else {
//                                $user_item = $current_user->cloneData();
//                                $picture = $current_user->getPicture();
//                            }
//                            $user_item->setContextID($context_id);
//                            if (!empty($picture)) {
//                                $value_array = explode('_',$picture);
//                                $value_array[0] = 'cid'.$user_item->getContextID();
//
//                                $new_picture_name = implode('_',$value_array);
//                                $disc_manager = $this->_environment->getDiscManager();
//                                $disc_manager->copyImageFromRoomToRoom($picture,$user_item->getContextID());
//                                $user_item->setPicture($new_picture_name);
//                            }
//
//                            //check room_settings
//                            if ( !$room_item->checkNewMembersNever()
//                                and !$room_item->checkNewMembersWithCode()
//                            ) {
//                                $user_item->request();
//                                $check_message = 'YES'; // for mail body
//                            } else {
//                                $user_item->makeUser(); // for mail body
//                                $check_message = 'NO';
//                                // save link to the group ALL
//                                $group_manager = $this->_environment->getLabelManager();
//                                $group_manager->setExactNameLimit('ALL');
//                                $group_manager->setContextLimit($room_item->getItemID());
//                                $group_manager->select();
//                                $group_list = $group_manager->get();
//                                if ($group_list->getCount() == 1) {
//                                    $group = $group_list->getFirst();
//                                    $group->setTitle('ALL');
//                                    $user_item->setGroupByID($group->getItemID());
//                                }
//                            }
//
//                            if ( $agb ) {
//                                $user_item->setAGBAcceptance();
//                            }
//#                     if ($room_item->checkNewMembersNever()){
//#                        $user_item->setStatus(2);
//#                     }
//                            $user_item->save();
//                            $user_item->setCreatorID2ItemID();
//
//                            // save task
//                            if ( !$room_item->checkNewMembersNever()
//                                and !$room_item->checkNewMembersWithCode()
//                            ) {
//                                $task_manager = $this->_environment->getTaskManager();
//                                $task_item = $task_manager->getNewItem();
//                                $task_item->setCreatorItem($current_user);
//                                $task_item->setContextID($room_item->getItemID());
//                                $task_item->setTitle('TASK_USER_REQUEST');
//                                $task_item->setStatus('REQUEST');
//                                $task_item->setItem($user_item);
//                                $task_item->save();
//                            }
//
//                            // send email to moderators if necessary
//                            $user_manager = $this->_environment->getUserManager();
//                            $user_manager->resetLimits();
//                            $user_manager->setModeratorLimit();
//                            $user_manager->setContextLimit($room_item->getItemID());
//                            $user_manager->select();
//                            $user_list = $user_manager->get();
//                            $email_addresses = array();
//                            $moderator_item = $user_list->getFirst();
//                            $recipients = '';
//                            while ($moderator_item) {
//                                $want_mail = $moderator_item->getAccountWantMail();
//                                if (!empty($want_mail) and $want_mail == 'yes') {
//                                    $email_addresses[] = $moderator_item->getEmail();
//                                    $recipients .= $moderator_item->getFullname()."\n";
//                                }
//                                $moderator_item = $user_list->getNext();
//                            }
//
//                            // language
//                            $language = $room_item->getLanguage();
//                            if ($language == 'user') {
//                                $language = $user_item->getLanguage();
//                                if ($language == 'browser') {
//                                    $language = $this->_environment->getSelectedLanguage();
//                                }
//                            }
//
//                            if ( count($email_addresses) > 0 ) {
//                                $translator = $this->_environment->getTranslationObject();
//                                $save_language = $translator->getSelectedLanguage();
//                                $translator->setSelectedLanguage($language);
//                                $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
//                                $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                                $body .= LF.LF;
//                                $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
//                                $body .= LF.LF;
//
//                                $tempMessage = "";
//                                switch ( cs_strtoupper($check_message) ) {
//                                    case 'YES':
//                                        $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
//                                        break;
//                                    case 'NO':
//                                        $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
//                                        break;
//                                    default:
//                                        $body .= $translator->getMessage('COMMON_MESSAGETAG_ERROR').' - '.__FILE__.' - '.__LINE__;
//                                        break;
//                                }
//
//                                $body .= LF.LF;
//                                $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
//                                $body .= LF;
//                                if ( cs_strtoupper($check_message) == 'YES') {
//                                    $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
//                                    $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID().'&mod=account&fct=index'.'&selstatus=1';
//                                } else {
//                                    $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID();
//                                }
//                                global $c_single_entry_point;
//                                $body .= str_replace('soap.php',$c_single_entry_point,$url);
//
//                                include_once('classes/cs_mail.php');
//                                $mail = new cs_mail();
//                                $mail->set_to(implode(',',$email_addresses));
//                                $server_item = $this->_environment->getServerItem();
//                                $default_sender_address = $server_item->getDefaultSenderAddress();
//                                if (!empty($default_sender_address)) {
//                                    $mail->set_from_email($default_sender_address);
//                                } else {
//                                    $mail->set_from_email('@');
//                                }
//                                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
//                                $mail->set_reply_to_name($user_item->getFullname());
//                                $mail->set_reply_to_email($user_item->getEmail());
//                                $mail->set_subject($subject);
//                                $mail->set_message($body);
//                                $mail->send();
//                                $translator->setSelectedLanguage($save_language);
//                            }
//
//                            // send email to user when account is free automatically (PROJECT ROOM)
//                            if ($user_item->isUser()) {
//
//                                // get contact moderator (TBD) now first moderator
//                                $user_list = $room_item->getModeratorList();
//                                $contact_moderator = $user_list->getFirst();
//
//                                // change context to project room
//                                $translator = $this->_environment->getTranslationObject();
//                                $translator->setEmailTextArray($room_item->getEmailTextArray());
//                                $translator->setContext('project');
//                                $save_language = $translator->getSelectedLanguage();
//
//                                // language
//                                $language = $room_item->getLanguage();
//                                if ($language == 'user') {
//                                    $language = $user_item->getLanguage();
//                                    if ($language == 'browser') {
//                                        $language = $this->_environment->getSelectedLanguage();
//                                    }
//                                }
//
//                                $translator->setSelectedLanguage($language);
//
//                                // email texts
//                                $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
//                                $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                                $body .= LF.LF;
//                                $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
//                                $body .= LF.LF;
//                                $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$user_item->getUserID(),$room_item->getTitle());
//                                $body .= LF.LF;
//                                $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
//                                $body .= LF.LF;
//                                $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID();
//                                global $c_single_entry_point;
//                                $body .= str_replace('soap.php',$c_single_entry_point,$url);
//
//                                // send mail to user
//                                include_once('classes/cs_mail.php');
//                                $mail = new cs_mail();
//                                $mail->set_to($user_item->getEmail());
//                                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
//                                $server_item = $this->_environment->getServerItem();
//                                $default_sender_address = $server_item->getDefaultSenderAddress();
//                                if (!empty($default_sender_address)) {
//                                    $mail->set_from_email($default_sender_address);
//                                } else {
//                                    $mail->set_from_email('@');
//                                }
//                                $mail->set_reply_to_email($contact_moderator->getEmail());
//                                $mail->set_reply_to_name($contact_moderator->getFullname());
//                                $mail->set_subject($subject);
//                                $mail->set_message($body);
//                                $mail->send();
//                            }
//                            return true;
//                        } else {
//                            return new SoapFault('ERROR','createMembershipBySession: user ('.$user_id.' | '.$auth_source.') allready exist in room ('.$context_id.'). - '.__FILE__.' - '.__LINE__);
//                        }
//                    } else {
//                        return new SoapFault('ERROR','createMembershipBySession: room ('.$context_id.') does not exist. - '.__FILE__.' - '.__LINE__);
//                    }
//                } elseif ($user_list->getCount() > 1) {
//                    return new SoapFault('ERROR','createMembershipBySession: user ('.$user_id.' | '.$auth_source.') exists '.$user_list->getCount().' times -> error in database. - '.__FILE__.' - '.__LINE__);
//                } else {
//                    return new SoapFault('ERROR','createMembershipBySession: user ('.$user_id.' | '.$auth_source.') does not exist. - '.__FILE__.' - '.__LINE__);
//                }
//            } else {
//                return new SoapFault('ERROR','createMembershipBySession: root and guest are not allowed to become member in an room. - '.__FILE__.' - '.__LINE__);
//            }
//        } else {
//            return new SoapFault('ERROR','createMembershipBySession: session id ('.$session_id.') is not valid. - '.__FILE__.' - '.__LINE__);
//        }
//    }