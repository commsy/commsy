<?php
class cs_popup_profile_controller {
	private $_environment = null;
	private $_popup_controller = null;
	private $_return = '';
	
	/**
	* constructor
	*/
	public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
		$this->_environment = $environment;
		$this->_popup_controller = $popup_controller;
	}
	
	public function edit($item_id) {

	}
	
	public function create($form_data) {
		
	}
	
	public function getReturn() {
		return $this->_return;
	}
	
	public function getFieldInformation() {
		return array(
			array(	'name'		=> 'title',
					'type'		=> 'text',
					'mandatory' => true),
			array(	'name'		=> 'description',
					'type'		=> 'text',
					'mandatory'	=> false)
		);
	}
	
	public function assignTemplateVars() {
		$translator = $this->_environment->getTranslationObject();
		
		// general information
		$general_information = array();
		
		// max upload size
		$val = ini_get('upload_max_filesize');
		$val = trim($val);
		$last = $val[mb_strlen($val) - 1];
		switch($last) {
			case 'k':
			case 'K':
				$val *= 1024;
				break;
			case 'm':
			case 'M':
				$val *= 1048576;
				break;
		}
		$meg_val = round($val / 1048576);
		$general_information['max_upload_size'] = $meg_val;
		
		$this->_popup_controller->assign('popup', 'general', $general_information);
		
		// portal information
		$portal_information = array();
		$portal_information['portal_name'] = $this->_environment->getCurrentPortalItem()->getTitle();
		$this->_popup_controller->assign('popup', 'portal', $portal_information);
		
		// form information
		$form_information = array();
		
		// languages
		$languages = array();
		$languages[] = array(
			'value'		=>	'browser',
			'text'		=>	$translator->getMessage('USER_BROWSER_LANGUAGE')
		);
		$languages[] = array(
			'value'		=>	'disabled',
			'text'		=>	'------------------'
		);
		
		$available_languages = $this->_environment->getAvailableLanguageArray();
		foreach($available_languages as $language) {
			$languages[] = array(
				'value'		=>	$language,
				'text'		=>	$translator->getLanguageLabelOriginally($language)
			);
		}
		
		$form_information['languages'] = $languages;
		
		$this->_popup_controller->assign('popup', 'form', $form_information);
	}
	
	private function cleanup_session($current_iid) {
		$environment = $this->_environment;
		$session = $this->_environment->getSessionItem();

		$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
		$session->unsetValue($environment->getCurrentModule().'_add_tags');
		$session->unsetValue($environment->getCurrentModule().'_add_files');
		$session->unsetValue($current_iid.'_post_vars');
	}
}