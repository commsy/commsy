<?php
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

define('DS', '/');

// load smarty library
require_once('libs/smarty/Smarty.class.php');

// load security functions
require_once('functions/security_functions.php');

/**
 * 
 * This class extends the Smarty main class, as an more flexible way to setup Smarty
 * @author christoph
 *
 */
class cs_smarty extends Smarty {
	private $theme = '';
	private $environment = null;
	private $translator = null;
	
	function __construct($environment, $theme = '') {
		// call parent constructor
		parent::__construct();
		
		// set environment and translator
		$this->environment = $environment;
		$this->translator = $this->environment->getTranslationObject();
		
		// set directory paths
		$this->setTemplateDir('htdocs/templates/');
		$this->setCompileDir('htdocs/templates/templates_c/');
		$this->setConfigDir('etc/smarty/');
		$this->setCacheDir('cache/');
		
		// set caching
		$this->caching = Smarty::CACHING_OFF;
		
		// theme support
		if(!$this->setTheme($theme) && !empty($theme)) {
			die('Error: Theme "' . $theme . '" does not exist');
		}
		
		// multilanguage support
		//$this->registerFilter('pre', array($this, 'smarty_filter_i18n'));
		$this->registerFilter('output', array($this, 'smarty_filter_i18n'));
		$this->registerPlugin('function', 'i18n', array($this, 'smarty_function_i18n'));
	}
	
	public function setTheme($theme) {
		$this->theme = $theme;
		
		if(!empty($theme) && file_exists($this->getTemplateDir(0) . 'themes/' . $theme)) {
			$this->setTemplateDir($this->getTemplateDir(0) . 'themes/' . $theme);
			$this->setCompileDir($this->getCompileDir(0) . $theme);
			return true;
		} else {
			return false;
		}
	}
	
	public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
		// modify compile and cache id for multilanguage support
		$compile_id = $this->environment->getSelectedLanguage() . '-' . $compile_id;
		$cache_id = $this->environment->getSelectedLanguage() . '-' . $cache_id;
		
		return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
	}
	
	public function setPostToken($active) {		
		if($active === true) {
			// register filter
			$this->registerFilter('output', array($this, 'smarty_filter_post_token'));
		} else {
			// unregister filter
			$this->unregisterFilter('output', array($this, 'smarty_filter_post_token'));;
		}
	}
	
	public function display($template, $output_mode) {
		// check if template exists
		if(!file_exists($this->getTemplateDir(0) . $template . '_' . $output_mode . '.tpl')) {
			// try fall back to html output
			if($output_mode != 'html') {
				$this->display($template, 'html');
				
			// try fall back to default theme
			} elseif($template !== 'default') {
				$this->display('default', 'html');
				
			} else {
				throw new Exception('Template ' . $this->getTemplateDir(0) . $template . '_' . $output_mode . '.tpl does not exist!', 101);
			}
		} else {
			parent::display($template . '_' . $output_mode . '.tpl');
		}
	}
	
	public function smarty_filter_i18n($tpl_source, Smarty_Internal_Template $template) {
		return preg_replace_callback('/___(.+?)___/', array($this, 'compile_lang'), $tpl_source);
	}
	
	public function smarty_filter_post_token($tpl_source, Smarty_Internal_template $template) {
		return addTokenToPost($tpl_source);
	}
	
	public function smarty_function_i18n(array $params, Smarty_Internal_Template $template) {
		$translator = $this->environment->getTranslationObject();
		$param1 = isset($params['param1']) ? $params['param1'] : '';
		$param2 = isset($params['param2']) ? $params['param2'] : '';
		$param3 = isset($params['param3']) ? $params['param3'] : '';
		$param4 = isset($params['param4']) ? $params['param4'] : '';
		$param5 = isset($params['param5']) ? $params['param5'] : '';
		
		return $translator->getMessage($params['tag'], $param1, $param2, $param3, $param4, $param5);
	}
	
	public function compile_lang($key) {
		return $this->translator->getMessage($key[1]);
	}
}
?>