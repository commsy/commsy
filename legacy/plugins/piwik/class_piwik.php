<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2014 Dr. Iver Jackewitz
//
// This file is part of the piwik plugin for CommSy.
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
class class_piwik extends cs_plugin {
   
   private $_plugin_folder = NULL;
   private $_plugin_config_folder = NULL;
   private $_method = '';
   private $_timeout_ms = 0;
	
	/** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_identifier = 'piwik';
      $this->_translator->addMessageDatFolder('plugins/'.$this->_identifier.'/messages');
      $this->_title      = ucfirst($this->_identifier);
      $this->_image_path = 'plugins/'.$this->getIdentifier();
      $this->_format_media_key = '(:'.$this->_identifier;
      $this->_method     = 'javascript'; // options = javascript / php - see etc/config.php
      $this->_timeout_ms = 200; // to configure see etc/config.php // only for php method
      
      $this->_plugin_folder = 'plugins'.DIRECTORY_SEPARATOR.$this->getIdentifier();
      
      // config file
      $this->_plugin_config_folder = $this->_plugin_folder.DIRECTORY_SEPARATOR.'etc';
      if ( file_exists($this->_plugin_config_folder.DIRECTORY_SEPARATOR.'config.php') ) {
      	include_once($this->_plugin_config_folder.DIRECTORY_SEPARATOR.'config.php');
      	if ( !empty($c_piwik_api_method) ) {
      	   $this->_method = $c_piwik_api_method;
      	}
      	if ( !empty($c_timeout_ms) ) {
      		$this->_timeout_ms = $c_timeout_ms;
      	}
      }
   }

   public function getDescription () {
      return $this->_translator->getMessage(strtoupper($this->_identifier).'_DESCRIPTION');
   }

   public function getHomepage () {
      return 'http://www.piwik.org';
   }

   public function isConfigurableInServer () {
      return true;
   }
   
   public function isConfigurableInPortal () {
      return true;
   }
   
   public function configurationAtServer ( $type = '', $values = array() ) {
   	return $this->configurationAtPortal( $type, $values );
   }

   public function configurationAtPortal ( $type = '', $values = array() ) {
      $retour = '';
      if ( $type == 'change_form' ) {
         $retour = true;
      } elseif ( $type == 'create_form' ) {
         if ( !empty($values['form']) ) {
            $retour = $values['form'];
            $retour->addTextfield( $this->_identifier.'_server_url',
                                   '',
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_SERVER_URL'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
            $retour->combine();
            $retour->addText($this->_identifier.'_config_form_desc_https','',$this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_DESCRIPTION_HTTPS'));
            $retour->combine();
            $https_values = array();
            $https_values[0]['text']  = 'https';
            $https_values[0]['value'] = 'https';
            $https_values[1]['text']  = 'http';
            $https_values[1]['value'] = 'http';
            $c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
            if ( stristr($c_commsy_domain,'https') ) {
            	$c_commsy_domain = 'https';
            } else {
            	$c_commsy_domain = 'http';
            }
            $https_values[2]['text']  = $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_HTTPS_VALUE_COMMSY',$c_commsy_domain);
            $https_values[2]['value'] = 'commsy';
            $retour->addRadioGroup( $this->_identifier.'_https',
                                    $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG'),
                                    $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
            		                  $https_values,
            		                  '',
            		                  false,
            		                  true
                                  );
            $retour->combine();
            $retour->addTextfield( $this->_identifier.'_site_id',
                                   '',
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_SITE_ID'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
            $retour->combine();
            $retour->addTextfield( $this->_identifier.'_cookie_domain',
                                   '',
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_COOKIE_DOMAIN'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
            $retour->combine();
            $retour->addText($this->_identifier.'_config_form_desc','',$this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_DESCRIPTION'));
            $retour->combine();
            $retour->addText($this->_identifier.'_config_form_desc2','',$this->_translator->getMessage(strtoupper($this->_identifier).'_CONFIG_FORM_DESCRIPTION2'));
         }
      } elseif ( $type == 'save_config'
                 and !empty($values['current_context_item'])
               ) {
         $config_array = array();
         if ( isset( $values[$this->_identifier.'_server_url'] ) ) {
         	$values[$this->_identifier.'_server_url'] = str_replace('http://', '', $values[$this->_identifier.'_server_url']);
         	$values[$this->_identifier.'_server_url'] = str_replace('https://', '', $values[$this->_identifier.'_server_url']);
         	$config_array[$this->_identifier.'_server_url'] = $values[$this->_identifier.'_server_url'];
         }
         if ( isset( $values[$this->_identifier.'_site_id'] ) ) {
            $config_array[$this->_identifier.'_site_id'] = $values[$this->_identifier.'_site_id'];
         }
         if ( isset( $values[$this->_identifier.'_cookie_domain'] ) ) {
            $config_array[$this->_identifier.'_cookie_domain'] = $values[$this->_identifier.'_cookie_domain'];
         }
         if ( isset( $values[$this->_identifier.'_https'] ) ) {
            $config_array[$this->_identifier.'_https'] = $values[$this->_identifier.'_https'];
         }
         $values['current_context_item']->setPluginConfigForPlugin($this->_identifier,$config_array);
      } elseif ( $type == 'load_values_item'
                 and !empty($values['current_context_item'])
               ) {
         $retour = array();
         $config = $values['current_context_item']->getPluginConfigForPlugin($this->_identifier);
         if ( !empty($config[$this->_identifier.'_server_url']) ) {
            $retour[$this->_identifier.'_server_url'] = $config[$this->_identifier.'_server_url'];
         }
         if ( !empty($config[$this->_identifier.'_site_id']) ) {
            $retour[$this->_identifier.'_site_id'] = $config[$this->_identifier.'_site_id'];
         }
         if ( !empty($config[$this->_identifier.'_cookie_domain']) ) {
            $retour[$this->_identifier.'_cookie_domain'] = $config[$this->_identifier.'_cookie_domain'];
         }
         if ( !empty($config[$this->_identifier.'_https']) ) {
            $retour[$this->_identifier.'_https'] = $config[$this->_identifier.'_https'];
         }
      }
      return $retour;
   }
   
   public function getInfosForBeforeBodyEndAsHTML () {
   	// only server and portal
   	// rooms not implemented yet
   	$retour = '';
   	if ( $this->_method == 'javascript' ) {
   	   if ( $this->_environment->inServer() ) {
            $retour .= $this->_getInfoForBeforeBodyEndAsHTML('server');
   	   } else {
   		   $retour .= $this->_getInfoForBeforeBodyEndAsHTML('server');
   		   $retour .= $this->_getInfoForBeforeBodyEndAsHTML('portal');
   	   }
   	} elseif ( $this->_method == 'php' ) {
   		// see executeAtTheEnd
   		#$retour .= $this->_getInfosForBeforeBodyEndAsHTMLPHP();
   	}
   	return $retour;
   }
   
   private function _getInfoForBeforeBodyEndAsHTML ($context) {
   	$retour = '';
   	if ( !empty($context) ) {
   		$context_item = NULL;
   		if ( $context == 'server' ) {
   			$context_item = $this->_environment->getServerItem();
   		} elseif ( $context == 'portal' ) {
   			$context_item = $this->_environment->getCurrentPortalItem();
   		}
   		if ( !empty($context_item)
   			  and $context_item->isPluginOn($this->_identifier)
   			) {
   			$config = $context_item->getPluginConfigForPlugin($this->_identifier);
   			if ( !empty($config[$this->_identifier.'_server_url']) ) {
   				$server_url = $config[$this->_identifier.'_server_url'];
   			}
   			if ( !empty($config[$this->_identifier.'_site_id']) ) {
   				$site_id = $config[$this->_identifier.'_site_id'];
   			}
   			if ( !empty($config[$this->_identifier.'_cookie_domain']) ) {
   				$cookie_domain = $config[$this->_identifier.'_cookie_domain'];
   			}
   			if ( !empty($config[$this->_identifier.'_https']) ) {
   				$server_https = $config[$this->_identifier.'_https'];
   			}
   			$choice1 = 'https';
   			$choice2 = 'http';
   			if ( !empty($server_https)
   				  and $server_https == 'https'
   				) {
   			   $choice2 = 'https';
   			} elseif ( !empty($server_https)
   				        and $server_https == 'http'
   				      ) {
   				$choice1 = 'http';
   			}
   			
   			if ( !empty($server_url)
   				  and !empty($site_id)
   				  and !empty($cookie_domain)
   				) {
   				$retour = '<!-- Piwik -->
<script type="text/javascript">
   var _paq = _paq || [];
   _paq.push(["setCookieDomain", "'.$cookie_domain.'"]);
   _paq.push(["trackPageView"]);
   _paq.push(["enableLinkTracking"]);

   (function() {
     var u=(("https:" == document.location.protocol) ? "'.$choice1.'" : "'.$choice2.'") + "://'.$server_url.'/";
     _paq.push(["setTrackerUrl", u+"piwik.php"]);
     _paq.push(["setSiteId", "'.$site_id.'"]);
     var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
     g.type="text/javascript";
     g.defer=true;
     g.async=true;
     g.src=u+"piwik.js";  
     s.parentNode.insertBefore(g,s);
   })();
</script>
   						
<!-- Piwik Image Tracker -->
<noscript>
   <img  src="http://'.$server_url.'?idsite='.$site_id.'&amp;rec=1" style="border:0" alt="" />
</noscript>

<!-- End Piwik Code -->'.LF;
   			}
   			// TBD: noscript url https
   			
   			// problem multiple site ids and asynchronic tracking
   			// ==================================================
   			// http://web-development-blog.de/piwik-tracking-methoden-im-vergleich/
   			// http://developer.piwik.org/api-reference/PHP-Piwik-Tracker
   			// http://piwik.org/docs/tracking-api/#php-client-for-tracking-web-api
   			// http://www.redirect301.de/asynchrones-tracking-mit-piwik.html
   			// http://developer.piwik.org/api-reference/tracking-javascript#multiple-piwik-trackers

   		}
   	}
   	return $retour;
   }
   
   private function _getInfosForBeforeBodyEndAsHTMLPHP () {
   	$retour = LF.'<!-- PIWIK tracking via PHP - BEGIN -->'.LF;

   	$tracking_array = array();
   	
   	// opt-out
   	if ( !empty($_COOKIE['CommSyAGBPiwik'])
   			and $_COOKIE['CommSyAGBPiwik'] == 1
   	   ) {
   		$retour .= '<!-- don\'t track - opt-out is activated -->'.LF;
   	}
   	// track
   	else {
   	   if ( $this->_environment->inServer() ) {
   		   $info_array = $this->_getInfosForTracking($this->_environment->getServerItem());
   		   if ( !empty($info_array) ) {
   			   $tracking_array[] = $info_array;
   		   }
   	   } else {
   	      $info_array = $this->_getInfosForTracking($this->_environment->getServerItem());
   		   if ( !empty($info_array) ) {
   			   $tracking_array[] = $info_array;
   	   	}
   	      $info_array = $this->_getInfosForTracking($this->_environment->getCurrentPortalItem());
   		   if ( !empty($info_array) ) {
   			   $tracking_array[] = $info_array;
   		   }
   	   }
   	}
   	
   	if ( !empty($tracking_array) ) {
   		
   		// site title
   		$title = '';
   		$current_context_item = $this->_environment->getCurrentContextItem();
   		if ( !empty($current_context_item) ) {
   			if ( !$current_context_item->isServer()
   				  and !$current_context_item->isPortal()
   				) {
   				$current_portal = $current_context_item->getContextItem();
   				if ( !empty($current_portal) ) {
   					$title .= $current_portal->getTitle().' > ';
   				}
   			}
   			$title .= $current_context_item->getTitle().' > ';
   			$title .= $this->_environment->getCurrentModule().' > ';
   			$title .= $this->_environment->getCurrentFunction();
   			if ( !empty($_GET['iid'])) {
   				$title .= ' > '.$_GET['iid'];
   			}
   		}
   		
   		// tracking
   		include_once('plugins/'.$this->_identifier.'/PiwikTracker.php');
   		foreach ($tracking_array as $site_array) {
   			if ( !empty($site_array['server_url'])
   				  and !empty($site_array['site_id'])
   				) {
   				if ( empty($site_array['server_https'])
   				     or $site_array['server_https'] == 'commsy'
   					) {
   					$c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
   					if ( stristr($c_commsy_domain,'https') ) {
   						$http = 'https';
   					} else {
   						$http = 'http';
   					}
   					unset($c_commsy_domain);
   				} else {
   					$http = $site_array['server_https'];
   				}
   				$t = new PiwikTracker($site_array['site_id'],$http.'://'.$site_array['server_url'].'/piwik.php');
   				$t->setRequestTimeout($this->_timeout_ms); // in milliseconds - to avoid long waiting time, when piwik server is gone or network is down
   				
   				// proxy
          global $symfonyContainer;
          $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
          $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port'); 

   				if ($c_proxy_ip) {
   					$proxy = $c_proxy_ip;
   		   		if ($c_proxy_port) {
  		   				$proxy .= ':'.$c_proxy_port;
      				}
      				$t->setProxy($proxy);
     				}
   				
   				$result = $t->doTrackPageView($title);
   	         $retour .= '<!-- tracking '.$site_array['site_id'].' -->'.LF;
   			   if ( empty($result) ) {
   			   	$retour .= '   <!-- don\'t receive result from tracking for site '.$site_array['site_id'].' -->'.LF;
   				}
   			}
   		}
   	}
   	
   	$retour .= '<!-- PIWIK tracking via PHP - END -->'.LF;
   	return $retour;
   }
   
   private function _getInfosForTracking ($context_item) {
   	$retour = array();
   	if ( !empty($context_item) 
   			and $context_item->isPluginOn($this->_identifier)
   		) {
   		$config = $context_item->getPluginConfigForPlugin($this->_identifier);
   		if ( !empty($config[$this->_identifier.'_server_url']) ) {
   			$retour['server_url'] = $config[$this->_identifier.'_server_url'];
   		}
   		if ( !empty($config[$this->_identifier.'_site_id']) ) {
   			$retour['site_id'] = $config[$this->_identifier.'_site_id'];
   		}
   		if ( !empty($config[$this->_identifier.'_cookie_domain']) ) {
   			$retour['cookie_domain'] = $config[$this->_identifier.'_cookie_domain'];
   		}
   		if ( !empty($config[$this->_identifier.'_https']) ) {
   			$retour['server_https'] = $config[$this->_identifier.'_https'];
   		}
   	}
   	return $retour;
   }
   
   public function executeAtTheEnd () {
   	if ( $this->_method == 'php' ) {
   	   echo($this->_getInfosForBeforeBodyEndAsHTMLPHP());
   	}
   }
   
   public function getMediaRegExp () {
   	$retour = array();
   	$retour[$this->_format_media_key] = '~\\(:piwik (donottrack):\\)~eu';
   	return $retour;
   }
   
   public function formatMedia ( $params ) {
   	$retour = '';
   	if ( !empty($params['key'])
   			and $params['key'] == $this->_format_media_key
   			and !empty($params['value_new'])
   			and strstr($params['value_new'],$this->_format_media_key)
   	   ) {
   		if ( !empty($params['args_array'][1])
   			  and $params['args_array'][1] == 'donottrack'
   			) {
   			$retour = $this->_formatMediaDoNotTrack();
   		}
   	}
   	return $retour;
   }
   
   private function _formatMediaDoNotTrack () {
   	$retour = '';
   	$context_item = $this->_environment->getCurrentContextItem();
   	if ( !empty($context_item)
   	  	  and $context_item->isPluginOn($this->_identifier)
   	   ) {
   		$config = $context_item->getPluginConfigForPlugin($this->_identifier);
   		if ( !empty($config[$this->_identifier.'_server_url']) ) {
   			$server_url = $config[$this->_identifier.'_server_url'];
   	   }
   		if ( !empty($config[$this->_identifier.'_site_id']) ) {
   			$site_id = $config[$this->_identifier.'_site_id'];
   		}
   	
   		if ( !empty($server_url)
   			   and !empty($site_id)
   			) {
   			if ( $this->_method == 'javascript' ) {
   			   $language = $this->_environment->getSelectedLanguage();
   			   if ( empty($language) ) {
   				   $language = 'de';
   			   }
   			   // TBD: https
   	         $retour = '<iframe frameborder="no" width="550px" height="190px" src="http://'.$server_url.'/index.php?module=CoreAdminHome&action=optOut&idSite='.$site_id.'&language='.$language.'"></iframe>';
   	      } elseif ( $this->_method == 'php' ) {
   		      $retour = $this->_translator->getMessage(strtoupper($this->_identifier).'_DESC_DO_NOT_TRACK', $server_url);
   		      
   		      // local cookie
   		      $checked = '';
   		      if ( !empty($_COOKIE['CommSyAGBPiwik'])
   		      	  and $_COOKIE['CommSyAGBPiwik'] == 1
   		      	) {
   		      	$checked = ' checked=checked';
   		      }
   		      $retour .= BRLF.'<input type="checkbox" id="CommSyAGBPiwik" onchange="piwik_set_check();"'.$checked.'> '.$this->_translator->getMessage(strtoupper($this->_identifier).'_DESC_DO_NOT_TRACK_CHECKBOX');  		      
   			}
   	   }
   	}
   	return $retour;
   }
   
   public function getInfosForHeaderAsHTML () {
   	$retour  = '   <script type="text/javascript">'.LF;
      $retour .= '      function piwik_setCookie(c_name,value,expiredays) {'.LF;
      $retour .= '         if (value == 0) {'.LF;
   	$retour .= '   		   expiredays = -1;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         var exdate=new Date()'.LF;
      $retour .= '         exdate.setDate(exdate.getDate()+expiredays)'.LF;
      $retour .= '         document.cookie=c_name+ "=" +escape(value)+((expiredays==null) ? "" : ";expires="+exdate)'.LF;
      $retour .= '      }'.LF;
   	
      $retour .= '      function piwik_set_check(){'.LF;
      $retour .= '         piwik_setCookie(\'CommSyAGBPiwik\', document.getElementById(\'CommSyAGBPiwik\').checked? 1 : 0, 3650);'.LF;
      $retour .= '      }'.LF;
      $retour .= '   </script>';
   	return $retour;
   }

   /** misc function for logging
    * 
    * @param string $msg
    */
   private function logToFile($msg){
     $fd = fopen('var/temp/'.$this->_identifier.'.log', "a");
     $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
     $version_addon = $this->_environment->getConfiguration('c_version_addon');
     if ( !empty($version_addon) ) {
        $str .= ' - ['.$version_addon.']';
     }
     fwrite($fd, $str . "\n");
     fclose($fd);
   }
}
?>