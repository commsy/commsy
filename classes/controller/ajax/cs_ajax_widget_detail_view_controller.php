<?php
	require_once('classes/controller/cs_ajax_controller.php');

	class cs_ajax_widget_detail_view_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
	public function actionGetDetailContent() {
			$module = $this->_data["module"];
			$itemId = $this->_data["itemId"];
			
			$function = "detail";
			
			global $c_smarty;
			if(isset($c_smarty) && $c_smarty === true) {
				require_once('classes/cs_smarty.php');
				global $c_theme;
				if(!isset($c_theme) || empty($c_theme)) $c_theme = 'default';
			
				// room theme
				$color = $this->_environment->getCurrentContextItem()->getColorArray();
				$theme = $color['schema'];
			
				if($theme !== 'default') {
					$c_theme = $theme;
				}
			
				$smarty = new cs_smarty($this->_environment, $c_theme);
			
				global $c_smarty_caching;
				if(isset($c_smarty_caching) && $c_smarty_caching === true) {
					$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
				}
				
				$smarty->assign("ajax", "onlyContent", true);
				
				// set smarty in environment
				$this->_environment->setTemplateEngine($smarty);
				
				$controller_name = "cs_" . $module . "_" . $function . "_controller";
				require_once("classes/controller/" . $function . "/" . $controller_name . ".php");
				
				// this overrides some environment properties to "fake" that we are in private room
				$currentUser = $this->_environment->getCurrentUserItem();
				$this->_environment->setCurrentFunction("detail");
				$this->_environment->setCurrentModule($module);
				$_GET["iid"] = $itemId;
				$privateRoomContextID = $currentUser->getOwnRoom()->getItemID();
				$this->_environment->setCurrentContextID($privateRoomContextID);
				$this->_environment->setCurrentUserItem($currentUser->getRelatedPrivateRoomUserItem());
				
				$controller = new $controller_name($this->_environment);
				
				$controller->processTemplate();
				
				ob_start();
				$controller->displayTemplate();
				
				$output = ob_get_clean();
				$this->setSuccessfullDataReturn($output);
				echo $this->_return;
			}
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// TODO: check for rights, see cs_ajax_accounts_controller
			
			// call parent
			parent::process();
		}
	}