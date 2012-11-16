<?php
/*
 * 	Copyright (C) 2008-2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

//disable unwanted slashing
// Check if magic_quotes_runtime is active
if(get_magic_quotes_runtime())
{
    // Deactivate
    set_magic_quotes_runtime(false);
}

/**
 * Some constants for Mediabird
 * @author fabian
 */
class MediabirdConstants {
	const processed    		= 0;
	const validates    		= 0;
	const accessDenied 		= 1;
	const locked       		= 2;
	const invalidData  		= 3;
	const invalidPage  		= 4;
	const invalidEmail		= 5;
	const invalidRevision	= 6;
	const serverError  		= 7;
	const disabled			= 8;
	const wrongPass	   		= 9;
	const wrongCaptcha		= 10;
	const nameNotUnique		= 11;
	const emailNotUnique	= 12;
	const moveError			= 13;
	const illegalType		= 14;
	const notEnoughQuota	= 15;
	const fileTooBig		= 16;
	const uploadFailed		= 17;
	const limitCountReached = 18;
	
	const groupLevelAdmin = 0xFFFF;
	const maxCardCount = 50;
	const maxRelationSize = 2048;
	const cardTypeHtml = 0;
	const cardTypePdf = 1;
	const cardTypeWiki = 2;
	const cardTypeBlog = 3;
	const fileTypeImage = 0;
	const fileTypePdf = 1;
	const fileTypeEquation = 2;
	const sessionRefreshTime = 45;
	
	/*
	 * @see Namespaces.js (config.TAG_COLORS)
	 * @type array
	 */
	static $tagColors = array(
		"FF6600","FFFF00","99FF00","33FF00",
		"FF3300","FF99CC","FFAAAA","00FF66",
		"FF0099","66CCFF","33BBFF","CCCCCC",
		"333333","009933","0033FF","0099FF"
	);	
}

/**
 * Stores constants for topic access 
 * @author fabian
 *
 */
class MediabirdTopicAccessConstants {
	const noAccess = 0; //not shared at all (for easier database)
	const allowViewingCards = 1;// [read-only]
	const allowSearchingCards = 2; // [read-only]
	const allowCopyingCards = 4; // [read-only]
	const allowEditingContent = 8;// (and title) [allow write]
	const allowAlteringMarkers = 16; // [allow write]
	const allowAddingCards = 32; // [allow write]
	const allowRearrangingCards = 64; // [allow structure]
	const allowRemovingCards = 128; // [allow structure]
	const allowRename = 256; // [allow structure]
	const presetReadOnly = 7;
	const presetWriteAccess = 63;
	const presetFullAccess = 511;
	const owner = 1023;
}

/**
 * Link type constants
 */
class MediabirdLinkTypeConstants {
	const external = 0;
	const platform = 1;
	const user = 2;
	const card = 3;
	const book = 4;
    const article = 5;
	const wikipedia = 6;
	const youtube = 7;
	const cobocards = 8;
	const mailto = 9;
};

class MediabirdUtility {
	
	static $ignoreQuotes = false;
	
	/**
	 * Get a value from a $_POST/$_GET array without slashes
	 * @return String
	 * @param $str String
	 */
	static function getArgNoSlashes($str) {
		if (!get_magic_quotes_gpc() || self::$ignoreQuotes) {
			return $str;
		}
		else {
			return stripslashes($str);
		}
	}

	/**
	 * Recursively deletes a folder and its contents
	 * @return Bool True on success, false otherwise
	 * @param $folder String Path to folder
	 */
	static function deleteFolder($folder) {
		// Sanity check
		if (!file_exists($folder)) {
			return false;
		}

		// Simple delete for a file
		if (is_file($folder) || is_link($folder)) {
			return unlink($folder);
		}

		// Loop through the folder
		$dir = dir($folder);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue ;
			}

			// Recurse
			deleteFolder($folder.DIRECTORY_SEPARATOR.$entry);
		}
		// Clean up
		$dir->close();
		return rmdir($folder);
	}

	/**
	 * Returns a new random file name
	 * @param $folder string Folder of which to detemine a free file name
	 * @return string File name that does not exist in the given folder
	 */
	static function getFreeFilename($folder) {
		if(!file_exists($folder)) {
			return null;
		}
		
		do {
			$name = substr(sha1(rand()), 0, 8);
		}
		while (file_exists($folder.$name));
		return $name;
	}

	

	
	/**
	 * Recursively determines the size of a folder in bytes
	 * @return int Number of bytes in folder and subfolders
	 * @param string $folder Path to folder
	 */
	static function getFolderSize($folder) {
		// Sanity check
		if (!file_exists($folder)) {
			return 0;
		}

		// Simple delete for a file
		if (is_file($folder) || is_link($folder)) {
			return is_link($folder)?0:filesize($folder);
		}

		// Loop through the folder
		$size = 0;
		$dir = dir($folder);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue ;
			}

			// Recurse
			$size += MediabirdUtility::getFolderSize($folder.DIRECTORY_SEPARATOR.$entry);
		}
		// Clean up
		$dir->close();
		return $size;
	}



	/**
	 * Validates an email address
	 * @return bool True if valid, false otherwise
	 * @param $email string Email address to validate
	 */
	static function checkEmail($email) {
		if (!preg_match("/^[a-zA-Z0-9]+[a-zA-Z0-9\._-]*@[a-zA-Z0-9_-]+[a-zA-Z0-9\._-]+$/", $email)) {
			return false;
		}
		return true;
	}

	static function checkKeyset($object,$keyset=array('id'),$allowLess=false) {
		if(!is_object($object))  return false;
		foreach($object as $property=>$v) {
			$i = array_search($property,$keyset);
			if($i!==false) {
				unset($keyset[$i]);
			}
			else {
				return false;
			}
		}
		return $allowLess || count($keyset) == 0;
	}
	
	static function checkUnique($array,$key=null) {
		foreach($array as $i => $v) {
			foreach($array as $j => $w) {
				if($i!=$j) {
					//determine actual comparison values
					if($key==null) {
						if($v==$w) {
							return false;
						}
					}
					else {
						if(property_exists($v,$key) && property_exists($w,$key) && $v->$key == $w->$key) {
							return false;
						}
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * Converts absolute links into relative ones for notes sent by the client
	 * @param string $input HTML containing (absolute) links
	 * @return string HTML featuring relative links for site-internal links
	 */
	static function correctAbsoluteLinks($input) {
		if(!isset(MediabirdConfig::$www_root)) {
			$baseUrl = dirname($_SERVER['PHP_SELF']);
			$baseUrl = str_replace(DIRECTORY_SEPARATOR,"/",$baseUrl);

			if(substr($baseUrl,strlen($baseUrl)-1)!="/") {
				$baseUrl.="/";
			}

			$http = ( isset ($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off")?"https://":"http://".str_replace(".", "\\.", $_SERVER["SERVER_NAME"]);
			$port = $_SERVER["SERVER_PORT"];
			$baseUrl = str_replace(".", "\\.", $baseUrl);
			$subex = "$http(:$port){0,1}($baseUrl){0,1}";
		}
		else {
			$subex = MediabirdConfig::$www_root;
		}

		//affected attributes
		$attrList = "href|cite|background|codebase|action|usemapcite|src|data|classid|src|href|profile|longdesc";


		//regex
		$search = "%(<[^>]+)($attrList)=([\"']{0,1})$subex%mi";

		//just omit the final part of the expression which represents the web server's absolute path like http://youdomain/path/to/php
		$replace = '\1\2=\3';

		return preg_replace($search, $replace, $input);
	}
	
	/**
	 * Cached instance of HTMLPurifier
	 * @var HTMLPurifier
	 */
	static private $_purifier;
	/**
	 * Filters HTML source for invalid constructs and forbidden items such as XSS scripts
	 * @param string $html HTML to be filtered
	 * @return string Filtered HTML
	 */
	static function purifyHTML($html) {
		//correct absolute links and make them relative
		if (!MediabirdConfig::$disable_absolute_link_correction) {
			$html = self::correctAbsoluteLinks($html);
		}

		if(class_exists("HTMLPurifier",false)) {
			if(!isset(self::$_purifier)) {
				$config = HTMLPurifier_Config::createDefault();
				$config->set('Attr', 'EnableID', true);
				$config->set('CSS', 'AllowedProperties', array(
				'font-weight','font-style','text-align','text-decoration', //support text formatting
				'float', //support image float
				'width','height', //support image size
				'padding','padding-top','padding-right','padding-bottom','padding-left', //support image padding
				'margin','margin-left' //support indendation
				//'direction' //support RTL/LTR
				));
				if(isset(MediabirdConfig::$cache_folder)) {
					$cachePath=MediabirdConfig::$cache_folder."filter";
					if(!file_exists($cachePath)) {
						mkdir($cachePath);
					}
					$config->set('Cache', 'SerializerPath', $cachePath);
				}
				$config->set('HTML', 'Doctype', 'HTML 4.01 Strict');
				self::$_purifier = new HTMLPurifier($config);
			}
			return self::$_purifier->purify($html);
		}
		else {
			return $html;
		}
	}
	
	/**
	 * Removes the given element from the array if it contains that
	 * @param array $array
	 * @param var $item
	 * @param bool $strict
	 * @return bool True if found, false otherwise
	 */
	static function arrayRemove(&$array,$item,$strict=false) {
		$key=array_search($item,$array,$strict);
		if($key!==false) {
			//in case this is an associative array, 
			//we need to get the index from the keys of the array, 
			//since array_splice doesn't want non-numeric keys
			$key = array_search($key,array_keys($array));
			//remove the found element
			array_splice($array,$key,1);
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Determines the desired content language of the user
	 * @param array $allowed_languages 
	 * @param string $default_language 
	 * @param bool $strict_mode 
	 * @return string
	 */
	static function determineBrowserLanguage($allowed_languages, $default_language, $strict_mode = false) {
		$lang_variable = null;
		if (! isset ($_SERVER['HTTP_ACCEPT_LANGUAGE']) || empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return $default_language;
		}
		else
		{
			$lang_variable = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		$accepted_languages = preg_split('/,\s*/', $lang_variable);

		// set default
		$current_lang = $default_language;
		$current_q = 0;

		// work through all specified languages
		foreach ($accepted_languages as $accepted_language) {
			$res = preg_match('/^([a-z]{1,8}(?:-[a-z]{1,8})*)'.
			'(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i', $accepted_language, $matches);
			if (!$res) {
				continue ;
			}

			$lang_code = explode('-', $matches[1]);

			// was quality given?
			if ( isset ($matches[2])) {
				// consider quality
				$lang_quality = (float)$matches[2];
			}
			else
			{
				// not given, assume 1.0
				$lang_quality = 1.0;
			}

			// work through all languages
			while (count($lang_code)) {
				// check if language wanted
				if (in_array(strtolower(join('-', $lang_code)), $allowed_languages)) {
					// check if quality high enough
					if ($lang_quality > $current_q) {
						// use this language
						$current_lang = strtolower(join('-', $lang_code));
						$current_q = $lang_quality;
						// exit while loop
						break;
					}
				}
				if ($strict_mode) {
					// exit while loop
					break;
				}
				array_pop($lang_code);
			}
		}

		// return language
		return $current_lang;
	}

	static private $rootUrl;
	static private $baseUrl;
	/**
	 * Helper function for makeLinksAbsolute
	 */
	static private function replaceUrl($matches) {

		$path = $matches[3];

		if (preg_match("%(:.*/)%mi", $path) == 1) {
			return $matches[0];
		}

		$i = (substr($path, 0, 1) == "\"" || substr($path, 0, 1) == "'")?1:0;

		$path = substr($path, 0, $i).(substr($path, $i, 1) == "/"?self::$rootUrl:self::$baseUrl).substr($path, 1);

		return $matches[1].$matches[2]."=".$path;
	}

	/**
	 * Makes absolute links relative 
	 * @param string $html HTML containing absolute links
	 * @return string
	 */
	static function makeLinksAbsolute($html) {
		//make relative paths in relevant attributes absolute. list comes from w3c
		$attrList = "href|cite|background|codebase|action|usemapcite|src|data|classid|src|href|profile|longdesc";

		$search = "%(<[^>]+)($attrList)=(\"[^\"]+\"|'[^']+'|[^\"' >]+[ >])%mi";

		$html = preg_replace_callback($search, "MediabirdUtility::replaceUrl", $html);

		return $html;
	}

	/**
	 * Retrieves a remote url using CURL making absolute links relative
	 * @param string $urlToLoad
	 * @return string HTML
	 */
	static function loadUrl($urlToLoad) {

		// create a new cURL resource
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $urlToLoad);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		//set up proxy if specified
		if(isset(MediabirdConfig::$proxy_address) && strlen(MediabirdConfig::$proxy_address)>0) {
			curl_setopt($ch,CURLOPT_PROXY,MediabirdConfig::$proxy_address.":".MediabirdConfig::$proxy_port);
		}
		
		//execute
		$html = curl_exec($ch);

		//check for error
		if ($html === false) {
			return null;
		}

		// grab URL and pass it to the browser
		$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

		// grab content type and pass it to the browser
		$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		if ( isset ($type)) {
			header('Content-Type: '.$type);
		}

		//parse the url
		$path = parse_url($finalUrl);

		//remove last bit of path
		if ( isset ($path['path'])) {
			$li = strrpos($path['path'], "/");
			if ($li > -1) {
				$path['path'] = substr($path['path'], 0, $li+1);
			}
		}
		//forget about query and fragment
		unset ($path['query']);
		unset ($path['fragment']);

		//construct base url
		self::$baseUrl = self::__glueUrl($path);

		//construct the root url
		unset ($path['path']);
		self::$rootUrl = self::__glueUrl($path);


		//correct relative URIs
		$html = self::makeLinksAbsolute($html);

		// close cURL resource, and free up system resources
		curl_close($ch);

		//echo modified HTML
		return $html;
	}
	/**
	 * Glues together a URL parsed by parse_url
	 * @param array $parsed Array of element given by a call to parse_url
	 * @return string
	 */
	private static function __glueUrl($parsed) {
		if (!is_array($parsed)) return false;

		$uri = isset ($parsed['scheme'])?$parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto')?'':'//'):'';
		$uri .= isset ($parsed['user'])?$parsed['user'].($parsed['pass']?':'.$parsed['pass']:'').'@':'';
		$uri .= isset ($parsed['host'])?$parsed['host']:'';
		$uri .= isset ($parsed['port'])?':'.$parsed['port']:'';
		$uri .= isset ($parsed['path'])?$parsed['path']:'';
		$uri .= isset ($parsed['query'])?'?'.$parsed['query']:'';
		$uri .= isset ($parsed['fragment'])?'#'.$parsed['fragment']:'';
		return $uri;
	}

	/**
	 * Sends the JSON response header
	 */
	public static function jsonHeader() {
		header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate;");
		header("Pragma: no-cache;");
		header('Content-Type: application/json;');
	}

	
	
	static function execExists($file) {
		if(file_exists($file) && is_executable($file)) {
			return true;
		}
		else {
			$path = getenv('PATH');
			if(isset($path)) {
				$paths = explode(PATH_SEPARATOR,$path);
				foreach($paths as $path) {
					if(file_exists($path.DIRECTORY_SEPARATOR.$file) && is_executable($path.DIRECTORY_SEPARATOR.$file)) {
						return true;
					}
				} 
			}
			return false;
		}
	}

	/**
	 * Explodes a serialized associative array
	 * Format goes like key1[inner sep]value2[outer esp]key2[inner sep]value2...
	 * @param $subject
	 * @param $outerSeparator
	 * @param $innerSeparator
	 * @return array
	 */
	public static function explodeSerializedArray($subject,$outerSeparator=",",$innerSeparator=":",$parseInt = false) {
		$outerItems = explode($outerSeparator,$subject);
		$result = array();
		foreach($outerItems as $outerItem) {
			$innerItems = explode($innerSeparator,$outerItem);
			if(count($innerItems)!=2) {
				return null;
			}
			if($parseInt) {
				$innerItems[0] = intval($innerItems[0]);
				$innerItems[1] = intval($innerItems[1]);
			}
			$result[$innerItems[0]] = $innerItems [1];
		}
		return $result;
	}
	
	public static function explodeIdRevList($subject) {
		return self::explodeSerializedArray($subject,",",":",true);
	}
}

?>
