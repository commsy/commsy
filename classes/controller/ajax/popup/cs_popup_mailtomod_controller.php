<?php
class cs_popup_mailtomod_controller implements cs_rubric_popup_controller{
    private $_environment = null;
    private $_popup_controller = null;
    private $_receiver_array = null;
    private $_return = '';

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup() {
        // assign template vars
        $this->assignTemplateVars();
    }

    public function getFieldInformation() {
        return array(
            array(	'name'		=> 'subject',
					'type'		=> 'text',
					'mandatory' => true),
            array(	'name'		=> 'content',
					'type'		=> 'text',
					'mandatory'	=> true)
        );
    }

    private function assignTemplateVars() {
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        // user information
        $user_information = array();
        $user_information['fullname'] = $current_user->getFullName();
        $user_information['mail'] = $current_user->getEmail();
        $this->_popup_controller->assign('popup', 'user', $user_information);

        $mod_information = array();
        $mod_information['list'] = implode(', ', $this->getRecieverList());
        $this->_popup_controller->assign('popup', 'mod', $mod_information);

        $translator = $this->_environment->getTranslationObject();
        
        if ( $context_item->isCommunityRoom() ) {
            $body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY', $context_item->getTitle());
        } elseif ( $context_item->isProjectRoom() ) {
            $body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT', $context_item->getTitle());
        } elseif ( $context_item->isGroupRoom() ) {
            $body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_GROUPROOM', $context_item->getTitle());
        } elseif ( $context_item->isPortal() ) {
            $body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PORTAL', $context_item->getTitle());
        } elseif ( $context_item->isServer() ) {
            $body_message = $translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_SERVER', $context_item->getTitle());
        }
        
        $this->_popup_controller->assign('popup', 'body', $body_message);
    }

    public function save($form_data, $additional = array()) {
        $mail = new cs_mail();
        //TODO: Mail mit Formulardaten etc. füttern
        //$mail->set_to($this->_environment->);
        $mail->set_from_email($this->_environment->getCurrentUser()->getEmail());
        $mail->set_from_name($this->_environment->getCurrentUser()->getFullName());

        $to_list = "";
        $context_item = $this->_environment->getCurrentContextItem();
        foreach ($context_item->getModeratorList() as $mod) {
            $to_list .= $mod->getEmail().',';
        }
        // delete last ','
        $to_list = substr($to_list, 0, strlen($to_list) -1);

        $mail->set_to($to_list);

        $mail->set_message($form_data['content']);
        $mail->set_subject($form_data['subject']);

        return $mail->send();
    }

    private function getRecieverList() {
        $translator = $this->_environment->getTranslationObject();
        
        $context_item = $this->_environment->getCurrentContextItem();
        $mod_list = $context_item->getModeratorList();
        $receiver_array = array();
        if (!$mod_list->isEmpty()) {
            $mod_item = $mod_list->getFirst();
            while ($mod_item) {
                $temp_array = array();
                $temp_array['value'] = $mod_item->getEmail();
                if ($mod_item->isEmailVisible()) {
                    $temp_array['text'] = $mod_item->getFullName().' ('.$mod_item->getEmail().')';
                } else {
                    $temp_array['text'] = $mod_item->getFullName().' ('.$translator->getMessage('USER_EMAIL_HIDDEN2').')';
                }
                $receiver_array[] = $temp_array;
                $mod_item = $mod_list->getNext();
            }
        }
        return $receiver_array;
    }

}