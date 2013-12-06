<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2013 Dr. Iver Jackewitz
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

class cs_connection_commsy {

   private $_environment = null;
   private $_translator = null;
   private $_error_array = array();
   private $_connection = null;

   public function __construct ($environment) {
      $this->_environment = $environment;
      $this->_translator = $this->_environment->getTranslationObject();
   }

   private function _addError ($text,$id="") {
   	if ( !empty($text) ) {
   		if ( !empty($id) ) {
   			$this->_error_array[$id] = $text;
   		} else {
   			$this->_error_array[] = $text;
   		}
   	}
   }
   
   public function getErrorArray () {
   	return $this->_error_array;
   }
   
   private function _initConnection ($url,$proxy="") {
   	$retour = false;
   	if ( !empty($url) ) {
	      if ( class_exists('SoapClient') ) {
	   		$options = array("trace" => 1, "exceptions" => 0, 'user_agent'=>'PHP-SOAP/php-version', 'connection_timeout' => 150);
	   		if ( $this->_environment->getConfiguration('c_proxy_ip')
	   			  and ( $proxy == CS_YES
	   			  		  or empty($proxy)
	   			  		)
	   		   ) {
	   			$options['proxy_host'] = $this->_environment->getConfiguration('c_proxy_ip');
	   		}
	   		if ( $this->_environment->getConfiguration('c_proxy_port')
	   			  and ( $proxy == CS_YES
	   			  		  or empty($proxy)
	   			  		)
	   		   ) {
	   			$options['proxy_port'] = $this->_environment->getConfiguration('c_proxy_port');
	   		}
	   		$soap_url = $url.'/soap_wsdl.php';
	   		$new_server = new SoapClient($soap_url, $options);
	   		if ( empty($new_server) ) {
	   			$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_SOAP_CONNECT',$url));
	   		} else {
	   			$this->_connection = $new_server;
	   			$retour = true;
	   		}
	   	} else {
	   		$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_PHP_SOAP'));
	   	}
   	} else {
   		$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_URL_EMPTY'));
   	}
   	return $retour;
   }
   
   public function testConnection ($url,$key,$proxy="") {
   	return true;
   	// TBD
   	$retour = false;
   	if ( $this->_initConnection($url,$proxy) ) {
   		$function_array = $this->_connection->__getFunctions();
   		pr($function_array);
   		exit;
   		// are all required methods available??
   	} else {
   		$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_SOAP_CONNECT',$url));
   	}
   	return $retour;
   }
}
?>