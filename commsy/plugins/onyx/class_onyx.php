<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2011 Dr. Iver Jackewitz
//
// This file is part of the onyx plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

include_once('classes/cs_plugin.php');
class class_onyx extends cs_plugin {
   
   private $_format_media_key = '(:qti';
   private $_player = NULL;
   private $_player_url_base = NULL;
   private $_player_url_wsdl = NULL;
   private $_player_url_run = NULL;
   private $_player_lms_key = NULL;
   
   private $_reporter = NULL;
   private $_reporter_url_wsdl = NULL;
    
   private $_proxy_use = NULL;

   private $_plugin_folder = NULL;
       
   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_translator->addMessageDatFolder('plugins/onyx/messages');
      $this->_identifier = 'onyx';
      $this->_title      = 'Onyx';
      $this->_image_path = 'plugins/'.$this->getIdentifier();
      
      $this->_player_url_base = $this->_getConfigValueFor($this->_identifier.'_player_url');
      $this->_player_url_wsdl = $this->_player_url_base.'/services?wsdl'; 
      $this->_player_url_run = $this->_player_url_base.'/onyxrun'; 
      $this->_player_lms_key = $this->_getConfigValueFor($this->_identifier.'_lms_name');
      $this->_reporter_url_wsdl = $this->_player_url_base.'/reporterservices?wsdl'; 
      $this->_proxy_use = $this->_getConfigValueFor($this->_identifier.'_proxy');
      
      $this->_plugin_folder = 'plugins'.DIRECTORY_SEPARATOR.$this->getIdentifier();
   }

   public function getDescription () {
      return $this->_translator->getMessage('ONYX_DESCRIPTION');
   }

   public function getHomepage () {
      return '';
   }

   public function isConfigurableInPortal () {
      return true;
   }

   public function configurationAtPortal ( $type = '', $values = array() ) {
      $retour = '';
      if ( $type == 'change_form' ) {
         $retour = true;
      } elseif ( $type == 'create_form' ) {
         if ( !empty($values['form']) ) {
            $retour = $values['form'];
            $retour->addTextfield( $this->_identifier.'_player_url',
                                   '',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_URL'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
            $retour->combine();
            $retour->addTextfield( $this->_identifier.'_lms_name',
                                   '',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_LMS_NAME'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
            $proxy_host = $this->_environment->getConfiguration('c_proxy_ip');
            $proxy_port = $this->_environment->getConfiguration('c_proxy_port');
            if ( !empty($proxy_host)
                 and !empty($proxy_port)
               ) {
               $retour->combine();
               $retour->addCheckbox( $this->_identifier.'_proxy',
                                     -1,
                                     false,
                                     $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG_PROXY'),
                                     $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG_DESC_PROXY',$proxy_host.':'.$proxy_port),
                                     false,
                                     false
                                   );
            }
         }
      } elseif ( $type == 'save_config'
                 and !empty($values['current_context_item'])
               ) {
         $config_array = array();
         if ( isset( $values[$this->_identifier.'_player_url'] ) ) {
            $config_array[$this->_identifier.'_player_url'] = $values[$this->_identifier.'_player_url'];
         }
         if ( isset( $values[$this->_identifier.'_lms_name'] ) ) {
            $config_array[$this->_identifier.'_lms_name'] = $values[$this->_identifier.'_lms_name'];
         }
         if ( isset( $values[$this->_identifier.'_proxy'] ) ) {
            $config_array[$this->_identifier.'_proxy'] = $values[$this->_identifier.'_proxy'];
         } else {
            $config_array[$this->_identifier.'_proxy'] = '';
         }
         $values['current_context_item']->setPluginConfigForPlugin($this->_identifier,$config_array);
      } elseif ( $type == 'load_values_item'
                 and !empty($values['current_context_item'])
               ) {
         $retour = array();
         $config = $values['current_context_item']->getPluginConfigForPlugin($this->_identifier);
         if ( !empty($config[$this->_identifier.'_player_url']) ) {
            $retour[$this->_identifier.'_player_url'] = $config[$this->_identifier.'_player_url'];
         }
         if ( !empty($config[$this->_identifier.'_lms_name']) ) {
            $retour[$this->_identifier.'_lms_name'] = $config[$this->_identifier.'_lms_name'];
         }
         if ( !empty($config[$this->_identifier.'_proxy']) ) {
            $retour[$this->_identifier.'_proxy'] = $config[$this->_identifier.'_proxy'];
         }
      }
      return $retour;
   }
   
   public function getMediaRegExp () {
      $retour = array();
      $retour[$this->_format_media_key] = '~\\(:qti (.*?)(\\s.*?)?\\s*?:\\)~eu';
      $retour[$this->_format_media_key.'rep'] = '~\\(:qtirep (.*?)(\\s.*?)?\\s*?:\\)~eu';
      return $retour;
   }
   
   public function formatMedia ( $params ) {
      $retour = '';
      if ( !empty($params['key'])
           and $params['key'] == $this->_format_media_key.'rep'
           and !empty($params['value_new'])
           and strstr($params['value_new'],$this->_format_media_key.'rep')
           and !empty($this->_player_url_base)
         ) {
      	$retour = $this->_formatMediaReporter($params);
      } elseif ( !empty($params['key'])
           and $params['key'] == $this->_format_media_key
           and !empty($params['value_new'])
           and strstr($params['value_new'],$this->_format_media_key)
           and !empty($this->_player_url_base)
         ) {
         $key = $params['key'];
         $value_new = $params['value_new'];
         $args_array = array();
         if ( !empty($params['args_array']) ) {
            $args_array = $params['args_array'];
         }

         if ( !empty($args_array[2]) ) {
            $args = $this->_text_converter->_parseArgs($args_array[2]);
            if ( !empty($args) ) {
               $args_array = array_merge($args_array,$args);
            }
         }
         
         $file_array = array();
         if ( !empty($params['file_array']) ) {
            $file_array = $params['file_array'];
         }
         $temp_file_name = htmlentities($args_array[1], ENT_NOQUOTES, 'UTF-8');
         if ( !empty($file_array[$temp_file_name]) ) {
            $file = $file_array[$temp_file_name];
         } elseif ( !empty($file_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')]) ) {
            $file = $file_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')];
         }

         // params
         $embedded = false;
         if ( !empty($args_array['embedded']) ) {
            $embedded = true;
         }

         $name = '';
         if ( !empty($args_array['text']) ) {
            $name = $args_array['text'];
         } elseif ( !empty($file) ) {
            $name = $file->getDisplayName();
         }

         $navi = NULL;
         if ( isset($args_array['navi']) ) {
            $navi = $args_array['navi'];
         }

         $saveResult = 0;
         if ( isset($args_array['save']) ) {
            $saveResult = $args_array['save'];
         }
         $saveAim = '';
         if ( isset($args_array['saveaim'])
         	  and $args_array['saveaim'] == 'section'
            ) {
         	$saveAim = $args_array['saveaim'];
         }
         $savePeriod = '';
         if ( isset($args_array['saveperiod'])
         	  and ( $args_array['saveperiod'] == 'day'
         	  		  or $args_array['saveperiod'] == 'week'
         	  		  or $args_array['saveperiod'] == 'month'
         	  )
         	) {
         	$savePeriod = $args_array['saveperiod'];
         }
          
         if ( !empty($args['target'])
              and ( $args['target'] == '_blank'
                    or $args['target'] == 'newwin'
                    or $args['target'] == 'tab'
                  )
            ) {
            $display = $args['target'];
         } elseif ( !empty($args['newwin']) ) {
            $display = 'newwin';
         } else {
            $display = 'window';
         }

         if ( isset($file) ) {

            // now connect onyx web player
            $player = $this->_getPlayerObject();
            
            // first: Anmeldung des Tests
            $session_item = $this->_environment->getSessionItem();
            
            // id a json_string with infos
            $id_array = array();
            $id_array['SID'] = $session_item->getSessionID();
            $id_array['cid'] = $this->_environment->getCurrentContextID();
            $id_array['fid'] = $file->getFileID();
            $id_array['save'] = $saveResult;
            if ( !empty($saveAim) ) {
            	$id_array['saveaim'] = $saveAim;
            }
            if ( !empty($savePeriod) ) {
            	$id_array['saveperiod'] = $savePeriod;
            }
            include_once('functions/date_functions.php');
            $id_array['time'] = getCurrentDateTimeInMySQL();
            $id = json_encode($id_array);
            $id = str_replace('"','\'',$id);
            
            $qti = $file->getString();
            $lang = $this->_environment->getSelectedLanguage();
            $inst = '';
            if ( !isset($navi) ) { 
               if ($embedded) {
                  $navi = 'onyxwithoutnav';
               } else {
                  $navi = '';
               }
            } else {
               if ( !empty($navi) and $navi == 'true' ) {
                  $navi = '';                  
               } else {
                  $navi = 'onyxwithoutnav';                  
               }
            }
            $lms = $this->_player_lms_key;
            $solution = 'true';
            
            $success = $player->run($id,$qti,$lang,$inst,$navi,$lms,$solution);  
            if ( $success and !is_soap_fault($success) ) {
               if ($embedded) {
                  $retour .= '<style type="text/css">
   <!--
      iframe.onyx {
         width: 630px;
         height: 500px;
         border: 0px;
      }
   -->
   </style>';
                  $retour .= '<div><iframe class="onyx" src="'.$this->_player_url_run.'?id='.$id.'"></iframe></div>';
               } else {
                  if ( $display == 'newwin'
                       or $display == '_blank'
                       or $display == 'tab'
                     ) {
                     $target = 'target="_blank"';
                     $onclick = '';
                  } else {
                     $target = 'target="help"';
                     $onclick = 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, dependent=yes, copyhistory=yes, width=900, height=600\');"';
                  }
                  
                  $c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
                  $retour = '<a href="'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID().'&amp;mod='.$this->_identifier.'&amp;fct=showqti&amp;iid='.$id.'" '.$target.' '.$onclick.'>'.$name.'</a>';                  
               }
            }            
         }
      }
      return $retour;
   }

   private function _formatMediaReporter ($params) {
   	$retour = '';
            
   	$key = $params['key'];
      $value_new = $params['value_new'];
      $args_array = array();
      if ( !empty($params['args_array']) ) {
         $args_array = $params['args_array'];
      }

      if ( !empty($args_array[2]) ) {
         $args = $this->_text_converter->_parseArgs($args_array[2]);
         if ( !empty($args) ) {
            $args_array = array_merge($args_array,$args);
         }
      }
         
      $file_array = array();
      if ( !empty($params['file_array']) ) {
         $file_array = $params['file_array'];
      }
      $temp_file_name = htmlentities($args_array[1], ENT_NOQUOTES, 'UTF-8');
      if ( !empty($file_array[$temp_file_name]) ) {
         $file = $file_array[$temp_file_name];
      } elseif ( !empty($file_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')]) ) {
         $file = $file_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')];
      }

      // params
      $embedded = false;
      if ( !empty($args_array['embedded']) ) {
         $embedded = true;
      }

      $name = '';
      if ( !empty($args_array['text']) ) {
         $name = $args_array['text'];
      } elseif ( !empty($file) ) {
         $name = $this->_translator->getMessage('ONYX_REPORTER_LINK').': '.$file->getDisplayName();
      }

      if ( !empty($args['target'])
           and ( $args['target'] == '_blank'
                 or $args['target'] == 'newwin'
                 or $args['target'] == 'tab'
               )
         ) {
         $display = $args['target'];
      } elseif ( !empty($args['newwin']) ) {
         $display = 'newwin';
      } else {
         $display = 'window';
      }
      
      $choice = 4; // Testauswertung
      if ( !empty($args_array['mode']) ) {
      	if ( $args_array['mode'] == 'stats' ) {
      		$choice = 5; // statistics
      	}
      }

      if ( isset($file) ) {

         // now connect onyx web reporter
         $reporter = $this->_getReporterObject();

         // first: Anmeldung des LMS
         $version = 2;
         $current_user_item = $this->_environment->getCurrentUserItem();
         $user_id = $current_user_item->getUserID();
         $role = 1;
         if ( !$current_user_item->isModerator() ) {
         	$role = 0;
         }
         $session_item = $this->_environment->getSessionItem();
         $session_id = $session_item->getSessionID();
         $firstname = $current_user_item->getFirstName();
         $lastname = $current_user_item->getLastName();
         
         $reporter_session = $reporter->armSiteXML(
         		$version, // 1 oder 2
         		$user_id,
         		$role, // 0 = student , 1 = tutor
         		$session_id, // secretToShare,
         		$firstname, // optionalUserLastName,
         		$lastname, // optionalUserFirstName,
         		"" // additionalParams
         );
          
         // second: Initialisierung des Tests
         $arrOnyxResults = array();
         foreach ($file_array as $file_item) {
         	if ( !empty($file_item)
         		  and $file_item->getFileID() != $file->getFileID()
         		  and strtolower($file_item->getExtension()) == 'zip'
         		  and stristr($file_item->getFileName(), $file->getFileID().'_')
         		) {
         		$zip = new ZipArchive;
         		$res = $zip->open($file_item->getDiskFileName());
         		if ( $res === TRUE ) {
         			$result_xml = $zip->getFromName('result.xml');
         			if ( !empty($result_xml) ) {
         				
         				$resultUserIdent = md5($file_item->getFileName());
         				
         				$file_name_array = explode('_', $file_item->getFileName());
         				$userFirstname = 'Should be';
         				if ( !empty($file_name_array[3] ) ) {
         					$userFirstname = $file_name_array[3];
         					if ( stristr($userFirstname,'.zip') ) {
         						$userFirstname = str_replace('.zip','',$userFirstname);
         					}
         				}
         				$userLastname = 'overwritten';
         				if ( !empty($file_name_array[2] ) ) {
         					$userLastname = $file_name_array[2];
         					if ( stristr($userLastname,'.zip') ) {
         						$userLastname = str_replace('.zip','',$userLastname);
         					}
         				}
         				 
         				$OnyxResult = new ONYX_StdClass();
         				$OnyxResult->studentId = $resultUserIdent;
         				$OnyxResult->firstname = $userFirstname;
         				$OnyxResult->lastname  = $userLastname;
         				$OnyxResult->groupname = "";
         				$OnyxResult->tutorname = "";
         				$OnyxResult->contentFile = $file->getString();
         				$OnyxResult->resultsFile = $result_xml;
         				
         				$arrOnyxResults[] = $OnyxResult;
         			}
         			$zip->close();
         		} else {
         			# error TBD
         		}
         	}
         }
         
         if ( !empty($arrOnyxResults) ) {
	         $contentPackage = $file->getString();
	         $success = $reporter->initiateSiteXML(
	         		$version, // 1 oder 2
	         		$reporter_session, // sessionId,
	         		$session_id, // secretToShare,
	         		$arrOnyxResults,
	         		$contentPackage, // optionalContentPackage,
	         		NULL // additionalParams
	         );
	
	         // third: show reporter
	         if ( $success and !is_soap_fault($success) ) {
	            $url = $success.$choice.'?sid='.$reporter_session.'&secret='.$session_id;
	         	if ($embedded) {
	               $retour .= '<style type="text/css">
	   <!--
	      iframe.onyx {
	         width: 630px;
	         height: 500px;
	         border: 0px;
	      }
	   -->
	   </style>';
	               $retour .= '<div><iframe class="onyx" src="'.$url.'"></iframe></div>';
	            } else {
	               if ( $display == 'newwin'
	                    or $display == '_blank'
	                    or $display == 'tab'
	                  ) {
	                  $target = 'target="_blank"';
	                  $onclick = '';
	               } else {
	                  $target = 'target="help"';
	                  $onclick = 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, dependent=yes, copyhistory=yes, width=900, height=600\');"';
	               }
	                  
	               $c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
	               $retour = '<a href="'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID().'&amp;mod='.$this->_identifier.'&amp;fct=showrep&amp;url='.rawurlencode($url).'" '.$target.' '.$onclick.'>'.$name.'</a>';

	               // save url in session for security reason
	               $onyx_reporter_url_array = array();
	               if ( $session_item->issetValue('onyx_reporter_url_array') ) {
	               	$onyx_reporter_url_array = $session_item->getValue('onyx_reporter_url_array');
	               }
	               $onyx_reporter_url_array[] = $url;
	               $session_item->setValue('onyx_reporter_url_array',$onyx_reporter_url_array);
	            }
	         } else {
	         	// error TBD
	         }
         } else {
         	$retour = $this->_translator->getMessage('ONYX_REPORTER_NO_RESULTS');
         }          
      }
   	return $retour;
   }
   
   private function _getPlayerObject () {
      $retour = NULL;
      if ( empty($this->_player) ) {
         if ( class_exists('SoapClient') ) {            
            $options = array("trace" => 1, "exceptions" => 0, 'user_agent'=>'PHP-SOAP/php-version', 'connection_timeout' => 150);
            if ( $this->_environment->getConfiguration('c_proxy_ip') ) {
               if ( isset($this->_proxy_use)
                    and !empty($this->_proxy_use)
                    and $this->_proxy_use == '-1'
                  ) {
                  // no proxy use
               } else {
                  $options['proxy_host'] = $this->_environment->getConfiguration('c_proxy_ip');
               }
            }
            if ( $this->_environment->getConfiguration('c_proxy_port') ) {
               if ( isset($this->_proxy_use)
                    and !empty($this->_proxy_use)
                    and $this->_proxy_use == '-1'
                  ) {
                  // no proxy use
               } else {
                  $options['proxy_port'] = $this->_environment->getConfiguration('c_proxy_port');
               }
            }
            $this->_player = new SoapClient($this->_player_url_wsdl, $options);
         } else {
            $html .= 'SOAP-Funktionen von PHP stehen nicht zur Verfügung. Bitte aktivieren Sie diese Funktionen oder lassen Sie diese aktivieren.';
         }
      }
      if ( !empty($this->_player) ) {
         $retour = $this->_player;
      }
      return $retour;
   }
   
   private function _getReporterObject () {
      $retour = NULL;
      if ( empty($this->_reporter) ) {
         if ( class_exists('SoapClient') ) {            
            $options = array("trace" => 1, "exceptions" => 0, 'user_agent'=>'PHP-SOAP/php-version', 'connection_timeout' => 150);
            if ( $this->_environment->getConfiguration('c_proxy_ip') ) {
               if ( isset($this->_proxy_use)
                    and !empty($this->_proxy_use)
                    and $this->_proxy_use == '-1'
                  ) {
                  // no proxy use
               } else {
                  $options['proxy_host'] = $this->_environment->getConfiguration('c_proxy_ip');
               }
            }
            if ( $this->_environment->getConfiguration('c_proxy_port') ) {
               if ( isset($this->_proxy_use)
                    and !empty($this->_proxy_use)
                    and $this->_proxy_use == '-1'
                  ) {
                  // no proxy use
               } else {
                  $options['proxy_port'] = $this->_environment->getConfiguration('c_proxy_port');
               }
            }
            $this->_reporter = new SoapClient($this->_reporter_url_wsdl, $options);
         } else {
            $html .= 'SOAP-Funktionen von PHP stehen nicht zur Verfügung. Bitte aktivieren Sie diese Funktionen oder lassen Sie diese aktivieren.';
         }
      }
      if ( !empty($this->_reporter) ) {
         $retour = $this->_reporter;
      }
      return $retour;
   }
   
   public function getPlayerRunUrl () {
      return $this->_player_url_run;
   }
   
   public function getTextFormatingInformationAsHTML () {
      $retour = $this->_translator->getMessage('ONYX_TEXTFORMATING_DESCRIPTION',$this->_environment->getCurrentContextID(),$this->_identifier,'showtextformatinginfo');
      return $retour;
   }
   
   public function getTextFormatingInformationForWindowAsHTML () {
      $retour  = $this->_translator->getMessage('ONYX_TEXTFORMATING_DESCRIPTION_WINDOW');
      $retour .= BRLF;
      $retour .= $this->_translator->getMessage('ONYX_TEXTFORMATING_DESCRIPTION_WINDOW_REP');
      return $retour;
   }
   
   /* RETURN and SAVE result
    * 
    * SOAP
    * ----
    * <message name="saveResult">
    *    <part name="uniqueId" type="xsd:string"/>
    *    <part name="resultFile" type="xsd:base64Binary"/>
    * </message>
    * 
    * unique id -> infos via json
    *
    */   
   public function getSOAPAPIArray () {
   	$retour = array();
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['uniqueId'] = 'string';
   	$temp_array['in']['resultFile'] = 'base64Binary';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'integer';
   	$retour['saveResult'] = $temp_array;
   	unset($temp_array);

   	return $retour;
   }
   
   public function saveResult2 ($args) {
   	return $this->saveResult($args);
   }
   
   public function saveResult ($args) {
   	if ( !empty($args[0]) 
   		  and !empty($args[1])
   	   ) {
   		$uniqueId = $args[0];
   		$uniqueID_array = json_decode(str_replace('\'','"',$uniqueId),true);
   		$sid = $uniqueID_array['SID'];
   		$cid = $uniqueID_array['cid'];
    		$fid = $uniqueID_array['fid'];
    		$time = $uniqueID_array['time'];
    		$time_short = str_replace('-','',$time);
    		$time_short = str_replace(':','',$time_short);
     		$time_short = str_replace('.','',$time_short);
     		$time_short = str_replace(' ','',$time_short);
     		$saveResult = 0; // 0 = save not, 1 = save anonym, 2 = save pseudonym
    		if ( !empty($uniqueID_array['save']) ) {
    			$saveResult = $uniqueID_array['save'];
    		}
   	   $saveAim = '';
   	   if ( !empty($uniqueID_array['saveaim']) ) {
    			$saveAim = $uniqueID_array['saveaim'];
    		}
   	   $savePeriod = '';
   	   if ( !empty($uniqueID_array['saveperiod']) ) {
    			$savePeriod = $uniqueID_array['saveperiod'];
    		}
    		$resultFile = $args[1];
    		
    		if ( !empty($saveResult)
    			  and ( $saveResult == 1
    			  		  or $saveResult == 2
    			  		)
    		   ) {
            $this->_environment->setCurrentContextID($cid);
    			$this->_environment->setSessionID($sid);
    			
	    		// get link file item
	    		$lif_manager = $this->_environment->getLinkItemFileManager();
	    		$lif_manager->setContextLimit($cid);
	    		$lif_manager->setFileIDLimit($fid);
	    		$lif_manager->select();
	    		$lif_list = $lif_manager->get();
	    		if ( $lif_list->isNotEmpty()
	    			  and $lif_list->getCount() == 1
	    		   ) {
	    			$lif_item = $lif_list->getFirst();
	    		
	    			// get data item
	    			$data_item = $lif_item->getLinkedItem();
	    			if ( !empty($data_item) ) {
	    			
                  // new file on disc
                  $file_name = $fid.'_result';
                  if ( $saveResult == 2 ) {
			            $session_manager = $this->_environment->getSessionManager();
			            $session_item = $session_manager->get($sid);
                  	$file_name .= '_'.md5($session_item->getValue('user_id').'-'.$session_item->getValue('auth_source'));
                  } else {
                     $file_name .= '_anonym';	
                  }
                  $file_name .= '_'.$time_short.'.zip';
	    				$disc_manager = $this->_environment->getDiscManager();
	    				$temp_file = $disc_manager->saveFileFromBase64($file_name,$resultFile);
	    				if ( isset($temp_file) and !empty($temp_file) ) {
	    		         // new file item
	    					$session_manager = $this->_environment->getSessionManager();
			            $session_item = $session_manager->get($sid);
	    					$file_manager = $this->_environment->getFileManager();
	    					$file_item = $file_manager->getNewItem();
	    					$file_item->setFilename($file_name);
	    					$file_item->setContextID($cid);
	    					$file_item->setPortalID($session_item->getValue('commsy_id'));
	    					$file_item->setTempName($temp_file);
	    					$file_item->save();
	    					
	    					// maybe section at a material item
	    					if ( !empty($saveAim) ) {
	    						if ( $saveAim == 'section'
	    							  and $data_item->isA(CS_MATERIAL_TYPE) 
	    							) {
	    							$section_key = 'one';
	    							$section_title = $this->_translator->getMessage('ONYX_SAVE_TITLE_RESULT');
	    							if ( !empty($savePeriod) ) {
	    								if ( $savePeriod == 'day' ) {
	    									$section_key = date('Y.m.d');
	    							      $section_title = $this->_translator->getMessage('ONYX_SAVE_TITLE_RESULT_DAY',date('d'),date('m'),date('Y'));
	    								} elseif ($savePeriod == 'week') {
	    									$section_key = date('Y-W');
	    							      $section_title = $this->_translator->getMessage('ONYX_SAVE_TITLE_RESULT_WEEK',date('W'),date('Y'));
	    								} elseif ($savePeriod == 'month') {
	    									$section_key = date('Y.m');
	    									include_once('functions/date_funtions.php');
	    									$month = getLongMonthNameFromInt(date('n'));
	    							      $section_title = $this->_translator->getMessage('ONYX_SAVE_TITLE_RESULT_MONTH',$month,date('Y'));
	    								}
	    							}
	    							$plugin_config = $data_item->getPluginConfigForPlugin($this->_identifier);
	    							$section_manager = $this->_environment->getSectionManager();
	    							$create_new = true;
	    							if ( isset($plugin_config)
	    								  and is_array($plugin_config)
	    								  and !empty($plugin_config['saveResult'][$fid][$section_key])
	    								) {
	    								// use old section
	    								$section_item = $section_manager->getItem($plugin_config['saveResult'][$fid][$section_key]);
	    								if ( !empty($section_item)
	    									  and !$section_item->isDeleted() 
	    									) {
	    									$create_new = false;
	    									$data_item = $section_item;
	    								}
	    							}
	    							if ($create_new) {
	    								// create new section
			                     $session_manager = $this->_environment->getSessionManager();
			                     $session_item = $session_manager->get($sid);
			                     $user_id = $session_item->getValue('user_id');
			                     $auth_source = $session_item->getValue('auth_source');
			                     $user_manager = $this->_environment->getUserManager();
			                     $user_manager->setContextLimit($cid);
			                     $user_manager->setUserIDLimit($user_id);
			                     $user_manager->setAuthSourceLimit($auth_source);
			                     $user_manager->select();
			                     $user_list = $user_manager->get();
			                     if ($user_list->getCount() == 1) {
			                     	$current_user = $user_list->getFirst();
			                     	$this->_environment->setCurrentUserItem($current_user);
			                     
			                        $section_item = $section_manager->getNewItem();
	    								   $section_item->setContextID($cid);
	    								   $section_item->setCreatorItem($current_user);
	    								   include_once('functions/date_functions.php');
	    								   $section_item->setCreationDate(getCurrentDateTimeInMySQL());
			                        $section_item->setModificatorItem($current_user);
                                 $section_item->setModificationDate(getCurrentDateTimeInMySQL());

							            // Set attributes
						               $section_item->setTitle($section_title);
							            #$section_item->setNumber($_POST['number']);
						               $section_item->setLinkedItemID($data_item->getItemID());
						               $section_item->save();

						               // Update the material regarding the latest section informations...
						               // (this takes care of saving the section itself, too)
						               $data_item->setModificatorItem($current_user);
						               if (!$data_item->isNotActivated()){
						               	$data_item->setModificationDate($section_item->getModificationDate());
						               }else{
						               	$data_item->setModificationDate($data_item->getModificationDate());
						               }
						               $section_list = $data_item->getSectionList();
						               $section_list->set($section_item);
						               $data_item->setSectionList($section_list);
						               $data_item->setSectionSaveID($section_item->getItemId());
						               
						               #$external_view_array = $data_item->getExternalViewerArray();
						               #$data_item->setExternalViewerAccounts($external_view_array);
						               
						               $data_item->save();

						               // saveResult_key
						               $plugin_config['saveResult'][$fid][$section_key] = $section_item->getItemID();
						               $data_item->setPluginConfigForPlugin($this->_identifier,$plugin_config);
						               $data_item->save();
						               
	    								   $data_item = $section_manager->getItem($plugin_config['saveResult'][$fid][$section_key]);
			                     }		
	    							}
	    						}
	    					}
	    					
	    					// file item to data item
	    					$file_id_array = $data_item->getFileIDArray();
	    					if ( !isset($file_id_array) ) {
	    						$file_id_array = array();
	    					}
	    					$file_id_array[] = $file_item->getFileID();
	    					$data_item->setFileIDArray($file_id_array);
	    					$data_item->save();
	    					unlink($temp_file);
	    					
	    					// save onyx hash in user_item
                     if ( $saveResult == 2 ) { // 2 = pseudonym
		    					$session_manager = $this->_environment->getSessionManager();
				            $session_item = $session_manager->get($sid);
		    					$user_id = $session_item->getValue('user_id');
		    					$auth_source = $session_item->getValue('auth_source');
		    					$user_manager = $this->_environment->getUserManager();
		    					$user_manager->setContextLimit($cid);
		    					$user_manager->setUserIDLimit($user_id);
		    					$user_manager->setAuthSourceLimit($auth_source);
		    					$user_manager->select();
		    					$user_list = $user_manager->get();
		    					if ($user_list->getCount() == 1) {
		    						$current_user = $user_list->getFirst();
			    					$onyx_hash = md5($session_item->getValue('user_id').'-'.$session_item->getValue('auth_source'));
			    					$user_item_plugin_array = $current_user->getPluginConfigForPlugin($this->_identifier);
			    					if ( empty($user_item_plugin_array['userhash'])
			    						  or $user_item_plugin_array['userhash'] != $onyx_hash
			    						) {
			    						$user_item_plugin_array['userhash'] = $onyx_hash;
			    						$current_user->setPluginConfigForPlugin($this->_identifier,$user_item_plugin_array);
			    						$current_user->save();
			    					}
			    					unset($current_user);
		    					}
		    					unset($user_list);
		    					unset($user_manager);
		    					unset($session_item);
		    					unset($session_manager);
                     }

	    					return $file_item->getFileID();	    					 
	    				} else {
	    					return new SoapFault('ERROR',$this->_title.': can not save temp file');
	    				}  					    			    		
	    			} else {
	    			   return new SoapFault('ERROR',$this->_title.': no data item found for file id (onyx test)');
	    			}
	    		} else {
	    			return new SoapFault('ERROR',$this->_title.': no link item found for file id (onyx test)');
	    		}
    		} else {
    			return 1; // 1 = nothing saved
    		}
   	} else {
   		return new SoapFault('ERROR',$this->_title.': uniqueId and/or resultFile is empty');
   	}
   }

   public function getFullWSDL () {
   	$retour = '';
   	$file_name = $this->_plugin_folder.DIRECTORY_SEPARATOR.'returnWSService.wsdl';
   	if ( file_exists($file_name) ) {
   	   $retour = file_get_contents($file_name);
   	   $soap_url_domain = $this->_environment->getConfiguration('c_commsy_domain');
   	   $soap_url_path = $this->_environment->getConfiguration('c_commsy_url_path');
   	   $soap_url_file = 'soap.php?plugin=onyx';
   	   $soap_url = '';
   	   if ( !empty($soap_url_domain) ) {
   	      $soap_url .= $soap_url_domain;
   	   }
   	   if ( !empty($soap_url_path) ) {
   	      $soap_url .= $soap_url_path;
   	   }
   	   $soap_url .= '/'.$soap_url_file;
   	   $retour = str_replace('<!--SOAP-ADDRESS-LOCATION-->', $soap_url, $retour);
   	   
   	   $xsd_url_file = 'commsy.php?mod=onyx&amp;fct=showxsschema';
   	   $sxd_url = '';
   	   if ( !empty($soap_url_domain) ) {
   	      $sxd_url .= $soap_url_domain;
   	   }
   	   if ( !empty($soap_url_path) ) {
   	      $sxd_url .= $soap_url_path;
   	   }
   	   $sxd_url .= '/'.$xsd_url_file;
   	   $retour = str_replace('<!--XSD-SCHEMA-LOCATION-->', $sxd_url, $retour);
   	}
   	return $retour;
   }
   
   public function getXSSchema () {
   	$retour = '';
   	$file_name = $this->_plugin_folder.DIRECTORY_SEPARATOR.'schema.xsd';
   	if ( file_exists($file_name) ) {
   	   $retour = file_get_contents($file_name);
   	}
   	return $retour;
   }
   
   public function getURIforSoapServer () {
   	return 'http://test.plugin.bps.de/';
   }

   private function logToFile($msg){
     $fd = fopen('var/temp/onyx.log', "a");
     $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
     $version_addon = $this->_environment->getConfiguration('c_version_addon');
     if ( !empty($version_addon) ) {
        $str .= ' - ['.$version_addon.']';
     }
     fwrite($fd, $str . "\n");
     fclose($fd);
   }
   
   public function getUserDetailConfigArray ( $params ) {
   	$retour = array();
   	if ( !empty($params['user_item'])
   		  and $params['user_item']->isA(CS_USER_TYPE)
   		) {
   		$plugin_config_array = $params['user_item']->getPluginConfigForPlugin($this->_identifier);
   		if ( !empty($plugin_config_array)
   			  and !empty($plugin_config_array['userhash'])
   			) {
   			$retour['title'] = $this->_translator->getMessage('ONYX_USER_DETAIL_HASH_TITLE');
   			$retour['desc'] = $plugin_config_array['userhash'];
   			return $retour;
   		}
   	}
   }
}

class ONYX_StdClass {
	public $studentId = NULL;
	public $firstname = NULL;
	public $lastname  = NULL;
	public $groupname = NULL;
	public $tutorname = NULL;
	public $contentFile = NULL;
	public $resultsFile = NULL;
}
?>