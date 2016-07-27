<?php

	class cs_context_logout_controller {
		protected $_environment = null;
		protected $_tpl_engine = null;
		protected $_tpl_file = null;
		protected $_tpl_path = null;
		protected $_utils = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// CommSy-Plugin logout-hook
			plugin_hook('logout');

			// delete session
			$session_manager = $environment->getSessionManager();
			$session = $environment->getSessionItem();
			$history = $session->getValue('history');
			$cookie = $session->getValue('cookie');
			$javascript = $session->getValue('javascript');
			$https = $session->getValue('https');
			$flash = $session->getValue('flash');
			if ( $session->issetValue('root_session_id') ) {
			   $root_session_id = $session->getValue('root_session_id');
			}
			$session_manager->delete($session->getSessionID(),true);
			$session->reset();

			include_once('classes/cs_session_item.php');
			$session = new cs_session_item();
			$session->createSessionID('guest');
			if ($cookie == '1') {
			   $session->setValue('cookie',2);
			} else {
			   $session->setValue('cookie',0);
			}
			if ($javascript == '1') {
			   $session->setValue('javascript',1);
			} elseif ($javascript == '-1') {
			   $session->setValue('javascript',-1);
			}
			if ($https == '1') {
			   $session->setValue('https',1);
			} elseif ($https == '-1') {
			   $session->setValue('https',-1);
			}
			if ($flash == '1') {
			   $session->setValue('flash',1);
			} elseif ($flash == '-1') {
			   $session->setValue('flash',-1);
			}

			if ( !empty($_GET['back_tool']) ) {
			   $back_tool = $_GET['back_tool'];
			   $back_file = $back_tool.'.php';
			} else {
			   $back_tool = '';
			   $back_file = '';
			}

			$environment->setSessionItem($session);

			// redirect
			$current_context = $environment->getCurrentContextItem();
			if ( isset($root_session_id) and !empty($root_session_id) ) {
			   // change cookie
			   if ( $cookie == '1' ) {
			      $session_manager = $environment->getSessionManager();
			      $session = $session_manager->get($root_session_id);
			      $session->setValue('cookie',2);
			      unset($session_manager);
			      $environment->setSessionItem($session);
			   }
			   $params = $history[0]['parameter'];
			   $params['SID'] = $root_session_id;
			   redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params,'','',$back_tool);
			} elseif ( !$current_context->isOpenForGuests()
			           and ( empty($back_tool)
			                 or ( !empty($back_tool)
			                      and $back_tool == 'commsy'
			                    )
			               )
			         ) {
			   if (!$current_context->isServer()) {
			      $parent_context = $current_context->getContextItem();
			      if ($parent_context->isOpenForGuests()) {
			         if ($parent_context->isPortal()) {
			            $params = array();
			            $params['room_id'] = $current_context->getItemID();
			            if ( $current_context->isGroupRoom() ) {
			               $project_room_item_id = $current_context->getLinkedProjectItemID();
			               if ( !empty($project_room_item_id) ) {
			                  $params['room_id'] = $project_room_item_id;
			               }
			            }
			            redirect($parent_context->getItemID(),'home','index',$params,'','',$back_tool);
			            unset($params);
			         } else {
			            redirect($parent_context->getItemID(),'home','index','','','',$back_tool);
			         }
			      }
			   } else {
			      redirect($current_context->getItemID(),'home','index','','','',$back_tool);
			   }
			} else {
			   redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter'],'','',$back_tool);
			}
			$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			redirect_with_url($url);
		}
	}