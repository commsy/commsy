<div id="content_row_two_max">
	<div class="input_row_100">
		<div class="float-left">___COMMON_NAME___:</div>
		<div id="popup_accounts_mail_namelist" class="input_container_180"></div>
	</div>
											
											
											
											{*

      if ( $this->_action_array['action'] != 'USER_EMAIL_SEND'
           and $this->_action_array['action'] != 'USER_EMAIL_ACCOUNT_PASSWORD'
           and $this->_action_array['action'] != 'USER_EMAIL_ACCOUNT_MERGE'
           ) {
         $this->_form->addCheckbox('with_mail','1',true,$this->_translator->getMessage('INDEX_ACTION_FORM_MAIL'),$this->_translator->getMessage('INDEX_ACTION_FORM_MAIL_OPTION'),'','','','onclick="cs_toggle();"');
      } else {
         $this->_form->addHidden('with_mail','1');
      }

      if ( $this->_with_copy_mod ) {
         $this->_form->combine();
         $this->_form->addCheckbox('copy','copy',false,'',$this->_translator->getMessage('MAILCOPY_TO_SENDER'),'','','','');
      } else {
         if ( isset($this->_cc_bcc_values[2]) and isset($this->_cc_bcc_values[3]) ) {
            $this->_form->addCheckbox('cc_moderator','cc_moderator',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[2]['text'],'','','','');
            $this->_form->combine('horizontal');
            $this->_form->addCheckbox('bcc_moderator','bcc_moderator',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[3]['text'],'','','','');
         }
         $this->_form->combine();
         $this->_form->addCheckbox('cc','cc',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[0]['text'],'','','','');
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('bcc','bcc',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[1]['text'],'','','','');
      }
      $this->_form->addTextField('subject','',$this->_translator->getMessage('COMMON_MAIL_SUBJECT'),'',200,'',false);
      $this->_form->addTextArea('content','',$this->_translator->getMessage('COMMON_CONTENT'),'',60,10, '', true,false,false);

      // buttons
      if ( $this->_action_array['action'] == 'USER_EMAIL_SEND'
           or $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD'
           or $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE') {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('INDEX_ACTION_SEND_MAIL_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','');
      } else {
         $tempMessage = "";
         switch( $this->_action_array['action'] )
         {
            case 'USER_ACCOUNT_DELETE':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_DELETE_BUTTON');
               break;
            case 'USER_ACCOUNT_FREE':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_FREE_BUTTON');
               break;
            case 'USER_ACCOUNT_LOCK':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_LOCK_BUTTON');
               break;
            case 'USER_MAKE_CONTACT_PERSON':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_MAKE_CONTACT_PERSON_BUTTON');
               break;
            case 'USER_STATUS_EDITOR':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_EDITOR_BUTTON');
               break;
            case 'USER_STATUS_MODERATOR':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_MODERATOR_BUTTON');
               break;
            case 'USER_STATUS_ORGANIZER':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_ORGANIZER_BUTTON');
               break;
            case 'USER_STATUS_USER':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_USER_BUTTON');
               break;
            case 'USER_UNMAKE_CONTACT_PERSON':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
               break;
            default:
               $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_account_action_form(539) ');
               break;
         }
         $this->_form->addButtonBar('option', $tempMessage, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'), '', '', '', '');
      }
      *}
											
</div>