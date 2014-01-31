<?php
/*
 * 	Copyright (C) 2008-2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

/**
 * Provides functions to integrate Mediabird into a web site
 */
class MediabirdHtmlHelper {
	const langEnglish = 0;
	const langGerman = 1;

	public $defaultOptions;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->defaultOptions = array (
			'language'=>MediabirdHtmlHelper::langEnglish,
			'markerPlugins'=> array (
				'client.markers.QuestionMarker',
				'client.markers.RepetitionMarker',
				'client.markers.ReferenceMarker',
				'client.markers.CheckboxMarker'
				/*,'client.markers.TemplateMarker'*/
				),
			'displayPlugins'=> array (
				'client.pageplugins.displayplugins.Image',
				'client.pageplugins.displayplugins.Link',
				'client.pageplugins.displayplugins.Table',
				'client.pageplugins.displayplugins.HTML',
				'client.pageplugins.displayplugins.LaTeXmage',
				'client.pageplugins.displayplugins.InsertHelper'
				//, 'client.pageplugins.displayplugins.PluginTemplate' 
			),
			'loadLogon'=>true,
			'serverPath'=>'server'.DIRECTORY_SEPARATOR,
			'imagePath'=>'images'.DIRECTORY_SEPARATOR,
			'cssPath'=>'css'.DIRECTORY_SEPARATOR.'style.css',
			'javascriptPath'=>'js'.DIRECTORY_SEPARATOR.'client.js',
			'jQueryPath'=>'js'.DIRECTORY_SEPARATOR.'jquery.js',
			'version'=>'0.6.3',
			'title'=>'Mediabird Study Notes',
			'containerId'=>'mediabirdContainer',
			'dummyPath'=>'dummy.php',
			'loadPath'=>'load.php?url=',
			'uploadPath'=>'upload.php',
			'feedbackPath'=>'internal',
			'prefixData'=>false
		);
	}

	/**
	 *
	 * @var stdClass
	 */
	public $user = null;



	/**
	 * Returns the body layout for the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function bodyLayout($options = array ()) {
		$options = array_merge($this->defaultOptions, $options);

		$ret = '<div class="container">';
		$ret .= '	<div class="headerbar"';
		if ( isset ($options['headerId'])) {
			$ret .= ' id="'.$options['headerId'].'"';
		}
		$ret .= '>';
		$ret .= '	</div>';
		$ret .= '	<div id="'.$options['containerId'].'">';
		$ret .= '	</div>';
		$ret .= '</div>';
		return $ret;
	}

	/**
	 * Creates the main/launch function for the Javascript core of Mediabird
	 */
	function _mainBodyScript($options) {
		//JS to set image path
		$script = "config.imagePath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["imagePath"])."\";\n";

		//JS to set dummy php path
		$script .= "config.dummyPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["dummyPath"])."\";\n";

		//JS to set URL loader php path
		$script .= "config.loadUrlPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["loadPath"])."\";\n";

		//JS to set uploader php path
		$script .= "config.uploadPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["uploadPath"])."\";\n";

		//JS to set view php path
		$script .= "config.viewPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["viewPath"])."\";\n";

		//JS to set feedback php path
		$script .= "config.feedbackPath = \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["feedbackPath"])."\";\n";

		//JS to set reference
		$script .= "config.reference = {};\n";
		
		if(file_exists(MediabirdConfig::$latex_path) && file_exists(MediabirdConfig::$convert_path)) {
			//JS to set latex info
			$script .= "config.hasLatex = true;\n";
		}
		
		//JS to specify reference destination
		if ( isset ($options['linkTarget'])) {
			$script .= "config.reference.target = \"".$options["linkTarget"]."\";\n";
		}
		
		//JS to set link url path
		if ( isset ($options['linkUrl']) && isset ($options['linkTitle'])) {
			$script .= "config.reference.link = \"".$options["linkUrl"]."\";\n";
			$script .= "config.reference.title = \"".str_replace("\"", "\\\"", $options["linkTitle"])."\";\n";
		}
		else if (isset ($options['autoLink']) && $options['autoLink']) {
			$script .= "config.reference.auto = true;\n";
		}
		
		//JS to allow internal link detection
		if ( isset ($options['linkPrefix'])) {
			$script .= "config.linkPrefix = \"".$options["linkPrefix"]."\";\n";
		}

		//JS to set TERMS_URL
		if ( isset ($options['TERMS_URL'])) {
			$script .= "config.TERMS_URL = \"".$options['TERMS_URL']."\";\n";
		}

		//JS to enable POST prefixing
		if ($options["prefixData"]) {
			$script .= "config.prefixData = true;\n";
		}

		//JS to specify if email invitations are possible
		if ( isset ($options["supportEmailInvites"]) && $options["supportEmailInvites"]) {
			$script .= "config.emailInvites = true;\n";
		}

		//JS to specify if email invitations are possible
		if ( isset ($options["naviFade"]) && $options["naviFade"]) {
			$script .= "config.naviFade = true;\n";
		}

		//JS to set full location to switch to full mode from overlay
		if ( isset ($options["reduceFeatureSet"]) && $options["reduceFeatureSet"]) {
			$script .= "config.reduceFeatureSet = true;\n";
		}

		//JS to set full location to switch to full mode from overlay
		if ( isset ($options["fullLocationFromOverlay"])) {
			$script .= "config.fullLocationFromOverlay = \"".$options["fullLocationFromOverlay"]."\";\n";
		}

		//JS for loading english resource strings
		$script .= "lang = {};\n\$.extend(lang, client.lang);\n";
		if ($options["language"] == MediabirdHtmlHelper::langGerman) {
			//JS for loading german resource strings
			$script .= "\$.extend(lang, client.lang.de);\n";
		}

		//JS for server interface
		if ( isset ($options["addArgs"])) {
			$script .= "config.customArgs = ".$options["addArgs"].";\n";
		}

		$script .= "var server = new client.ServerInterface( {\nserverPath: \"".str_replace(DIRECTORY_SEPARATOR, "/", $options["serverPath"])."\"";
		if ( isset ($options["furtherArgs"])) {
			$script .= ",\n".$options["furtherArgs"];
		}
		$script .= "\n});\n";

		$markerTypes = $options["markerPlugins"];
		foreach ($markerTypes as $markerType) {
			$script .= "server.addMarkerType(".$markerType.");\n";
		}

		$displayPlugins = $options["displayPlugins"];
		foreach ($displayPlugins as $displayPlugin) {
			$script .= "server.addDisplayPlugin(new ".$displayPlugin."());\n";
		}

		//JS to create the page object
		if (! isset ($options['containerObject'])) {
			$script .= 'var page = new client.Page($("#'.$options['containerId'].'"), server';
		}
		else {
			$script .= 'var page = new client.Page('.$options['containerObject'].', server';
		}

		if ( isset ($options['headerId'])) {
			$script .= ',$("#'.$options['headerId'].'")';
		}

		$script .= ");\n";

		if ( isset ($options["loadCard"])) {
			//fixme: add support for this
			$script .= "config.customLoadCard = ".$options["loadCard"].";\n";
		}

		$script .= "utility.setupAlerts(page.container);\n";
		$script .= "utility.setupLoader(page.container);\n";
		
		
		if ($options["loadLogon"]) {
			//JS to load the logon form component
			$script .= "var plugin;\nplugin=new client.pageplugins.Logon();\npage.loadPagePlugin(plugin);\n";
		}
		else {
			//JS to load the main component
			$script .= "var plugin;\nplugin=new client.pageplugins.Overview();\n";
		}

		if ( isset ($options["user"]) || isset ($this->user)) {
			if ( isset ($options["user"])) {
				$user = $options["user"];
			}
			else {
				$user = $this->user;
			}
			$script .= "var user = {\n";
			$script .= "\tname:\"".str_replace("\"", "\\\"", $user["name"])."\",\n";
			$script .= "\tid:".$user["id"].",\n";
			if ( isset ($user["email"])) {
				$script .= "\temail:\"".addslashes($user["email"])."\",\n";
			}
			if ( isset ($user["lastLogin"])) {
				$script .= "\tlastLogin:".$user["lastLogin"].",\n";
			}
			if ( isset ($user["picUrl"])) {
				$script .= "\tpicUrl:\"".addslashes($user["picUrl"])."\",\n";
			}
			if ( isset ($user["settings"]) && ($settings = json_decode($user["settings"]))) {
				$script .= "\tsettings:".json_encode($settings)."\n";
			}
			else {
				$script .= "\tsettings:{}\n";
			}
			$script .= "}\n";
			$script .= "server.resumeSession(user);\n";
			if ($options["loadLogon"]) {
				$script .= "plugin.gotoOverview();\n";
			}
			else {
				$script .= "page.loadPagePlugin(plugin);\n";
			}
		}
		else {
			if ($options["loadLogon"]) {
				$script .= "plugin.loadLogon();\n";
			}
			else {
				$script .= "page.loadPagePlugin(plugin);\n";
			}
		}

		return $script;
	}

	/**
	 * Returns the body script for the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function bodyScript($options = array ()) {
		$options = array_merge($this->defaultOptions, $options);

		$script = "<script type=\"text/javascript\">\n//<![CDATA[\n";

		//JS main function
		$script .= "function _main(){\n";

		$mainscript = $this->_mainBodyScript($options);

		$script .= $mainscript;



		//JS end of main function
		$script .= "}\n";

		//JS to call main function
		$script .= "_main();\n";

		//JS end of script
		$script .= "//]]>\n</script>";

		return $script;
	}

	/**
	 * Returns the body script for an overlay displaying the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function bodyScriptOverlay($options = array ()) {
		$options = array_merge($this->defaultOptions, $options, array ('containerObject'=>'overlayContainer'));

		$script .= "<script type=\"text/javascript\">\n//<![CDATA[\n";

		//JS to create overlay link handler
		$script .= 'var link = $("#'.$options['linkId'].'");
		link.one("click",function() {
		var overlayContainer = $(document.createElement("div")).addClass("mediabird-overlay").appendTo(this.ownerDocument.body);
		'.$this->_mainBodyScript($options).'
		var overlay = new client.integration.Overlay();
		overlay.load(overlayContainer);
		});
		';

		//JS end of script
		$script .= "//]]>\n</script>";

		return $script;
	}


	/**
	 * Generates the complete style cache file
	 * Not applicable in debug mode
	 * @param array $cssFiles
	 * @param string $destination
	 * @param string $cssPrefix
	 */
	function generateCSSCache($cssFiles, $destination, $cssPrefix) {
		if ($file = fopen($destination, "w")) {
			foreach ($cssFiles as $source) {
				$content = file_get_contents($source)."\n";

				$content = preg_replace("%url\('[^']*/([^/]+)'\)%",
				"url('".$cssPrefix."\\1')", $content);

				fwrite($file, $content);
			}
			fclose($file);
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Activates the javascript minifier for the javascript release build
	 * Not applicable in debug mode
	 * @return
	 * @param object $options[optional]
	 */
	function enableMinifier($options = array ()) {
		$options = array_merge($this->defaultOptions, $options);
		include_once ($options['minifierPath']);
	}

	/**
	 * Generates the javascript release file
	 * @param array $javascriptFiles
	 * @param string $destination
	 */
	function generateJavascriptCache($javascriptFiles, $destination) {
		if ($file = fopen($destination, "w")) {
			foreach ($javascriptFiles as $source) {
				$content = file_get_contents($source)."\n";
				fwrite($file, $content);
			}
			fclose($file);
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Loads the default file list into the default options
	 */
	function loadDefaultFileArrays() {
		$this->defaultOptions['cssFiles'] = array (
		'client'.DIRECTORY_SEPARATOR.'style.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Logon.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Overview.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'MainView.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'NoteDisplay.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Trainer.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Question.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Check.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'Link.css',
		'client'.DIRECTORY_SEPARATOR.'pageplugins'.DIRECTORY_SEPARATOR.'File.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'PngEditor.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'FilterSearch.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'Slider.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'TagDisplay.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'ThumbnailBar.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'ShareBar.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'ThumbnailNavigation.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'FlashcardPanel.css',
		//'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'WidgetTemplate.css',
		'client'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'TreeView.css'
		);
		$this->defaultOptions['javascriptFiles'] = array (
		"utility".DIRECTORY_SEPARATOR."json.js",
		"utility".DIRECTORY_SEPARATOR."utility.js",
		"utility".DIRECTORY_SEPARATOR."utility.ui.js",
		"utility".DIRECTORY_SEPARATOR."utility.mozillaNode.js",
		"utility".DIRECTORY_SEPARATOR."jquery.selection.js",
		"utility".DIRECTORY_SEPARATOR."jquery.balloon.js",
		"utility".DIRECTORY_SEPARATOR."jquery.droppables.js",
		"client".DIRECTORY_SEPARATOR."Namespaces.js",
		"client".DIRECTORY_SEPARATOR."Config.js",
		"client".DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR."lang.js",
		"client".DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR."lang.de.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Object.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Topic.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Card.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."User.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."ExternalUser.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Right.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Relation.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Link.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Question.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Answer.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Star.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Vote.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Flashcard.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Tag.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."File.js",
		"client".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."Check.js",
		"client".DIRECTORY_SEPARATOR."ServerInterface.js",
		"client".DIRECTORY_SEPARATOR."Page.js",
		"client".DIRECTORY_SEPARATOR."Widget.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."Editor.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."PngEditor.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."TreeView2.js",
		//"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."WidgetTemplate.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."Slider.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."TagDisplay.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."ThumbnailBar.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."UserThumb.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."ShareBar.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."CardThumb.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."ThumbnailNavigation.js",
		"client".DIRECTORY_SEPARATOR."widgets".DIRECTORY_SEPARATOR."FlashcardPanel.js",
		"client".DIRECTORY_SEPARATOR."PagePlugin.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Logon.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Overview.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Introduction.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."MainView.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Trainer.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Link.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Question.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."Check.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."File.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."NoteDisplay.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."NoteDisplayInterface.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."NoteDisplayPlugin.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Image.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Link.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."Table.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."HTML.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."InsertHelper.js",
		"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."LaTeXmage.js",
		//"client".DIRECTORY_SEPARATOR."pageplugins".DIRECTORY_SEPARATOR."displayplugins".DIRECTORY_SEPARATOR."PluginTemplate.js",
		"client".DIRECTORY_SEPARATOR."Marker.js",
		//"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Importance.js",
		//"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Template.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Checkbox.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Question.js",
		//"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Translation.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Repetition.js",
		"client".DIRECTORY_SEPARATOR."markers".DIRECTORY_SEPARATOR."Reference.js"
		);
	}

	/**
	 * Returns the head tag for the main page
	 * @return string
	 * @param object $options[optional]
	 */
	function headTag($options = array ()) {
		if (( isset ($this->defaultOptions['debug']) && $this->defaultOptions['debug']) || ( isset ($options['debug']) && $options['debug'])) {
			$this->loadDefaultFileArrays();
		}

		$options = array_merge($this->defaultOptions, $options);

		if (! isset ($options['noMeta'])) {
			$script = '<title>'.htmlentities($options['title']).'</title>'."\n";
			$script .= '<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8"/>'."\n";
			$script .= '<link type="text/css" rel="stylesheet" href="css/default.css"/>'."\n";

			if ($options['debug']) {
				$files = $options['cssFiles'];
				foreach ($files as $file) {
					$script .= '<link type="text/css" rel="stylesheet" href="'.str_replace(DIRECTORY_SEPARATOR, '/', $file).'"/>'."\n";
				}
			}
			else {
				$script .= '<link type="text/css" rel="stylesheet" href="'.str_replace(DIRECTORY_SEPARATOR, '/', $options['cssPath']).'"/>'."\n";
			}
		}

		if (! isset ($options['noScripts'])) {
			if ( isset ($options['jQueryPath'])) {
				$script .= '<script type="text/javascript" src="'.str_replace(DIRECTORY_SEPARATOR, '/', $options['jQueryPath']).'"></script>'."\n";
			}
			if ($options['debug']) {
				$files = $options['javascriptFiles'];
				foreach ($files as $file) {
					$script .= '<script type="text/javascript" src="'.str_replace(DIRECTORY_SEPARATOR, '/', $file).'"></script>'."\n";
				}
			}
			else {
				$script .= '<script type="text/javascript" src="'.str_replace(DIRECTORY_SEPARATOR, '/', $options['javascriptPath']).'"></script>';
			}
		}
		return $script;
	}

	/**
	 * Registers a user in the database
	 */
	function registerUser($name, $active, $email, $pic_url = null, $mediabirdDb) {
		$ret = null;

		$user = (object)null;
		$user->name = $name;
		$user->last_login = $user->created = $user->modified = $mediabirdDb->datetime(time());
		$user->active = $active;
		if($email != null) {
			$user->email = $email;
		}
		if($pic_url != null){
			$user->pic_url = $pic_url;	
		}

		if ($id=$mediabirdDb->insertRecord(MediabirdConfig::tableName('User',true),$user)) {
			$ret = intval($id);
		}
		else {
			error_log("could not register user");
		}
		return $ret;
	}

	/**
	 * Updates a user in the database
	 */
	function updateUser($id, $name, $active, $email, $pic_url = null, $mediabirdDb) {
		$ret = null;

		$user = (object)null;
		$user->id = $id;
		$user->name=$name;
		$user->last_login=$mediabirdDb->datetime(time());
		$user->active=$active;
		if ($email != null) {
			$user->email=$email;
		}
		if($pic_url != null){
			$user->pic_url = $pic_url;
		}
		if ($mediabirdDb->updateRecord(MediabirdConfig::tableName('User',true),$user)) {
			return $id;
		}
		else {
			error_log("update user failed");
		}
		return $ret;
	}

	/**
	 * Links a given external user with a Mediabird user if not already linked
	 * and returns the internal id of that user
	 * @param int $externalId Id of external user
	 * @param string $system Name of external system, such as "facebook"
	 * @param string $name Name of external user
	 * @param int $active State of new user, 1 for active, 0 for disabled
	 * @param string $email Email address of user
	 * @param MediabirdDbo $mediabirdDb Database connection to be used
	 * @return int Id of internal user
	 */
	function linkUser($externalId, $system, $name, $active, $email, $pic_url = null, $mediabirdDb, &$wasCreated = false) {
		$select = "external_id=$externalId AND system='".$mediabirdDb->escape($system)."'";

		$linkRecord = $mediabirdDb->getRecord(MediabirdConfig::tableName("AccountLink",true),$select);

		if ($linkRecord) {
			$linkRecord->internal_id = intval($linkRecord->internal_id);
			
			$this->updateUser($linkRecord->internal_id, $name, $active, $email, $pic_url, $mediabirdDb);
			
			$wasCreated = false;
			
			return intval($linkRecord->internal_id);
		}
		else {
			if ($internalId = $this->registerUser($name, $active, $email, $pic_url, $mediabirdDb)) {
				$linkRecord = (object) null;
				$linkRecord->system = $system;
				$linkRecord->external_id = $externalId;
				$linkRecord->internal_id = $internalId;
				if (!$mediabirdDb->insertRecord(MediabirdConfig::tableName("AccountLink",true),$linkRecord)) {
					return null;
				}
				
				$wasCreated = true;
				
				return $internalId;
			}
			else {
				return null;
			}
		}
	}
	
	/**
	 * Creates an object representing the current user
	 * @return object
	 */
	function loadUser($userId,$mediabirdDb) {
		if ($record = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"id=$userId")) {
			//get last login time
			$lastLogin = $mediabirdDb->timestamp($record->last_login);

			//check for session time
			if(isset($_SESSION['mb_session_time'])) {
				$sessionTime = intval($_SESSION['mb_session_time']);
				$lastLogin = $sessionTime;
			}
			
			//get session record
			if(!isset($sessionTime) && 
			   ($sessionRecord = $mediabirdDb->getRecord(MediabirdConfig::tableName('Session',true),"user_id=$userId"))) {
				$sessionTime = intval($sessionRecord->modified);
				if($sessionTime > $lastLogin) {
					$lastLogin = $sessionTime;
				}
			}
			
			$user = array (
				'name'=>$record->name,
				'settings'=>$record->settings,
				'id'=>intval($record->id),
				'lastLogin'=>$lastLogin
			);
			
			
			if(isset($record->email)) {
				$user['email']=$record->email;
			}
			if(isset($record->pic_url)) {
				$user['picUrl']=$record->pic_url;
			}
			$this->user = $user;
			
			//save session time
			$_SESSION['mb_session_time'] = $lastLogin;
			
			return $user;
		}
		else {
			return null;
		}
	}

	/**
	 * Determine note sheets that are related to a given URL
	 * Technically speaking, this functions finds all note sheets that feature a reference marker pointing at the given location
	 * Sorts results by modification date, descending
	 * @param string $url Location
	 * @param int $userId Id of the user whose notes are to be determined
	 * @param MediabirdDbo $mediabirdDb Database connection to be used
	 * @return string[]
	 */
	function findRelatedNotes($url, $userId, $mediabirdDb) {
		//find all topics which are accessible
		$query = "SELECT id FROM ".MediabirdConfig::tableName('Topic')." WHERE id IN (
			SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE mask > 1 AND user_id=$userId
		)";

		$topicIds = (array)null;
		if ($result = $mediabirdDb->getRecordSet($query)) {
			//collect ids
			while ($results = $mediabirdDb->fetchNextRecord($result)) {
				$topicIds[] = intval($results->id);
			}
		}
		else {
			error_log($query);
			return null;
		}

		if (count($topicIds) > 0) {
			$query = "SELECT id FROM ".MediabirdConfig::tableName("Card")." WHERE id IN
			(SELECT card_id FROM ".MediabirdConfig::tableName("Marker")." WHERE id IN
				(SELECT marker_id FROM ".MediabirdConfig::tableName("Relation")." WHERE 
					(shared=1 OR user_id IN (0,$userId)) AND relation_id IN 
					(SELECT id FROM ".MediabirdConfig::tableName("Link")." WHERE url='".$mediabirdDb->escape($url)."')
				)
			) AND topic_id IN (".join(",", $topicIds).")
			ORDER BY modified DESC";

			$ownCardIds = array ();
			if ($result = $mediabirdDb->getRecordSet($query)) {
				while ($results = $mediabirdDb->fetchNextRecord($result)) {
					$card = intval($results->id);
					$ownCardIds[] = $card;
				}
				return array ($ownCardIds, array());
			}
			else {
				error_log($query);
				return null;
			}
		}
		else {
			return array ();
		}
	}
	
	/**
	 * Determines new problems that user with user Id can answer to
	 * Returns problem object with: question, answer, questioner, card name, status date, topic name and group name 
	 * Sorts results by modification date, descending
	 * @param $userId Id of the user whose notes are to be determined
	 * @param int $fromDate Minimum date from which to return the problems
	 * @param MediabirdDbo $mediabirdDb Database connection to use
	 * @return object
	 */
	function findNewProblems($userId,$fromDate,$mediabirdDb) {
		
		//determine questions this user can access
		//and that are of question type 3
		
		$select = "question_mode=3 AND created>'".$mediabirdDb->datetime($fromDate)."' AND (user_id=$this->userId OR id IN (
			SELECT relation_id FROM ".MediabirdConfig::tableName("Relation")." WHERE relation_type='question' AND marker_id IN (
				SELECT id FROM ".MediabirdConfig::tableName("Marker")." WHERE shared=1 AND card_id IN (
					SELECT id FROM ".MediabirdConfig::tableName("Card")." WHERE topic_id IN (
						SELECT topic_id FROM ".MediabirdConfig::tableName("Right")." WHERE user_id=$this->userId AND mask>=".MediabirdTopicAccessConstants::allowViewingCards."
					)
				)
			)
		))";
		
		$problems = (array)null;
		$cards = (array)null;
		
		if($records = $mediabirdDb->getRecords(MediabirdConfig::tableName('Question',true),$selectProblem,'created DESC','id, question, user_id, modified, created')) {
			foreach ($records as $result) {
				//count answers to that question
				
				$problem = (object)null;
				$problem->id = intval($result->id);
				
				$problem->created = $problem->date = $mediabirdDb->timestamp($result->created);
				$problem->modified = $mediabirdDb->timestamp($result->modified);
				
				$problem->question = $result->question;
				
				$select = "question_id=$result->id";
				if($firstAnswerRecords = $mediabirdDb->getRecords(MediabirdConfig::tableName("Answer",true),$select,'created ASC','*', '', 1)) {
					$problem->answer = $firstAnswerRecords[0]->answer;
				
					if($resultQuestioner = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"id=$firstAnswerRecord->user_id")){
						$problem->questioner = $resultQuestioner->name;
					}
				}
				
				/**
				 * formerly given
				 * cardId, cardTitle, topicId, topicTitle, [groupName]
				 */
						 
				$problems[] = $problem;	
			}
					
		}	
		return $problems;
	}
}
?>
