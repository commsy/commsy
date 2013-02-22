<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

class MediabirdFiles extends MediabirdModel {
	var $name = "Files";
	
	/**
	 * @deprecated
	 */
	function validate($data,&$cache,&$reason) {
	}
	/**
	 * @deprecated
	 */
	function update($data,$cache,&$changes) {
	}

	/**
	 * Check if a given upload record is authorized to the current user
	 * @param object $record
	 * @return bool
	 */
	function uploadRecordAuthorized($record) {
		if(!$record) {
			return false;
		}

		//check if upload is not password-protected
		if($record->password===null) {
			if($record->user_id == $this->userId) {
				return true;
			}

			//check if user knows the author
			//<=>
			//check if there is at least one share between a topic the current user is member of and the user owning the upload
			$select ="user_id=$record->user_id AND
					mask>0 AND
					topic_id IN (
						SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE user_id=$this->userId AND mask>0
					)";

			//return true if there is at least one record
			return $this->db->countRecords(MediabirdConfig::tableName("Right",true),$select)>0;
		}

		$select = "user_id=$this->userId AND upload_id=$record->id AND mask>0";
		//return true if there is at least one upload access record
		return $this->db->countRecords(MediabirdConfig::tableName("UploadAccess",true),$select)>0;
	}

	/**
	 * Authorize user using a password
	 * @param int $uploadId
	 * @param string $password
	 * @param array $results
	 * @return int
	 */
	function authorizeFile($uploadId,$password,&$results) {
		if(!is_int($uploadId)) {
			return MediabirdConstants::invalidData;
		}

		//get file name
		if($record = $this->db->getRecord(MediabirdConfig::tableName('Upload',true),"id=$uploadId")) {
			if($record->type != MediabirdConstants::fileTypePdf) {
				return MediabirdConstants::invalidData;
			}

			//check if pass already given
			if($record->password && strlen($record->password)>0) {
				//password already given in db
				if($password != $record->password) {
					return MediabirdConstants::wrongPass;
				}
			}
			else {
				//password should be updated if correct

				//check password
				$info = $this->getPdfInfo($this->getBaseFolder().$record->filename,$password);

				if(!$info || !property_exists($info,"pageCount")) {
					//pass wrong
					return MediabirdConstants::wrongPass;
				}
				else {
					//update password in upload table
					$record->password = $password;
					if(!$this->db->updateRecord(MediabirdConfig::tableName("Upload",true),$record)) {
						return MediabirdConstants::serverError;
					}
				}
			}

			//we're authed, continue!

			//check if record already exists
			$select = "user_id=$this->userId AND upload_id=$uploadId";
			if($this->db->countRecords(MediabirdConfig::tableName("UploadAccess",true),$select)>0) {
				return MediabirdConstants::processed;
			}

			//insert auth record
			$authRecord = (object)null;
			$authRecord->user_id = $this->userId;
			$authRecord->upload_id = $uploadId;
			$authRecord->mask = MediabirdTopicAccessConstants::allowViewingCards;
			$authRecord->created = $authRecord->modified = $this->db->datetime(time());

			if(!$authRecord->id = $this->db->insertRecord(MediabirdConfig::tableName("UploadAccess",true),$authRecord)) {
				return MediabirdConstants::serverError;
			}

			//return file info
			$info = (object)null;
			$info->id = intval($record->id);
			$info->created = $this->db->timestamp($record->created);
			$info->type = intval($record->type);
			$info->userId = intval($record->user_id);
			$info->filename = basename($record->filename);

			if(!$this->extendPdfInfo($info,$record)) {
				return MediabirdConstants::serverError;
			}

			//return file info
			$results['files'] []= $info;

			return MediabirdConstants::processed;
		}
		else {
			return MediabirdConstants::invalidData;
		}
	}

	/**
	 * Loads list of files
	 */
	function load($data,&$results) {
		if(!isset($data)) {
			$data = array();
		}
		$fromTime = isset($data['files']['fromTime']) ? $data['files']['fromTime'] : 0;

		$loadedIds = isset($data['files']['loadedIds']) ? array_values($data['files']['loadedIds']) : array();

		$ids = isset($data['files']['restrictIds']) ? array_values($data['files']['restrictIds']) : array();

		$links = array();
			
		//select files owned by current user
		$select = "user_id=$this->userId";

		//select files current user was granted access to (protected files)
		$select .= " OR id IN (
			SELECT upload_id FROM ".MediabirdConfig::tableName("UploadAccess")." WHERE user_id=$this->userId AND mask>0 
		)";

		//select files of users the current user knows
		$select .= " OR id IN (
			SELECT content_id FROM ".MediabirdConfig::tableName("Card")." WHERE content_type=".MediabirdConstants::cardTypePdf." AND topic_id IN (
				SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE user_id=$this->userId AND mask>0
			)
		)";
			
		if(count($ids)>0) {
			$select = "id IN (".join(",",$ids).") AND (".$select.")";
		}
			
		$files = array();
			
		if($records = $this->db->getRecords(MediabirdConfig::tableName("Upload",true),$select)) {
			foreach($records as $record) {
				$file = (object)null;
				$file->id = intval($record->id);
					
				MediabirdUtility::arrayRemove($loadedIds,$file->id);
					
				$file->created = $this->db->timestamp($record->created);

				if($file->created > $fromTime){
					$file->type = intval($record->type);
					$file->userId = intval($record->user_id);
					$file->filename = basename($record->filename);


					if($file->type==MediabirdConstants::fileTypePdf) {
						$this->extendPdfInfo($file,$record);
					}

					if(!property_exists($file,'title') || $file->title == $file->filename) {
						$file->title = $record->title;
					}

					$files []= $file;
				}
			}
		}
			
			
		$results['files'] = $files;
		if(count($loadedIds)>0) {
			$results['removedFileIds'] = $loadedIds;
		}
		return true;
	}

	/**
	 * Returns the PDF header info
	 * @param $filename File name
	 * @param [$password] Password, optional
	 * @return object
	 */
	function getPdfInfo($filename,$password=null) {
		$pdfInfoPath = MediabirdConfig::$pdfinfo_path;
		$pdfTkPath = MediabirdConfig::$pdftk_path;

		if(!file_exists($filename)) {
			return null;
		}

		$encInfo = '';

		$info = (object)null;

		$info->encrypted = false;

		if(!empty($pdfInfoPath) && MediabirdUtility::execExists($pdfInfoPath)) {
			if(isset($password) && strlen($password)>0) {
				$info->encrypted = true;
				$encInfo = "-upw $password";
			}
			$cmd=sprintf('%s -enc UTF-8 %s %s',escapeshellarg($pdfInfoPath),$encInfo,escapeshellarg($filename));
		}
		else if(!empty($pdfTkPath) && MediabirdUtility::execExists($pdfTkPath)){
			if(isset($password) && strlen($password)>0) {
				$info->encrypted = true;
				$encInfo = "input_pw $password";
			}
			$cmd=sprintf('%s %s %s dump_data',escapeshellarg($pdfTkPath),escapeshellarg($filename),$encInfo);
		}
		else {
			return null;
		}

		$infoCachePath = MediabirdConfig::$cache_folder.md5($cmd);

		if(file_exists($infoCachePath) && is_readable($infoCachePath)) {
			$contents = file_get_contents($infoCachePath);
			$code = strlen($contents)>0 ? 0 : 1;

			$output = explode("\n",$contents);
			$lastLine = $output[count($output)-1];
		}
		else {

			$lastLine = exec($cmd,$output,$code);

			if($code == 0 && empty($lastLine)) {
				return null;
			}
			else {
				file_put_contents($infoCachePath,join("\n",$output));
			}
		}

		if($code == 0) {
			$info->pageCount = 0;
			$info->title = basename($filename);
			$nextTitle = false;
			foreach($output as $line) {
				if(preg_match("/(\w+)\s*\:\s*(.+)\s*$/",$line,$matches)) {
					if($matches[1]=="Pages" || $matches[1]=="NumberOfPages") {
						$info->pageCount = intval($matches[2]);
					}
					if($matches[1]=="Title" || $nextTitle) {
						$info->title = $matches[2];
					}
					if($matches[1]=="Encrypted") {
						$info->encrypted = strpos($matches[2], "yes") === 0;
					}
					$nextTitle = $matches[1]=="InfoKey" && $matches[2]=="Title";
				}
			}
		}
		else {
			$info->encrypted = true;
		}
		return $info;
	}

	function extendPdfInfo($file,$record) {
		if($info = $this->getPdfInfo($this->getBaseFolder().$record->filename,$record->password)) {
			if($info->encrypted && $record->user_id != $this->userId && !$this->uploadRecordAuthorized($record)) {
				$file->encrypted = 1;
			}
			else {
				if(isset($info->pageCount)) {
					$file->pageCount = $info->pageCount;
				}
				if(isset($info->title)) {
					$file->title = $info->title;
				}
				$file->encrypted = $info->encrypted ? 1 : 0;
			}
		}
		else {
			return false;
		}


		if(!property_exists($file,'title') || $file->title == $file->filename) {
			$file->title = $record->title;
		}
		$file->altTitle = $record->title;
		return true;
	}

	/**
	 * Stores a reference to a file in the database
	 * @param $filepath string
	 * @param $type int
	 * @return object File representation
	 */
	function registerUpload($filepath,$type,$password=null,$title=null) {
		$select = "	filename='".$this->db->escape($filepath)."' AND
					type=$type";

		if($record = $this->db->getRecord(MediabirdConfig::tableName('Upload',true),$select)) {
			$file = (object)null;
			$file->id = intval($record->id);
			$file->filename = basename($record->filename);
			$file->title = $record->title;
			$file->created = $this->db->timestamp($record->created);
			$file->type = $type;
			$file->userId = intval($record->user_id);

			return $file;
		}

		$record = (object)null;
		$record->user_id = $this->userId;
		$record->type = $type;
		$record->filename = $filepath;
		$record->title = $title;
		$record->password = $password;
		$time = time();
		$record->created = $record->modified = $this->db->datetime($time);

		if($record->id = $this->db->insertRecord(MediabirdConfig::tableName('Upload',true),$record)) {
			$file = (object)null;
			$file->id = intval($record->id);
			$file->filename = basename($filepath);
			$file->title = $title;
			$file->created = $time;
			$file->type = $record->type;
			$file->userId = $record->user_id;

			return $file;
		}
		else {
			return null;
		}
	}

	/**
	 * Determines if there is enough space for a file of the given size taking the
	 * quota limit into account if there is any
	 * @return bool True if enough space and false otherwise
	 * @param $quota int Quota of the user
	 * @param $fileSize int Size of file to be added to the user's folder
	 * @param $default bool Default answer if no quota set
	 */
	function enoughQuota($quota, $fileSize, $default) {
		if ($quota == 0) {
			return $default;
		}
		//determine user folder size
		$folder = $this->getUserFolder();
		if (file_exists($folder)) {
			$folderSize = MediabirdUtility::getFolderSize($folder);
		}
		else {
			$folderSize = 0;
		}
		return $folderSize+$fileSize <= $quota;
	}

	/**
	 * Returns full path to user folder
	 * @return string
	 */
	function getUserFolder() {
		if(property_exists($this->controller->auth,"userSubfolder")) {
			return MediabirdConfig::$uploads_folder.$this->controller->auth->userSubfolder.DIRECTORY_SEPARATOR;
		}
		else {
			return MediabirdConfig::$uploads_folder.$this->userId.DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Returns sub-path to user folder from base folder
	 * @return string
	 */
	function getUserSubfolder() {
		if(property_exists($this->controller->auth,"userSubfolder")) {
			return $this->controller->auth->userSubfolder.DIRECTORY_SEPARATOR;
		}
		else {
			return $this->userId.DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Returns full path to folder containing user folders
	 * @return string
	 */
	function getBaseFolder() {
		return MediabirdConfig::$uploads_folder;
	}

	/**
	 * Returns full path to folder containing cache files
	 * @return string
	 */
	function getCacheFolder() {
		return MediabirdConfig::$cache_folder;
	}

	/**
	 * Return quota left for current user
	 * @return int
	 */
	function quotaLeft() {
		//determine quota

		if($records = $this->db->getRecords(MediabirdConfig::tableName("User",true),"id=$this->userId",'','quota')) {
			$quota = intval($records[0]->quota);
		}
		else {
			return 0;
		}

		if ($quota == 0) {
			return -1;
		}

		$folder = $this->getUserFolder();
		if (file_exists($folder)) {
			$folderSize = MediabirdUtility::getFolderSize($folder);
		}
		else {
			$folderSize = 0;
		}

		return $quota-$folderSize;
	}

	/**
	 * Stores uploaded files locally
	 * @param array $key
	 * @param string $folder
	 * @param int $quotaLeft
	 * @param array $allowedMime
	 * @return array
	 */
	function storeUpload($key, $folder, $quotaLeft = -1, $prefix = '', $allowedMime = array ('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-bmp')) {
		//init variables
		$destName = null;
		$title = null;
		$error = null;

		if (! isset ($_FILES[$key])) {
			$error = MediabirdConstants::uploadFailed;
		}
		else {
			$uploadError = $_FILES["file"]["error"];
			if ($uploadError == UPLOAD_ERR_OK) {
				$size = $_FILES[$key]['size'];
				$filepath = $_FILES[$key]['tmp_name'];
				$title = $_FILES[$key]['name'];

				if ($size <= $quotaLeft || $quotaLeft == -1) {
					$mime = null;
					if (function_exists("mime_content_type")) {
						$mime = mime_content_type($filepath);
					}
					if ($mime == null || in_array($mime, $allowedMime)) {
						//compute md5 hash
						$name = md5_file($filepath);

						$file = $folder.$name;

						if (move_uploaded_file($filepath, $file)) {
							chmod($file, 0644); //0 indicates octal notation

							if (substr($file, 0, strlen($prefix)) == $prefix) {
								$file = substr($file, strlen($prefix));
							}
							else {
								$file = $prefix.$file;
							}
							$destName = str_replace(DIRECTORY_SEPARATOR, '/', $file);
						}
						else {
							$error = MediabirdConstants::moveError;
						}
					}
					else {
						$error = MediabirdConstants::illegalType;
					}
				}
				else {
					$error = MediabirdConstants::notEnoughQuota;
				}
			}
			else if ($uploadError == UPLOAD_ERR_INI_SIZE || $uploadError == UPLOAD_ERR_FORM_SIZE) {
				$error = MediabirdConstants::fileTooBig;
			}
			else if ($uploadError == UPLOAD_ERR_NO_FILE) {
				$error = MediabirdConstants::uploadFailed;
			}
			else {
				$error = MediabirdConstants::uploadFailed;
			}
		}
		return array ('error'=>$error, 'filename'=>$destName, 'title'=>$title);
	}

	/**
	 * Generates HTML for upload response
	 * @param $destName string
	 * @param $error string
	 * @return string
	 */
	function generateUploadHtml($destName, $error) {
		//begin document
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
		$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
		$html .= '<head>'."\n";
		$html .= '<title>Mediabird eLearning - File Upload</title>'."\n";
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
		$html .= '</head>'."\n";
		$html .= '<body>'."\n";
		$html .= '<script type="text/javascript">'."\n";
		$html .= '//<![CDATA['."\n";

		//generate javascript
		$html .= 'if (parent.window.utility.globalCallback!==undefined) {';
		$html .= '	parent.window.utility.globalCallback(';
		if ( isset ($destName)) {
			if(substr($destName,0,1)=="{") {
				$html .= $destName;
			}
			else {
				$html .= '"'.str_replace(DIRECTORY_SEPARATOR, "/", $destName).'"';
			}
		}
		else {
			$html .= 'null';
		}
		$html .= ',';
		if ( isset ($error)) {
			$html .= $error;
		}
		else {
			$html .= 'null';
		}
		$html .= ');'."\n";
		$html .= '}'."\n";

		//end script tag
		$html .= ' //]]>'."\n";
		$html .= ' </script>'."\n";
		$html .= ' </body>'."\n";
		$html .= '</html>'."\n";

		return $html;
	}

	/**
	 * Handles upload requests
	 * @param array $key Key in the $_FILE array
	 * @param int $type File type to expect
	 * @param array [$allowedMime] Allowed mime times
	 * @return string
	 */
	function handleUpload($key, $type, $allowedMime = false) {
		$prefix = $this->getBaseFolder();
		$folder = $this->getUserFolder();
		if(!file_exists($folder)) {
			mkdir($folder);
		}

		if($allowedMime===false) {
			if($type==MediabirdConstants::fileTypeImage) {
				$allowedMime = array ('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-bmp');
			}
			else if($type==MediabirdConstants::fileTypePdf) {
				$allowedMime = array ('application/pdf');
			}
		}

		$quotaLeft = $this->quotaLeft();

		$fileinfo = $this->storeUpload($key, $folder, $quotaLeft, $prefix, $allowedMime);
		$error = $fileinfo['error'];
		$filename = $fileinfo['filename'];
		$title = $fileinfo['title'];

		if(!empty($filename)) {
			//by default, file will not feature a password
			$password = null;
			if($type==MediabirdConstants::fileTypePdf && ($pdfInfo = $this->getPdfInfo($prefix.$filename))) {
				if($pdfInfo->encrypted) {
					//file is encrypted, so save a dummy password just to mark it protected
					$password = '';
				}
			}

			$file = $this->registerUpload($fileinfo['filename'],$type,$password,$title);

			if($type==MediabirdConstants::fileTypePdf && isset($pdfInfo)) {
				if(property_exists($pdfInfo,'pageCount')) {
					$file->pageCount = $pdfInfo->pageCount;
				}
				if(property_exists($pdfInfo,'title')) {
					$file->title = $pdfInfo->title;
				}
				$file->encrypted = $pdfInfo->encrypted;
			}

			if(!property_exists($file,'title') || $file->title == $file->filename) {
				$file->title = $title;
			}

			$info = json_encode($file);
		}
		else {
			$info = null;
		}
		return $this->generateUploadHtml($info, $error);
	}

	/**
	 * Reads a file into the response stream
	 * @param string $name
	 */
	function readUpload($name) {
		$mime = null;

		$path = $this->getBaseFolder().$name;

		if (function_exists("mime_content_type")) {
			$mime = @mime_content_type($path);
		}
		if (!$mime) {
			$mime = "image/png";
		}

		return $this->readFile($path,$mime);
	}

	/**
	 * Check if user is authorized to access the given file and if the page is valid
	 * @param int $uploadId
	 * @param bool $mustOwn
	 * @return bool
	 */
	function checkFileAuth($uploadId,$mustOwn=false) {
		//check if upload is authorized
		$record = $this->db->getRecord(MediabirdConfig::tableName("Upload",true),"id=$uploadId");

		if($mustOwn && $record->user_id != $this->userId) {
			return false;
		}

		$authedUploadIds = isset($_SESSION['mb_auth_up_ids']) ? $_SESSION['mb_auth_up_ids'] : array();

		if(in_array($uploadId,$authedUploadIds)) {
			return $record;
		}

		if($this->uploadRecordAuthorized($record)) {
			if($record->password==='') {
				//the file is encrypted but no password has been stored yet
				return false;
			}

			if(array_search($uploadId,$authedUploadIds)===false) {
				$authedUploadIds []= $uploadId;
				$_SESSION['mb_auth_up_ids'] = $authedUploadIds;
			}

			return $record;
		}
		return false;
	}

	/**
	 * Checks if given page num is in range for given upload record
	 * @param stdClass $record
	 * @param int $page
	 * @return bool
	 */
	function checkPageNum($record,$page) {
		$info = $this->getPdfInfo($this->getBaseFolder().$record->filename,$record->password);

		if(	isset($info) &&
		property_exists($info,'pageCount') &&
		$page <= $info->pageCount &&
		$page > 0 ) {
			return $record;
		}
	}

	/**
	 * Makes sure the given folder does not exceed $limit in size
	 */
	function enforceSizeLimit($folder,$limit) {
		//get all files in that folder

		if($handle = opendir($folder)) {

			$fileStats = array();

			while (false !== ($file = readdir($handle))) {
				if($file != "." && $file != ".." && !is_dir($file)) {
					$fileStat = stat($folder.$file);
					$fileStats[$file] = array(
						'name'=>$file,
						'size'=>$fileStat['size'],
						'mtime'=>$fileStat['mtime']
					);
				}
			}

			closedir($handle);

			//sort by mtime
			usort($fileStats,array("MediabirdFiles", "compareFileStat"));

			//calculate folder size
			$folderSize = 0;
			foreach($fileStats as $fileStat) {
				$folderSize += $fileStat['size'];
			}

			while($folderSize > $limit) {
				$fileStat = array_pop($fileStats);

				if(!unlink($folder.$fileStat['name'])) {
					return false;
				}

				$folderSize -= $fileStat['size'];
			};

			return true;
		}
		return false;
	}


	/**
	 * Serves for comparing two file stat entries
	 * used from enforceSizeLimit
	 */
	private static function compareFileStat($a,$b) {
		return $b['mtime'] - $a['mtime'];
	}

	/**
	 * Reads a PDF page as PNG file
	 * Renders PNG if not available
	 * @param $filename
	 * @param $num
	 * @param $folder
	 * @param $thumb
	 * @return bool
	 */
	function readPdfPage($filename,$num,$folder,$password=null,$thumb=false) {
		$cachePath = $this->getCacheFolder().($thumb?'thumbs':'pdf').DIRECTORY_SEPARATOR;

		if(!file_exists($cachePath)) {
			mkdir($cachePath);

			if(!file_exists($cachePath)) {
				return null;
			}
		}

		$sessKey = $thumb ? 'mb_cache_thumb_test' : 'mb_cache_pdf_test';

		//get last access time stamp
		if(isset($_SESSION[$sessKey])) {
			$lastAccess = $_SESSION[$sessKey];
		}

		//get timestamp
		$time = time();

		//check cache size limit no more often than all 60 seconds
		if(!isset($lastAccess) || ($time - $lastAccess >= 60)) {
			$_SESSION[$sessKey] = $time;

			//quickly calculate folder size
			$this->enforceSizeLimit($cachePath,MediabirdConfig::$cache_size*1000);
		}

		$destpath = sprintf("%s%s-%d%s.jpg",$cachePath,basename($filename),$num,$thumb?"t":"");

		$auth="";
		if(isset($password)) {
			$auth="-authenticate $password";
		}

		$width = $thumb ? "-thumbnail 100" : "";
		$quality = $thumb ? "" : "-quality 85";
		$density = $thumb ? "" : "-density 96";
		$colorspace = "-colorspace RGB";

		//magic expects zero-based numbers
		$num--;

		$cmd =
		escapeshellarg(MediabirdConfig::$magic_path).
			" $auth $density $colorspace ".
		escapeshellarg($folder.$filename).
			"[$num] $width $quality ".
		escapeshellarg($destpath);
		if(!file_exists($destpath)) {
			exec($cmd,$output); //for debug check $output if required
		}
		if(!file_exists($destpath)) {
			return null;
		}

		return $this->readFile($destpath,"image/jpeg");

	}

	function readFile($path,$mime) {
		if(!is_file($path)) {
			error_log("unable to find $path");
			header('HTTP/1.0 404 Not Found');
			exit;
		}

		if(!is_readable($path)) {
			error_log("unable to read $path");
			header('HTTP/1.0 403 Forbidden');
			exit;
		}

		//force caching
		header("Cache-Control: max-age=50400, must-revalidate");
		header("Pragma: cache");

		$stat = @stat($path);
		$etag = sprintf('%x-%x-%x', $stat['ino'], $stat['size'], $stat['mtime'] * 1000000);

		$client_etag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false;
		$client_last_modified = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
		$client_last_modified_timestamp = strtotime($client_last_modified);
		$last_modified_timestamp = $stat['mtime'];

		if(($client_last_modified && $client_etag) ? (($client_last_modified_timestamp == $last_modified_timestamp) && ($client_etag == $etag)) : (($client_last_modified_timestamp == $last_modified_timestamp) || ($client_etag == $etag))){
			header('HTTP/1.1 304 Not Modified',true,304);
			exit();
		}
		else {
			header('Last-Modified:'.gmdate("D, d M Y H:i:s",$stat['mtime']) . " GMT");
			header('ETag:'.$etag);
		}

		//give content type
		header("Content-Type: $mime");
		header('Content-Length: ' . $stat['size']);

		//send file
		$res = $this->readFileChunked($path,false);
		if($res===false) {
			header('HTTP/1.0 500 Internal Server Error');
			return false;
		}
		else {
			return $res;
		}
	}

	private function readFileChunked($filename,$retbytes=true) {
		$chunksize = 4*(1024*1024); // how many bytes per chunk
		$buffer = '';
		$cnt =0;

		//clear output buffer
		ob_end_clean();
		
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}

		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			@ob_flush();
			flush();
			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status) {
			return $cnt; // return num. bytes delivered like readfile() does.
		}
		return $status;
	}

	/**
	 * Renders equation
	 * @param $equation
	 * @return unknown_type
	 */
	function renderEquation($equation,&$results) {
		$renderer = new LatexRender(MediabirdConfig::$cache_folder);

		$renderer->_latex_path = MediabirdConfig::$latex_path;
		$renderer->_convert_path = MediabirdConfig::$convert_path;

		$userFolder = $this->getUserFolder();
		if(!file_exists($userFolder)) {
			mkdir($userFolder);
		}

		$path = $renderer->checkFormulaCache($equation,$userFolder);

		if (!$path) {
			$quotaLeft = $this->quotaLeft();

			if ($quotaLeft == 0) {
				return MediabirdConstants::notEnoughQuota;
			}

			$path = $renderer->renderLatex($equation);

			if ($path && file_exists($path)) {
				$fileSize = filesize($path);

				if ($fileSize < $quotaLeft || $quotaLeft == -1) {
					$status_code = copy($path, $userFolder.$renderer->destinationFile);
					if (!$status_code) {
						$result = MediabirdConstants::serverError;
					}
					else {
						$path = $renderer->destinationFile;
					}
				}
				else {
					$result = MediabirdConstants::notEnoughQuota;
				}
			}
			else {
				error_log("File does not seem to exist: '$path'");
				$result = MediabirdConstants::serverError;
			}
			$renderer->cleanTemporaryDirectory();

			//check if a return value has been saved (i.e. an error occurred)
			if(isset($result)) {
				return $result;
			}
		}

		//construct subpath info
		$path = $this->getUserSubfolder().$path;

		//store file in database
		$file = $this->registerUpload($path,MediabirdConstants::fileTypeEquation);

		if($file) {
			$results['files'] []= $file;
			return MediabirdConstants::processed;
		}
		else {
			return MediabirdConstants::serverError;
		}
	}

	var $copyParams = array('ids');

	function validateCopy($data,&$cache,&$reason) {

		//incoming: array of ids to be deep-copied
		$validates = is_object($data) && MediabirdUtility::checkKeyset($data,$this->copyParams);

		if($validates && (!is_array($data->ids) || count($data->ids)==0)) {
			$validates = false;
		}
			
		if($validates) {
			foreach($data->ids as $id) {
				if(!is_int($id)) {
					$validates = false;
					break;
				}
			}
		}

		if($validates) {
			//select files that the current user was granted access to (protected files)
			$select .= "id IN (
				SELECT upload_id FROM ".MediabirdConfig::tableName("UploadAccess")." WHERE user_id=$this->userId AND mask>0 
			)";

			//select files of users the current user knows
			$select .= " OR id IN (
				SELECT content_id FROM ".MediabirdConfig::tableName("Card")." WHERE content_type=".MediabirdConstants::cardTypePdf." AND topic_id IN (
					SELECT topic_id FROM ".MediabirdConfig::tableName('Right')." WHERE user_id=$this->userId AND mask>0
				)
			)";

			//select files not owned by current user (slicing)
			$select = "user_id != $this->userId AND (".$select.")";

			//slice by given ids
			$select = "id IN (".join(",",$data->ids).") AND (".$select.")";

			$records = $this->db->getRecords(MediabirdConfig::tableName("Upload",true),$select);

			if(!$records || count($records) != count($data->ids)) {
				$validates = false;
			}
		}
			
		if($validates) {
			$cache['uploadRecords'] = isset($records) ? $records : array();
		}
			
		if(!$validates && !isset($reason)) {
			$reason = MediabirdConstants::invalidData;
		}
			
		return $validates;
	}

	function copy($data,$cache,&$changes) {
		$records = $cache['uploadRecords'];

		$time = time();

		$files = array();

		foreach($records as $record) {

			$filePath = $this->getBaseFolder().$record->filename;

			$basename = basename($record->filename);
			$folder = $this->getUserFolder();

			$copyPath = $folder.$basename;

			$basename = basename($copyPath);

			$copyName = $this->getUserSubfolder().$basename;

			if(!copy($filePath,$copyPath)) {
				return MediabirdConstants::serverError;
			}

			$file = $this->registerUpload($copyName,intval($record->type),$record->password,$record->title);

			if($file) {
				//authorize if necessary
				if(!empty($record->password)) {
					$tempResults = array();
					$this->authorizeFile($file->id,$record->password,$tempResults);
				}

				//load pdf info if required
				if($file->type == MediabirdConstants::fileTypePdf) {
					if(!$copyRecord = $this->db->getRecord(MediabirdConfig::tableName("Upload",true),"id=$file->id")) {
						return MediabirdConstants::serverError;
					}
					$this->extendPdfInfo($file,$copyRecord);
				}

				$files []= $file;
			}
			else {
				return MediabirdConstants::serverError;
			}
		}

		$changes['files'] = $files;
			
		return MediabirdConstants::processed;
	}

	function delete($ids,&$results) {
		//check if user is owner of upload
		$select = "id IN (".join(",",$ids).") AND user_id=$this->userId";

		if($this->db->countRecords(MediabirdConfig::tableName("Upload",true),$select) != count($ids)) {
			return MediabirdConstants::accessDenied;
		}

		//now delete upload and cards featuring that file

		//detemine all cards that feature the file
		$select = "content_type=1 AND content_id IN (".join(",",$ids).")";

		$okay = true;

		if($cardRecords = $this->db->getRecords(MediabirdConfig::tableName("Card",true),$select)) {

			//collect their ids
			$cardIds = array();
			foreach($cardRecords as $cardRecord) {
				$cardIds []= intval($cardRecord->id);
			}

			$results['removedCardIds'] = $cardIds;

			//prepare delete statements
			$cardIdString = join(",",$cardIds);

			$select = "marker_id IN
				(SELECT id FROM ".MediabirdConfig::tableName('Marker')." WHERE card_id IN ($cardIdString))";
			$okay = $okay && parent::deleteGeneric('Relation',$select,$results);

			$select = "card_id IN ($cardIdString)";
			$okay = $okay && parent::deleteGeneric('CardTag',$select);

			$select = "card_id IN ($cardIdString)";
			$okay = $okay && parent::deleteGeneric('Marker',$select,$results);

			$select = "id IN ($cardIdString)";
			$okay = $okay && parent::deleteGeneric('Card',$select,$results);
		}

		$select = "id IN (".join(",",$ids).")";
		$okay = $okay && parent::deleteGeneric('Upload',$select,$results);

		if($okay) {
			return MediabirdConstants::processed;
		}
		else {
			return MediabirdConstants::serverError;
		}
	}
}
?>
