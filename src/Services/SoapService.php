<?php

namespace App\Services;

use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use cs_environment;
use SoapFault;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use App\Services\LegacyEnvironment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SoapService
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Mailer
     */
    private Mailer $mailer;

    /**
     * @var array
     */
    private array $sessionIdArray = [];

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        ParameterBagInterface $parameterBag
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->mailer = $mailer;

        /**
         * The require statement is necessary to import the legacy class. Otherwise, the class cannot be found
         * in a productive server environment. The problem does not arise in the development stack, even when using
         * the production environment. There must be some side effects in relation to the server configuration, which
         * are not obvious yet.
         */
        $projectDir = $parameterBag->get('kernel.project_dir');
        require_once($projectDir . '/legacy/classes/cs_session_item.php');
    }

    /**
     * Returns a new guest session id
     * 
     * @param  int $portalId
     * 
     * @return string session id
     */
    public function getGuestSession($portalId)
    {
        if (!$portalId) {
            return new SoapFault('ERROR', 'portalId not set!');
        }

        $this->legacyEnvironment->setCurrentContextID($portalId);

        // create guest session
        $sessionItem = new \cs_session_item();
        $sessionItem->createSessionID('guest');
        $sessionItem->setValue('portalId', $portalId);
        $sessionItem->setSoapSession();

        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionManager->save($sessionItem);

        return $sessionItem->getSessionID();
    }

    /**
     * Authenticates a user
     *
     * @param string $userId
     * @param string $password
     * @param int $portalId
     * @param int $authSourceId
     * 
     * @return string session id
     * 
     */
    public function authenticate($userId, $password, $portalId = 99, $authSourceId = 0)
    {
        if (!$userId) {
            return new SoapFault('ERROR', 'userId not set!');
        }

        if (!$password) {
            return new SoapFault('ERROR', 'password not set!');
        }

        $this->legacyEnvironment->setCurrentContextID($portalId);

        $authentication = $this->legacyEnvironment->getAuthenticationObject();
        if (!isset($authentication)) {
            return new SoapFault('ERROR', 'no authentication found!');
        }

        if ($authentication->isAccountGranted($userId, $password, $authSourceId)) {
            if ($this->isSessionActive($userId, $portalId)) {
                $sessionId = $this->getActiveSessionId($userId, $portalId);
                if (!$sessionId) {
                    return new SoapFault('ERROR', 'no session found!');
                }

                return $sessionId;
            } else {
                $sessionItem = new \cs_session_item();
                $sessionItem->createSessionID($userId);

                // save portal id in session to be sure, that user didn't
                // switch between portals
                $sessionItem->setValue('user_id', $userId);
                $sessionItem->setValue('commsy_id', $portalId);

                if (!$authSourceId) {
                    $authSourceId = $authentication->getAuthSourceItemID();
                }

                $sessionItem->setValue('auth_source', $authSourceId);
                $sessionItem->setValue('cookie', '3');
                $sessionItem->setSoapSession();

                // save session
                $sessionManager = $this->legacyEnvironment->getSessionManager();
                $sessionManager->save($sessionItem);

                return $sessionItem->getSessionID();
            }
        } else {
            return new SoapFault('ERROR', 'permission denied!');
        }
    }

    /**
     * Creates a new wiki
     * 
     * @param  string $sessionId
     * @param  string $contextId
     * 
     * @return bool success
     */
    public function createWiki($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new SoapFault('ERROR', 'session invalid!');
        }

//          $room_manager = $this->_environment->getRoomManager();
//          $room_item = $room_manager->getItem($context_id);

//          $item->setWikiSkin();
//          $item->setWikiEditPW();
//          $item->setWikiAdminPW();
//          $item->setWikiEditPW();
//          $item->setWikiReadPW();
//          $item->setWikiTitle();
//          $item->setWikiShowCommSyLogin();
//          $item->setWikiWithSectionEdit();
//          $item->setWikiWithHeaderForSectionEdit();
//          $item->setWikiEnableFCKEditor();
//          $item->setWikiEnableSearch();
//          $item->setWikiEnableSitemap();
//          $item->setWikiEnableStatistic();
//          $item->setWikiEnableRss();
//          $item->setWikiEnableCalendar();
//          $item->setWikiEnableNotice();
//          $item->setWikiEnableGallery();
//          $item->setWikiEnablePdf();
//          $item->setWikiEnableSwf();
//          $item->setWikiEnableWmplayer();
//          $item->setWikiEnableQuicktime();
//          $item->setWikiEnableYoutubeGoogleVimeo();
//          $item->setWikiEnableDiscussion();
//          //$item->setWikiDiscussionArray();
//          $item->setWikiEnableDiscussionNotification();
//          $item->setWikiEnableDiscussionNotificationGroups();

//          $wiki_manager = $this->_environment->getWikiManager();
//          $wiki_manager->deleteWiki($room_item);
    }

    /**
     * Deletes a wiki
     * 
     * @param  string $sessionId
     * @param  string $contextId
     * 
     * @return bool success
     */
    public function deleteWiki($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new SoapFault('ERROR', 'session invalid!');
        }

//          $room_manager = $this->_environment->getRoomManager();
//          $room_item = $room_manager->getItem($context_id);
//          $wiki_manager = $this->_environment->getWikiManager();
//          $wiki_manager->deleteWiki($room_item);
    }

    /**
     * Checks valid session
     * 
     * @param  string $sessionId
     * 
     * @return bool success
     */
    public function isSessionValid($sessionId)
    {
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionItem = $sessionManager->get($sessionId);

        if (isset($sessionItem) && $sessionItem->issetValue('user_id')) {
            return true;
        }

        return false;
    }

    /**
     * Returns a userId
     *
     * @param string $sessionId
     *
     * @return string user_id
     */
    public function getUserIdBySessionId($sessionId)
    {
        if ($this->isSessionValid($sessionId)) {
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionItem = $sessionManager->get($sessionId);

            return $sessionItem->getValue('user_id');
        }
        return false;
    }

    /**
     * Returns information about an the user identified by the session id
     *
     * @param string $sessionId The session id
     * @param int $contextId The context id
     *
     * @throws SoapFault
     *
     * @return string | null
     */
    public function getUserInfo($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new SoapFault('ERROR', 'given session id is invalid!');
        }

        // grep the session
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionItem = $sessionManager->get($sessionId);

        // extract information from session object
        $userId = $sessionItem->getValue('user_id');
        $authSource = $sessionItem->getValue('auth_source');

        // get the user object
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($contextId);
        $userManager->setUserIDLimit($userId);
        $userManager->setAuthSourceLimit($authSource);
        $userManager->select();

        $userList = $userManager->get();
        if ($userList->getCount() == 1) {
            return $userList->getFirst()->getDataAsXML();
        }

        return new SoapFault('ERROR', 'no user found!');
    }

    /**
     * Creates a new user
     *
     * @param string $sessionId The session id
     * @param int $portalId portal id
     * @param string $firstname first name
     * @param string $lastname last name
     * @param string $mail email
     * @param string $userId user id
     * @param string $userPassword $password
     * @param bool $agb agb accepted
     * @param bool $sendMail send mail to user
     *
     * @throws SoapFault
     *
     * @return bool
     */
//    public function createUser($sessionId, $portalId, $firstname, $lastname, $mail, $userId, $userPassword, $agb = false, $sendMail = true)
//    {
//
//    }

    public function createUser ($session_id,$portal_id,$firstname,$lastname,$mail,$user_id,$user_pwd,$agb = false,$send_email = true) {
        $session_id = $this->_encode_input($session_id);
        $portal_id = $this->_encode_input($portal_id);
        if ( is_numeric($session_id) ) {
            $temp = $session_id;
            $session_id = $portal_id;
            $portal_id = $temp;
        }
        if ( !empty($session_id) ) {
            $this->legacyEnvironment->setSessionID($session_id);
            $session_item = $this->legacyEnvironment->getSessionItem();
            if ( !isset($session_item)  ) {
                $session_manager = $this->legacyEnvironment->getSessionManager();
                $session_item = $session_manager->get($session_id);
                if ( !isset($session_item)  ) {
                    $last_query = $session_manager->getLastQuery();
                    return new SoapFault('ERROR','createUser: can not get session_item with query: '.$last_query.' - '.__FILE__.' - '.__LINE__);
                }
            }
            $current_user_id = $session_item->getValue('user_id');
            $user_manager = $this->legacyEnvironment->getUserManager();
            if ($current_user_id == 'root') {
                $current_user = $this->legacyEnvironment->getCurrentUserItem();
            } else {
                $current_server_id = $session_item->getValue('commsy_id');
                $auth_source_id = $session_item->getValue('auth_source');
                $user_manager->setContextLimit($current_server_id);
                $user_manager->setUserIdLimit($current_user_id);
                $user_manager->setAuthSourceLimit($auth_source_id);
                $user_manager->select();
                $current_user_list = $user_manager->get();
                $current_user = $current_user_list->getFirst();
            }
            if ( !empty($current_user) ) {
                $this->legacyEnvironment->setCurrentUserItem($current_user);
                if ( $current_user->isRoot()
                    or ( $current_server_id == $this->legacyEnvironment->getServerID()
                        and $current_user->getUserID() == 'IMS_USER'
                    )
                ) {
                    $user_id = $this->_encode_input($user_id);
                    include_once('functions/text_functions.php');
                    if ( !withUmlaut($user_id) ) {
                        $user_pwd = $this->_encode_input($user_pwd);
                        $portal_id = $this->_encode_input($portal_id);
                        $firstname = $this->_encode_input($firstname);
                        $lastname = $this->_encode_input($lastname);
                        $mail = $this->_encode_input($mail);
                        $agb = $this->_encode_input($agb);
                        if ( empty($agb) ) {
                            $agb = false;
                        }
                        $language = 'DE'; // (TBD)
                        // $language = $this->legacyEnvironment->getSelectedLanguage();

                        $portal_manager = $this->legacyEnvironment->getPortalManager();
                        $portal_item = $portal_manager->getItem($portal_id);
                        if ( !empty($portal_item) ) {
                            $this->legacyEnvironment->setCurrentContextID($portal_id);
                            $authentication = $this->legacyEnvironment->getAuthenticationObject();
                            $current_portal = $this->legacyEnvironment->getCurrentPortalItem();
                            $auth_source_id = $current_portal->getAuthDefault();

                            if ( $authentication->is_free($user_id,$auth_source_id) ) {
                                // Create new item
                                $new_account = $authentication->getNewItem();
                                $new_account->setUserID($user_id);
                                $new_account->setPassword($user_pwd);
                                $new_account->setFirstname($firstname);
                                $new_account->setLastname($lastname);
                                $new_account->setLanguage($language);
                                $new_account->setEmail($mail);
                                $new_account->setPortalID($portal_id);
                                if ( !empty($auth_source_id) ) {
                                    $new_account->setAuthSourceID($auth_source_id);
                                } else {
                                    $current_portal = $this->legacyEnvironment->getCurrentPortalItem();
                                    $new_account->setAuthSourceID($current_portal->getAuthDefault());
                                    $auth_source_id = $current_portal->getAuthDefault();
                                    unset($current_portal);
                                }
                                $save_only_user = false;
                                $authentication->save($new_account,$save_only_user);

                                $portal_user = $authentication->getUserItem();
                                $error = $authentication->getErrorMessage();
                                if ( empty($error) ) {
                                    // portal: send mail to moderators in different languages
                                    $portal_item = $this->legacyEnvironment->getCurrentPortalItem();
                                    $user_list = $portal_item->getModeratorList();
                                    $email_addresses = array();
                                    $user_item = $user_list->getFirst();
                                    $recipients = '';
                                    $language = $portal_item->getLanguage();
                                    while ($user_item) {
                                        $want_mail = $user_item->getAccountWantMail();
                                        if (!empty($want_mail) and $want_mail == 'yes') {
                                            if ($language == 'user'  and $user_item->getLanguage() != 'browser') {
                                                $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                                            } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                                                $email_addresses[$this->legacyEnvironment->getSelectedLanguage()][] = $user_item->getEmail();
                                            } else {
                                                $email_addresses[$language][] = $user_item->getEmail();
                                            }
                                            $recipients .= $user_item->getFullname().LF;
                                        }
                                        unset($user_item);
                                        $user_item = $user_list->getNext();
                                    }
                                    $translator = $this->legacyEnvironment->getTranslationObject();
                                    $save_language = $translator->getSelectedLanguage();
                                    unset($user_item);
                                    unset($user_list);
                                    foreach ($email_addresses as $key => $value) {
                                        $translator->setSelectedLanguage($key);
                                        if (count($value) > 0) {
                                            //include_once('classes/cs_mail.php');
                                            //$mail = new cs_mail();
                                            //$mail->set_to(implode(',',$value));
                                            $server_item = $this->legacyEnvironment->getServerItem();
                                            $default_sender_address = $server_item->getDefaultSenderAddress();
                                            $senderAddress = '';
                                            if (!empty($default_sender_address)) {
                                                //$mail->set_from_email($default_sender_address);
                                                $senderAddress = $default_sender_address;
                                            } else {
                                                //$mail->set_from_email('@');
                                                $senderAddress = '@';
                                            }
                                            //$mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
                                            //$mail->set_reply_to_name($portal_user->getFullname());
                                            //$mail->set_reply_to_email($portal_user->getEmail());
                                            //$mail->set_subject($translator->getMessage('USER_GET_MAIL_SUBJECT',$portal_user->getFullname()));
                                            $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                                            $body .= LF.LF;
                                            $temp_language = $portal_user->getLanguage();
                                            if ($temp_language == 'browser') {
                                                $temp_language = $this->legacyEnvironment->getSelectedLanguage();
                                            }
                                            $body .= $translator->getMessage('USER_GET_MAIL_BODY',
                                                $portal_user->getFullname(),
                                                $portal_user->getUserID(),
                                                $portal_user->getEmail(),
                                                $translator->getLanguageLabelTranslated($temp_language)
                                            );
                                            unset($temp_language);
                                            $body .= LF.LF;
                                            $check_message = 'NO';

                                            switch ( $check_message )
                                            {
                                                case 'YES':
                                                    $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                                                    break;
                                                case 'NO':
                                                    $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                                                    break;
                                                default:
                                                    break;
                                            }
                                            $body .= LF.LF;
                                            $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                                            $body .= LF;

                                            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$portal_item->getItemID().'&mod=account&fct=index'.'&selstatus=1';
                                            global $c_single_entry_point;
                                            $body .= str_replace('soap.php',$c_single_entry_point,$url);

                                            $this->mailer->sendRaw(
                                                $translator->getMessage('USER_GET_MAIL_SUBJECT',$portal_user->getFullname()),
                                                $body,
                                                $recipient,
                                                $translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle())
                                            );
                                        }
                                    }
                                    $translator->setSelectedLanguage($save_language);

                                    // activate user
                                    $portal_user->makeUser();
                                    if ( $agb ) {
                                        $portal_user->setAGBAcceptance();
                                    }
                                    $portal_user->save();
                                    $current_user = $portal_user;
                                    $this->legacyEnvironment->setCurrentUserItem($current_user);

                                    // send email to user
                                    if ( $send_email
                                        and $current_user->isUser()
                                    ) {
                                        $mod_text = '';
                                        $mod_list = $portal_item->getContactModeratorList();
                                        if (!$mod_list->isEmpty()) {
                                            $mod_item = $mod_list->getFirst();
                                            $contact_moderator = $mod_item;
                                            while ($mod_item) {
                                                if (!empty($mod_text)) {
                                                    $mod_text .= ','.LF;
                                                }
                                                $mod_text .= $mod_item->getFullname();
                                                $mod_text .= ' ('.$mod_item->getEmail().')';
                                                unset($mod_item);
                                                $mod_item = $mod_list->getNext();
                                            }
                                        }
                                        unset($mod_item);
                                        unset($mod_list);

                                        $language = $this->legacyEnvironment->getSelectedLanguage();
                                        $translator->setSelectedLanguage($language);
                                        //include_once('classes/cs_mail.php');
                                        //$mail = new cs_mail();
                                        //$mail->set_to($current_user->getEmail());
                                        //$mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
                                        $server_item = $this->legacyEnvironment->getServerItem();
                                        $default_sender_address = $server_item->getDefaultSenderAddress();
                                        $senderAddress = '';
                                        if (!empty($default_sender_address)) {
                                            //$mail->set_from_email($default_sender_address);
                                            $senderAddress = $default_sender_address;
                                        } else {
                                            $user_manager = $this->legacyEnvironment->getUserManager();
                                            $root_user = $user_manager->getRootUser();
                                            $root_mail_address = $root_user->getEmail();
                                            if ( !empty($root_mail_address) ) {
                                                //$mail->set_from_email($root_mail_address);
                                                $senderAddress = $root_mail_address;
                                            } else {
                                                //$mail->set_from_email('@');
                                                $senderAddress = '@';
                                            }
                                        }
                                        if (!empty($contact_moderator)) {
                                            //$mail->set_reply_to_email($contact_moderator->getEmail());
                                            //$mail->set_reply_to_name($contact_moderator->getFullname());
                                        }
                                        //$mail->set_subject($translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$portal_item->getTitle()));
                                        $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                                        $body .= LF.LF;
                                        $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$current_user->getFullname());
                                        $body .= LF.LF;
                                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$portal_user->getUserID(),$portal_item->getTitle());
                                        $body .= LF.LF;
                                        if ( empty($contact_moderator) ) {
                                            $body .= $translator->getMessage('SYSTEM_MAIL_REPLY_INFO').LF;
                                            $body .= $mod_text;
                                            $body .= LF.LF;
                                        } else {
                                            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$portal_item->getTitle());
                                            $body .= LF.LF;
                                        }
                                        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->legacyEnvironment->getCurrentContextID();
                                        global $c_single_entry_point;
                                        $body .= str_replace('soap.php',$c_single_entry_point,$url);

                                        $this->mailer->sendRaw(
                                            $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$portal_item->getTitle()),
                                            $body,
                                            RecipientFactory::createRecipient($current_user),
                                            $translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle())
                                        );
                                    }

                                    // login in user
                                    #return $this->authenticate($this->_encode_output($user_id),$this->_encode_output($user_pwd),$this->_encode_output($portal_id),$this->_encode_output($auth_source_id));
                                    return true;
                                } else {
                                    return new SoapFault('ERROR','createUser: error while saving user account ('.$error.')! - '.__FILE__.' - '.__LINE__);
                                }
                            } else {
                                return new SoapFault('ERROR','createUser: account is not free! - ('.$user_id.')'.__FILE__.' - '.__LINE__);
                            }
                        } else {
                            return new SoapFault('ERROR','createUser: Portal ID is not valid! - '.__FILE__.' - '.__LINE__);
                        }
                    } else {
                        return new SoapFault('ERROR','createUser: user_id is not valid: user_id has umlauts! - '.__FILE__.' - '.__LINE__);
                    }
                } else {
                    return new SoapFault('ERROR','createUser: Logged in user is not allowed to create accounts. - '.__FILE__.' - '.__LINE__);
                }
            } else {
                return new SoapFault('ERROR','createUser: can not identify current user. - '.__FILE__.' - '.__LINE__);
            }
        } else {
            return new SoapFault('ERROR','createUser: session id ('.$session_id.') is not set. - '.__FILE__.' - '.__LINE__);
        }
    }

    /**
     * Returns statistic information
     *
     * @param string $sessionId The session id
     * @param string $dateStart starting date
     * @param string $dateEnd ending date
     *
     * @throws SoapFault
     *
     * @return string | null
     */
    public function getStatistics($sessionId, $dateStart, $dateEnd)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new SoapFault('ERROR', 'given session id is invalid!');
        }

        $sessionId = $this->_encode_input($sessionId);
        $this->legacyEnvironment->setSessionID($sessionId);
        $session = $this->legacyEnvironment->getSessionItem();
        $user_id = $session->getValue('user_id');
        $auth_source_id = $session->getValue('auth_source');
        $context_id = $session->getValue('commsy_id');
        $this->legacyEnvironment->setCurrentContextID($context_id);

        $user_manager = $this->legacyEnvironment->getUserManager();
        $user_manager->setContextLimit($context_id);
        $user_manager->setUserIDLimit($user_id);
        $user_manager->setAuthSourceLimit($auth_source_id);
        $user_manager->select();
        $user_list = $user_manager->get();

        if ($user_list->getCount() == 1) {
            $user_item = $user_list->getFirst();
            if ($user_item->isRoot()) {
                if (!empty($dateStart)) {
                    $dateStart = $this->_encode_input($dateStart);
                    if (!empty($dateEnd)) {
                        $dateEnd = $this->_encode_input($dateEnd);
                    } else {
                        $dateEnd = 'NOW';
                    }
                    if ($dateEnd == 'NOW') {
                        $dateEnd = date('Y-m-d') . ' 23:59:59';
                    }
                    $server_item = $this->legacyEnvironment->getServerItem();
                    if (!empty($server_item)) {
                        include_once('functions/misc_functions.php');
                        return array2XML($server_item->getStatistics($dateStart, $dateEnd));
                    } else {
                        $info = 'ERROR: GET STATISTICS';
                        $info_text = 'server_item is empty';
                        return new SoapFault($info, $info_text);
                    }
                } else {
                    $info = 'ERROR: GET STATISTICS';
                    $info_text = 'date_start (second parameter) is empty';
                    return new SoapFault($info, $info_text);
                }
            } else {
                $info = 'ERROR: GET STATISTICS';
                $info_text = 'only root is allowed to use this function';
                return new SoapFault($info, $info_text);
            }
        } else {
            $info = 'ERROR: GET STATISTICS';
            $info_text = 'multiple user (' . $user_id . ') with auth source (' . $auth_source_id . ')';
            return new SoapFault($info, $info_text);
        }
    }

    /**
     * Returns list of dates
     *
     * @param string $sessionId The session id
     * @param integer $contextId The context id
     *
     * @throws SoapFault
     *
     * @return string | null
     */
    public function getDatesList($sessionId, $contextId)
    {
        if (!$this->isSessionValid($sessionId)) {
            return new SoapFault('ERROR', 'given session id is invalid!');
        }

        $this->legacyEnvironment->setSessionID($sessionId);
        $session = $this->legacyEnvironment->getSessionItem();

        $this->legacyEnvironment->setCurrentContextID($contextId);

        $userId = $session->getValue('user_id');
        $authSourceId = $session->getValue('auth_source');
        $user_manager = $this->legacyEnvironment->getUserManager();
        $userItem = $user_manager->getItemByUserIDAuthSourceID($userId, $authSourceId);

        $reader_manager = $this->legacyEnvironment->getReaderManager();

        $datesManager = $this->legacyEnvironment->getDatesManager();
        $datesManager->setContextLimit($contextId);
        $datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $datesManager->setDateModeLimit(2);
        $datesManager->select();

        $dateList = $datesManager->get();
        $xml = "<dates_list>\n";

        /** @var \cs_dates_item $dateItem */
        $dateItem = $dateList->getFirst();
        while ($dateItem) {
            $xml .= "<date_item>\n";
            $xml .= "<date_id><![CDATA[" . $dateItem->getItemID() . "]]></date_id>\n";
            $temp_title = $dateItem->getTitle();
            $temp_title = $this->prepareText($temp_title);
            $xml .= "<date_title><![CDATA[" . $temp_title . "]]></date_title>\n";
            $xml .= "<date_starting_date><![CDATA[" . $dateItem->getDateTime_start() . "]]></date_starting_date>\n";
            $xml .= "<date_ending_date><![CDATA[" . $dateItem->getDateTime_end() . "]]></date_ending_date>\n";
            $reader = $reader_manager->getLatestReaderForUserByID($dateItem->getItemID(), $userItem->getItemID());
            if (empty($reader)) {
                $xml .= "<date_read><![CDATA[new]]></date_read>\n";
            } elseif ($reader['read_date'] < $dateItem->getModificationDate()) {
                $xml .= "<date_read><![CDATA[changed]]></date_read>\n";
            } else {
                $xml .= "<date_read><![CDATA[]]></date_read>\n";
            }
            if ($dateItem->mayEdit($userItem)) {
                $xml .= "<date_edit><![CDATA[edit]]></date_edit>\n";
            } else {
                $xml .= "<date_edit><![CDATA[non_edit]]></date_edit>\n";
            }
            $xml .= "</date_item>\n";
            $dateItem = $dateList->getNext();
        }
        $xml .= "</dates_list>";

        return $xml;
    }

    /**
     * Returns list of dates
     *
     * @param string $sessionId The session id
     * @param integer $contextId The context id
     * @param integer $startTimestamp Starting timestamp
     * @param integer $endTimestamp Ending timestamp
     *
     * @throws SoapFault
     *
     * @return string
     */
    public function getDatesInRange($sessionId, $contextId, $startTimestamp, $endTimestamp)
    {
        if (!$this->isSessionValid($sessionId)) {
            throw new SoapFault('ERROR', 'given session id is invalid!');
        }

        $startDate = date("Y-m-d H:i:s", $startTimestamp);
        $endDate = date("Y-m-d H:i:s", $endTimestamp);

        $datesManager = $this->legacyEnvironment->getDatesManager();
        $datesManager->setContextLimit($contextId);
        $datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $datesManager->setDateModeLimit(2);
        $datesManager->setBetweenLimit($startDate, $endDate);

        $datesManager->select();
        $datesList = $datesManager->get();
        $xml = "<dates_list>\n";

        /** @var \cs_dates_item $dateItem */
        $dateItem = $datesList->getFirst();

        while ($dateItem) {
            $xml .= "<date_item>\n";

            $xml .= "<date_id><![CDATA[".$dateItem->getItemID()."]]></date_id>\n";

            $tempTitle = $dateItem->getTitle();
            $tempTitle = $this->prepareText($tempTitle);
            $xml .= "<date_title><![CDATA[".$tempTitle."]]></date_title>\n";

            $tempDescription = $dateItem->getDescription();
            $tempDescription = $this->prepareText($tempDescription);
            $xml .= "<date_description><![CDATA[".$tempDescription."]]></date_description>\n";

            $xml .= "<date_place><![CDATA[".$dateItem->getPlace()."]]></date_place>\n";

            $xml .= "<date_starting_date><![CDATA[".$dateItem->getDateTime_start()."]]></date_starting_date>\n";
            $xml .= "<date_ending_date><![CDATA[".$dateItem->getDateTime_end()."]]></date_ending_date>\n";

            $xml .= "</date_item>\n";

            $dateItem = $datesList->getNext();
        }

        $xml .= "</dates_list>";

        return $xml;
    }

    /**
     * Returns list of announcements
     *
     * @param string $sessionId The session id
     * @param integer $contextId The context id
     * @param integer $validTimestamp Valid timestamp
     *
     * @throws SoapFault
     *
     * @return string
     */
    public function getAnnouncementsInRange($sessionId, $contextId, $validTimestamp)
    {
        if (!$this->isSessionValid($sessionId)) {
            throw new SoapFault('ERROR', 'given session id is invalid!');
        }

        $validDate = date("Y-m-d H:i:s", $validTimestamp);

        $announcementManager = $this->legacyEnvironment->getAnnouncementManager();
        $announcementManager->setContextLimit($contextId);
        $announcementManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $announcementManager->setDateLimit($validDate);

        $announcementManager->select();
        $announcementList = $announcementManager->get();
        $xml = "<announcements_list>\n";
        $announcementItem = $announcementList->getFirst();

        while ($announcementItem) {
            $xml .= "<announcement_item>\n";

            $xml .= "<announcement_id><![CDATA[".$announcementItem->getItemID()."]]></announcement_id>\n";

            $tempTitle = $announcementItem->getTitle();
            $tempTitle = $this->prepareText($tempTitle);
            $xml .= "<announcement_title><![CDATA[".$tempTitle."]]></announcement_title>\n";

            $tempDescription = $announcementItem->getDescription();
            $tempDescription = $this->prepareText($tempDescription);
            $xml .= "<announcement_description><![CDATA[".$tempDescription."]]></announcement_description>\n";

            $xml .= "<announcement_ending_date><![CDATA[".$announcementItem->getSecondDateTime()."]]></announcement_ending_date>\n";

            $xml .= "</announcement_item>\n";

            $announcementItem = $announcementList->getNext();
        }

        $xml .= "</announcements_list>";

        return $xml;
    }

//
//    public function getDateDetails($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $dates_manager = $this->_environment->getDatesManager();
//            $date_item = $dates_manager->getItem($item_id);
//            $xml  = "<date_item>\n";
//            $xml .= "<date_id><![CDATA[".$date_item->getItemID()."]]></date_id>\n";
//            $temp_title = $date_item->getTitle();
//            $temp_title = $this->prepareText($temp_title);
//            $xml .= "<date_title><![CDATA[".$temp_title."]]></date_title>\n";
//            $xml .= "<date_starting_date><![CDATA[".$date_item->getDateTime_start()."]]></date_starting_date>\n";
//            $xml .= "<date_ending_date><![CDATA[".$date_item->getDateTime_end()."]]></date_ending_date>\n";
//            $xml .= "<date_place><![CDATA[".$date_item->getPlace()."]]></date_place>\n";
//            $temp_description = $date_item->getDescription();
//            $allow_edit = true;
//            if(stristr($temp_description, '<table')){
//                $allow_edit = false;
//            }
//            $temp_description = $this->prepareText($temp_description);
//            $xml .= "<date_description><![CDATA[".$temp_description."]]></date_description>\n";
//            $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) ) {
//                $xml .= "<date_read><![CDATA[new]]></date_read>\n";
//            } elseif ( $reader['read_date'] < $date_item->getModificationDate() ) {
//                $xml .= "<date_read><![CDATA[changed]]></date_read>\n";
//            } else {
//                $xml .= "<date_read><![CDATA[]]></date_read>\n";
//            }
//            if($date_item->mayEdit($user_item) && $allow_edit){
//                $xml .= "<date_edit><![CDATA[edit]]></date_edit>\n";
//            } else {
//                $xml .= "<date_edit><![CDATA[non_edit]]></date_edit>\n";
//            }
//            $modifier_user = $date_item->getModificatorItem();
//            $xml .= "<date_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></date_last_modifier>\n";
//            $xml .= "<date_last_modification_date><![CDATA[".$date_item->getModificationDate()."]]></date_last_modification_date>\n";
//            $xml .= "<date_files>\n";
//            $file_list = $date_item->getFileList();
//            $temp_file = $file_list->getFirst();
//            while($temp_file){
//                $xml .= "<date_file>\n";
//                $xml .= "<date_file_name><![CDATA[".$temp_file->getFileName()."]]></date_file_name>\n";
//                $xml .= "<date_file_id><![CDATA[".$temp_file->getFileID()."]]></date_file_id>\n";
//                $xml .= "<date_file_size><![CDATA[".$temp_file->getFileSize()."]]></date_file_size>\n";
//                $xml .= "<date_file_mime><![CDATA[".$temp_file->getMime()."]]></date_file_mime>\n";
//                //if($temp_file->getMime() == 'image/gif' || $temp_file->getMime() == 'image/jpeg' || $temp_file->getMime() == 'image/png'){
//                //   $xml .= "<date_file_data><![CDATA[".$temp_file->getBase64()."]]></date_file_data>\n";
//                //   debugToFile($temp_file->getBase64());
//                //}
//                $xml .= "</date_file>\n";
//                $temp_file = $file_list->getNext();
//            }
//            $xml .= "</date_files>\n";
//            $xml .= "</date_item>\n";
//            $xml = $this->_encode_output($xml);
//            $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) or $reader['read_date'] < $date_item->getModificationDate() ) {
//                $reader_manager->markRead($date_item->getItemID(),0);
//            }
//            $noticed = $noticed_manager->getLatestNoticedForUserByID($date_item->getItemID(), $user_item->getItemID());
//            if ( empty($noticed) or $noticed['read_date'] < $date_item->getModificationDate() ) {
//                $noticed_manager->markNoticed($date_item->getItemID(),0);
//            }
//            return $xml;
//        }
//    }








    private function _encode_input($value)
    {
        // TODO: check if 'utf8_decode' makes any sense in 2018 - probably not!
        //return utf8_decode($value);
        return $value;
    }

    private function prepareText($text)
    {
        $text = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u', '', $text);
        $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
        $text = str_ireplace("<li>", "CS_LI", $text);
        $text = str_ireplace("\n", "CS_NEWLINE", $text);
        $text = str_ireplace("\r", "", $text);
        $text = str_ireplace("\t", "", $text);
        $text = str_ireplace("CS_LICS_NEWLINE", "CS_BULL ", $text);
        $text = str_ireplace("CS_LI", "CS_BULL ", $text);
        $text = str_ireplace("CS_NEWLINE", "\n", $text);
        $text = str_ireplace("<br />", "", $text);
        $current_encoding = mb_detect_encoding($text, 'auto');
        $text = iconv($current_encoding, 'UTF-8', $text);
        $text = strip_tags($text);
        $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
        $text = str_ireplace("CS_BULL", "&bull;", $text);
        $text = trim($text);
        if (empty($text)) {
            $text = ' ';
        }
        $text = base64_encode($text);
        return $text;
    }

    private function isSessionActive($userId, $portalId)
    {
        if (!empty($this->sessionIdArray[$portalId][$userId])) {
            return true;
        } else {
            $sessionId = $this->getActiveSessionId($userId, $portalId);
            if ($sessionId) {
                return true;
            }
        }

        return false;
    }

    private function getActiveSessionId($userId, $portalId)
    {
        if (!empty($this->sessionIdArray[$portalId][$userId])) {
            return $this->sessionIdArray[$portalId][$userId];
        } else {
            $sessionManager = $this->legacyEnvironment->getSessionManager();
            $sessionId = $sessionManager->getActiveSOAPSessionID($userId, $portalId);

            if (!empty($sessionId)) {
                $this->sessionIdArray[$portalId][$userId] = $sessionId;
                $this->updateSessionCreationDate($sessionId);

                return $sessionId;
            }
        }

        return null;
    }

    private function updateSessionCreationDate($sessionId)
    {
        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionManager->updateSessionCreationDate($sessionId);
    }
}

/**
 * TODO
 */

//class cs_connection_soap {
//    private $_environment = null;
//
//    private $_session_id_array = array();
//
//    private $_valid_session_id_array = array();
//
//    private $_material_limit_array = array();
//
//    private $_soap_fault = null;
//
//    public function __construct ($environment) {
//        $this->_environment = $environment;
//    }
//
//    private function _htmlTextareaSecurity ( $value ) {
//        if ( strlen($value) != strlen(strip_tags($value)) ) {
//            $value = preg_replace('~<!-- KFC TEXT -->~u','',$value);
//            $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u','',$value);
//            if ( strlen($value) != strlen(strip_tags($value)) ) {
//                $text_converter = $this->_environment->getTextConverter();
//                if ( isset($text_converter) ) {
//                    $value = $text_converter->cleanBadCode($value);
//                }
//            }
//            include_once('functions/security_functions.php');
//            $fck_text = '<!-- KFC TEXT '.getSecurityHash($value).' -->';
//            $value = $fck_text.$value.$fck_text;
//        }
//        return $value;
//    }
//
//    public function getGuestSession($portal_id) {
//        if ( empty($portal_id) ) {
//            return new SoapFault('ERROR','portal_id is empty!');
//        } else {
//            $portal_id = $this->_encode_input($portal_id);
//            $this->_environment->setCurrentContextID($portal_id);
//            // make session
//            include_once('classes/cs_session_item.php');
//            $session = new cs_session_item();
//            $session->createSessionID('guest');
//            $session->setValue('portal_id',$portal_id);
//            $session->setSoapSession();
//            $session_manager = $this->_environment->getSessionManager();
//            $session_manager->save($session);
//            $retour = $session->getSessionID();
//            return $this->_encode_output($retour);
//        }
//    }
//
//    public function getCountUser($session_id, $portal_id) {
//        $portal_id = $this->_encode_input($portal_id);
//        $session_id = $this->_encode_input($session_id);
//        $user_count =-1;
//
//        if($this->_isSessionValid($session_id)) {
//            if ($this->_isSessionActive('guest',$portal_id)) {
//                $portal_manager = $this->_environment->getPortalManager();
//                $portal_item = $portal_manager->getItem($portal_id);
//                $user_count = $portal_item->getCountMembers();
//            } else {
//                return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
//            }
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//
//        return $this->_encode_output($user_count);
//    }
//
//    //public function getCountRooms($session_id, $portal_id) {
//    public function getCountRooms($session_id, $portal_id) {
//        $portal_id = $this->_encode_input($portal_id);
//        $session_id = $this->_encode_input($session_id);
//        $room_count =-1;
//
//        if($this->_isSessionValid($session_id)) {
//            if ($this->_isSessionActive('guest',$portal_id)) {
//                $portal_manager = $this->_environment->getPortalManager();
//                $portal_item = $portal_manager->getItem($portal_id);
//                $date_current = date("Y-m-d H:i:s");
//                $room_count = $portal_item->getCountRooms('',$date_current);
//            } else {
//                return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
//            }
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//        return $this->_encode_output($room_count);
//    }
//
//    public function getActiveRoomList($session_id, $portal_id, $count) {
//        if($this->_isSessionValid($session_id)) {
//            if ($this->_isSessionActive('guest',$portal_id)) {
//                $room_manager = $this->_environment->getRoomManager();
//                $room_manager->setContextLimit($portal_id);
//                $room_manager->setRoomTypeLimit(CS_PROJECT_TYPE);
//                $room_manager->setOrder('activity_rev');
//                $room_manager->setIntervalLimit(0,$count);
//                $room_manager->select();
//                $test = $room_manager->getLastQuery();
//                $room_list = $room_manager->get();
//
//
//                $room_item = $room_list->getFirst();
//                $xml = "<room_list>\n";
//                while($room_item) {
//                    $xml .= $room_item->getXMLData();
//                    $room_item = $room_list->getNext();
//                }
//                $xml .= "</room_list>";
//                $xml = $this->_encode_output($xml);
//            } else {
//                return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
//            }
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//        return $xml;
//    }
//
//
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
//
//    public function authenticate ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
//        $user_id = $this->_encode_input($user_id);
//        $password = $this->_encode_input($password);
//        $portal_id = $this->_encode_input($portal_id);
//        if ( !empty($auth_source_id) and $auth_source_id != 0 ) {
//            $auth_source_id = $this->_encode_input($auth_source_id);
//        }
//        $result = '';
//
//        $info = 'ERROR';
//        $info_text = 'default-error';
//        if ( empty($user_id) or empty($password) ) {
//            $info = 'ERROR';
//            $info_text = 'user_id or password lost';
//        } else {
//            if ( !isset($this->_environment) ) {
//                $info = 'ERROR';
//                $info_text = 'environment lost';
//            } else {
//                $this->_environment->setCurrentContextID($portal_id);
//                $authentication = $this->_environment->getAuthenticationObject();
//                if ( isset($authentication) ) {
//                    if ($authentication->isAccountGranted($user_id,$password,$auth_source_id)) {
//                        if ($this->_isSessionActive($user_id,$portal_id)) {
//                            $result = $this->_getActiveSessionID($user_id,$portal_id);
//                            if ( empty($result) ) {
//                                $info = 'ERROR';
//                                $info_text = 'no session id from session manager -> database error';
//                            }
//                        } else {
//                            // make session
//                            include_once('classes/cs_session_item.php');
//                            $session = new cs_session_item();
//                            $session->createSessionID($user_id);
//                            // save portal id in session to be sure, that user didn't
//                            // switch between portals
//                            $session->setValue('user_id',$user_id);
//                            $session->setValue('commsy_id',$portal_id);
//                            if ( empty($auth_source_id) or $auth_source_id == 0 ) {
//                                $auth_source_id = $authentication->getAuthSourceItemID();
//                            }
//                            $session->setValue('auth_source',$auth_source_id);
//                            $session->setValue('cookie','3');
//                            $session->setSoapSession();
//
//                            // save session
//                            $session_manager = $this->_environment->getSessionManager();
//                            $session_manager->save($session);
//
//                            $result = $session->getSessionID();
//                        }
//                    } else {
//                        $info = 'ERROR';
//                        $info_text = 'account not granted for user '.$user_id;
//                    }
//                } else {
//                    $info = 'ERROR';
//                    $info_text = 'authentication object lost';
//                }
//            }
//        }
//        if ( empty($result) and !empty($info) ) {
//            $result = new SoapFault($info,$info_text);
//        } else {
//            $result = $this->_encode_output($result);
//        }
//        return $result;
//    }
//
//    public function authenticateWithLogin ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
//        $user_id = $this->_encode_input($user_id);
//        $password = $this->_encode_input($password);
//        $portal_id = $this->_encode_input($portal_id);
//        if ( !empty($auth_source_id) and $auth_source_id != 0 ) {
//            $auth_source_id = $this->_encode_input($auth_source_id);
//        }
//        $result = '';
//
//        $info = 'ERROR';
//        $info_text = 'default-error';
//        if ( empty($user_id) or empty($password) ) {
//            $info = 'ERROR';
//            $info_text = 'user_id or password lost';
//        } else {
//            if ( !isset($this->_environment) ) {
//                $info = 'ERROR';
//                $info_text = 'environment lost';
//            } else {
//                $this->_environment->setCurrentContextID($portal_id);
//                $authentication = $this->_environment->getAuthenticationObject();
//                if ( isset($authentication) ) {
//                    if ($authentication->isAccountGranted($user_id,$password,$auth_source_id)) {
//                        if ($this->_isSessionActive($user_id,$portal_id)) {
//                            $result = $this->_getActiveSessionID($user_id,$portal_id);
//                            if ( empty($result) ) {
//                                $info = 'ERROR';
//                                $info_text = 'no session id from session manager -> database error';
//                            }
//                        } else {
//                            // make session
//                            include_once('classes/cs_session_item.php');
//                            $session = new cs_session_item();
//                            $session->createSessionID($user_id);
//                            // save portal id in session to be sure, that user didn't
//                            // switch between portals
//                            $session->setValue('user_id',$user_id);
//                            $session->setValue('commsy_id',$portal_id);
//                            if ( empty($auth_source_id) or $auth_source_id == 0 ) {
//                                $auth_source_id = $authentication->getAuthSourceItemID();
//                            }
//                            $session->setValue('auth_source',$auth_source_id);
//                            $session->setValue('cookie','0');
//                            $session->setLoginSession();
//                            //$session->setSoapSession();
//
//                            // save session
//                            $session_manager = $this->_environment->getSessionManager();
//                            $session_manager->save($session);
//
//                            $result = $session->getSessionID();
//                        }
//                    } else {
//                        $info = 'ERROR';
//                        $info_text = 'account not granted '.$user_id.' - '.$password.' - '.$portal_id;
//                    }
//                } else {
//                    $info = 'ERROR';
//                    $info_text = 'authentication object lost';
//                }
//            }
//        }
//        if ( empty($result) and !empty($info) ) {
//            $result = new SoapFault($info,$info_text);
//        } else {
//            $result = $this->_encode_output($result);
//        }
//        return $result;
//    }
//
//    public function authenticateViaSession($session_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_updateSessionCreationDate($session_id);
//            $session_manager = $this->_environment->getSessionManager();
//            $session_item = $session_manager->get($session_id);
//            return $session_item->getValue('user_id');
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function wordpressAuthenticateViaSession($session_id) {
//        $result = null;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_updateSessionCreationDate($session_id);
//            $this->_environment->setSessionID($session_id);
//            $session_item = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($session_item->getValue('commsy_id'));
//
//            // get user data from portal user item
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($session_item->getValue('user_id'),$session_item->getValue('auth_source'));
//            $result = array(
//                'login'     => $user_item->getUserID(),
//                'email'     => $user_item->getEmail(),
//                'firstname' => $user_item->getFirstName(),
//                'lastname'  => $user_item->getLastName()
//            );
//
//            // TBD: commsy authentication via soap
//
//            // get md5-password for commsy internal accounts
//            $auth_source_id = $session_item->getValue('auth_source');
//            $auth_source_manager = $this->_environment->getAuthSourceManager();
//            $auth_source_item = $auth_source_manager->getItem($auth_source_id);
//            if ( $auth_source_item->isCommSyDefault() ) {
//                $user_id = $session_item->getValue('user_id');
//                $auth_source = $session_item->getValue('auth_source');
//                $commsy_id = $session_item->getValue('commsy_id');
//                //$result = array($user_id, $auth_source);
//                $authentication = $this->_environment->getAuthenticationObject();
//                $authManager = $authentication->getAuthManagerByAuthSourceItem($auth_source_item);
//                $authManager->setContextID($commsy_id);
//                //$result = array(get_class($authManager));
//                $auth_item = $authManager->getItem($user_id);
//                $result['password']  = $auth_item->getPasswordMD5();
//            } else {
//                // dummy password for external accounts
//                include_once('functions/date_functions.php');
//                $result['password'] = md5(getCurrentDateTimeInMySQL().rand(1,999).$this->_environment->getConfiguration('c_security_key'));
//            }
//        }
//        return $result;
//    }
//
//    private function _isSessionActive ($user_id, $portal_id) {
//        $retour = false;
//        if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
//            $retour = true;
//        } else {
//            $session_id = $this->_getActiveSessionID($user_id,$portal_id);
//            if ( !empty($session_id) ) {
//                $retour = true;
//            }
//        }
//        return $retour;
//    }
//
//    private function _getActiveSessionID ($user_id, $portal_id) {
//        $retour = '';
//        if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
//            $retour = $this->_session_id_array[$portal_id][$user_id];
//        } else {
//            $session_manager = $this->_environment->getSessionManager();
//            $retour = $session_manager->getActiveSOAPSessionID($user_id,$portal_id);
//            if ( !empty($retour) ) {
//                $this->_session_id_array[$portal_id][$user_id] = $retour;
//                $this->_updateSessionCreationDate($retour);
//            }
//        }
//        return $retour;
//    }
//
//    private function _isSessionValid ($session_id) {
//        $retour = false;
//        if ( !empty($this->_valid_session_id_array[$session_id]) ) {
//            $retour = true;
//        } else {
//            $session_manager = $this->_environment->getSessionManager();
//            $session_item = $session_manager->get($session_id);
//            if ( isset($session_item) and $session_item->issetValue('user_id') ) {
//                $this->_valid_session_id_array[$session_id] = $session_id;
//                $retour = true;
//            }
//        }
//        return $retour;
//    }
//
//    private function _updateSessionCreationDate ($session_id) {
//        $session_manager = $this->_environment->getSessionManager();
//        $session_manager->updateSessionCreationDate($session_id);
//    }
//
//    public function refreshSession ($session_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_updateSessionCreationDate($session_id);
//            return true;
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function logout ($session_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $session_manager = $this->_environment->getSessionManager();
//            $session_manager->delete($session_id);
//            return true;
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function IMS ($session_id, $ims_xml) {
//        if ($this->_isSessionValid($session_id)) {
//            include_once('classes/cs_connection_soap_ims.php');
//            $ims_object = new cs_connection_soap_ims($this->_environment);
//            $this->_updateSessionCreationDate($session_id);
//            return $ims_object->IMS($session_id, $ims_xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function getMaterialList ($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        $context_id = $this->_encode_input($context_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $room_manager = $this->_environment->getRoomManager();
//            $room_item = $room_manager->getItem($context_id);
//            if ( !isset($room_item)
//                or empty($room_item)
//            ) {
//                $room_manager = $this->_environment->getPrivateRoomManager() ;
//                $room_item = $room_manager->getItem( $context_id) ;
//            }
//            $room_context_id = $room_item->getContextID();
//            if ( $room_context_id != $portal_id ) {
//                $info = 'ERROR: GET MATERIAL LIST';
//                $info_text = 'room with id ('.$context_id.') is not on the commsy portal form session with id ('.$portal_id.')';
//                $result = new SoapFault($info,$info_text);
//            } elseif ( !$room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                $info = 'ERROR: GET MATERIAL LIST';
//                $info_text = 'user with user_id ('.$user_id.') is not allowed to enter the room with id ('.$context_id.')';
//                $result = new SoapFault($info,$info_text);
//            } else {
//                $result = $this->_getMaterialListAsXML($room_item->getItemID(),$session_id);
//                $result = $this->_encode_output($result);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: GET MATERIAL LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getPrivateRoomMaterialList ($session_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $room_manager = $this->_environment->getPrivateRoomManager();
//            $room_item_id = $room_manager->getItemIDOfRelatedOwnRoomForUser($user_id,$auth_source,$portal_id);
//            if ( isset($room_item_id) and !empty($room_item_id) ) {
//                $result = $this->_getMaterialListAsXML($room_item_id,$session_id);
//                $result = $this->_encode_output($result);
//            }
//            $this->_updateSessionCreationDate($session_id);
//            $this->_log('material','SOAP:getPrivateMaterialList','SID='.$session_id);
//        } else {
//            $info = 'ERROR: GET MATERIAL LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    private function _getMaterialListAsXML ($room_id,$session_id) {
//        $retour = '';
//        $material_manager = $this->_environment->getMaterialManager();
//        $material_manager->resetLimits();
//        $material_manager->setContextLimit($room_id);
//        // set limits
//        $session_manager = $this->_environment->getSessionManager();
//        $session = $session_manager->get($session_id);
//        $this->_material_limit_array = $session->getValue('material_limit_array');
//
//        if(isset($this->_material_limit_array['group_limit'])) {
//            $material_manager->setGroupLimit ($this->_material_limit_array['group_limit']);
//        }
//        if(isset($this->_material_limit_array['topic_limit'])) {
//            $material_manager->setTopicLimit($this->_material_limit_array['topic_limit']);
//        }
//        if(isset($this->_material_limit_array['label_limit'])) {
//            $material_manager->setLabelLimit($this->_material_limit_array['label_limit']);
//        }
//        if(isset($this->_material_limit_array['buzzword_limit'])) {
//            $material_manager->setBuzzwordLimit($this->_material_limit_array['buzzword_limit']);
//        }
//        $material_manager->select();
//        $material_list = $material_manager->get();
//        $retour .= '<material_list>';
//        if (!$material_list->isEmpty()) {
//            $material_item = $material_list->getFirst();
//            while ($material_item) {
//                $retour .= $material_item->getDataAsXML();
//                $material_item = $material_list->getNext();
//            }
//        }
//        $retour .= '</material_list>';
//        return $retour;
//    }
//
//    public function getFileListFromMaterial ($session_id, $material_id) {
//        return $this->getFileListFromItem($session_id,$material_id);
//    }
//
//    public function getFileListFromItem ($session_id, $item_id) {
//        $session_id = $this->_encode_input($session_id);
//        $item_id = $this->_encode_input($item_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentPortalID($portal_id);
//            $auth_source = $session->getValue('auth_source');
//            $item_manager = $this->_environment->getItemManager();
//            $commsy_item = $item_manager->getItem($item_id);
//            if ( isset($commsy_item) and !empty($commsy_item) ) {
//                $context_id = $commsy_item->getContextID();
//                if ( !empty($context_id) ) {
//                    $this->_environment->setCurrentContextID($context_id);
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !isset($room_item)
//                        or empty($room_item)
//                    ) {
//                        $room_manager = $this->_environment->getPrivateRoomManager();
//                        $room_item = $room_manager->getItem( $context_id);
//                    }
//                    if ( isset($room_item) and !empty($room_item) ) {
//                        if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                            $real_manager = $this->_environment->getManager($commsy_item->getItemType());
//                            if ( isset($real_manager) and !empty($real_manager) ) {
//                                $real_item = $real_manager->getItem($item_id);
//                                if ( isset($real_item) and !empty($real_item) ) {
//                                    $result  = '<file_list>';
//                                    if ( method_exists($real_item,'getFileList') ) {
//                                        $file_list = $real_item->getFileList();
//                                        if (!$file_list->isEmpty()) {
//                                            $file_item = $file_list->getFirst();
//                                            while ($file_item) {
//                                                $result .= $file_item->getDataAsXML();
//                                                $file_item = $file_list->getNext();
//                                            }
//                                        }
//                                    }
//                                    $result .= '</file_list>';
//                                    $result = $this->_encode_output($result);
//                                } else {
//                                    $info = 'ERROR: GET FILE LIST';
//                                    $info_text = 'item ('.$item_id.') does not exists';
//                                    $result = new SoapFault($info,$info_text);
//                                }
//                            } else {
//                                $info = 'ERROR: GET FILE LIST';
//                                $info_text = 'lost item type of the item ('.$item_id.')';
//                                $result = new SoapFault($info,$info_text);
//                            }
//                        } else {
//                            $info = 'ERROR: GET FILE LIST';
//                            $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the item ('.$item_id.')';
//                            $result = new SoapFault($info,$info_text);
//                        }
//                    } else {
//                        $info = 'ERROR: GET FILE LIST';
//                        $info_text = 'context ('.$context_id.') of item ('.$item_id.') does not exits';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: GET FILE LIST';
//                    $info_text = 'context of item ('.$item_id.') lost';
//                    $result = new SoapFault($info,$info_text);
//                }
//            } else {
//                $info = 'ERROR: GET FILE LIST';
//                $info_text = 'material id ('.$item_id.') does not exist';
//                $result = new SoapFault($info,$info_text);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: GET FILE LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getSectionListFromMaterial ($session_id, $material_id) {
//        $session_id = $this->_encode_input($session_id);
//        $material_id = $this->_encode_input($material_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentPortalID($portal_id);
//            $auth_source = $session->getValue('auth_source');
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_item = $material_manager->getItem($material_id);
//            if ( isset($material_item) and !empty($material_item) ) {
//                $context_id = $material_item->getContextID();
//                if ( !empty($context_id) ) {
//                    $this->_environment->setCurrentContextID($context_id);
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !isset($room_item)
//                        or empty($room_item)
//                    ) {
//                        $room_manager = $this->_environment->getPrivateRoomManager();
//                        $room_item = $room_manager->getItem($context_id);
//                    }
//                    if ( isset($room_item) and !empty($room_item) ) {
//                        if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                            $result  = '<section_list>';
//                            $section_list = $material_item->getSectionList();
//                            if (!$section_list->isEmpty()) {
//                                $section_item = $section_list->getFirst();
//                                while ($section_item) {
//                                    $result .= $section_item->getDataAsXML();
//                                    $section_item = $section_list->getNext();
//                                }
//                            }
//                            $result .= '</section_list>';
//                            $result = $this->_encode_output($result);
//                        } else {
//                            $info = 'ERROR: GET MATERIAL LIST';
//                            $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the material ('.$material_id.')';
//                            $result = new SoapFault($info,$info_text);
//                        }
//                    } else {
//                        $info = 'ERROR: GET MATERIAL LIST';
//                        $info_text = 'context ('.$context_id.') of material ('.$material_id.') does not exits';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: GET MATERIAL LIST';
//                    $info_text = 'context of material ('.$material_id.') lost';
//                    $result = new SoapFault($info,$info_text);
//                }
//            } else {
//                $info = 'ERROR: GET MATERIAL LIST';
//                $info_text = 'material id ('.$material_id.') does not exist';
//                $result = new SoapFault($info,$info_text);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: GET MATERIAL LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getFileItem ($session_id, $file_id) {
//        $session_id = $this->_encode_input($session_id);
//        $file_id = $this->_encode_input($file_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $file_manager = $this->_environment->getFileManager();
//            $file_item = $file_manager->getItem($file_id);
//            if ( isset($file_item) and !empty($file_item) ) {
//                $context_id = $file_item->getContextID();
//                if ( !empty($context_id) ) {
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !isset($room_item)
//                        or empty($room_item)
//                    ) {
//                        $room_manager = $this->_environment->getPrivateRoomManager();
//                        $room_item = $room_manager->getItem($context_id);
//                    }
//                    if ( isset($room_item) and !empty($room_item) ) {
//                        if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                            $file_item->setPortalID($portal_id);
//                            $result = $file_item->getDataAsXML(true);
//                            $result = $this->_encode_output($result);
//                        } else {
//                            $info = 'ERROR: GET FILE ITEM';
//                            $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the file ('.$file_id.')';
//                            $result = new SoapFault($info,$info_text);
//                        }
//                    } else {
//                        $info = 'ERROR: GET FILE ITEM';
//                        $info_text = 'context ('.$context_id.') of file ('.$file_id.') does not exits';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: GET FILE ITEM';
//                    $info_text = 'context of file ('.$file_id.') lost';
//                    $result = new SoapFault($info,$info_text);
//                }
//            } else {
//                $info = 'ERROR: GET FILE ITEM';
//                $info_text = 'file id ('.$file_id.') does not exist';
//                $result = new SoapFault($info,$info_text);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: GET FILE ITEM';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function deleteFileItem ($session_id, $file_id) {
//        $session_id = $this->_encode_input($session_id);
//        $file_id = $this->_encode_input($file_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $file_manager = $this->_environment->getFileManager();
//            $file_item = $file_manager->getItem($file_id);
//            if ( isset($file_item) and !empty($file_item) ) {
//                $context_id = $file_item->getContextID();
//                if ( !empty($context_id) ) {
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !isset($room_item)
//                        or empty($room_item)
//                    ) {
//                        $room_manager = $this->_environment->getPrivateRoomManager();
//                        $room_item = $room_manager->getItem($context_id);
//                    }
//                    if ( isset($room_item) and !empty($room_item) ) {
//                        if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                            if ( $file_item->mayEditByUserID($user_id,$auth_source) ) {
//                                $file_item->delete();
//                                $result = 'success';
//                                $result = $this->_encode_output($result);
//                            } else {
//                                $info = 'ERROR: DELETE FILE ITEM';
//                                $info_text = 'user_id ('.$user_id.') don\'t have the permission to delete the file ('.$file_id.')';
//                                $result = new SoapFault($info,$info_text);
//                            }
//                        } else {
//                            $info = 'ERROR: DELETE FILE ITEM';
//                            $info_text = 'user_id ('.$user_id.') don\'t have the permission to enter the room ('.$room_item->getTitle().')';
//                            $result = new SoapFault($info,$info_text);
//                        }
//                    } else {
//                        $info = 'ERROR: DELETE FILE ITEM';
//                        $info_text = 'context ('.$context_id.') of file ('.$file_id.') does not exits';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: DELETE FILE ITEM';
//                    $info_text = 'context of file ('.$file_id.') lost';
//                    $result = new SoapFault($info,$info_text);
//                }
//            } else {
//                $info = 'ERROR: DELETE FILE ITEM';
//                $info_text = 'file id ('.$file_id.') does not exist';
//                $result = new SoapFault($info,$info_text);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: DELETE FILE ITEM';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function addPrivateRoomMaterialList ($session_id, $material_list_xml) {
//        $session_id = $this->_encode_input($session_id);
//        $result_array = array();
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $room_manager = $this->_environment->getPrivateRoomManager();
//            $room_item_id = $room_manager->getItemIDOfRelatedOwnRoomForUser($user_id,$auth_source,$portal_id);
//            if ( isset($room_item_id) and !empty($room_item_id) ) {
//                $this->_environment->setCurrentContextID($room_item_id);
//                $material_xml_object = simplexml_load_string($material_list_xml);
//                $user_manager = $this->_environment->getUserManager();
//                $user_manager->setContextLimit($room_item_id);
//                $user_manager->setUserIDLimit($user_id);
//                $user_manager->setAuthSourceLimit($auth_source);
//                $user_manager->select();
//                $user_list = $user_manager->get();
//                if ($user_list->getCount() == 1) {
//                    $user_item = $user_list->getFirst();
//                    $material_manager = $this->_environment->getMaterialManager();
//                    foreach ($material_xml_object->material_item as $material_xml_item) {
//                        $material_item = $material_manager->getNewItem();
//                        $material_item->setContextID($room_item_id);
//                        $material_item->setCreatorID($user_item->getItemID());
//                        $material_item->setModifierID($user_item->getItemID());
//                        $title = $this->_encode_input((string)$material_xml_item->title);
//                        if ( isset($title) and !empty($title) ) {
//                            $material_item->setTitle($title);
//                        }
//                        $year = $this->_encode_input((int)$material_xml_item->date->year);
//                        if ( isset($year) and !empty($year) ) {
//                            $material_item->setPublishingDate($year);
//                        }
//                        if ( isset($material_xml_item->author_list) and !empty($material_xml_item->author_list) ) {
//                            $author_list_string = '';
//                            $first = true;
//                            foreach ($material_xml_item->author_list->author_item as $author_xml_item) {
//                                if ($first) {
//                                    $first = false;
//                                } else {
//                                    $author_list_string .= '; ';
//                                }
//                                $author_list_string .= $this->_encode_input((string)$author_xml_item);
//                            }
//                            if ( !empty($author_list_string) ) {
//                                $material_item->setAuthor($author_list_string);
//                            }
//                        }
//
//                        // study_log information
//                        if ( isset($material_xml_item->extras) and !empty($material_xml_item->extras) ) {
//                            $extra_xml_string = $this->_encode_input($material_xml_item->extras->asXML());
//                            $extra_xml_object = simplexml_load_string($extra_xml_string);
//                            $xml = '';
//                            foreach ($extra_xml_object->children() as $key => $extra_xml) {
//                                $extra_xml = $this->_encode_input($extra_xml);
//                                if ( $key == 'study_log' ) {
//                                    $xml .= '<study_log>'.htmlentities($extra_xml, ENT_NOQUOTES, 'UTF-8').'</study_log>';
//                                }
//                            }
//                            if ( !empty($xml) ) {
//                                $extra_array = XML2Array('<extras>'.$xml.'</extras>');
//                                $material_item->setExtraInformation($extra_array);
//                            }
//                        }
//
//                        // bib stuff
//                        $value = $this->_encode_input((string)$material_xml_item->description);
//                        if ( isset($value) and !empty($value) ) {
//                            $value = $this->_htmlTextareaSecurity($value);
//                            $material_item->setDescription($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->label);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setLabel($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->bib_kind);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setBibkind($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->common);
//                        if ( isset($value) and !empty($value) ) {
//                            $value = $this->_htmlTextareaSecurity($value);
//                            $material_item->setBibliographicValues($value);
//                        }
//                        if ( isset($material_xml_item->editor_list) and !empty($material_xml_item->editor_list) ) {
//                            $editor_list_string = '';
//                            $first = true;
//                            foreach ($material_xml_item->editor_list->editor_item as $editor_xml_item) {
//                                if ($first) {
//                                    $first = false;
//                                } else {
//                                    $editor_list_string .= '; ';
//                                }
//                                $editor_list_string .= $this->_encode_input((string)$editor_xml_item);
//                            }
//                            if ( !empty($editor_list_string) ) {
//                                $material_item->setEditor($editor_list_string);
//                            }
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->booktitle);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setBooktitle($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->publisher);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setPublisher($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->edition);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setEdition($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->volume);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setVolume($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->series);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setSeries($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->isbn);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setISBN($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->issn);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setISSN($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->pages);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setPages($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->journal);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setJournal($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->issue);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setIssue($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->university);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setUniversity($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->faculty);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setFaculty($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->thesis_kind);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setThesiskind($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->url);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setURL($value);
//                        }
//                        $value = $this->_encode_input((string)$material_xml_item->url_date);
//                        if ( isset($value) and !empty($value) ) {
//                            $material_item->setURLDate($value);
//                        }
//
//                        $material_item->save();
//                        $item_id = (int)$material_xml_item->item_id;
//                        $result_array[$item_id] = $material_item->getItemID();
//                    }
//                    $result  = '<link_list>'.LF;
//                    foreach ($result_array as $key => $value) {
//                        $result .= '<link>'.LF;
//                        $result .= '<original_id>'.$key.'</original_id>'.LF;
//                        $result .= '<commsy_id>'.$value.'</commsy_id>'.LF;
//                        $result .= '</link>'.LF;
//                    }
//                    $result .= '</link_list>'.LF;
//                    $result = $this->_encode_output($result);
//                } else {
//                    $info = 'ERROR: ADD PRIVATE ROOM MATERIAL LIST';
//                    $info_text = 'user id ('.$user_id.') is not valid';
//                    $result = new SoapFault($info,$info_text);
//                }
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: ADD PRIVATE ROOM MATERIAL LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function addFileForMaterial ($session_id, $material_id, $file_item_xml) {
//        $session_id = $this->_encode_input($session_id);
//        $material_id = $this->_encode_input($material_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_item = $material_manager->getItem($material_id);
//            if ( isset($material_item) and !empty($material_item) ) {
//                $context_id = $material_item->getContextID();
//                if ( !empty($context_id) ) {
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !isset($room_item)
//                        or empty($room_item)
//                    ) {
//                        $room_manager = $this->_environment->getPrivateRoomManager();
//                        $room_item = $room_manager->getItem($context_id);
//                    }
//                    if ( isset($room_item) and !empty($room_item) ) {
//                        if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                            $file_xml_object = simplexml_load_string($file_item_xml);
//                            $file_name = $this->_encode_input((string)$file_xml_object->filesname);
//                            $file_base64 = (string)$file_xml_object->base64;
//                            $disc_manager = $this->_environment->getDiscManager();
//                            $temp_file = $disc_manager->saveFileFromBase64($file_name,$file_base64);
//                            if ( isset($temp_file) and !empty($temp_file) ) {
//                                $file_manager = $this->_environment->getFileManager();
//                                $file_item = $file_manager->getNewItem();
//                                $file_item->setFilename(rawurlencode(rawurldecode(basename($file_name))));
//                                $file_item->setContextID($context_id);
//                                $file_item->setPortalID($portal_id);
//                                $file_item->setTempName($temp_file);
//                                $file_item->save();
//                                $file_id_array = $material_item->getFileIDArray();
//                                $file_id_array[] = $file_item->getFileID();
//                                $material_item->setFileIDArray($file_id_array);
//                                $material_item->save();
//                                unlink($temp_file);
//                                $result = $file_item->getFileID();
//                                $result = $this->_encode_output($result);
//                            } else {
//                                $info = 'ERROR: ADD FILE FOR MATERIAL';
//                                $info_text = 'don\'t have the permission to save the file';
//                                $result = new SoapFault($info,$info_text);
//                            }
//                        } else {
//                            $info = 'ERROR: ADD FILE FOR MATERIAL';
//                            $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the material ('.$material_id.')';
//                            $result = new SoapFault($info,$info_text);
//                        }
//                    } else {
//                        $info = 'ERROR: ADD FILE FOR MATERIAL';
//                        $info_text = 'context ('.$context_id.') of material ('.$material_id.') does not exits';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: ADD FILE FOR MATERIAL';
//                    $info_text = 'context of material ('.$material_id.') lost';
//                    $result = new SoapFault($info,$info_text);
//                }
//            } else {
//                $info = 'ERROR: ADD FILE FOR MATERIAL';
//                $info_text = 'material id ('.$material_id.') does not exist';
//                $result = new SoapFault($info,$info_text);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: ADD FILE FOR MATERIAL';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function linkFileToMaterial ($session_id, $material_id, $file_id) {
//        $session_id = $this->_encode_input($session_id);
//        $material_id = $this->_encode_input($material_id);
//        $file_id = $this->_encode_input($file_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_item = $material_manager->getItem($material_id);
//            if ( isset($material_item) and !empty($material_item) ) {
//                $context_id = $material_item->getContextID();
//                if ( !empty($context_id) ) {
//                    $room_manager = $this->_environment->getRoomManager();
//                    $room_item = $room_manager->getItem($context_id);
//                    if ( !isset($room_item)
//                        or empty($room_item)
//                    ) {
//                        $room_manager = $this->_environment->getPrivateRoomManager();
//                        $room_item = $room_manager->getItem($context_id);
//                    }
//                    if ( isset($room_item) and !empty($room_item) ) {
//                        if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                            $file_manager = $this->_environment->getFileManager();
//                            $file_item = $file_manager->getItem($file_id);
//                            if ( isset($file_item) and !empty($file_item) ) {
//                                $context_id2 = $file_item->getContextID();
//                                if ( !empty($context_id2) ) {
//                                    if ( $context_id == $context_id2) {
//                                        $user_manager = $this->_environment->getUserManager();
//                                        $user_manager->setContextLimit($context_id);
//                                        $user_manager->setUserIDLimit($user_id);
//                                        $user_manager->setAuthSourceLimit($auth_source);
//                                        $user_manager->select();
//                                        $user_list = $user_manager->get();
//                                        if ($user_list->getCount() == 1) {
//                                            $user_item = $user_list->getFirst();
//                                            if ( $material_item->mayEdit($user_item) ) {
//                                                $file_item->setPortalID($portal_id);
//                                                $file_manager = $this->_environment->getFileManager();
//                                                $new_file_item = $file_manager->getNewItem();
//                                                $new_file_item->setFilename(rawurlencode(rawurldecode($file_item->getFilename())));
//                                                $new_file_item->setContextID($context_id);
//                                                $new_file_item->setPortalID($portal_id);
//                                                $new_file_item->setTempName($file_item->getDiskFilename());
//                                                $new_file_item->save();
//                                                $file_id_array = $material_item->getFileIDArray();
//                                                $file_id_array[] = $new_file_item->getFileID();
//                                                $material_item->setFileIDArray($file_id_array);
//                                                $material_item->save();
//                                                $result = $file_item->getFileID();
//                                                $result = $this->_encode_output($result);
//                                            } else {
//                                                $info = 'ERROR: LINK FILE TO MATERIAL';
//                                                $info_text = 'user with user_id ('.$user_id.') is not allowed to edit material ('.$material_id.')';
//                                                $result = new SoapFault($info,$info_text);
//                                            }
//                                        } else {
//                                            $info = 'ERROR: LINK FILE TO MATERIAL';
//                                            $info_text = 'multiple users with user_id ('.$user_id.') in context ('.$context_id.')';
//                                            $result = new SoapFault($info,$info_text);
//                                        }
//                                    } else {
//                                        $info = 'ERROR: LINK FILE TO MATERIAL';
//                                        $info_text = 'context ('.$context_id2.') of file ('.$file_id.') and context ('.$context_id.') of material are not equal';
//                                        $result = new SoapFault($info,$info_text);
//                                    }
//                                } else {
//                                    $info = 'ERROR: LINK FILE TO MATERIAL';
//                                    $info_text = 'context ('.$context_id2.') of file ('.$file_id.') does not exits';
//                                    $result = new SoapFault($info,$info_text);
//                                }
//                            } else {
//                                $info = 'ERROR: LINK FILE TO MATERIAL';
//                                $info_text = 'file id ('.$file_id.') does not exist';
//                                $result = new SoapFault($info,$info_text);
//                            }
//                        } else {
//                            $info = 'ERROR: LINK FILE TO MATERIAL';
//                            $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the material ('.$material_id.')';
//                            $result = new SoapFault($info,$info_text);
//                        }
//                    } else {
//                        $info = 'ERROR: LINK FILE TO MATERIAL';
//                        $info_text = 'context ('.$context_id.') of material ('.$material_id.') does not exits';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: LINK FILE TO MATERIAL';
//                    $info_text = 'context of material ('.$material_id.') lost';
//                    $result = new SoapFault($info,$info_text);
//                }
//            } else {
//                $info = 'ERROR: LINK FILE TO MATERIAL';
//                $info_text = 'material id ('.$material_id.') does not exist';
//                $result = new SoapFault($info,$info_text);
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: ADD FILE FOR MATERIAL';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getItemsSinceLastLogin ($session_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($portal_id);
//            $portal_item = $this->_environment->getCurrentPortalItem();
//            $result  = '<portal_item>';
//            $result .= '<name>'.$portal_item->getTitle().'</name>'.LF;
//            $result .= '<item_id>'.$portal_item->getItemID().'</item_id>'.LF;
//            $result .= '<type>ROOM_TYPE_PORTAL</type>'.LF;
//            $auth_source = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($portal_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ($user_list->getCount() == 1) {
//                $user_item = $user_list->getFirst();
//                $result .= '<room_list>'.LF;
//                $room_list = $user_item->getRelatedCommunityList();
//                $room_list->addList($user_item->getRelatedProjectList());
//                $room_item = $room_list->getFirst();
//                $item_manager = $this->_environment->getItemManager();
//                while ($room_item) {
//                    $result .= '<room_item>'.LF;
//                    $result .= '<name><![CDATA['.$room_item->getTitle().']]></name>'.LF;
//                    $result .= '<item_id><![CDATA['.$room_item->getItemID().']]></item_id>'.LF;
//                    if ( $room_item->getItemType() == CS_PROJECT_TYPE ) {
//                        $result .= '<type><![CDATA[ROOM_TYPE_PROJECT]]></type>'.LF;
//                    } elseif ( $room_item->getItemType() == CS_GROUP_TYPE ) {
//                        $result .= '<type><![CDATA[ROOM_TYPE_GROUP]]></type>'.LF;
//                    } else {
//                        $result .= '<type><![CDATA[ROOM_TYPE_COMMUNITY]]></type>'.LF;
//                    }
//                    $item_manager->setContextLimit($room_item->getItemID());
//                    $item_manager->setUserUserIDLimit($user_item->getUserID());
//                    $item_manager->setUserAuthSourceIDLimit($user_item->getAuthSource());
//                    $item_manager->setUserSinceLastloginLimit();
//                    $item_manager->setOutputLimitToXML();
//                    $item_manager->select();
//                    $item_list_xml = $item_manager->get();
//                    if ($item_list_xml != '<items_list></items_list>') {
//                        $item_list_xml = str_replace('<deletion_date></deletion_date>','',$item_list_xml);
//                        $item_list_xml = str_replace('<deleter_id></deleter_id>','',$item_list_xml);
//                        $item_list_xml = preg_replace('~<context_id>[\d]*</context_id>~u','',$item_list_xml);
//                        $result .= $item_list_xml;
//                    }
//                    $result .= '</room_item>'.LF;
//                    $room_item = $room_list->getNext();
//                }
//                $result .= '</room_list>'.LF;
//            } else {
//                $info = 'ERROR: GET ITEMS SINCE LASTLOGIN';
//                $info_text = 'multiple users with user_id ('.$user_id.') in context ('.$portal_id.')';
//                $result = new SoapFault($info,$info_text);
//            }
//            $result .= '</portal_item>';
//            $result = $this->_encode_output($result);
//        } else {
//            $info = 'ERROR: GET ITEMS SINCE LASTLOGIN';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    private function _checkUserInfoForCompletness($user_info) {
//        $valid = true;
//    }
//
//    public function addMaterialLimit($session_id, $key, $value) {
//        $key = $this->_encode_input($key);
//        $value = $this->_encode_input($value);
//        $session_id = $this->_encode_input($session_id);
//        $this->_material_limit_array[$key] = $value;
//        $session_manager = $this->_environment->getSessionManager();
//        $session = $session_manager->get($session_id);
//        $session->setValue('material_limit_array', $this->_material_limit_array);
//        $session_manager->save($session);
//        return $this->_encode_output($this->_material_limit_array);
//    }
//
//    public function getBuzzwordList($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        $context_id = $this->_encode_input($context_id);
//        if($this->_isAccessValid($session_id, $context_id)) {
//            $retour = '';
//            $buzzword_manager = $this->_environment->getLabelManager();
//            $buzzword_manager->resetLimits();
//            $buzzword_manager->setContextLimit($context_id);
//            $buzzword_manager->setTypeLimit('buzzword');
//            $buzzword_manager->select();
//            $buzzword_list = $buzzword_manager->get();
//            $retour .= '<buzzword_list>';
//            if (!$buzzword_list->isEmpty()) {
//                $buzzword_item = $buzzword_list->getFirst();
//                while ($buzzword_item) {
//                    $retour .= $buzzword_item->getDataAsXML();
//                    $buzzword_item = $buzzword_list->getNext();
//                }
//            }
//            $retour .= '</buzzword_list>';
//            $retour = $this->_encode_output($retour);
//            return $retour;
//        } else {
//            return $this->_soap_fault;
//        }
//    }
//
//    public function getLabelList($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        $context_id = $this->_encode_input($context_id);
//        if($this->_isAccessValid($session_id, $context_id)) {
//            $label_manager =  $this->_environment->getLabelManager();
//            $label_manager->resetLimits();
//            $label_manager->setContextLimit($context_id);
//            $label_manager->setTypeLimit('label');
//            $label_manager->select();
//            $label_list = $label_manager->get();
//            $retour = '<label_list>';
//            if (!$label_list->isEmpty()) {
//                $label_item = $label_list->getFirst();
//                while ($label_item) {
//                    $retour .= $label_item->getDataAsXML();
//                    $label_item = $label_list->getNext();
//                }
//            }
//            $retour .= '</label_list>';
//            $retour = $this->_encode_output($retour);
//            return $retour;
//        } else {
//            return $this->_soap_fault;
//        }
//    }
//
//    public function getGroupList($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        $context_id = $this->_encode_input($context_id);
//        if($this->_isAccessValid($session_id, $context_id)) {
//            $retour = '';
//            $group_manager =  $this->_environment->getGroupManager();
//            $group_manager->resetLimits();
//            $group_manager->setContextLimit($context_id);
//            $group_manager->select();
//            $group_list = $group_manager->get();
//            $retour .= '<group_list>';
//            if (!$group_list->isEmpty()) {
//                $group_item = $group_list->getFirst();
//                while ($group_item) {
//                    $retour .= $group_item->getDataAsXML();
//                    $group_item = $group_list->getNext();
//                }
//            }
//            $retour .= '</group_list>';
//            $retour = $this->_encode_output($retour);
//            return $retour;
//        } else {
//            return $this->_soap_fault;
//        }
//    }
//
//    public function getTopicList($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        $context_id = $this->_encode_input($context_id);
//        if($this->_isAccessValid($session_id, $context_id)) {
//            $retour = '';
//            $topic_manager =  $this->_environment->getTopicManager();
//            $topic_manager->resetLimits();
//            $topic_manager->setContextLimit($context_id);
//            $topic_manager->select();
//            $topic_list = $topic_manager->get();
//            $retour .= '<topic_list>';
//            if (!$topic_list->isEmpty()) {
//                $topic_item = $topic_list->getFirst();
//                while ($topic_item) {
//                    $retour .= $topic_item->getDataAsXML();
//                    $topic_item = $topic_list->getNext();
//                }
//            }
//            $retour .= '</topic_list>';
//            $retour = $this->_encode_output($retour);
//            return $retour;
//        } else {
//            return $this->_soap_fault;
//        }
//    }
//
//    private function _isAccessValid($session_id, $context_id) {
//        $result = false;
//        $this->_soap_fault = new SoapFault('UNKNOWN','an unknown error occured');
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//            $room_manager = $this->_environment->getRoomManager();
//            $room_item = $room_manager->getItem($context_id);
//            if ( !isset($room_item)
//                or empty($room_item)
//            ) {
//                $room_manager = $this->_environment->getPrivateRoomManager();
//                $room_item = $room_manager->getItem($context_id);
//            }
//            $room_context_id = $room_item->getContextID();
//            if ( $room_context_id != $portal_id ) {
//                $result = false;
//                $info = 'ERROR: GET MATERIAL LIST';
//                $info_text = 'room with id ('.$context_id.') is not on the commsy portal form session with id ('.$portal_id.')';
//                $this->_soap_fault = new SoapFault($info,$info_text);
//            } elseif ( !$room_item->mayEnterByUserID($user_id,$auth_source) ) {
//                $result = false;
//                $info = 'ERROR: GET MATERIAL LIST';
//                $info_text = 'user with user_id ('.$user_id.') is not allowed to enter the room with id ('.$context_id.')';
//                $this->_soap_fault = new SoapFault($info,$info_text);
//            } else {
//                $result = true;
//                $this->_soap_fault = null;
//            }
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $result = false;
//            $info = 'ERROR: GET MATERIAL LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $this->_soap_fault = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function _log ($mod,$fct,$params) {
//        $array = array();
//        $array['iid'] = -1;
//
//        if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
//            $array['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
//        } else {
//            $array['user_agent'] = 'SOAP: No Info';
//        }
//        if ( !empty($_POST) ) {
//            $array['post_content'] = implode(',',$_POST);
//        } else {
//            $array['post_content'] = '';
//        }
//
//        $array['remote_addr']    = $_SERVER['REMOTE_ADDR'];
//        $array['script_name']    = $_SERVER['SCRIPT_NAME'];
//        $array['query_string']   = $_SERVER['QUERY_STRING'];
//        $array['request_method'] = $_SERVER['REQUEST_METHOD'];
//
//        $current_user = $this->_environment->getCurrentUserItem();
//        $array['user_item_id'] = $current_user->getItemID();
//        $array['user_user_id'] = $current_user->getUserID();
//        unset($current_user);
//
//        $array['context_id'] = $this->_environment->getCurrentContextID();
//        $array['module'] = $mod;
//        $array['function'] = $fct;
//        $array['parameter_string'] = $params;
//
//        $log_manager = $this->_environment->getLogManager();
//        $log_manager->saveArray($array);
//    }
//
//    public function _log_in_file ($params) {
//        global $c_commsy_path_file;
//        if ( !file_exists($c_commsy_path_file . '/var/soap.log') ) {
//            $logFileName = $c_commsy_path_file . '/var/soap.log';
//            $logFileHandle = fopen($logFileName, 'w');
//            fclose($logFileHandle);
//        }
//        $file_contents = file_get_contents($c_commsy_path_file . '/var/soap.log');
//        foreach ($params as $param) {
//            $file_contents =  $file_contents . "\n" . time() . ' - ' . $param[0] . ' - ' . $param[1];
//        }
//        file_put_contents($c_commsy_path_file . '/var/soap.log', $file_contents);
//    }
//
//    public function getUserInfo ($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            $user_info = '';
//            if ($user_list->getCount() == 1) {
//                $user_item = $user_list->getFirst();
//                $user_info = $user_item->getDataAsXML();
//            }
//            $result = $this->_encode_output($user_info);
//        } else {
//            $info = 'ERROR: GET USER INFO';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getRSSUrl ($session_id) {
//        $retour = '';
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                $user_priv_item = $user_item->getRelatedPrivateRoomUserItem();
//                if ( isset($user_priv_item) ) {
//                    $hash_manager = $this->_environment->getHashManager();
//                    $retour = $hash_manager->getRSSHashForUser($user_priv_item->getItemID());
//                    unset($hash_manager);
//                    if ( !empty($retour) ) {
//                        global $c_commsy_domain, $c_commsy_url_path;
//                        $retour = $c_commsy_domain.$c_commsy_url_path.'/rss.php?cid='.$user_priv_item->getContextID().'&hid='.$retour;
//                        $result = $this->_encode_output($retour);
//                    } else {
//                        $info = 'ERROR: GET RSS URL';
//                        $info_text = 'rss hash is empty ('.$user_id.','.$auth_source_id.','.$context_id.')';
//                        $result = new SoapFault($info,$info_text);
//                    }
//                } else {
//                    $info = 'ERROR: GET RSS URL';
//                    $info_text = 'private room user does not exist ('.$user_id.','.$auth_source_id.','.$context_id.')';
//                    $result = new SoapFault($info,$info_text);
//                }
//                unset($user_priv_item);
//                unset($user_item);
//            } else {
//                $info = 'ERROR: GET RSS URL';
//                $info_text = 'database error: user ('.$user_id.','.$auth_source_id.','.$context_id.') not equal';
//                $result = new SoapFault($info,$info_text);
//            }
//            unset($user_list);
//            unset($user_manager);
//            unset($session);
//        } else {
//            $info = 'ERROR: GET RSS URL';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getRoomList ($session_id) {
//        $retour = '';
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $hash_manager = $this->_environment->getHashManager();
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                if ( !empty($user_item) ) {
//                    $this->_environment->setCurrentUserItem($user_item);
//                }
//                $own_room = $user_item->getOwnRoom();
//                $list = $own_room->getCustomizedRoomList();
//                if ( !(isset($list) and $list->isNotEmpty()) ) {
//                    $community_list = $user_item->getRelatedCommunityList();
//                    $project_list = $user_item->getRelatedProjectListForMyArea();
//                    $group_list = $user_item->getRelatedGroupList();
//
//                    $class_factory = $this->_environment->getClassFactory();
//                    include_once('classes/cs_list.php');
//                    $list = new cs_list();
//                    if ( !empty($community_list) and $community_list->isNotEmpty() ) {
//                        $list->addList($community_list);
//                    }
//                    if ( !empty($project_list) and $project_list->isNotEmpty() ) {
//                        $list->addList($project_list);
//                    }
//                    if ( !empty($group_list) and $group_list->isNotEmpty() ) {
//                        $list->addList($group_list);
//                    }
//                }
//                unset($user_item);
//                if ( isset($list) and $list->isNotEmpty() ) {
/*                    $retour = '<?xml version="1.0" encoding="utf-8"?>'.LF;*/
//                    $retour .= '   <list>'.LF;
//
//                    // portal
//                    $item = $this->_environment->getCurrentPortalItem();
//                    $retour .= '      <item>'.LF;
//                    $retour .= '         <title><![CDATA['.$item->getTitle().']]></title>'.LF;
//                    if ( $item->getItemID() > 99 ) {
//                        $retour .= '         <id><![CDATA['.$item->getItemID().']]></id>'.LF;
//                        global $c_commsy_domain, $c_commsy_url_path;
//                        include_once('functions/curl_functions.php');
//                        $retour .= '         <url><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$item->getItemID(),'home','index',array()).']]></url>'.LF;
//                    }
//                    $retour .= '      </item>'.LF;
//                    $retour .= '      <item>'.LF;
//                    $retour .= '         <title>-------------------------------</title>'.LF;
//                    $retour .= '         <id></id>'.LF;
//                    $retour .= '      </item>'.LF;
//
//                    // own room
//                    $item = $own_room;
//                    $retour .= '      <item>'.LF;
//                    $translator = $this->_environment->getTranslationObject();
//                    $retour .= '         <title><![CDATA['.$translator->getMessage($item->getTitle()).']]></title>'.LF;
//                    if ( $item->getItemID() > 99 ) {
//                        $retour .= '         <id><![CDATA['.$item->getItemID().']]></id>'.LF;
//                        global $c_commsy_domain, $c_commsy_url_path;
//                        include_once('functions/curl_functions.php');
//                        $retour .= '         <url><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$item->getItemID(),'home','index',array()).']]></url>'.LF;
//
//                        // rss
//                        if ( $item->isRSSOn() ) {
//                            $own_room_user_item = $item->getOwnerUserItem();
//                            if ( !empty($own_room_user_item) ) {
//                                $rss_hash = $hash_manager->getRSSHashForUser($own_room_user_item->getItemID());
//                                if ( !empty($rss_hash) ) {
//                                    global $c_commsy_domain, $c_commsy_url_path;
//                                    $rss_url = $c_commsy_domain.$c_commsy_url_path.'/rss.php?cid='.$item->getItemID().'&hid='.$rss_hash;
//                                    $retour .= '         <rss><![CDATA['.$rss_url.']]></rss>'.LF;
//                                }
//                                unset($rss_hash);
//                                unset($rss_url);
//                            }
//                        }
//                    }
//                    $retour .= '      </item>'.LF;
//                    $retour .= '      <item>'.LF;
//                    $retour .= '         <title>-------------------------------</title>'.LF;
//                    $retour .= '         <id></id>'.LF;
//                    $retour .= '      </item>'.LF;
//
//                    $item = $list->getFirst();
//                    while ( $item ) {
//                        $retour .= '      <item>'.LF;
//                        $retour .= '         <title><![CDATA['.$item->getTitle().']]></title>'.LF;
//                        if ( $item->getItemID() > 99 ) {
//                            $retour .= '         <id><![CDATA['.$item->getItemID().']]></id>'.LF;
//                            global $c_commsy_domain, $c_commsy_url_path;
//                            include_once('functions/curl_functions.php');
//                            $retour .= '         <url><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$item->getItemID(),'home','index',array()).']]></url>'.LF;
//
//                            // rss
//                            if ( $item->isRSSOn() ) {
//                                $user_room_item = $item->getUserByUserID($user_id,$auth_source_id);
//                                if ( !empty($user_room_item)
//                                    and !empty($hash_manager)
//                                ) {
//                                    $rss_hash = $hash_manager->getRSSHashForUser($user_room_item->getItemID());
//                                    if ( !empty($rss_hash) ) {
//                                        global $c_commsy_domain, $c_commsy_url_path;
//                                        $rss_url = $c_commsy_domain.$c_commsy_url_path.'/rss.php?cid='.$item->getItemID().'&hid='.$rss_hash;
//                                        $retour .= '         <rss><![CDATA['.$rss_url.']]></rss>'.LF;
//                                    }
//                                    unset($rss_hash);
//                                    unset($rss_url);
//                                }
//                            }
//                        }
//                        $retour .= '      </item>'.LF;
//                        $item = $list->getNext();
//                    }
//                    unset($hash_manager);
//                    $retour .= '   </list>'.LF;
//                    unset($list);
//                    $result = $this->_encode_output($retour);
//                }
//                if ( !empty($retour) ) {
//                    $result = $this->_encode_output($retour);
//                }
//                unset($own_room);
//                unset($user_item);
//            } else {
//                $info = 'ERROR: GET ROOM LIST';
//                $info_text = 'database error: user ('.$user_id.','.$auth_source_id.','.$context_id.') not equal';
//                $result = new SoapFault($info,$info_text);
//            }
//            unset($user_list);
//            unset($user_manager);
//            unset($session);
//        } else {
//            $info = 'ERROR: GET ROOM LIST';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function getAuthenticationForWiki ($session_id, $context_id, $user_id) {
//        #$this->_log_in_file(array(array('$user_id', $user_id)));
//        $result = 'notAuthenticated';
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source = $session->getValue('auth_source');
//            $this->_environment->setCurrentContextID($context_id);
//            $context_item = $this->_environment->getCurrentContextItem();
//
//            if ( !empty($auth_source)
//                and !empty($user_id)
//            ) {
//                $user_manager = $this->_environment->getUserManager();
//                $user_manager->setContextLimit($context_id);
//                $user_manager->setUserIDLimit($user_id);
//                $user_manager->setAuthSourceLimit($auth_source);
//                $user_manager->select();
//                $user_list = $user_manager->get();
//                if ( $user_list->getCount() >= 1 ) {
//                    $user_item = $user_list->getFirst();
//                    if ( $user_item->isModerator() ){
//                        $result = 'moderator';
//                    } elseif ( $user_item->isUser() ) {
//                        if ( $context_item->isWikiRoomModWriteAccess() ) {
//                            $result = 'read';
//                        } else {
//                            $result = 'user';
//                        }
//                    }
//                } elseif ( $context_item->isWikiPortalReadAccess() ) {
//                    $portal_id = $session->getValue('commsy_id');
//                    if ( !empty($portal_id) ) {
//                        $user_manager->setContextLimit($portal_id);
//                        $user_manager->setUserIDLimit($user_id);
//                        $user_manager->setAuthSourceLimit($auth_source);
//                        $user_manager->select();
//                        $user_list = $user_manager->get();
//                        if ( $user_list->getCount() == 1 ) {
//                            $user_item = $user_list->getFirst();
//                            if ( $user_item->isUser() ) {
//                                $result = 'read';
//                            }
//                        }
//                    }
//                }
//                unset($user_manager);
//                unset($user_list);
//                unset($user_item);
//            } else {
//                $info = 'ERROR: GET AUTHENTICATION FOR WIKI';
//                $info_text = 'session id ('.$session_id.') is not valid: no auth source id or no user_id';
//                $result = new SoapFault($info,$info_text);
//            }
//        } else {
//            $info = 'ERROR: GET AUTHENTICATION FOR WIKI';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function savePosForItem ($session_id, $item_id, $x, $y) {
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//
//            $item_id = $this->_encode_input($item_id);
//            $item_manager = $this->_environment->getItemManager();
//            $item_type = $item_manager->getItemType($item_id);
//            $manager = $this->_environment->getManager($item_type);
//            $item = $manager->getItem($item_id);
//            if ( $item->mayEditByUserID($user_id,$auth_source) ) {
//                $x = $this->_encode_input($x);
//                $y = $this->_encode_input($y);
//                $item->setPosX($x);
//                $item->setPosY($y);
//                $item->save();
//                $this->_log('material','SOAP:savePosForItem','SID='.$session_id.'&item_id='.$item_id.'&x='.$x.'&y='.$y);
//            } else {
//                $info = 'ERROR: SAVE POS FOR ITEM';
//                $info_text = 'user ('.$user_id.' / '.$auth_source.') is not allowed to edit item ('.$item_id.')';
//                $result = new SoapFault($info,$info_text);
//            }
//            unset($manager);
//            unset($item);
//            unset($session);
//            unset($item_manager);
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: SAVE POS FOR ITEM';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function savePosForLink ($session_id, $item_id, $label_id, $x, $y) {
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $portal_id = $session->getValue('commsy_id');
//            $auth_source = $session->getValue('auth_source');
//
//            $item_id = $this->_encode_input($item_id);
//            $item_manager = $this->_environment->getItemManager();
//            $item_type = $item_manager->getItemType($item_id);
//
//            if ( $item_type == CS_LINKITEM_TYPE ) {
//                $result = $this->savePosForItem($session_id,$item_id,$x,$y);
//            } else {
//                $label_id = $this->_encode_input($label_id);
//                $label_type = $item_manager->getItemType($label_id);
//                if ( $label_type == CS_LINKITEM_TYPE ) {
//                    $result = $this->savePosForItem($session_id,$label_id,$x,$y);
//                } elseif ( $label_type == CS_TAG_TYPE ) {
//                    $link_manager = $this->_environment->getLinkItemManager();
//                    $link_item = $link_manager->getItemByFirstAndSecondID($item_id,$label_id);
//                    if ( isset($link_item) ) {
//                        $result = $this->savePosForItem($session_id,$link_item->getItemID(),$x,$y);
//                    }
//                } else {
//                    $manager = $this->_environment->getLinkManager();
//                    if ( $item_type != CS_BUZZWORD_TYPE ) {
//                        $real_item_id = $item_id;
//                        $real_item_type = $item_type;
//                        $buzz_item_id = $label_id;
//                        $buzz_item_type = $label_type;
//                    } else {
//                        $real_item_id = $label_id;
//                        $real_item_type = $label_type;
//                        $buzz_item_id = $item_id;
//                        $buzz_item_type = $item_type;
//                    }
//                    $manager_real_item = $this->_environment->getManager($real_item_type);
//                    $real_item = $manager_real_item->getItem($real_item_id);
//                    $link_type = 'buzzword_for';
//                    $link_list = $manager->getLinks($link_type,$real_item);
//                    foreach ( $link_list as $link_item ) {
//                        if ( $link_item['from_item_id'] == $buzz_item_id
//                            or $link_item['to_item_id'] == $buzz_item_id
//                        ) {
//                            $x = $this->_encode_input($x);
//                            $y = $this->_encode_input($y);
//                            $link_item['x'] = $x;
//                            $link_item['y'] = $y;
//                            $manager->savePos($link_item);
//                            $this->_log('material','SOAP:savePosForLink','SID='.$session_id.'&item_id='.$label_id.'&item_id='.$label_id.'&x='.$x.'&y='.$y);
//                            break;
//                        }
//                    }
//                }
//            }
//            unset($manager_real_item);
//            unset($real_item);
//            unset($manager);
//            unset($item_manager);
//            $this->_updateSessionCreationDate($session_id);
//        } else {
//            $info = 'ERROR: SAVE POS FOR ITEM';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function deleteWiki ($session_id, $context_id) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $room_manager = $this->_environment->getRoomManager();
//            $room_item = $room_manager->getItem($context_id);
//            $wiki_manager = $this->_environment->getWikiManager();
//            $wiki_manager->deleteWiki($room_item);
//        }
//    }
//
//    public function createWiki ($session_id, $context_id, $settings) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $room_manager = $this->_environment->getRoomManager();
//            $room_item = $room_manager->getItem($context_id);
//
//            $item->setWikiSkin();
//            $item->setWikiEditPW();
//            $item->setWikiAdminPW();
//            $item->setWikiEditPW();
//            $item->setWikiReadPW();
//            $item->setWikiTitle();
//            $item->setWikiShowCommSyLogin();
//            $item->setWikiWithSectionEdit();
//            $item->setWikiWithHeaderForSectionEdit();
//            $item->setWikiEnableFCKEditor();
//            $item->setWikiEnableSearch();
//            $item->setWikiEnableSitemap();
//            $item->setWikiEnableStatistic();
//            $item->setWikiEnableRss();
//            $item->setWikiEnableCalendar();
//            $item->setWikiEnableNotice();
//            $item->setWikiEnableGallery();
//            $item->setWikiEnablePdf();
//            $item->setWikiEnableSwf();
//            $item->setWikiEnableWmplayer();
//            $item->setWikiEnableQuicktime();
//            $item->setWikiEnableYoutubeGoogleVimeo();
//            $item->setWikiEnableDiscussion();
//            //$item->setWikiDiscussionArray();
//            $item->setWikiEnableDiscussionNotification();
//            $item->setWikiEnableDiscussionNotificationGroups();
//
//            $wiki_manager = $this->_environment->getWikiManager();
//            $wiki_manager->deleteWiki($room_item);
//        }
//    }
//
//    public function changeUserEmail($session_id, $email){
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                $user_item->setEmail($email);
//                $user_item->save();
//            }
//        } else {
//            $info = 'ERROR: CHANGE USER EMAIL';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function changeUserEmailAll($session_id, $email){
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                $dummy_user = $user_manager->getNewItem();
//                $dummy_user->setEmail($email);
//                $user_item->changeRelatedUser($dummy_user);
//                $user_item->setEmail($email);
//                $user_item->save();
//            }
//        } else {
//            $info = 'ERROR: CHANGE USER EMAIL ALL';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function changeUserName($session_id, $firstname, $lastname){
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                $dummy_user = $user_manager->getNewItem();
//                $dummy_user->setFirstname($firstname);
//                $dummy_user->setLastname($lastname);
//                $user_item->changeRelatedUser($dummy_user);
//                $user_item->setFirstname($firstname);
//                $user_item->setLastname($lastname);
//                $user_item->save();
//            }
//        } else {
//            $info = 'ERROR: CHANGE USER EMAIL ALL';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function changeUserId($session_id, $new_user_id){
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                $authentication = $this->_environment->getAuthenticationObject();
//                $authentication->changeUserID($new_user_id,$user_item);
//                $session->setValue('user_id',$new_user_id);
//                $session_manager = $this->_environment->getSessionManager();
//                $session_manager->save($session);
//            }
//        } else {
//            $info = 'ERROR: CHANGE USER_ID';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    public function setUserExternalId($session_id, $external_id){
//        $result = true;
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                $dummy_user = $user_manager->getNewItem();
//                $dummy_user->setExternalID($external_id);
//                $user_item->changeRelatedUser($dummy_user);
//                $user_item->setExternalID($external_id);
//                $user_item->save();
//            }
//        } else {
//            $info = 'ERROR: SET USER EXTRA';
//            $info_text = 'session id ('.$session_id.') is not valid';
//            $result = new SoapFault($info,$info_text);
//        }
//        return $result;
//    }
//
//    function logToFile($msg){
//        $fd = fopen('', "a");
//        $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
//        fwrite($fd, $str . "\n");
//        fclose($fd);
//    }
//
//    public function updateLastlogin ($session_id, $tool = 'commsy', $room_id = 0) {
//        $session_id = $this->_encode_input($session_id);
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_manager = $this->_environment->getUserManager();
//            if ( !empty($room_id)
//                and $room_id != $context_id
//            ) {
//                $user_manager->setContextLimit($room_id);
//            } else {
//                $user_manager->setContextLimit($context_id);
//            }
//            $user_manager->setUserIDLimit($user_id);
//            $user_manager->setAuthSourceLimit($auth_source_id);
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            if ( $user_list->getCount() == 1 ) {
//                $user_item = $user_list->getFirst();
//                include_once('functions/date_functions.php');
//                if ( $tool != 'commsy' ) {
//                    $user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),$tool);
//                    $user_item->setChangeModificationOnSave(false);
//                    $user_item->save();
//                }
//                if ( !empty($room_id)
//                    and $room_id != $context_id
//                ) {
//                    $portal_user_item = $user_item->getRelatedCommSyUserItem();
//                    if ( isset($portal_user_item) ) {
//                        if ( $tool != 'commsy' ) {
//                            $portal_user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),$tool);
//                            $portal_user_item->setChangeModificationOnSave(false);
//                            $portal_user_item->save();
//                            unset($portal_user_item);
//                        }
//                    }
//                }
//                return true;
//            } else {
//                return new SoapFault('ERROR: UPDATELASTLOGIN','can not find user ('.$user_id.' | '.$auth_source_id.')!');
//            }
//        } else {
//            return new SoapFault('ERROR: UPDATELASTLOGIN','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function getAGBFromRoom ( $context_id, $language ) {
//        $result = '';
//        $context_id = $this->_encode_input($context_id);
//        $language = $this->_encode_input($language);
//        if ( !empty($context_id) ) {
//            $room_manager = $this->_environment->getRoomManager();
//            $room_item = $room_manager->getItem($context_id);
//            unset($room_manager);
//            if ( !empty($room_item) ) {
//                if ( $room_item->withAGB() ) {
//                    $agb_text_array = $room_item->getAGBTextArray();
//                    $language_array = array_keys($agb_text_array);
//                    if ( !in_array($language,$language_array)
//                        and !in_array(mb_strtoupper($language,'UTF-8'),$language_array)
//                        and !in_array(mb_strtolower($language,'UTF-8'),$language_array)
//                    ) {
//                        $language = 'de';
//                    }
//                    include_once('functions/text_functions.php');
//                    $result = $agb_text_array[cs_strtoupper($language)];
//                } else {
//                    $result = new SoapFault('ERROR: getAGBFromRoom','agbs in room ('.$context_id.') are switched off.');
//                }
//            } else {
//                $result = new SoapFault('ERROR: getAGBFromRoom','Context-ID ('.$context_id.') not valid!');
//            }
//        } else {
//            $result = new SoapFault('ERROR: getAGBFromRoom','context_id is empty!');
//        }
//        return $result;
//    }
//
//
//
//    // ----------------------------------------
//    //  Additional methods for Typo3 connection
//    // ----------------------------------------
//
//    public function getActiveRoomListForUser($session_id, $portal_id, $count) {
//        if($this->_isSessionValid($session_id)) {
//            // TODO: check for authenticated user id
//            #if ($this->_isSessionActive('guest',$portal_id)) {
//            $room_manager = $this->_environment->getRoomManager();
//            $room_manager->setContextLimit($portal_id);
//            $room_manager->setRoomTypeLimit(CS_PROJECT_TYPE);
//            $room_manager->setOrder('activity_rev');
//            $room_manager->setIntervalLimit(0,$count);
//            $room_manager->select();
//            $test = $room_manager->getLastQuery();
//            $room_list = $room_manager->get();
//
//
//            $room_item = $room_list->getFirst();
//            $xml = "<room_list>\n";
//            while($room_item) {
//                $xml .= $room_item->getXMLData();
//                $room_item = $room_list->getNext();
//            }
//            $xml .= "</room_list>";
//            $xml = $this->_encode_output($xml);
//            #} else {
//            #   return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
//            #}
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//        return $xml;
//    }
//
//
//    // ----------------------------------------
//    //  Additional methods for iOS application
//    // ----------------------------------------
//
//    public function authenticateForApp ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
//        $user_id = $this->_encode_input($user_id);
//        $password = $this->_encode_input($password);
//        $portal_id = $this->_encode_input($portal_id);
//        if ( !empty($auth_source_id) and $auth_source_id != 0 ) {
//            $auth_source_id = $this->_encode_input($auth_source_id);
//        }
//        $result = '';
//
//        $info = 'ERROR';
//        $info_text = 'default-error';
//        if ( empty($user_id) or empty($password) ) {
//            $info = 'ERROR';
//            $info_text = 'user_id or password lost';
//        } else {
//            if ( !isset($this->_environment) ) {
//                $info = 'ERROR';
//                $info_text = 'environment lost';
//            } else {
//                $this->_environment->setCurrentContextID($portal_id);
//                $authentication = $this->_environment->getAuthenticationObject();
//                if ( isset($authentication) ) {
//                    if ($authentication->isAccountGranted($user_id,$password,$auth_source_id)) {
//                        if ($this->_isSessionActiveForApp($user_id,$portal_id)) {
//                            $result = $this->_getActiveSessionIDForApp($user_id,$portal_id);
//                            if ( empty($result) ) {
//                                $info = 'ERROR';
//                                $info_text = 'no session id from session manager -> database error';
//                            }
//                        } else {
//                            // make session
//                            include_once('classes/cs_session_item.php');
//                            $session = new cs_session_item();
//                            $session->createSessionID($user_id);
//                            // save portal id in session to be sure, that user didn't
//                            // switch between portals
//                            $session->setValue('user_id',$user_id);
//                            $session->setValue('commsy_id',$portal_id);
//                            if ( empty($auth_source_id) or $auth_source_id == 0 ) {
//                                $auth_source_id = $authentication->getAuthSourceItemID();
//                            }
//                            $session->setValue('auth_source',$auth_source_id);
//                            $session->setValue('cookie','0');
//                            $session->setSoapSession();
//
//                            // save session
//                            $session_manager = $this->_environment->getSessionManager();
//                            $session_manager->save($session);
//
//                            $result = $session->getSessionID();
//
//
//                        }
//                    } else {
//                        $info = 'ERROR';
//                        $info_text = 'account not granted '.$user_id.' - '.$password.' - '.$portal_id;
//                    }
//                } else {
//                    $info = 'ERROR';
//                    $info_text = 'authentication object lost';
//                }
//            }
//        }
//
//        if ( empty($result) and !empty($info) ) {
//            $result = new SoapFault($info,$info_text);
//        } else {
//            $result = $this->_encode_output($result);
//        }
//        return $result;
//    }
//
//    private function _isSessionActiveForApp ($user_id, $portal_id) {
//        $retour = false;
//        if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
//            $retour = true;
//        } else {
//            $session_id = $this->_getActiveSessionIDForApp($user_id,$portal_id);
//            if ( !empty($session_id) ) {
//                $retour = true;
//            }
//        }
//        return $retour;
//    }
//
//    private function _getActiveSessionIDForApp ($user_id, $portal_id) {
//        $retour = '';
//        if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
//            $retour = $this->_session_id_array[$portal_id][$user_id];
//        } else {
//            $session_manager = $this->_environment->getSessionManager();
//            $retour = $session_manager->getActiveSOAPSessionIDForApp($user_id,$portal_id);
//            if ( !empty($retour) ) {
//                $this->_session_id_array[$portal_id][$user_id] = $retour;
//                $this->_updateSessionCreationDate($retour);
//            }
//        }
//
//        return $retour;
//    }
//
//    public function getPortalConfig($portal_id)
//    {
//        $this->_environment->setCurrentContextID($portal_id);
//        $portalItem = $this->_environment->getCurrentContextItem();
//
//        $translator = $this->_environment->getTranslationObject();
//
//        $timeArray = array();
//        $timeList = $portalItem->getTimeListRev();
//
//        $timeItem = $timeList->getFirst();
//        while($timeItem) {
//            $timeArray[$timeItem->getItemID()] = $translator->getTimeMessage($timeItem->getTitle());
//
//            $timeItem = $timeList->getNext();
//        }
//
//        $configArray = array(
//            "showRooms" => $portalItem->getShowRoomsOnHome(),
//            "communityRoomCreation" => $portalItem->getCommunityRoomCreationStatus(),
//            "projectRoomLink" => $portalItem->getProjectRoomLinkStatus(),
//            "projectRoomCreation" => $portalItem->getProjectRoomCreationStatus(),
//            "showTime" => $portalItem->showTime(),
//            "timeText" => $timeArray,
//            "timeName" => $portalItem->getTimeNameArray(),
//
//        );
//
//        return $configArray;
//    }
//
//    public function getUserInformation($sessionId, $contextId)
//    {
//        if ($this->_isSessionValid($sessionId)) {
//            $this->_environment->setCurrentContextID($contextId);
//            $this->_environment->setSessionID($sessionId);
//            $session = $this->_environment->getSessionItem();
//            $authSourceId = $session->getValue('auth_source');
//            $userId = $session->getValue('user_id');
//
//            $portalItem = $this->_environment->getCurrentContextItem();
//
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//            $portalUserItem = $userItem->getRelatedPortalUserItem();
//
//            $touAccepted = true;
//            if ($portalUserItem->isUser() && !$portalUserItem->isRoot()) {
//                $userTouDate = $portalUserItem->getAGBAcceptanceDate();
//                $portalTouDate = $portalItem->getAGBChangeDate();
//
//                if ($userTouDate < $portalTouDate && $portalItem->getAGBStatus() == 1) {
//                    $touAccepted = false;
//                }
//            }
//
//            $xml = "<user>\n";
//            $xml .= "   <status><![CDATA[" . $userItem->getStatus() . "]]></status>\n";
//            $xml .= "   <portal_tou_accepted><![CDATA[" . ($touAccepted ? 'yes' : 'no') . "]]></portal_tou_accepted>\n";
//            $xml .= "</user>\n";
//
//            return $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//    }
//
//    public function getPortalRoomListByCountAndSearch($sessionId, $contextId, $start = 0, $count = 10, $search = '', $timeLimit = '', $roomTypeLimit = '', $order = 'title')
//    {
//        if($this->_isSessionValid($sessionId)) {
//            $this->_environment->setSessionID($sessionId);
//
//            $sessionItem = $this->_environment->getSessionItem();
//            $userId = $sessionItem->getValue('user_id');
//            $context_id = $sessionItem->getValue('commsy_id');
//            $authSourceId = $sessionItem->getValue('auth_source');
//
//            $this->_environment->setCurrentContextID($contextId);
//            $translator = $this->_environment->getTranslationObject();
//
//            // get user item
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//
//            $roomManager = $this->_environment->getRoomManager();
//            $roomManager->setContextLimit($contextId);
//
//            if($roomTypeLimit == 'community'){
//                $roomManager->setRoomTypeLimit(CS_COMMUNITY_TYPE);
//            } else if($roomTypeLimit == 'project') {
//                $roomManager->setRoomTypeLimit(CS_PROJECT_TYPE);
//            }
//
//            if($timeLimit){
//                $roomManager->setTimeLimit($timeLimit);
//            }
//
//            if(!empty($search)) {
//                $roomManager->setSearchLimit($search);
//            }
//
//            $portalItem = $this->_environment->getCurrentContextItem();
//            $maxActivity = $portalItem->getMaxRoomActivityPoints();
//
//            global $c_commsy_domain, $c_commsy_url_path;
//            include_once('functions/curl_functions.php');
//
//            $roomListCount = $roomManager->_performQuery('count');
//            $roomManager->setIntervalLimit($start, $count);
//            $roomManager->setOrder($order);
//            $roomManager->select();
//            $roomList = $roomManager->get();
//            $roomListCount = $roomListCount[0]['count'];
//            $xml = "<room_list>\n";
//            $roomItem = $roomList->getFirst();
//            while ($roomItem) {
//                $mayEnter = false;
//                if ($userItem && $roomItem->mayEnter($userItem)) {
//                    $mayEnter = true;
//                }
//
//                // activity
//                $percentage = $roomItem->getActivityPoints();
//
//                if ($maxActivity != 0) {
//                    if (empty($percentage)) {
//                        $percentage = 0;
//                    } else {
//                        $divisior = $maxActivity / 20;
//                        $percentage = max(0, log(($percentage / $divisior) + 1));
//                        $temp = log(($maxActivity / $divisior) + 1);
//                        $percentage = round(($percentage / $temp) * 100, 2);
//                    }
//                } else {
//                    $percentage = 0;
//                }
//
//                $xml .= "<room_item>";
//                $xml .= "<title><![CDATA[".$roomItem->getTitle()."]]></title>\n";
//                $xml .= "<access><![CDATA[".($mayEnter ? "yes" : "no") ."]]></access>\n";
//                $xml .= "<item_id><![CDATA[".$roomItem->getItemID()."]]></item_id>\n";
//                $xml .= "<context_id><![CDATA[".$roomItem->getContextID()."]]></context_id>\n";
//                $xml .= "<link><![CDATA[" . $c_commsy_domain . $c_commsy_url_path . "/" . _curl(false, $roomItem->getItemId(), 'home', 'index', array()) . "]]></link>\n";
//                $xml .= "<room_user><![CDATA[is_room_user]]></room_user>\n";
//                $xml .= "<membership_pending><![CDATA[membership_is_not_pending]]></membership_pending>\n";
//                $xml .= "<contact><![CDATA[".$roomItem->getContactPersonString()."]]></contact>\n";
//                $xml .= "<activity><![CDATA[".$percentage."]]></activity>\n";
//                $xml .= "</room_item>\n";
//
//                $roomItem = $roomList->getNext();
//            }
//            $xml .= "</room_list>";
//
//            return array("count" => $roomListCount, "xml" => $this->_encode_output($xml));
//
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//    }
//
//    public function getPortalRoomListGuest($sessionId, $portalId)
//    {
//        if ($this->_isSessionValid($sessionId)) {
//            $portalManager = $this->_environment->getPortalManager();
//            $portalItem = $portalManager->getItem($portalId);
//
//            $showRooms = $portalItem->getShowRoomsOnHome();
//
//            $roomManager = $this->_environment->getRoomManager();
//            $roomManager->setContextLimit($portalId);
//
//            $roomManager->select();
//            $roomList = $roomManager->get();
//
//            $xml = "<room_list>\n";
//            $roomItem = $roomList->getFirst();
//            while ($roomItem) {
//                $xml .= "<room_item>";
//                $xml .= "<title><![CDATA[".$roomItem->getTitle()."]]></title>\n";
//                $xml .= "<item_id><![CDATA[".$roomItem->getItemID()."]]></item_id>\n";
//                $xml .= "<context_id><![CDATA[".$roomItem->getContextID()."]]></context_id>\n";
//                $xml .= "<room_user><![CDATA[is_room_user]]></room_user>\n";
//                $xml .= "<membership_pending><![CDATA[membership_is_not_pending]]></membership_pending>\n";
//                $xml .= "<contact><![CDATA[".$roomItem->getContactPersonString()."]]></contact>\n";
//                $xml .= "</room_item>\n";
//
//                $roomItem = $roomList->getNext();
//            }
//
//            $xml .= "</room_list>";
//            return $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//    }
//
//    public function getPortalRoomList($session_id, $portal_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            #$user_room_list = $user_item->getRelatedProjectList();
//            $room_manager = $this->_environment->getRoomManager();
//            #$room_manager->setContextLimit($portal_id);
//            #$room_manager->setRoomTypeLimit(CS_PROJECT_TYPE);
//            #$room_manager->setOrder('activity_rev');
//            #$room_manager->select();
//            #$user_room_list = null;
//            $room_list = $room_manager->getRelatedRoomListForUser($user_item);
//
//            #$room_list = $user_room_list;
//
//            $room_item = $room_list->getFirst();
//            $xml = "<room_list>\n";
//            while($room_item) {
//                #$user_room_item = $user_room_list->getFirst();
//                #$is_room_user = false;
//                #while($user_room_item){
//                #   if($user_room_item->getItemID() == $room_item->getItemID()){
//                #      $is_room_user = true;
//                #   }
//                #   $user_room_item = $user_room_list->getNext();
//                #}
//                $is_room_user = true;
//
//                #$is_membership_pending = false;
//                #if($is_room_user){
//                #   $room_user = $room_item->getUserByUserID($user_id, $auth_source_id);
//                #   if($room_user->getStatus() == '1'){
//                #      $is_membership_pending = true;
//                #   }
//                #}
//                $is_membership_pending = false;
//
//                $xml .= "<room_item>";
//                $xml .= "<title><![CDATA[".$room_item->getTitle()."]]></title>\n";
//                $xml .= "<item_id><![CDATA[".$room_item->getItemID()."]]></item_id>\n";
//                $xml .= "<context_id><![CDATA[".$room_item->getContextID()."]]></context_id>\n";
//                if($is_room_user and !$is_membership_pending){
//                    $xml .= "<room_user><![CDATA[is_room_user]]></room_user>\n";
//                } else {
//                    $xml .= "<room_user><![CDATA[is_not_room_user]]></room_user>\n";
//                }
//                if($is_membership_pending){
//                    $xml .= "<membership_pending><![CDATA[membership_is_pending]]></membership_pending>\n";
//                } else {
//                    $xml .= "<membership_pending><![CDATA[membership_is_not_pending]]></membership_pending>\n";
//                }
//                $xml .= "<contact><![CDATA[".$room_item->getContactPersonString()."]]></contact>\n";
//                $xml .= "</room_item>\n";
//                $room_item = $room_list->getNext();
//            }
//            $xml .= "</room_list>";
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//        return $xml;
//    }
//
//    public function getPortalList() {
//        $portal_manager = $this->_environment->getPortalManager();
//        $portal_manager->select();
//        $portal_list = $portal_manager->get();
//        $xml = "<portal_list>\n";
//        $portal_item = $portal_list->getFirst();
//        while($portal_item) {
//            $xml .= "<portal_item>\n";
//            $xml .= "<portal_id><![CDATA[".$portal_item->getItemID()."]]></portal_id>";
//            $xml .= "<portal_title><![CDATA[".$portal_item->getTitle()."]]></portal_title>";
//            $xml .= "</portal_item>\n";
//            $portal_item = $portal_list->getNext();
//        }
//        $xml .= "</portal_list>";
//        $xml = $this->_encode_output($xml);
//        return $xml;
//    }
//
//
//    // Dates
//
//
//    public function saveDate($session_id, $context_id, $item_id, $title, $place, $description, $startingDate, $startingTime, $endingDate, $endingTime, $uploadFiles, $deleteFiles) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $dates_manager = $this->_environment->getDatesManager();
//            debugToFile($item_id);
//            if($item_id != 'NEW'){
//                $date_item = $dates_manager->getItem($item_id);
//            } else {
//                debugToFile('is NEW');
//                $date_item = $dates_manager->getNewItem();
//                $date_item->setContextID($context_id);
//                $date_item->setCreatorItem($user_item);
//                $date_item->setCreationDate(getCurrentDateTimeInMySQL());
//            }
//            $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
//            $date_item->setTitle($title);
//            $place = html_entity_decode($place, ENT_COMPAT, 'UTF-8');
//            $date_item->setPlace($place);
//            $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
//            $date_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
//            debugToFile($description);
//            $date_item->setStartingDay($startingDate);
//            $date_item->setStartingTime($startingTime);
//            $date_item->setDateTime_start($startingDate.' '.$startingTime);
//            $date_item->setEndingDay($endingDate);
//            $date_item->setEndingTime($endingTime);
//            $date_item->setDateTime_end($endingDate.' '.$endingTime);
//            $date_item->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($date_item->getItemID(),0);
//            $noticed_manager->markNoticed($date_item->getItemID(),0);
//
//            $this->_uploadFiles($uploadFiles, $date_item);
//
//            $this->_deleteFiles($session_id, $deleteFiles, $date_item);
//        }
//    }
//
//    function _uploadFiles($uploadFiles, $item){
//        $uploadFilesArray = explode(',', $uploadFiles);
//        $new_id_array = array();
//        foreach($uploadFilesArray as $uploadFileData){
//            if($uploadFileData != ''){
//                $temp_file_name = 'upload_'.time().'.jpg';
//                $disc_manager = $this->_environment->getDiscManager();
//                $bin = base64_decode($uploadFileData);
//                file_put_contents($disc_manager->getTempFolder().'/'.$temp_file_name, $bin);
//                $file_manager = $this->_environment->getFileManager();
//                $new_file = $file_manager->getNewItem();
//                $new_file->setFileName($temp_file_name);
//                $new_file->setTempName($disc_manager->getTempFolder().'/'.$temp_file_name);
//                $new_file->setTempKey($temp_file_name);
//                $new_file->save();
//                $new_id_array[] = $new_file->getFileID();
//            }
//        }
//        $old_id_array = $item->getFileIDArray();
//        $merge_id_array = array_merge($old_id_array, $new_id_array);
//        $item->setFileIDArray($merge_id_array);
//        $item->save();
//    }
//
//    function _deleteFiles($session_id, $deleteFiles, $item){
//        $deleteFilesArray = explode(',', $deleteFiles);
//        foreach($deleteFilesArray as $deleteFile){
//            if($deleteFile != ''){
//                $this->deleteFileItem($session_id, $deleteFile);
//            }
//        }
//    }
//
//    public function updateDate($session_id, $item_id, $title, $place, $starting_date, $ending_date, $description) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//
//        }
//    }
//
//    public function deleteDate($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $dates_manager = $this->_environment->getDatesManager();
//            $date_item = $dates_manager->getItem($item_id);
//            $date_item->delete();
//        }
//    }
//
//
//    // Materials
//
//    public function getMaterialsList($session_id, $context_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $reader_manager = $this->_environment->getReaderManager();
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_manager->setContextLimit($context_id);
//            $material_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
//            $material_manager->select();
//            $material_list = $material_manager->get();
//            $xml = "<material_list>\n";
//            $material_item = $material_list->getFirst();
//            while($material_item) {
//                if($material_item->maySee($user_item)){
//                    $xml .= "<material_item>\n";
//                    $xml .= "<material_id><![CDATA[".$material_item->getItemID()."]]></material_id>\n";
//                    $temp_title = $material_item->getTitle();
//                    $temp_title = $this->prepareText($temp_title);
//                    $xml .= "<material_title><![CDATA[".$temp_title."]]></material_title>\n";
//                    $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
//                    if ( empty($reader) ) {
//                        $xml .= "<material_read><![CDATA[new]]></material_read>\n";
//                    } elseif ( $reader['read_date'] < $material_item->getModificationDate() ) {
//                        $xml .= "<material_read><![CDATA[changed]]></material_read>\n";
//                    } else {
//                        $xml .= "<material_read><![CDATA[]]></material_read>\n";
//                    }
//                    if($material_item->mayEdit($user_item)){
//                        $xml .= "<material_edit><![CDATA[edit]]></material_edit>\n";
//                    } else {
//                        $xml .= "<material_edit><![CDATA[non_edit]]></material_edit>\n";
//                    }
//                    $xml .= "</material_item>\n";
//                }
//                $material_item = $material_list->getNext();
//            }
//            $xml .= "</material_list>";
//            $xml = $this->_encode_output($xml);
//            return $xml;
//        }
//    }
//
//    public function getMaterialDetails($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_item = $material_manager->getItem($item_id);
//            $xml  = "<material_item>\n";
//            $xml .= "<material_id><![CDATA[".$material_item->getItemID()."]]></material_id>\n";
//            $temp_title = $material_item->getTitle();
//            $temp_title = $this->prepareText($temp_title);
//            $xml .= "<material_title><![CDATA[".$temp_title."]]></material_title>\n";
//            $temp_description = $material_item->getDescription();
//            $allow_edit = true;
//            if(stristr($temp_description, '<table')){
//                $allow_edit = false;
//            }
//            $temp_description = $this->prepareText($temp_description);
//            $xml .= "<material_description><![CDATA[".$temp_description."]]></material_description>\n";
//            $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) ) {
//                $xml .= "<material_read><![CDATA[new]]></material_read>\n";
//            } elseif ( $reader['read_date'] < $material_item->getModificationDate() ) {
//                $xml .= "<material_read><![CDATA[changed]]></material_read>\n";
//            } else {
//                $xml .= "<material_read><![CDATA[]]></material_read>\n";
//            }
//            if($material_item->mayEdit($user_item) && $allow_edit){
//                $xml .= "<material_edit><![CDATA[edit]]></material_edit>\n";
//            } else {
//                $xml .= "<material_edit><![CDATA[non_edit]]></material_edit>\n";
//            }
//            $modifier_user = $material_item->getModificatorItem();
//            $xml .= "<material_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></material_last_modifier>\n";
//            $xml .= "<material_last_modification_date><![CDATA[".$material_item->getModificationDate()."]]></material_last_modification_date>\n";
//            $xml .= "<material_files>\n";
//            $file_list = $material_item->getFileList();
//            $temp_file = $file_list->getFirst();
//            while($temp_file){
//                $xml .= "<material_file>\n";
//                $xml .= "<material_file_name><![CDATA[".$temp_file->getFileName()."]]></material_file_name>\n";
//                $xml .= "<material_file_id><![CDATA[".$temp_file->getFileID()."]]></material_file_id>\n";
//                $xml .= "<material_file_size><![CDATA[".$temp_file->getFileSize()."]]></material_file_size>\n";
//                $xml .= "<material_file_mime><![CDATA[".$temp_file->getMime()."]]></material_file_mime>\n";
//                $xml .= "</material_file>\n";
//                $temp_file = $file_list->getNext();
//            }
//            $xml .= "</material_files>\n";
//
//            $section_manager = $this->_environment->getSectionManager();
//            $section_manager->setMaterialItemIDLimit($item_id);
//            $section_manager->select();
//            $section_list = $section_manager->get();
//            $section_item = $section_list->getFirst();
//            $xml .= "<material_sections>\n";
//            while($section_item){
//                $xml .= "<material_section>\n";
//                $xml .= "<material_section_id>".$section_item->getItemID()."</material_section_id>\n";
//                $temp_title = $section_item->getTitle();
//                $temp_title = $this->prepareText($temp_title);
//                $xml .= "<material_section_title>".$temp_title."</material_section_title>\n";
//                $temp_description = $section_item->getDescription();
//                $temp_description = $this->prepareText($temp_description);
//                $xml .= "<material_section_description>".$temp_description."</material_section_description>\n";
//                $modifier_user = $section_item->getModificatorItem();
//                $xml .= "<material_section_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></material_section_last_modifier>\n";
//                $xml .= "<material_section_last_modification_date><![CDATA[".$section_item->getModificationDate()."]]></material_section_last_modification_date>\n";
//                $xml .= "<material_section_files>\n";
//                $file_list = $section_item->getFileList();
//                $temp_file = $file_list->getFirst();
//                while($temp_file){
//                    $xml .= "<material_section_file>\n";
//                    $xml .= "<material_section_file_name><![CDATA[".$temp_file->getFileName()."]]></material_section_file_name>\n";
//                    $xml .= "<material_section_file_id><![CDATA[".$temp_file->getFileID()."]]></material_section_file_id>\n";
//                    $xml .= "<material_section_file_size><![CDATA[".$temp_file->getFileSize()."]]></material_section_file_size>\n";
//                    $xml .= "<material_section_file_mime><![CDATA[".$temp_file->getMime()."]]></material_section_file_mime>\n";
//                    $xml .= "</material_section_file>\n";
//                    $temp_file = $file_list->getNext();
//                }
//                $xml .= "</material_section_files>\n";
//                $xml .= "<material_section_number>".$section_item->getNumber()."</material_section_number>\n";
//                $xml .= "</material_section>\n";
//                $section_item = $section_list->getNext();
//            }
//            $xml .= "</material_sections>\n";
//
//            $xml .= "</material_item>\n";
//            $xml = $this->_encode_output($xml);
//            $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) or $reader['read_date'] < $material_item->getModificationDate() ) {
//                $reader_manager->markRead($material_item->getItemID(),0);
//            }
//            $noticed = $noticed_manager->getLatestNoticedForUserByID($material_item->getItemID(), $user_item->getItemID());
//            if ( empty($noticed) or $noticed['read_date'] < $material_item->getModificationDate() ) {
//                $noticed_manager->markNoticed($material_item->getItemID(),0);
//            }
//            return $xml;
//        }
//    }
//
//    public function saveMaterial($session_id, $context_id, $item_id, $title, $description, $uploadFiles, $deleteFiles) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $material_manager = $this->_environment->getMaterialManager();
//            debugToFile($item_id);
//            if($item_id != 'NEW'){
//                $material_item = $material_manager->getItem($item_id);
//            } else {
//                debugToFile('is NEW');
//                $material_item = $material_manager->getNewItem();
//                $material_item->setContextID($context_id);
//                $material_item->setCreatorItem($user_item);
//                $material_item->setCreationDate(getCurrentDateTimeInMySQL());
//            }
//
//            $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
//            $material_item->setTitle($title);
//            $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
//            $material_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
//            $material_item->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($material_item->getItemID(),0);
//            $noticed_manager->markNoticed($material_item->getItemID(),0);
//
//            $this->_uploadFiles($uploadFiles, $material_item);
//
//            $this->_deleteFiles($session_id, $deleteFiles, $material_item);
//        }
//    }
//
//    public function deleteMaterial($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_item = $material_manager->getItem($item_id);
//            $material_item->delete();
//        }
//    }
//
//    public function saveSection($session_id, $context_id, $item_id, $title, $description, $number, $uploadFiles, $deleteFiles, $material_item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $material_manager = $this->_environment->getMaterialManager();
//            $section_manager = $this->_environment->getSectionManager();
//            debugToFile($item_id);
//            if($item_id != 'NEW'){
//                $section_item = $section_manager->getItem($item_id);
//            } else {
//                debugToFile('is NEW');
//                $section_item = $section_manager->getNewItem();
//                $section_item->setContextID($context_id);
//                $section_item->setCreatorItem($user_item);
//                $section_item->setCreationDate(getCurrentDateTimeInMySQL());
//                $section_item->setLinkedItemID($material_item_id);
//            }
//            $section_item->setTitle($title);
//            $section_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
//            $section_item->setNumber($number);
//
//            $material_item = $material_manager->getItem($material_item_id);
//            $section_list = $material_item->getSectionList();
//
//            $section_list->set($section_item);
//            $material_item->setSectionList($section_list);
//            $material_item->setSectionSaveID($section_item->getItemId());
//
//            $material_item->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($section_item->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($section_item->getItemID(),0);
//            $noticed_manager->markNoticed($section_item->getItemID(),0);
//
//            $this->_uploadFiles($uploadFiles, $section_item);
//
//            $this->_deleteFiles($session_id, $deleteFiles, $section_item);
//        }
//    }
//
//    public function deleteSection($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        include_once('functions/date_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $section_manager = $this->_environment->getSectionManager();
//            $section_item = $section_manager->getItem($item_id);
//            $section_item->deleteVersion();
//
//            $material_item = $section_item->getLinkedItem();
//            $material_item->setModificationDate(getCurrentDateTimeInMySQL());
//            $material_item->save();
//        }
//    }
//
//    // Discussions
//
//    public function getDiscussionList($session_id, $context_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $reader_manager = $this->_environment->getReaderManager();
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_manager->setContextLimit($context_id);
//            $discussion_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
//            $discussion_manager->select();
//            $discussion_list = $discussion_manager->get();
//            $xml = "<discussion_list>\n";
//            $discussion_item = $discussion_list->getFirst();
//            while($discussion_item) {
//                $xml .= "<discussion_item>\n";
//                $xml .= "<discussion_id><![CDATA[".$discussion_item->getItemID()."]]></discussion_id>\n";
//                $temp_title = $discussion_item->getTitle();
//                $temp_title = $this->prepareText($temp_title);
//                $xml .= "<discussion_title><![CDATA[".$temp_title."]]></discussion_title>\n";
//                $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
//                if ( empty($reader) ) {
//                    $xml .= "<discussion_read><![CDATA[new]]></discussion_read>\n";
//                } elseif ( $reader['read_date'] < $discussion_item->getModificationDate() ) {
//                    $xml .= "<discussion_read><![CDATA[changed]]></discussion_read>\n";
//                } else {
//                    $xml .= "<discussion_read><![CDATA[]]></discussion_read>\n";
//                }
//                if($discussion_item->mayEdit($user_item)){
//                    $xml .= "<discussion_edit><![CDATA[edit]]></discussion_edit>\n";
//                } else {
//                    $xml .= "<discussion_edit><![CDATA[non_edit]]></discussion_edit>\n";
//                }
//                $xml .= "</discussion_item>\n";
//                $discussion_item = $discussion_list->getNext();
//            }
//            $xml .= "</discussion_list>";
//            $xml = $this->_encode_output($xml);
//            return $xml;
//        }
//    }
//
//    public function getDiscussionDetails($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_item = $discussion_manager->getItem($item_id);
//            $xml  = "<discussion_item>\n";
//            $xml .= "<discussion_id><![CDATA[".$discussion_item->getItemID()."]]></discussion_id>\n";
//            $temp_title = $discussion_item->getTitle();
//            $temp_title = $this->prepareText($temp_title);
//            $xml .= "<discussion_title><![CDATA[".$temp_title."]]></discussion_title>\n";
//            $modifier_user = $discussion_item->getModificatorItem();
//            $xml .= "<discussion_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></discussion_last_modifier>\n";
//            $xml .= "<discussion_last_modification_date><![CDATA[".$discussion_item->getModificationDate()."]]></discussion_last_modification_date>\n";
//            $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) ) {
//                $xml .= "<discussion_read><![CDATA[new]]></discussion_read>\n";
//            } elseif ( $reader['read_date'] < $discussion_item->getModificationDate() ) {
//                $xml .= "<discussion_read><![CDATA[changed]]></discussion_read>\n";
//            } else {
//                $xml .= "<discussion_read><![CDATA[]]></discussion_read>\n";
//            }
//            if($discussion_item->mayEdit($user_item)){
//                $xml .= "<discussion_edit><![CDATA[edit]]></discussion_edit>\n";
//            } else {
//                $xml .= "<discussion_edit><![CDATA[non_edit]]></discussion_edit>\n";
//            }
//
//            if($discussion_item->getDiscussionType() == 'threaded'){
//                $xml .= "<discussion_threaded><![CDATA[threaded]]></discussion_threaded>\n";
//            } else {
//                $xml .= "<discussion_threaded><![CDATA[non_threaded]]></discussion_threaded>\n";
//            }
//
//            $xml .= "<discussion_articles>\n";
//
//            $disc_articles_manager = $this->_environment->getDiscussionArticlesManager();
//            $disc_articles_manager->setDiscussionLimit($discussion_item->getItemID(), array());
//
//            $discussion_type = $discussion_item->getDiscussionType();
//            if($discussion_type == 'threaded') {
//                $disc_articles_manager->setSortPosition();
//            }
//            if(isset($_GET['status']) && $_GET['status'] == 'all_articles') {
//                $disc_articles_manager->setDeleteLimit(false);
//            }
//
//            $disc_articles_manager->select();
//            $articles_list = $disc_articles_manager->get();
//
//            //$articles_list = $discussion_item->getAllArticles();
//            $temp_article = $articles_list->getFirst();
//            while($temp_article){
//                $xml .= "<discussion_article>\n";
//                $xml .= "<discussion_article_id><![CDATA[".$temp_article->getItemID()."]]></discussion_article_id>\n";
//                $temp_title = $temp_article->getTitle();
//                $temp_title = $this->preparefText($temp_title);
//                $xml .= "<discussion_article_title><![CDATA[".$temp_title."]]></discussion_article_title>\n";
//                $temp_description = $temp_article->getDescription();
//                $allow_edit = true;
//                if(stristr($temp_description, '<table')){
//                    $allow_edit = false;
//                }
//                $temp_description = $this->prepareText($temp_description);
//                $xml .= "<discussion_article_description><![CDATA[".$temp_description."]]></discussion_article_description>\n";
//                $xml .= "<discussion_article_files>\n";
//                $article_file_list = $temp_article->getFileList();
//                $temp_article_file = $article_file_list->getFirst();
//                while($temp_article_file){
//                    $xml .= "<discussion_article_file>\n";
//                    $xml .= "<discussion_article_file_name><![CDATA[".$temp_article_file->getFileName()."]]></discussion_article_file_name>\n";
//                    $xml .= "<discussion_article_file_id><![CDATA[".$temp_article_file->getFileID()."]]></discussion_article_file_id>\n";
//                    $xml .= "<discussion_article_file_size><![CDATA[".$temp_article_file->getFileSize()."]]></discussion_article_file_size>\n";
//                    $xml .= "<discussion_article_file_mime><![CDATA[".$temp_article_file->getMime()."]]></discussion_article_file_mime>\n";
//                    $xml .= "</discussion_article_file>\n";
//                    $temp_article_file = $article_file_list->getNext();
//                }
//                $xml .= "</discussion_article_files>\n";
//                $modifier_user = $temp_article->getModificatorItem();
//                $xml .= "<discussion_article_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></discussion_article_last_modifier>\n";
//                $xml .= "<discussion_article_last_modification_date><![CDATA[".$temp_article->getModificationDate()."]]></discussion_article_last_modification_date>\n";
//                if($temp_article->mayEdit($user_item) && $allow_edit){
//                    $xml .= "<discussion_article_edit><![CDATA[edit]]></discussion_article_edit>\n";
//                } else {
//                    $xml .= "<discussion_article_edit><![CDATA[non_edit]]></discussion_article_edit>\n";
//                }
//                $xml .= "</discussion_article>\n";
//                $temp_article = $articles_list->getNext();
//            }
//            $xml .= "</discussion_articles>\n";
//
//            $xml .= "</discussion_item>\n";
//            $xml = $this->_encode_output($xml);
//            $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) or $reader['read_date'] < $discussion_item->getModificationDate() ) {
//                $reader_manager->markRead($discussion_item->getItemID(),0);
//            }
//            $noticed = $noticed_manager->getLatestNoticedForUserByID($discussion_item->getItemID(), $user_item->getItemID());
//            if ( empty($noticed) or $noticed['read_date'] < $discussion_item->getModificationDate() ) {
//                $noticed_manager->markNoticed($discussion_item->getItemID(),0);
//            }
//            return $xml;
//        }
//    }
//
//    public function saveDiscussionArticle($session_id, $context_id, $item_id, $title, $description, $uploadFiles, $deleteFiles, $discussion_item_id, $answerTo) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_article_manager = $this->_environment->getDiscussionArticleManager();
//            debugToFile($item_id);
//            if($item_id != 'NEW'){
//                $discarticle_item = $discussion_article_manager->getItem($item_id);
//            } else {
//                debugToFile('is NEW');
//                $discarticle_item = $discussion_article_manager->getNewItem();
//                $discarticle_item->setContextID($context_id);
//                $discarticle_item->setCreatorItem($user_item);
//                $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
//                $discarticle_item->setDiscussionID($discussion_item_id);
//
//                if($answerTo != "NEW") {
//                    $discussionManager = $this->_environment->getDiscussionManager();
//                    $discussionItem = $discussionManager->getItem($discussion_item_id);
//
//                    // get the position of the discussion article this is a response to
//                    $answerToItem = $discussion_article_manager->getItem($answerTo);
//                    $answerToPosition = $answerToItem->getPosition();
//
//                    // load discussion articles
//                    $discussion_article_manager->reset();
//
//                    $discussion_article_manager->setDiscussionLimit($discussion_item_id, "");
//                    $discussion_article_manager->select();
//
//                    $discussionArticlesList = $discussion_article_manager->get();
//
//                    // build an array with all positions > $answerToPosition
//                    $positionArray = array();
//                    $discussionArticle = $discussionArticlesList->getFirst();
//                    while ($discussionArticle) {
//                        $articlePosition = $discussionArticle->getPosition();
//
//                        if ($articlePosition > $answerToPosition) {
//                            $positionArray[] = $articlePosition;
//                        }
//
//                        $discussionArticle = $discussionArticlesList->getNext();
//                    }
//                    sort($positionArray);
//
//                    // check if there is at least one direct answer to the $answerToItem
//                    $hasChild = in_array($answerToPosition . ".1001", $positionArray);
//
//                    // if there is none, this article will be the first child
//                    if (!$hasChild) {
//                        $discarticle_item->setPosition($answerToPosition . ".1001");
//                    }
//
//                    // otherwise we need do determ the correct position for appending
//                    else {
//                        // explode all sub-positions
//                        $answerToPositionArray = explode(".", $answerToPosition);
//
//                        $compareArray = array();
//                        $end = count($positionArray) - 1;
//                        for ($i = 0; $i <= $end; $i++) {
//                            $valueArray = explode(".", $positionArray[$i]);
//
//                            $in = true;
//                            $end2 = count($answerToPositionArray) - 1;
//                            for ($j = 0; $j <= $end2; $j++) {
//                                if (isset($valueArray[$j]) && $answerToPositionArray[$j] != $valueArray[$j]) {
//                                    $in = false;
//                                }
//                            }
//
//                            if ($in && count($valueArray) == count($answerToPositionArray) + 1) {
//                                $compareArray[] = $valueArray[count($answerToPositionArray)];
//                            }
//                        }
//
//                        $length = count($compareArray) - 1;
//                        $result = $compareArray[$length];
//                        $endResult = $result + 1;
//
//                        $discarticle_item->setPosition($answerToPosition . "." . $endResult);
//                    }
//                } else {
//                    $discarticle_item->setPosition("1");
//                }
//            }
//            $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
//            $discarticle_item->setSubject($title);
//            $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
//            $discarticle_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
//
//            $discarticle_item->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($discarticle_item->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($discarticle_item->getItemID(),0);
//            $noticed_manager->markNoticed($discarticle_item->getItemID(),0);
//
//            $this->_uploadFiles($uploadFiles, $discarticle_item);
//
//            $this->_deleteFiles($session_id, $deleteFiles, $discarticle_item);
//        }
//    }
//
//    public function saveDiscussionWithInitialArticle($session_id, $context_id, $item_id, $title, $item_id_article, $title_article, $description_article, $uploadFiles, $deleteFiles) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_item = $discussion_manager->getNewItem();
//            $discussion_item->setContextID($context_id);
//            $discussion_item->setCreatorItem($user_item);
//            $discussion_item->setCreationDate(getCurrentDateTimeInMySQL());
//            $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
//            $discussion_item->setTitle($title);
//            $discussion_item->save();
//
//            $discussion_article_manager = $this->_environment->getDiscussionArticleManager();
//            $discarticle_item = $discussion_article_manager->getNewItem();
//            $discarticle_item->setContextID($context_id);
//            $discarticle_item->setCreatorItem($user_item);
//            $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
//            $discarticle_item->setDiscussionID($discussion_item->getItemID());
//            $discarticle_item->setPosition("1");
//            $title_article = html_entity_decode($title_article, ENT_COMPAT, 'UTF-8');
//            $discarticle_item->setSubject($title_article);
//            $description_article = html_entity_decode($description_article, ENT_COMPAT, 'UTF-8');
//            $discarticle_item->setDescription(str_ireplace("\n", "\n".'<br />', $description_article));
//            $discarticle_item->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($discarticle_item->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($discussion_item->getItemID(),0);
//            $noticed_manager->markNoticed($discussion_item->getItemID(),0);
//            $reader_manager->markRead($discarticle_item->getItemID(),0);
//            $noticed_manager->markNoticed($discarticle_item->getItemID(),0);
//
//            $this->_uploadFiles($uploadFiles, $discarticle_item);
//
//            $this->_deleteFiles($session_id, $deleteFiles, $discarticle_item);
//        }
//    }
//
//    public function saveDiscussion($session_id, $context_id, $item_id, $title) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_item = $discussion_manager->getItem($item_id);
//            $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
//            $discussion_item->setTitle($title);
//            $discussion_item->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($discussion_item->getItemID(),0);
//            $noticed_manager->markNoticed($discussion_item->getItemID(),0);
//        }
//    }
//
//    public function deleteDiscussion($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        include_once('functions/date_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_item = $discussion_manager->getItem($item_id);
//            $discussion_item->delete();
//        }
//    }
//
//    public function deleteDiscussionArticle($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        include_once('functions/date_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $discarticle_manager = $this->_environment->getDiscussionArticleManager();
//            $discarticle_item = $discarticle_manager->getItem($item_id);
//            $discarticle_item->delete();
//        }
//    }
//
//    // User
//
//    public function getUserList($session_id, $context_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $reader_manager = $this->_environment->getReaderManager();
//            $user_manager = $this->_environment->getUserManager();
//            $user_manager->setContextLimit($context_id);
//            $user_manager->setUserLimit();
//            $user_manager->select();
//            $user_list = $user_manager->get();
//            $xml = "<user_list>\n";
//            $user_list_item = $user_list->getFirst();
//            while($user_list_item) {
//                $xml .= "<user_item>\n";
//                $xml .= "<user_id><![CDATA[".$user_list_item->getItemID()."]]></user_id>\n";
//                $temp_title = $user_list_item->getFullname();
//                $temp_title = $this->prepareText($temp_title);
//                $xml .= "<user_title><![CDATA[".$temp_title."]]></user_title>\n";
//                $reader = $reader_manager->getLatestReaderForUserByID($user_list_item->getItemID(), $user_item->getItemID());
//                if ( empty($reader) ) {
//                    $xml .= "<user_read><![CDATA[new]]></user_read>\n";
//                } elseif ( $reader['read_date'] < $user_list_item->getModificationDate() ) {
//                    $xml .= "<user_read><![CDATA[changed]]></user_read>\n";
//                } else {
//                    $xml .= "<user_read><![CDATA[]]></user_read>\n";
//                }
//                $xml .= "</user_item>\n";
//                $user_list_item = $user_list->getNext();
//            }
//            $xml .= "</user_list>";
//            #debugToFile($xml);
//            $xml = $this->_encode_output($xml);
//            return $xml;
//        }
//    }
//
//    public function getUserDetails($session_id, $context_id, $item_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            //$user_manager = $this->_environment->getUserManager();
//            $user_details_item = $user_manager->getItem($item_id);
//            $xml = "<user_item>\n";
//            $xml .= "<user_id><![CDATA[".$user_details_item->getItemID()."]]></user_id>\n";
//            $xml .= "<user_title><![CDATA[".$user_details_item->getFullname()."]]></user_title>\n";
//            $xml .= "<user_firstname><![CDATA[".$user_details_item->getFirstname()."]]></user_firstname>\n";
//            $xml .= "<user_name><![CDATA[".$user_details_item->getLastname()."]]></user_name>\n";
//            if($user_details_item->isEmailVisible()){
//                $xml .= "<user_email><![CDATA[".$user_details_item->getEmail()."]]></user_email>\n";
//            }
//            $xml .= "<user_phone1><![CDATA[".$user_details_item->getTelephone()."]]></user_phone1>\n";
//            $xml .= "<user_phone2><![CDATA[".$user_details_item->getCellularphone()."]]></user_phone2>\n";
//            $temp_description = $user_details_item->getDescription();
//            $temp_description = $this->prepareText($temp_description);
//            $xml .= "<discussion_description><![CDATA[".$temp_description."]]></discussion_description>\n";
//            $reader = $reader_manager->getLatestReaderForUserByID($user_details_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) ) {
//                $xml .= "<user_read><![CDATA[new]]></user_read>\n";
//            } elseif ( $reader['read_date'] < $user_details_item->getModificationDate() ) {
//                $xml .= "<user_read><![CDATA[changed]]></user_read>\n";
//            } else {
//                $xml .= "<user_read><![CDATA[]]></user_read>\n";
//            }
//            if($user_details_item->mayEdit($user_item)){
//                $xml .= "<user_edit><![CDATA[edit]]></user_edit>\n";
//            } else {
//                $xml .= "<user_edit><![CDATA[non_edit]]></user_edit>\n";
//            }
//            $user_image = $user_details_item->getPicture();
//            if($user_image){
//                #$user_image_handle = fopen('var');
//                $disc_manager = $this->_environment->getDiscManager();
//                $user_image_handle = fopen($disc_manager->getFilePath($this->_environment->getCurrentPortalID(), $this->_environment->getCurrentContextID()).$user_image, 'r');
//                $user_image_file = fread($user_image_handle, filesize($disc_manager->getFilePath($this->_environment->getCurrentPortalID(), $this->_environment->getCurrentContextID()).$user_image));
//                $xml .= "<user_image>\n";
//                $xml .= "<user_image_data><![CDATA[".base64_encode($user_image_file)."]]></user_image_data>\n";
//                $xml .= "</user_image>\n";
//            }
//            $xml .= "</user_item>\n";
//            $xml = $this->_encode_output($xml);
//            $reader = $reader_manager->getLatestReaderForUserByID($user_details_item->getItemID(), $user_item->getItemID());
//            if ( empty($reader) or $reader['read_date'] < $user_details_item->getModificationDate() ) {
//                $reader_manager->markRead($user_details_item->getItemID(),0);
//            }
//            $noticed = $noticed_manager->getLatestNoticedForUserByID($user_details_item->getItemID(), $user_item->getItemID());
//            if ( empty($noticed) or $noticed['read_date'] < $user_details_item->getModificationDate() ) {
//                $noticed_manager->markNoticed($user_details_item->getItemID(),0);
//            }
//            debugToFile($xml);
//            return $xml;
//        }
//    }
//
//    public function saveUser($session_id, $context_id, $item_id, $name, $firstname, $email, $phone1, $phone2) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $this->_environment->setCurrentUser($user_item);
//
//            $user_item_save = $user_manager->getItem($item_id);
//            $name = html_entity_decode($name, ENT_COMPAT, 'UTF-8');
//            $user_item_save->setLastname($name);
//            $firstname = html_entity_decode($firstname, ENT_COMPAT, 'UTF-8');
//            $user_item_save->setFirstname($firstname);
//            $email = html_entity_decode($email, ENT_COMPAT, 'UTF-8');
//            $user_item_save->setEmail($email);
//            $phone1 = html_entity_decode($phone1, ENT_COMPAT, 'UTF-8');
//            $user_item_save->setTelephone($phone1);
//            $phone2 = html_entity_decode($phone2, ENT_COMPAT, 'UTF-8');
//            $user_item_save->setCellularphone($phone2);
//            $user_item_save->save();
//
//            $reader_manager = $this->_environment->getReaderManager();
//            $noticed_manager = $this->_environment->getNoticedManager();
//            $reader = $reader_manager->getLatestReaderForUserByID($user_item_save->getItemID(), $user_item->getItemID());
//            $reader_manager->markRead($user_item_save->getItemID(),0);
//            $noticed_manager->markNoticed($user_item_save->getItemID(),0);
//        }
//    }
//
//
//    // Files
//
//    public function uploadFile($session_id, $context_id, $file_id, $file_data) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//
//            $temp_file_name = 'upload_'.time().'.jpg';
//
//            $disc_manager = $this->_environment->getDiscManager();
//            $bin = base64_decode($file_data);
//            file_put_contents($disc_manager->getTempFolder().'/'.$temp_file_name, $bin);
//
//            $file_manager = $this->_environment->getFileManager();
//            $new_file = $file_manager->getNewItem();
//            $new_file->setFileName($temp_file_name);
//            $new_file->setTempName($disc_manager->getTempFolder().'/'.$temp_file_name);
//            $new_file->setTempKey($temp_file_name);
//            $new_file->save();
//
//            $xml = '<upload_file_id>'.$new_file->getItemID().'</upload_file_id>';
//            $xml = $this->_encode_output($xml);
//
//            return $xml;
//        }
//    }
//
//
//    // Room
//
//    public function getRoomReadCounter($session_id, $context_id){
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $reader_manager = $this->_environment->getReaderManager();
//
//            $dates_manager = $this->_environment->getDatesManager();
//            $dates_manager->setContextLimit($context_id);
//            $dates_manager->setDateModeLimit(2);
//            $dates_manager->select();
//            $dates_list = $dates_manager->get();
//            $date_item = $dates_list->getFirst();
//            $date_counter = 0;
//            while($date_item) {
//                $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
//                if ( empty($reader) ) {
//                    $date_counter++;
//                } elseif ( $reader['read_date'] < $date_item->getModificationDate() ) {
//                    $date_counter++;
//                }
//                $date_item = $dates_list->getNext();
//            }
//
//            $material_manager = $this->_environment->getMaterialManager();
//            $material_manager->setContextLimit($context_id);
//            $material_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
//            $material_manager->select();
//            $material_list = $material_manager->get();
//            $material_item = $material_list->getFirst();
//            $material_counter = 0;
//            while($material_item) {
//                $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
//                if ( empty($reader) ) {
//                    $material_counter++;
//                } elseif ( $reader['read_date'] < $material_item->getModificationDate() ) {
//                    $material_counter++;
//                }
//                $material_item = $material_list->getNext();
//            }
//
//            $discussion_manager = $this->_environment->getDiscussionManager();
//            $discussion_manager->setContextLimit($context_id);
//            $discussion_manager->select();
//            $discussion_list = $discussion_manager->get();
//            $discussion_item = $discussion_list->getFirst();
//            $discussion_counter = 0;
//            while($discussion_item) {
//                $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
//                if ( empty($reader) ) {
//                    $discussion_counter++;
//                } elseif ( $reader['read_date'] < $discussion_item->getModificationDate() ) {
//                    $discussion_counter++;
//                }
//                $discussion_item = $discussion_list->getNext();
//            }
//
//            $user_counter_manager = $this->_environment->getUserManager();
//            $user_counter_manager->setContextLimit($context_id);
//            $user_manager->setUserLimit();
//            $user_counter_manager->select();
//            $user_counter_list = $user_counter_manager->get();
//            $user_counter_item = $user_counter_list->getFirst();
//            $user_counter = 0;
//            while($user_counter_item) {
//                $reader = $reader_manager->getLatestReaderForUserByID($user_counter_item->getItemID(), $user_item->getItemID());
//                if ( empty($reader) ) {
//                    $user_counter++;
//                } elseif ( $reader['read_date'] < $user_counter_item->getModificationDate() ) {
//                    $user_counter++;
//                }
//                $user_counter_item = $user_counter_list->getNext();
//            }
//
//            $xml  = '<read_counter>';
//            $xml .= '<counter_dates>'.$date_counter.'</counter_dates>';
//            $xml .= '<counter_materials>'.$material_counter.'</counter_materials>';
//            $xml .= '<counter_discussions>'.$discussion_counter.'</counter_discussions>';
//            $xml .= '<counter_users>'.$user_counter.'</counter_users>';
//            $xml .= '</read_counter>';
//
//            $xml = $this->_encode_output($xml);
//
//            return $xml;
//        }
//    }
//
//    public function getModerationUserList($session_id) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $room_manager = $this->_environment->getRoomManager();
//            $room_list = $room_manager->getRelatedRoomListForUser($user_item);
//
//            $room_item = $room_list->getFirst();
//            $xml = "<moderation_user_list>\n";
//            while($room_item) {
//                $is_moderator = false;
//                $room_user = $room_item->getUserByUserID($user_id, $auth_source_id);
//                if($room_user->getStatus() == '3'){
//                    $is_moderator = true;
//                }
//
//                if($is_moderator){
//                    $user_manager->resetLimits();
//                    $user_manager->setContextLimit($room_item->getItemID());
//                    $user_manager->setRegisteredLimit();
//                    $user_manager->select();
//                    $user_list = $user_manager->get();
//                    $temp_user_item = $user_list->getFirst();
//                    while($temp_user_item){
//                        if($temp_user_item->getStatus() == '1'){
//                            $xml .= "<moderation_user_item>";
//                            $xml .= "<firstname><![CDATA[".$temp_user_item->getFirstname()."]]></firstname>\n";
//                            $xml .= "<lastname><![CDATA[".$temp_user_item->getLastname()."]]></lastname>\n";
//                            $xml .= "<item_id><![CDATA[".$temp_user_item->getItemID()."]]></item_id>\n";
//                            $xml .= "<context_id><![CDATA[".$room_item->getItemID()."]]></context_id>\n";
//                            $xml .= "<context_name><![CDATA[".$room_item->getTitle()."]]></context_name>\n";
//                            $xml .= "</moderation_user_item>\n";
//                        }
//                        $temp_user_item = $user_list->getNext();
//                    }
//                }
//
//                $room_item = $room_list->getNext();
//            }
//            $xml .= "</moderation_user_list>";
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//        return $xml;
//    }
//
//    public function activateUser($session_id, $activate_user_id, $with_email) {
//        include_once('functions/development_functions.php');
//        if($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $context_id = $session->getValue('commsy_id');
//            $this->_environment->setCurrentContextID($context_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            $activate_user_item = $user_manager->getItem($activate_user_id);
//            $activate_user_item->makeUser();
//            $activate_user_item->save();
//
//            if($with_email == 'true' && false){
//                $translator = $this->_environment->getTranslationObject();
//                $room_manager = $this->_environment->getRoomManager();
//
//                $temp_room = $room_manager->getItem($activate_user_item->getContextID());
//                if($temp_room->getRoomType() == "project"){
//                    $body  = $translator->getMessage('MAIL_BODY_USER_STATUS_USER_PR', $activate_user_item->getUserID(), $temp_room->getTitle());
//                } else if($temp_room->getRoomType() == "group"){
//                    $body  = $translator->getMessage('MAIL_BODY_USER_STATUS_USER_GR', $activate_user_item->getUserID(), $temp_room->getTitle());
//                } else if($temp_room->getRoomType() == "community") {
//                    $body  = $translator->getMessage('MAIL_BODY_USER_STATUS_USER_GP', $activate_user_item->getUserID(), $temp_room->getTitle());
//                }
//
//                include_once('classes/cs_mail.php');
//                $mail = new cs_mail();
//                $mail->set_to($activate_user_item->getEmail());
//                $server_item = $this->_environment->getServerItem();
//                $default_sender_address = $server_item->getDefaultSenderAddress();
//                if (!empty($default_sender_address)) {
//                    $mail->set_from_email($default_sender_address);
//                } else {
//                    $mail->set_from_email('@');
//                }
//                $current_context = $this->_environment->getCurrentContextItem();
//                $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
//                $mail->set_reply_to_name($user_item->getFullname());
//                $mail->set_reply_to_email($user_item->getEmail());
//                $mail->set_subject($subject);
//                $mail->set_message($body);
//                $mail->send();
//            }
//
//            $xml = "<activateUser>\n";
//            $xml .= "</activateUser>";
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session not valid!');
//        }
//        return $xml;
//    }
//
//
//    /*
//    * for plugin soap methods
//    */
//    public function __call ($name, $arguments) {
//
//        // maybe plugin method
//        // first argument = session id or json-string with session id
//        $sid = '';
//        if ( !empty($arguments[0]) ) {
//            // 32 = length of md5 hash
//            if ( strlen($arguments[0]) == 32 ) {
//                $sid = $arguments[0];
//            }
//            // maybe json
//            else {
//                $arg_array = json_decode($arguments[0],true);
//                if ( empty($arg_array) ) {
//                    $arg_array = json_decode(str_replace('\'','"',$arguments[0]),true);
//                }
//                if ( !empty($arg_array['SID']) ) {
//                    $sid = $arg_array['SID'];
//                } elseif ( !empty($arg_array['sid']) ) {
//                    $sid = $arg_array['sid'];
//                }
//            }
//        }
//
//        // now the context
//        if ( !empty($sid) ) {
//            // session valid ?
//            if ( $this->_isSessionValid($sid) ) {
//                // get session item
//                $session_manager = $this->_environment->getSessionManager();
//                $session_item = $session_manager->get($sid);
//                if ( $session_item->issetValue('commsy_id') ) {
//                    $portal_id = $session_item->getValue('commsy_id');
//                    $this->_environment->setCurrentPortalID($portal_id);
//                    $this->_environment->setCurrentContextID($portal_id);
//
//                    // plugin function
//                    $retour = plugin_hook_output_all($name,$arguments);
//                } else {
//                    return new SoapFault('ERROR','can not find portal id in session item');
//                }
//            } else {
//                return new SoapFault('ERROR','Session ('.$sid.') not valid!');
//            }
//        }
//
//        // return
//        if ( !empty($retour) ) {
//            return $retour;
//        } else {
//            return new SoapFault('ERROR','SOAP function ('.$name.') is not defined');
//        }
//    }
//    public static function __callStatic($name, $arguments) {
//        $this->__call($name, $arguments);
//    }
//
//    // portal2portal
//    public function getSessionIdFromConnectionKey ($session_id, $portal_id, $user_key, $server_key) {
//        if ($this->_isSessionValid($session_id)) {
//            $connection_obj = $this->_environment->getCommSyConnectionObject();
//            $this->_updateSessionCreationDate($session_id);
//            return $connection_obj->getSessionIdFromConnectionKeySOAP($session_id, $portal_id, $user_key, $server_key);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function getRoomListAsJson ($session_id) {
//        if ($this->_isSessionValid($session_id)) {
//            $connection_obj = $this->_environment->getCommSyConnectionObject();
//            $this->_updateSessionCreationDate($session_id);
//            return $connection_obj->getRoomListAsJsonSOAP($session_id);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function getPortalListAsJson () {
//        $connection_obj = $this->_environment->getCommSyConnectionObject();
//        return $connection_obj->getPortalListAsJsonSOAP();
//    }
//
//    public function saveExternalConnectionKey ($session_id, $user_key) {
//        if ($this->_isSessionValid($session_id)) {
//            $connection_obj = $this->_environment->getCommSyConnectionObject();
//            $this->_updateSessionCreationDate($session_id);
//            return $connection_obj->saveExternalConnectionKeySOAP($session_id, $user_key);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function getOwnConnectionKey ($session_id) {
//        if ($this->_isSessionValid($session_id)) {
//            $connection_obj = $this->_environment->getCommSyConnectionObject();
//            $this->_updateSessionCreationDate($session_id);
//            return $connection_obj->getOwnConnectionKeySOAP($session_id);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function setPortalConnectionInfo ($session_id, $server_key, $portal_id, $tab_id, $user_key) {
//        if ($this->_isSessionValid($session_id)) {
//            $connection_obj = $this->_environment->getCommSyConnectionObject();
//            $this->_updateSessionCreationDate($session_id);
//            return $connection_obj->setPortalConnectionInfoSOAP($session_id, $server_key, $portal_id, $tab_id, $user_key);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function deleteConnection ($session_id, $tab_id) {
//        if ($this->_isSessionValid($session_id)) {
//            $connection_obj = $this->_environment->getCommSyConnectionObject();
//            $this->_updateSessionCreationDate($session_id);
//            return $connection_obj->deleteConnectionSOAP($session_id, $tab_id);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//    }
//
//    public function getAuthSources($session_id, $portal_id)
//    {
//        $xml = '';
//
//        if ($this->_isSessionValid($session_id)) {
//            $authSourceManager = $this->_environment->getAuthSourceManager();
//            $authSourceManager->setContextLimit($portal_id);
//            $authSourceManager->select();
//            $list = $authSourceManager->get();
//
//            if (!$list->isEmpty()) {
//                $item = $list->getFirst();
//                $xml .= "<auth_sources>\n";
//
//                while ($item) {
//                    $xml .= "<auth_source>\n";
//
//                    $xml .= "<id><![CDATA[".$item->getItemId()."]]></id>\n";
//                    $xml .= "<title><![CDATA[".$item->getTitle()."]]></title>\n";
//                    $xml .= "<allow_add><![CDATA[".($item->allowAddAccount() ? 'yes' : 'no') ."]]></allow_add>\n";
//                    $xml .= "<default><![CDATA[".($item->isCommSyDefault() ? 'yes' : 'no') ."]]></default>\n";
//                    $xml .= "<password>";
//                    $xml .=     "<big_char><![CDATA[".($item->getPasswordSecureBigchar() == "1" ? 'yes' : 'no') ."]]></big_char>\n";
//                    $xml .=     "<number><![CDATA[".($item->getPasswordSecureNumber() == "1" ? 'yes' : 'no') ."]]></number>\n";
//                    $xml .=     "<small_char><![CDATA[".($item->getPasswordSecureSmallchar() == "1" ? 'yes' : 'no') ."]]></small_char>\n";
//                    $xml .=     "<special_char><![CDATA[".($item->getPasswordSecureSpecialchar() == "1" ? 'yes' : 'no') ."]]></special_char>\n";
//                    $xml .=     "<length><![CDATA[".($item->getPasswordLength() != "" ? $item->getPasswordLength() : 'no') ."]]></length>\n";
//                    $xml .= "</password>";
//
//                    $xml .= "</auth_source>\n";
//
//                    $item = $list->getNext();
//                }
//            }
//            $xml .= "</auth_sources>";
//            $xml = $this->_encode_output($xml);
//
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function getBarInformation($session_id, $portal_id)
//    {
//        $xml = '';
//
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $this->_environment->setCurrentContextID($portal_id);
//            $user_id = $session->getValue('user_id');
//            $auth_source_id = $session->getValue('auth_source');
//            $user_manager = $this->_environment->getUserManager();
//            $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
//            if ($user_item) {
//                $own_room_item = $user_item->getOwnRoom();
//            }
//
//            $xml .= "<bar_config>\n";
//
//            if (isset($own_room_item) && !$this->_environment->isArchiveMode()) {
//
//                $xml .= "<widgets><![CDATA[".($own_room_item->getCSBarShowWidgets() == '1' ? 'yes' : 'no') ."]]></widgets>\n";
//                $xml .= "<calendar><![CDATA[".($own_room_item->getCSBarShowCalendar() == '1' ? 'yes' : 'no') ."]]></calendar>\n";
//                $xml .= "<stack><![CDATA[".($own_room_item->getCSBarShowStack() == '1' ? 'yes' : 'no') ."]]></stack>\n";
//                $xml .= "<portfolio><![CDATA[".($own_room_item->getCSBarShowPortfolio() == '1' ? 'yes' : 'no') ."]]></portfolio>\n";
//                $xml .= "<connection><![CDATA[".($own_room_item->getCSBarShowConnection() == '1' ? 'yes' : 'no') ."]]></connection>\n";
//            } else {
//                $xml .= "<widgets>no</widgets>\n";
//                $xml .= "<calendar>no</calendar>\n";
//                $xml .= "<stack>no</stack>\n";
//                $xml .= "<portfolio>no</portfolio>\n";
//                $xml .= "<connection>no</connection>\n";
//            }
//
//            $xml .= "<portal_name><![CDATA[" . $this->_environment->getCurrentContextItem()->getTitle() . "]]></portal_name>\n";
//            $xml .= "<portal_security><![CDATA[" . ($this->_environment->getCurrentContextItem()->withAGBDatasecurity() == '1' ? 'yes' : 'no') . "]]></portal_security>\n";
//
//            if ($user_item) {
//                $xml .= "<user_name><![CDATA[".$user_item->getFullName()."]]></user_name>\n";
//                $xml .= "<own_room_id><![CDATA[".$own_room_item->getItemId()."]]></own_room_id>\n";
//            }
//
//            $xml .= "</bar_config>";
//            $xml = $this->_encode_output($xml);
//
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function getContextToU($session_id, $context_id)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($context_id);
//            $contextItem = $this->_environment->getCurrentContextItem();
//
//            $xml .= "<tou>\n";
//            if ($contextItem->withAGB()) {
//                $languages = $contextItem->getAGBTextArray();
//
//                foreach ($languages as $language => $tou) {
//                    $xml .= "<" . $language . "><![CDATA[" . $tou . "]]></" . $language . ">\n";
//                }
//            }
//            $xml .= "</tou>";
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function registerUser($session_id, $context_id, $firstname, $lastname, $email, $identification, $password, $tou)
//    {
//        $xml = "";
//        $valid = true;
//        $errorArray = array();
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($context_id);
//            $contextItem = $this->_environment->getCurrentContextItem();
//            $translator = $this->_environment->getTranslationObject();
//
//            // check email
//            if (!isEmailValid($email)) {
//                $valid = false;
//                $errorArray['email'] = $translator->getMessage('USER_EMAIL_ERROR');
//            }
//
//            // check tou
//            if ($contextItem->withAGB() && $contextItem->withAGBDatasecurity()) {
//                if (!$tou) {
//                    $valid = false;
//                    $errorArray['tou'] = $translator->getMessage('CONFIGURATION_AGB_ACCEPT_ERROR');
//                }
//            }
//
//            // get the commsy authentication source
//            $authSourceList = $contextItem->getAuthSourceList();
//            if (isset($authSourceList) && !empty($authSourceList)) {
//                $authSourceItem = $authSourceList->getFirst();
//                $found = false;
//                while ($authSourceItem and !$found) {
//                    if ($authSourceItem->isCommSyDefault()) {
//                        $found = true;
//                    } else {
//                        $authSourceItem = $authSourceList->getNext();
//                    }
//                }
//            }
//            //$authSourceItem = $contextItem->getAuthDefault();
//
//            // check password security
//            if ($authSourceItem->getPasswordLength() > 0) {
//                if (mb_strlen($password) < $authSourceItem->getPasswordLength()) {
//                    $valid = false;
//                    $errorArray['password_length'] = $translator->getMessage('USER_NEW_PASSWORD_LENGTH_ERROR', $authSourceItem->getPasswordLength());
//                }
//            }
//            if ($authSourceItem->getPasswordSecureBigchar() == 1) {
//                if (!preg_match('~[A-Z]+~u', $password)) {
//                    $valid = false;
//                    $errorArray['password_bigchar'] = $translator->getMessage('USER_NEW_PASSWORD_BIGCHAR_ERROR');
//                }
//            }
//            if ($authSourceItem->getPasswordSecureSpecialchar() == 1) {
//                if (!preg_match('~[^a-zA-Z0-9]+~u', $password)) {
//                    $valid = false;
//                    $errorArray['password_specialchar'] = $translator->getMessage('USER_NEW_PASSWORD_SPECIALCHAR_ERROR');
//                }
//            }
//            if ($authSourceItem->getPasswordSecureNumber() == 1) {
//                if (!preg_match('~[0-9]+~u', $password)) {
//                    $valid = false;
//                    $errorArray['password_number'] = $translator->getMessage('USER_NEW_PASSWORD_NUMBER_ERROR');
//                }
//            }
//            if ($authSourceItem->getPasswordSecureSmallchar() == 1) {
//                if (!preg_match('~[a-z]+~u', $password)) {
//                    $valid = false;
//                    $errorArray['password_smallchar'] = $translator->getMessage('USER_NEW_PASSWORD_SMALLCHAR_ERROR');
//                }
//            }
//
//            // check for unique user id
//            $authentication = $this->_environment->getAuthenticationObject();
//            if (!$authentication->is_free($identification, $authSourceItem->getItemId())) {
//                $valid = false;
//                $errorArray['user_id'] = $translator->getMessage('USER_USER_ID_ERROR', $identification);
//            } else if(withUmlaut($identification)) {
//                $valid = false;
//                $errorArray['user_id'] = $translator->getMessage('USER_USER_ID_ERROR_UMLAUT', $identification);
//            }
//
//            if ($valid) {
//                // create user
//                $textConverter = $this->_environment->getTextConverter();
//
//                $firstname = $textConverter->sanitizeHTML($firstname);
//                $lastname = $textConverter->sanitizeHTML($lastname);
//
//                $newAccount = $authentication->getNewItem();
//                $newAccount->setUserID($identification);
//                $newAccount->setPassword($password);
//                $newAccount->setFirstname($firstname);
//                $newAccount->setLastname($lastname);
//                $newAccount->setLanguage("browser");
//                $newAccount->setEmail($email);
//                $newAccount->setPortalID($context_id);
//                $newAccount->setAuthSourceId($authSourceItem->getItemId());
//
//                $authentication->save($newAccount, false);
//
//                if ($authentication->getErrorMessage() == "") {
//                    $portalUserItem = $authentication->getUserItem();
//
//                    // tou
//                    if ($contextItem->withAGB() && $contextItem->withAGBDatasecurity()) {
//                        if ($tou) {
//                            $portalUserItem->setAGBAcceptance();
//                        }
//                    }
//
//                    // send mail to moderators
//                    $savedLanguage = $translator->getSelectedLanguage();
//
//                    $moderatorList = $contextItem->getModeratorList();
//                    $emailArray = array();
//                    $moderatorItem = $moderatorList->getFirst();
//                    $recipients = "";
//                    $language = $contextItem->getLanguage();
//                    while ($moderatorItem) {
//                        $wantMail = $moderatorItem->getAccountWantMail();
//                        if (!empty($wantMail) && $wantMail == 'yes') {
//                            if ($language == "user" && $moderatorItem->getLanguage() != "browser") {
//                                $emailArray[$moderatorItem->getLanguage()][] = $moderatorItem->getEmail();
//                            } else if ($language == "user" && $moderatorItem->getLanguage() == "browser") {
//                                $emailArray[$language][] = $moderatorItem->getEmail();
//                            }
//
//                            $recipients .= $moderatorItem->getFullname() . LF;
//                        }
//
//                        $moderatorItem = $moderatorList->getNext();
//                    }
//
//                    foreach ($emailArray as $language => $addresses) {
//                        $translator->setSelectedLanguage($language);
//
//                        if (sizeof($addresses) > 0) {
//                            include_once('classees/cs_mail.php');
//                            $mail = new cs_mail();
//                            $mail->set_to(implode(',', $addresses));
//
//                            $serverItem = $this->environment->getServerItem();
//                            $defaultSenderAddress = $serverItem->getDefaultSenderAddress();
//                            if (!empty($defaultSenderAddress)) {
//                                $mail->set_from_email($defaultSenderAddress);
//                            } else {
//                                $mail->set_from_mail('@');
//                            }
//
//                            $mail->set_from_name($translator->getMessage("SYSTEM_MAIL_MESSAGE", $contextItem->getTitle()));
//                            $mail->set_reply_to_name($portalUser->getFullname());
//                            $mail->set_reply_to_email($portalUser->getEmail());
//                            $mail->set_subject($translator->getMessage("USER_GET_MAIL_SUBJECT", $portalUser->getFullname()));
//
//                            $body = $translator->getMessage("MAIL_AUTO", $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                            $body .= LF.LF;
//
//                            $tempLanguage = $portalUser->getLanguage();
//                            if ($tempLanguage == "browser") {
//                                $tempLanguage = $this->_environment->getSelectedLanguage();
//                            }
//
//                            // data security
//                            if ($contextItem->getHideAccountname()) {
//                                $userId = "XXX " . $translator->getMessage("COMMON_DATASECURITY");
//                            } else {
//                                $userId = $portalUser->getUserID();
//                            }
//                            $body .= $translator->getMessage("USER_GET_MAIL_BODY", $portalUser->getFullname(), $userid, $portalUser->getEmail(), $translator->getLanguageLabelTranslated($tempLanguage));
//                            $body .= LF.LF;
//                            $body .= $translator->getMessage("USER_GET_MAIL_STATUS_NO");
//                            $body .= LF.LF;
//                            $body .= $translator->getMessage("MAIL_SEND_TO", $recipients);
//                            $body .= LF;
//                            $body .= "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?cid=" . $contextItem->getItemID() . "&mod=account&fct=index&selstatus=1";
//
//                            $mail->set_message($body);
//                            $mail->send();
//
//                            $translator->setSelectedLanguage($savedLanguage);
//
//                            // activate user
//                            $portalUser->makeUser();
//                            $portalUser->save();
//                            $this->_environment->setcurrentUserItem($portalUser);
//
//                            // send mail to user
//                            if ($portalUser->isUser()) {
//                                $modText = "";
//                                $modList = $contextItem->getContactModeratorList();
//
//                                if ($modList->isEmpty()) {
//                                    $modItem = $modList->getFirst();
//                                    $contactModerator = $modItem;
//
//                                    while ($modItem) {
//                                        if (!empty($modText)) {
//                                            $modText .= ',' . LF;
//                                        }
//
//                                        $modText .= $modItem->getFullname();
//                                        $modText .= " (" . $modItem->getEmail() . ")";
//                                        $modItem = $modList->getNext();
//                                    }
//                                }
//
//                                $language = getSelectedLanguage();
//                                $translator->setSelectedLanguage($language);
//
//                                include_once("classes/cs_mail.php");
//
//                                $mail = new cs_mail();
//                                $mail->set_to($portalUser->getEmail());
//                                $mail->set_from_name($translator->getMessage("SYSTEM_MAIL_MESSAGE", $contextItem->getTitle()));
//
//                                $serverItem = $this->_environment->getServerItem();
//                                $defaultSenderAddress = $serverItem->getDefaultSenderAddress();
//
//                                if (!empty($defaultSenderAddress)) {
//                                    $mail->set_from_email($defaultSenderAddress);
//                                } else {
//                                    $userManager = $this->_environment->getUserManager();
//                                    $rootUser = $userManager->getRootUser();
//                                    $rootMailAddress = $rootUser->getEmail();
//                                    if (!empty($rootMailAddress)) {
//                                        $mail->set_from_email($rootMailAddress);
//                                    } else {
//                                        $mail->set_from_email('@');
//                                    }
//                                }
//
//                                if (!empty($contactModerator)) {
//                                    $mail->set_reply_to_email($contactModerator->getEmail());
//                                    $mail->set_reply_to_name($contactModerator->getFullname());
//                                }
//
//                                $mail->set_subject($translator->getMessage("MAIL_SUBJECT_USER_ACCOUNT_FREE", $contextItem->getTitle()));
//
//                                $body = $translator->getMessage("MAIL_AUTO", $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                                $body .= LF.LF;
//                                $body .= $translator->getEmailMEssage("MAIL_BODY_HELLO", $portalUser->getFullname());
//                                $body .= LF.LF;
//                                $body .= $translator->getEmailMessage("MAIL_BODY_USER_STATUS_USER", $portalUser->getUserID(), $contextItem->getTitle());
//                                $body .= LF.LF;
//
//                                if (empty($contactModerator)) {
//                                    $body .= $translator->getMessage("SYSTEM_MAIL_REPLY_INFO") . LF;
//                                    $body .= $modText;
//                                    $body .= LF.LF;
//                                } else {
//                                    $body .= $translator->getEmailMessage("MAIL_BODY_CIAO", $contactModerator->getFullname(), $contextItem->getTitle());
//                                    $body .= LF.LF;
//                                }
//
//                                $body .= "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?cid=" . $this->_environment->getCurrentContextID();
//                                $mail->set_message($body);
//
//                                $mail->send();
//                            }
//                        }
//                    }
//                } else {
//                    $errorArray['account'] = '';
//                }
//            }
//
//            if (sizeof($errorArray) > 0) {
//                $xml = "<errors>\n";
//                foreach ($errorArray as $code => $description) {
//                    $xml .= "<" . $code . "><![CDATA[" . $description . "]]></" . $code . ">\n";
//                }
//                $xml .= "</errors>";
//            } else {
//                $xml = "<success></success>";
//            }
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function sendIdForget($session_id, $context_id, $email)
//    {
//        $xml = "";
//        $valid = true;
//        $errorArray = array();
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($context_id);
//            $translator = $this->_environment->getTranslationObject();
//
//            if (!isEmailValid($email)) {
//                $errorArray['format'] = $translator->getMessage('USER_EMAIL_VALID_ERROR');
//            }
//
//            if (empty($errorArray)) {
//                $userManager = $this->_environment->getUserManager();
//                $userManager->resetLimits();
//                $userManager->setContextLimit($this->_environment->getCurrentPortalID());
//                $userManager->setUserLimit();
//                $userManager->setSearchLimit($email);
//                $userManager->select();
//
//                $userList = $userManager->get();
//
//                // did we hit something?
//                if ($userList->isEmpty()) {
//                    $errorArray['not_found'] = $translator->getMessage('ERROR_EMAIL_DOES_NOT_EXIST');
//                } else {
//                    $userManager->resetLimits();
//                    $userManager->setContextLimit($this->_environment->getCurrentPortalID());
//                    $userManager->setEmailLimit($email);
//                    $userManager->select();
//
//                    $userList = $userManager->get();
//                    $userItem = $userList->getFirst();
//
//                    $portalItem = $this->_environment->getCurrentPortalItem();
//                    $authSourceId = null;
//                    $accountText = "";
//                    $userFullname = "";
//                    $showAuthSource = false;
//                    while ($userItem) {
//                        if ($authSourceId && $authSourceId != $userItem->getAuthSource()) {
//                            $showAuthSource = true;
//                            break;
//                        } else {
//                            $authSourceId = $userItem->getAuthSource();
//                        }
//
//                        $userItem = $userList->getNext();
//                    }
//
//                    $first = true;
//                    $userItem = $userList->getFirst();
//                    while ($userItem) {
//                        if ($first) {
//                            $first = false;
//                        } else {
//                            $accountText .= LF;
//                        }
//
//                        $accountText .= $userItem->getUserID();
//
//                        if ($showAuthSource) {
//                            $authSourceItem = $portalItem->getAuthSource($userItem->getAuthSource());
//                            $accountText .= " (" . $authSourceItem->getTitle() . ")";
//                        }
//                        $userFullname = $userItem->getFullname();
//
//                        $userItem = $userList->getNext();
//                    }
//
//                    // send email
//                    $modText = "";
//                    $modList = $portalItem->getContactModeratorList();
//                    if (!$modList->isEmpty()) {
//                        $modItem = $modList->getFirst();
//                        $contactModerator = $modItem;
//                        while($modItem) {
//                            if (!empty($modText)) {
//                                $modText .= "," . LF;
//                            }
//
//                            $modText .= $modItem->getFullname();
//                            $modText .= " (" . $modItem->getEmail() . ")";
//
//                            $modItem = $modList->getNext();
//                        }
//                    }
//
//                    include_once('classes/cs_mail.php');
//                    $mail = new cs_mail();
//                    $mail->set_to($email);
//
//                    $serverItem = $this->_environment->getServerItem();
//                    $defaultSenderAddress = $serverItem->getDefaultSenderAddress();
//
//                    if (!empty($defaultSenderAddress)) {
//                        $mail->set_from_email($defaultSenderAddress);
//                    } else {
//                        $mail->set_from_email('@');
//                    }
//
//                    if (isset($contactModerator)) {
//                        $mail->set_reply_to_email($contactModerator->getEmail());
//                        $mail->set_reply_to_name($contactModerator->getFullname());
//                    }
//
//                    $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $portalItem->getTitle()));
//                    $mail->set_subject($translator->getMessage('USER_ACCOUNT_FORGET_MAIL_SUBJECT', $portalItem->getTitle()));
//
//                    $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                    $body .= LF . LF;
//                    $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $userFullname);
//                    $body .= LF . LF;
//                    $body .= $translator->getMessage('USER_ACCOUNT_FORGET_MAIL_BODY', $portalItem->getTitle(), $accountText);
//                    $body .= LF . LF;
//
//                    if (empty($contactModerator)) {
//                        $body .= $translator->getMessage('SYSTEM_MAIL_REPLY_INFO') . LF;
//                        $body .= $modText;
//                        $body .= LF . LF;
//                    } else {
//                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $contactModerator->getFullname(), $portalItem->getTitle());
//                        $body .= LF . LF;
//                    }
//
//                    $body .= "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?cid=" . $this->_environment->getCurrentContextID();
//                    $mail->set_message($body);
//
//                    if (!$mail->send()) {
//                        $errorArray['send'] = "";
//                    }
//                }
//            }
//
//            if (sizeof($errorArray) > 0) {
//                $xml = "<errors>\n";
//                foreach ($errorArray as $code => $description) {
//                    $xml .= "<" . $code . "><![CDATA[" . $description . "]]></" . $code . ">\n";
//                }
//                $xml .= "</errors>";
//            } else {
//                $xml = "<success></success>";
//            }
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function sendPwForget($session_id, $context_id, $identification) {
//        $xml = "";
//        $valid = true;
//        $errorArray = array();
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($context_id);
//            $translator = $this->_environment->getTranslationObject();
//
//            $portalItem = $this->_environment->getCurrentPortalItem();
//
//            $authSourceList = $portalItem->getAuthSourceListEnabled();
//            $authSourceItem = $authSourceList->getFirst();
//            $defaultAuthSource = null;
//            while ($authSourceItem) {
//                if ($authSourceItem->isCommSyDefault()) {
//                    $defaultAuthSource = $authSourceItem;
//                    break;
//                } else {
//                    $authSourceItem = $authSourceList->getNext();
//                }
//            }
//
//            $userManager = $this->_environment->getUserManager();
//            $checkUser = $userManager->exists($identification, $authSourceItem->getItemId());
//            if (!$checkUser) {
//                $errorArray['missing'] = "Die Kennung " . $identification . " existiert nicht. Bitte überprüfen Sie Ihre Eingabe.";
//            } else {
//                $userManager->resetLimits();
//                $userManager->setContextLimit($context_id);
//                $userManager->setUserIDLimit($identification);
//                $userManager->setAuthSourceLimit($authSourceItem->getItemId());
//                $userManager->select();
//
//                $userList = $userManager->get();
//                $userItem = $userList->getFirst();
//                $authSourceManager = $this->_environment->getAuthSourceManager();
//                $sessionManager = $this->_environment->getSessionManager();
//                while ($userItem) {
//                    $authSourceItem = $authSourceManager->getItem($userItem->getAuthSource());
//
//                    if ($authSourceItem->allowAddAccount()) {
//                        include_once('classes/cs_session_item.php');
//
//                        $specialSessionItem = new cs_session_item();
//                        $specialSessionItem->createSssionID($identification);
//                        $specialSessionItem->setValue('auth_source', $userItem->getAuthSource());
//
//                        if ($identification == 'root') {
//                            $specialSessionItem->setValue('commsy_id', $this->_environment->getServerID());
//                        } else {
//                            $specialSessionItem->setValue('commsy_id', $this->_environment->getCurrentPortalID());
//                        }
//
//                        // if ( isset($_SERVER["SERVER_ADDR"]) and !empty($_SERVER["SERVER_ADDR"])) {
//                        //    $new_special_session_item->setValue('password_forget_ip',$_SERVER["SERVER_ADDR"]);
//                        // } else {
//                        //    $new_special_session_item->setValue('password_forget_ip',$_SERVER["HTTP_HOST"]);
//                        // }
//
//                        include_once('functions/date_functions.php');
//                        $specialSessionItem->setValue('passwort_forget_time', getCurrentDateTimeInMySQL());
//                        $specialSessionItem->setValue('javascript', -1);
//                        $specialSessionItem->setValue('cookie', 0);
//
//                        $sessionManager->save($specialSessionItem);
//                    }
//
//                    $userEmail = $userItem->getEMail();
//                    $userFullname = $userItem->getFullname();
//
//                    $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?cid=" . $this->_environment->getCurrentPortalID();
//                    if ($authSourceItem->allowAddAccount()) {
//                        $url .= "&SID=" . $specialSessionItem->getSessionId();
//                    }
//
//                    // send email
//                    $modText = "";
//                    $modList = $portalitem->getModeratorList();
//
//                    if (!$modList->isEmpty()) {
//                        $modItem = $modList->getFirst();
//                        $contactModerator = $modItem;
//                        while ($modItem) {
//                            if (!empty($modText)) {
//                                $modText .= "," . LF;
//                            }
//
//                            $modText .= $modItem->getFullname();
//                            $modText .= " (" . $modItem->getEmail() . ")";
//
//                            $modItem = $modList->getNext();
//                        }
//                    }
//
//                    include_once('classes/cs_mail.php');
//                    $mail = new cs_mail();
//                    $mail->set_to($userEmail);
//
//                    $serverItem = $this->_environment->getServerItem();
//                    $defaultSenderAddress = $serverItem->getDefaultSenderAddress();
//                    if (!empty($defaultSenderAddress)) {
//                        $mail->set_from_email($defaultSenderAddress);
//                    } else {
//                        $mail->set_from_email("@");
//                    }
//
//                    if (!empty($contactModerator)) {
//                        $mail->set_reply_to_email($contactModerator->getEmail());
//                        $mail->set_reply_to_name($contactModerator->getFullname());
//                    }
//
//                    $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $portalItem->getTitle()));
//                    $mail->set_subject($translator->getMessage('USER_PASSWORD_MAIL_SUBJECT', $portalItem->getTitle()));
//
//                    $body = $translator->getMessage('MAIL_AUTO', $translator->getDateInLang(getCurrentDateTimeInMySQL()), $translator->getTimeInLang(getCurrentDateTimeInMySQL()));
//                    $body .= LF . LF;
//                    $body .= $translator->getEmailMessage('MAIL_BODY_HELLO', $userFullname);
//                    $body .= LF . LF;
//
//                    if ($authSourceItem->allowAddAccount()) {
//                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY', $identification, $portalItem->getTitle(), $url, '15');
//                    } else {
//                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY', $identification, $portalItem->getTitle());
//                        $body .= LF . LF;
//                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY2', $authSourceItem->getTitle());
//
//                        $link = $authSourceItem->getPasswordChangeLink();
//                        $contactMail = $authSourceItem->getContactEMail();
//
//                        if (!empty($link)) {
//                            $body .= LF . LF;
//                            $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY2_LINK', $link);
//                        }
//
//                        if (!empty($contact_mail)) {
//                            $body .= LF . LF;
//                            $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY2_MAIL', $authSourceItem->getTitle(), $contactMail);
//                        }
//
//                        $body .= LF . LF;
//                        $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY_SORRY3');
//                    }
//
//                    $body .= LF . LF;
//                    if (empty($contactModerator)) {
//                        $body .= $translator->getMessage('SYSTEM_MAIL_REPLY_INFO') . LF;
//                        $body .= $modText;
//                        $body .= LF . LF;
//                    } else {
//                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO', $contactModerator->getFullname(), $contextItem->getTitle());
//                        $body .= LF . LF;
//                    }
//
//                    $mail->set_message($body);
//                    if (!$mail->send()) {
//                        $errorArray["send_" . $userItem->getItemId()] = '';
//                    }
//
//                    $userItem = $userList->getNext();
//                }
//            }
//
//            if (sizeof($errorArray) > 0) {
//                $xml = "<errors>\n";
//                foreach ($errorArray as $code => $description) {
//                    $xml .= "<" . $code . "><![CDATA[" . $description . "]]></" . $code . ">\n";
//                }
//                $xml .= "</errors>";
//            } else {
//                $xml = "<success></success>";
//            }
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function getRoomDetails($session_id, $context_id, $room_id)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($context_id);
//            $this->_environment->setSessionID($session_id);
//
//            $translator = $this->_environment->getTranslationObject();
//            $sessionItem = $this->_environment->getSessionItem();
//            $userId = $sessionItem->getValue('user_id');
//            $authSourceId = $sessionItem->getValue('auth_source');
//
//            // get user item
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//
//            // room access
//            $userManager->setUserIdLimit($userId);
//            $userManager->setAuthSourceLimit($authSourceId);
//            $userManager->setContextLimit($room_id);
//            $userManager->select();
//            $userList = $userManager->get();
//
//            $roomManager = $this->_environment->getRoomManager();
//            $roomItem = $roomManager->getItem($room_id);
//
//            global $c_commsy_domain, $c_commsy_url_path;
//            include_once('functions/curl_functions.php');
//
//            $contactPersons = $roomItem->getContactModeratorList();
//            $communityRooms = $roomItem->getCommunityList();
//            $mayEdit = ($userItem && ($userItem->isModerator() || $roomItem->mayEdit($userItem)));
//
//            $xml .= "<room>\n";
//            $xml .=     "<id><![CDATA[" . $roomItem->getItemId() . "]]></id>\n";
//            $xml .=     "<name><![CDATA[" . $roomItem->getTitle() . "]]></name>\n";
//            $xml .=     "<access><![CDATA[" . ($userList->isEmpty() ? "no" : "yes") . "]]></access>\n";
//            $xml .=     "<link><![CDATA[" . $c_commsy_domain . $c_commsy_url_path . "/" . _curl(false, $roomItem->getItemId(), 'home', 'index', array()) . "]]></link>\n";
//            $xml .=     "<description><![CDATA[" . $roomItem->getDescription() . "]]></description>\n";
//            $xml .=     "<mayEdit><![CDATA[" . ($mayEdit ? "yes" : "no") . "]]></mayEdit>\n";
//            $xml .=     "<isDeleted><![CDATA[" . ($roomItem->isDeleted() ? "yes" : "no") . "]]></isDeleted>\n";
//
//            $type = "group";
//            if ($roomItem->isProjectRoom()) {
//                $type = "project";
//            } else if ($roomItem->isCommunityRoom()) {
//                $type = "community";
//            }
//            $xml .=     "<type><![CDATA[" . $type . "]]></type>\n";
//
//            $xml .=     "<contacts>\n";
//            if ($contactPersons->isNotEmpty()) {
//                $contactPerson = $contactPersons->getFirst();
//
//                while ($contactPerson) {
//                    $xml .= "<person>\n";
//                    $xml .= "   <fullName><![CDATA[" . $contactPerson->getFullName() . "]]></fullName>\n";
//                    $xml .= "   <email><![CDATA[" . $contactPerson->getEmail() . "]]></email>\n";
//                    $xml .= "</person>\n";
//
//                    $contactPerson = $contactPersons->getNext();
//                }
//            }
//            $xml .=     "</contacts>\n";
//
//            $xml .=     "<communities>\n";
//            if ($communityRooms->isNotEmpty()) {
//                $communityRoom = $communityRooms->getFirst();
//
//                while ($communityRoom) {
//                    $xml .= "   <room>\n";
//                    $xml .= "      <id><![CDATA[" . $communityRoom->getItemId() . "]]></id>\n";
//                    $xml .= "      <title><![CDATA[" . $communityRoom->getTitle() . "]]></title>\n";
//                    $xml .= "   </room>\n";
//
//                    $communityRoom = $communityRooms->getNext();
//                }
//            }
//            $xml .=     "</communities>\n";
//
//            if ($this->_environment->getCurrentContextItem()->showTime() && ($roomItem->isProjectRoom() || $roomItem->isCommunityRoom())) {
//                $timeList = $roomItem->getTimeList();
//
//                if ($timeList->isNotEmpty()) {
//                    if ($roomItem->isContinuous()) {
//                        $xml .= "   <time assigned='true' continues='true'>\n";
//                        $xml .= "      <intervalTranslation><![CDATA[" . $translator->getMessage('COMMON_TIME_NAME') . "]]></intervalTranslation>\n";
//
//                        $timeItem = $timeList->getFirst();
//
//                        if ($roomItem->isClosed()) {
//                            $timeItemLast = $timeList->getLast();
//
//                            if ($timeItemLast->getItemId() == $timeItem->getItemId()) {
//                                $xml .= "<intervals>\n";
//                                $xml .= "   <interval>\n";
//                                $xml .= "      <title><![CDATA[" . $translator->getMessage("COMMON_FROM2") . " " . $translator->getTimeMessage($timeItem->getTitle()) . "]]></title>\n";
//                                $xml .= "      <id><![CDATA[" . $timeItem->getItemId() . "]]></id>\n";
//                                $xml .= "   </interval>\n";
//                                $xml .= "   <interval>\n";
//                                $xml .= "      <title><![CDATA[" . $translator->getMessage("COMMON_TO") . " " . $translator->getTimeMessage($timeItemLast->getTitle()) . "]]></title>\n";
//                                $xml .= "      <id><![CDATA[" . $timeItem->getItemId() . "]]></id>\n";
//                                $xml .= "   </interval>\n";
//                                $xml .= "</intervals>\n";
//                            } else {
//                                $xml .= "   <constant><![CDATA[" . $translator->getTimeMessage($timeItem->getTitle()) . "]]></constant>\n";
//                            }
//                        } else {
//                            $xml .= "   <constant><![CDATA[" . $translator->getMessage("ROOM_CONTINUOUS_SINCE") . ' ' . BRLF . $translator->getTimeMessage($timeItem->getTitle()) . "]]></constant>\n";
//                        }
//                    } else {
//                        $xml .= "   <time assigned='true' continues='false'>\n";
//                        $xml .= "      <intervalTranslation><![CDATA[" . $translator->getMessage('COMMON_TIME_NAME') . "]]></intervalTranslation>\n";
//
//                        $timeItem = $timeList->getFirst();
//                        $xml .= "<intervals>\n";
//                        while ($timeItem) {
//                            $xml .= "<interval>\n";
//                            $xml .= "   <title><![CDATA[" . $translator->getTimeMessage($timeItem->getTitle()) . "]]></title>\n";
//                            $xml .= "   <id><![CDATA[" . $timeItem->getItemId() . "]]></id>\n";
//                            $xml .= "</interval>\n";
//
//                            $timeItem = $timeList->getNext();
//                        }
//                        $xml .= "</intervals>\n";
//                    }
//                } else {
//                    $xml .= "<time assigned='false'>\n";
//                    $xml .= "   <intervalTranslation><![CDATA[" . $translator->getMessage('COMMON_TIME_NAME') . "]]></intervalTranslation>\n";
//                }
//            } else {
//                $xml .= "<time>\n";
//            }
//            $xml .= "</time>\n";
//
//            $xml .= "</room>";
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function sendContactMail($session_id, $context_id, $message)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
//            $context_id = $this->_encode_input($context_id);
//            $this->_environment->setCurrentContextID($context_id);
//            $contextItem = $this->_environment->getCurrentContextItem();
//            $errorArray = array();
//
//            // get the contact moderator list and prepare mail data
//            $moderatorList = $contextItem->getContactModeratorList();
//            $emailAddresses = array();
//            $recipients = "";
//            $moderatorItem = $moderatorList->getFirst();
//            while ($moderatorItem) {
//                $emailAddresses[] = $moderatorItem->getEmail();
//                $recipients .= $moderatorItem->getFullname() . LF;
//
//                $moderatorItem = $moderatorList->getnext();
//            }
//
//            // get user item
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $userId = $session->getValue('user_id');
//            $authSourceId = $session->getValue('auth_source');
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//
//            // determe language
//            $language = $contextItem->getLanguage();
//            if ($language == "user") {
//                $langguage = $userItem->getLanguage();
//                if ($language == "browser") {
//                    $langauge = $this->_environment->getSelectedLanguage();
//                }
//            }
//
//            $emailAddresses = array_filter($emailAddresses, function($element) {
//                return trim($element) !== "";
//            });
//
//            // setup and send mails
//            if ($emailAddresses) {
//                $translator = $this->_environment->getTranslationObject();
//
//                $oldLanguage = $translator->getSelectedLanguage();
//                $subject = $translator->getMessage("USER_ASK_MAIL_SUBJECT", $userItem->getFullname(), $contextItem->getTitle());
//
//                $body = "";
//                $body .= $this->_encode_input($message);
//                $body .= LF . LF;
//                $body .= "---" . LF;
//                $body .= $translator->getMessage("MAIL_SEND_TO", $recipients);
//                $body .= LF;
//
//                include_once('classes/cs_mail.php');
//                $mail = new cs_mail();
//                $mail->set_to(implode(',', $emailAddresses));
//                //$mail->set_from_email($userItem->getEmail());
//                //$mail->set_from_name($userItem->getFullname());
//                $mail->set_from_email($this->_environment->getServerItem()->getDefaultSenderAddress());
//                $mail->set_from_name($this->_environment->getCurrentPortalItem()->getTitle());
//                $mail->set_reply_to_name($userItem->getFullname());
//                $mail->set_reply_to_email($userItem->getEmail());
//                $mail->set_subject($subject);
//                $mail->set_message($body);
//                if (!$mail->send()) {
//                    $errorArray["send"] = '';
//                }
//
//                $translator->getSelectedLanguage($oldLanguage);
//            } else {
//                $errorArray["missing"] = "Es wurden keine E-Mailadressen hinterlegt.";
//            }
//
//            if (sizeof($errorArray) > 0) {
//                $xml = "<errors>\n";
//                foreach ($errorArray as $code => $description) {
//                    $xml .= "<" . $code . "><![CDATA[" . $description . "]]></" . $code . ">\n";
//                }
//                $xml .= "</errors>";
//            } else {
//                $xml = "<success></success>";
//            }
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    private function getRoomTemplates($isProjectType, $contextId, $userItem) {
//        if ($isProjectType) {
//            $roomManager = $this->_environment->getProjectManager();
//        } else {
//            $roomManager = $this->_environment->getCommunityManager();
//        }
//
//        $portalItem = $this->_environment->getCurrentContextItem();
//        $translator = $this->_environment->getTranslationObject();
//
//        $roomManager->setContextLimit($contextId);
//        $roomManager->setTemplateLimit();
//        $roomManager->select();
//
//        $roomList = $roomManager->get();
//        $defaultTemplateId = $portalItem->getDefaultProjectTemplateID();
//
//        $templateArray = array();
//        if ($roomList->isNotEmpty() || $defaultTemplateId != '-1') {
//            $templateArray[] = array(
//                "text"      => "*" . $translator->getMessage("CONFIGURATION_TEMPLATE_NO_CHOICE"),
//                "value"     => -1
//            );
//            $templateArray[] = array(
//                "text"      => "------------------------",
//                "value"     => "disabled"
//            );
//
//            if ($defaultTemplateId != "-1") {
//                $defaultItem = $roomManager->getItem($defaultTemplateId);
//
//                if (isset($defaultItem)) {
//                    $templateAvailability = $defaultItem->getTemplateAvailability();
//
//                    if ($templateAvailability == "0" && ($isProjectType || $defaultItem->isClosed())) {
//                        $templateArray[] = array(
//                            "text"      => "*" . $defaultItem->getTitle(),
//                            "value"     => $defaultItem->getItemID()
//                        );
//                        $templateArray[] = array(
//                            "text"      => "------------------------",
//                            "value"     => "disabled"
//                        );
//                    }
//                }
//            }
//
//            $roomItem = $roomList->getFirst();
//            while ($roomItem) {
//                if ($isProjectType) {
//                    $templateAvailability = $roomItem->getTemplateAvailability();
//                    $communityRoomMember = false;
//                    $communityList = $roomItem->getCommunityList();
//                    $userCommunityList = $userItem->getRelatedCommunityList();
//
//                    if ($communityList->isNotEmpty() && $userCommunityList->isNotEmpty()) {
//                        $communityItem = $communityList->getFirst();
//                        while ($communityItem) {
//                            $userCommunityItem = $userCommunityList->getFirst();
//                            while ($userCommunityItem) {
//                                if ($userCommunityItem->getItemID() == $communityItem->getItemID()) {
//                                    $communityRoomMember = true;
//                                }
//
//                                $userCommunityItem = $userCommunityList->getNext();
//                            }
//
//                            $communityItem = $communityList->getNext();
//                        }
//                    }
//
//                    if (  $templateAvailability == "0" ||
//                        ($this->_environment->inPortal() && $templateAvailability == "3" && $communityRoomMember) ||
//                        ($templateAvailability == "1" && $roomItem->mayEnter($userItem)) ||
//                        ($templateAvailability == "2" && $roomItem->mayEnter($userItem) && ($roomItem->isModeratorByUserID($userItem->getUserID(), $userItem->getAuthSource())))) {
//                        if ($roomItem->getItemID() != $defaultTemplateId || $roomItem->getTemplateAvailability() != "0") {
//                            //$this->_with_template_form_element2 = true;
//                            $templateArray[] = array(
//                                "text"      => $roomItem->getTitle(),
//                                "value"     => $roomItem->getItemID()
//                            );
//                        }
//                    }
//                } else {
//                    $templateAvailability = $roomItem->getCommunityTemplateAvailability();
//
//                    if (  $templateAvailability == "0" ||
//                        ($templateAvailability == "1" && $roomItem->mayEnter($userItem)) ||
//                        ($templateAvailability == "2" && $roomItem->mayEnter($userItem) && ($roomItem->isModeratorByUserID($userItem->getUserID(), $userItem->getAuthSource())))) {
//                        if ($roomItem->getItemID() != $defaultTemplateId || $roomItem->getTemplateAvailability() != "0") {
//                            //$this->_with_template_form_element3 = true;
//                            $templateArray[] = array(
//                                "text"      => $roomItem->getTitle(),
//                                "value"     => $roomItem->getItemID()
//                            );
//                        }
//                    }
//                }
//
//                $roomItem = $roomList->getNext();
//            }
//        }
//
//        return $templateArray;
//    }
//
//    public function getPortalRoomConfiguration($session_id, $portal_id)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($portal_id);
//            $portalItem = $this->_environment->getCurrentContextItem();
//            $translator = $this->_environment->getTranslationObject();
//
//            // get user item
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $userId = $session->getValue('user_id');
//            $authSourceId = $session->getValue('auth_source');
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//
//            $languageArray = $this->_environment->getAvailableLanguageArray();
//
//            $xml .= "<config>\n";
//
//            // languages
//            $xml .= "<languages>\n";
//            foreach ($languageArray as $language) {
//                $xml .= "<language><![CDATA[" . $language . "]]></language>\n";
//            }
//            $xml .= "</languages>\n";
//
//            // templates
//            $projectTemplateArray = $this->getRoomTemplates(true, $portal_id, $userItem);
//            $communityTemplateArray = $this->getRoomTemplates(false, $portal_id, $userItem);
//
//            if (!empty($projectTemplateArray) || !empty($communityTemplateArray)) {
//                $xml .= "<templates>\n";
//
//                if (!empty($projectTemplateArray)) {
//                    $xml .= "<project>\n";
//
//                    foreach ($projectTemplateArray as $projectTemplate) {
//                        $xml .= "<template>\n";
//                        $xml .= "<text><![CDATA[" . $projectTemplate["text"] . "]]></text>\n";
//                        $xml .= "<value><![CDATA[" . $projectTemplate["value"] . "]]></value>\n";
//                        $xml .= "</template>\n";
//                    }
//
//                    $xml .= "</project>\n";
//                }
//
//                if (!empty($communityTemplateArray)) {
//                    $xml .= "<community>\n";
//
//                    foreach ($communityTemplateArray as $communityTemplate) {
//                        $xml .= "<template>\n";
//                        $xml .= "<text><![CDATA[" . $communityTemplate["text"] . "]]></text>\n";
//                        $xml .= "<value><![CDATA[" . $communityTemplate["value"] . "]]></value>\n";
//                        $xml .= "</template>\n";
//                    }
//
//                    $xml .= "</community>\n";
//                }
//
//                $xml .= "</templates>\n";
//            }
//
//            // intervals
//            if ($portalItem->showTime()) {
//                $xml .= "<intervals>\n";
//
//                $xml .= "<title><![CDATA[" . $translator->getMessage('COMMON_TIME_NAME') . "]]></title>\n";
//
//                $currentTimeTitle = $portalItem->getTitleOfCurrentTime();
//
//                // if (isset($this->_item)) {
//                //     $time_list = $this->_item->getTimeList();
//                //     if ($time_list->isNotEmpty()) {
//                //        $time_item = $time_list->getFirst();
//                //        $linked_time_title = $time_item->getTitle();
//                //     }
//                // }
//                // if ( !empty($linked_time_title)
//                //      and $linked_time_title < $current_time_title
//                //        ) {
//                //         $start_time_title = $linked_time_title;
//                // } else {
//                //         $start_time_title = $current_time_title;
//                $startTimeTitle = $currentTimeTitle;
//                // }
//
//                $timeList = $portalItem->getTimeList();
//                if ($timeList->isNotEmpty()) {
//                    $timeItem = $timeList->getFirst();
//
//                    while ($timeItem) {
//                        if ($timeItem->getTitle() >= $startTimeTitle) {
//                            $xml .= "<interval>\n";
//                            $xml .= "<text><![CDATA[" . $translator->getTimeMessage($timeItem->getTitle()) . "]]></text>\n";
//                            $xml .= "<value><![CDATA[" . $timeItem->getItemID() . "]]></value>\n";
//                            $xml .= "</interval>\n";
//                        }
//
//                        $timeItem = $timeList->getNext();
//                    }
//                }
//
//                $xml .= "<interval>\n";
//                $xml .= "<text><![CDATA[" . $translator->getMessage("COMMON_CONTINUOUS") . "]]></text>\n";
//                $xml .= "<value><![CDATA[" . "cont" . "]]></value>\n";
//                $xml .= "</interval>\n";
//
//                $xml .= "</intervals>\n";
//            }
//
//            // community rooms
//            $communityRoomArray = array();
//            $communityList = $portalItem->getCommunityList();
//
//            if ($communityList->isNotEmpty()) {
//                $communityItem = $communityList->getFirst();
//
//                while ($communityItem) {
//                    if ($communityItem->isAssignmentOnlyOpenForRoomMembers() && !$communityItem->isUser($currentUser)) {
//                        $communityRoomArray[] = array(
//                            "text"      => $communityItem->getTitle(),
//                            "value"     => "disabled"
//                        );
//                    } else {
//                        $communityRoomArray[] = array(
//                            "text"      => $communityItem->getTitle(),
//                            "value"     => $communityItem->getItemID()
//                        );
//                    }
//
//                    $communityItem = $communityList->getNext();
//                }
//            }
//
//            $xml .= "<community_rooms>\n";
//
//            $xml .= "<mandatory><![CDATA[" . (($portalItem->getProjectRoomLinkStatus() == "optional") ? "no" : "yes") . "]]></mandatory>\n";
//
//            foreach ($communityRoomArray as $communityRoom) {
//                $xml .= "<room>\n";
//                $xml .= "<text><![CDATA[" . $communityRoom["text"] . "]]></text>\n";
//                $xml .= "<value><![CDATA[" . $communityRoom["value"] . "]]></value>\n";
//                $xml .= "</room>\n";
//            }
//
//            $xml .= "</community_rooms>\n";
//
//
//
//
//            /*if ( isset($this->_item)
//           and $this->_item->isProjectRoom()
//         ) {
//
//   $community_room_list = $this->_item->getCommunityList();
//   if ($community_room_list->getCount() > 0) {
//      $community_room_item = $community_room_list->getFirst();
//      while ($community_room_item) {
//         $temp_array['text'] = $community_room_item->getTitle();
//         $temp_array['value'] = $community_room_item->getItemID();
//         $community_room_array[] = $temp_array;
//         $community_room_item = $community_room_list->getNext();
//      }
//   }
//}
//$this->_shown_community_room_array = $community_room_array;
//
//
//
//*/
//
//
//
//
//
//
//
//            $xml .= "</config>";
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function touAccepted($session_id, $context_id)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
//            $this->_environment->setCurrentContextID($context_id);
//            $contextItem = $this->_environment->getCurrentContextItem();
//
//            // get user item
//            $this->_environment->setSessionID($session_id);
//            $session = $this->_environment->getSessionItem();
//            $userId = $session->getValue('user_id');
//            $authSourceId = $session->getValue('auth_source');
//            $userManager = $this->_environment->getUserManager();
//            $userItem = $userManager->getItemByUserIDAuthSourceID($userId, $authSourceId);
//
//            $portalUserItem = $userItem->GetRelatedPortalUserItem();
//            $portalUserItem->setAGBAcceptance();
//            $portalUserItem->save();
//
//            $xml .= "<tou>\n";
//            $xml .= "</tou>";
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
//    public function saveRoom($session_id, $context_id, $title, $id, $type, $template, $language, $intervals, $assignments, $description)
//    {
//        $xml = "";
//        if ($this->_isSessionValid($session_id)) {
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
//            // new or edit
//            $isNew = false;
//            if (!trim($id)) {
//                $isNew = true;
//            }
//
//            if ($isValid) {
//                $manager = $this->_environment->getManager($type);
//                if ($isNew) {
//                    if ($type == "project") {
//                        $projectOpeningStatus = $contextItem->getProjectRoomCreationStatus();
//                        if (!$userItem->isUser() || $projectOpeningStatus != "portal") {
//                            return new SoapFault('ERROR','Insufficent rights!');
//                        }
//                    } else if($type == "community") {
//                        $communityOpeningStatus = $contextItem->getCommunityRoomCreationStatus();
//                        if (!($userItem->isUser() && $communityOpeningStatus == "all") && !$userItem->isModerator()) {
//                            return new SoapFault('ERROR','Insufficent rights!');
//                        }
//                    } else {
//                        return new SoapFault('ERROR','Wrong type!');
//                    }
//
//                    $item = $manager->getNewItem();
//                    $item->setCreatorItem($userItem);
//                    $item->setCreationDate(getCurrentDateTimeInMySQL());
//
//                    if ($this->_environment->inCommunityRoom()) {
//                        $item->setContextID($this->_environment->getCurrentPortalID());
//                    } else {
//                        $item->setContextID($this->_environment->getCurrentContextID());
//                    }
//
//                    $item->open();
//
//                    if ($this->_environment->inPortal() || $this->_environment->inCommunityRoom()) {
//                        $item->setRoomContext($contextItem->getRoomContext());
//                    }
//                } else {
//                    if ($item->mayEdit($userItem)) {
//                        $item = $manager->getItem(trim($id));
//                    } else {
//                        return new SoapFault('ERROR','Insufficent rights!');
//                    }
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
//
//            $xml .= "<room>\n";
//            $xml .= "<id><![CDATA[" . $item->getItemId() . "]]></id>\n";
//            $xml .= "</room>";
//
//            $xml = $this->_encode_output($xml);
//        } else {
//            return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
//        }
//
//        return $xml;
//    }
//
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
//
//    public function archiveRoom($session_id, $portal_id, $room_id)
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
//                if (!$room->isTemplate()) {
//                    $room->moveToArchive();
//                }
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
//}