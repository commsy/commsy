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


if ( !empty($_GET['surveyId']) && !empty($_GET['timestamp']) )
{
	$currentPortalItem = $environment->getCurrentPortalItem();
	$currentContextItem = $environment->getCurrentContextItem();
	
	// check context
	if (	!($environment->inPortal() || $environment->inServer()) &&
			$currentPortalItem->withLimeSurveyFunctions() &&
			$currentPortalItem->isLimeSurveyActive() &&
			$currentContextItem->isLimeSurveyActive() )
	{
		// check user
		$currentUserItem = $environment->getCurrentUserItem();
		
		if ( $currentUserItem->isModerator() )
		{
			// get the export folder for this single export
			$discManager = $environment->getDiscManager();
			$mainExportFolder = $discManager->getFilePath() . "limesurvey_export/";
				
			$surveyFolder = $mainExportFolder . $_GET["surveyId"] . "/";
			$timestampFolder = $surveyFolder . $_GET["timestamp"] . "/";
			
			// either deliver a single file or all files as a zip archive
			if ( !empty($_GET["file"]) )
			{
				switch ( $_GET["file"] )
				{
					case "survey":
						$fileNameDeliver = "export_" . $_GET["surveyId"] . "_survey.lss";
						
						if ( file_exists($timestampFolder . "survey.lss") )
						{
							$fileName = $timestampFolder . "survey.lss";
						}
						else
						{
							include_once('functions/error_functions.php');
							trigger_error("file does not exists", E_USER_WARNING);
						}
						
						break;
						
					case "statistics":
						$fileNameDeliver = "export_" . $_GET["surveyId"] . "_statistics.pdf";
							
						if ( file_exists($timestampFolder . "statistics.pdf") )
						{
							$fileName = $timestampFolder . "statistics.pdf";
						}
						else
						{
							include_once('functions/error_functions.php');
							trigger_error("file does not exists", E_USER_WARNING);
						}
						
						break;
					
					case "responses":
						$fileNameDeliver = "export_" . $_GET["surveyId"] . "_responses.csv";
							
						if ( file_exists($timestampFolder . "responses.csv") )
						{
							$fileName = $timestampFolder . "responses.csv";
						}
						else
						{
							include_once('functions/error_functions.php');
							trigger_error("file does not exists", E_USER_WARNING);
						}
						
						break;
						
					default:
						include_once('functions/error_functions.php');
						trigger_error("no filename given", E_USER_WARNING);
				}
			}
			else
			{
				$fileNameDeliver = "export_" . $_GET["surveyId"] . ".zip";
				
				// create zip file
				$zipFile = $timestampFolder . "export_" . $_GET["surveyId"] . ".zip";
				
				if (file_exists(realpath($zipFile))) unlink($zipFile);
				
				if (class_exists("ZipArchive")) {
					include_once('functions/misc_functions.php');
						
					$zipArchive = new ZipArchive();
						
					if ($zipArchive->open($zipFile, ZIPARCHIVE::CREATE) !== TRUE) {
						include_once('functions/error_functions.php');
						trigger_error('can not open zip-file ' . $zipFile, E_USER_WARNING);
					}
				
					// write to zip
					$tempDir = getcwd();
					chdir($timestampFolder);
				
					$zipArchive = addFolderToZip(".", $zipArchive);
					chdir($tempDir);
						
					$zipArchive->close();
					
					$fileName = $zipFile;
				} else {
					include_once('functions/error_functions.php');
					trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNING);
				}
			}
			
			// send header and file
			header("Content-Disposition: attachment; filename=" . urlencode($fileNameDeliver));
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Description: File Transfer");
			header("Content-Length: " . filesize($fileName));
			flush(); // this doesn't really matter.
			
			$fp = fopen($fileName, "r");
			while ( !feof($fp) )
			{
				echo fread($fp, 65536);
				flush(); // this is essential for large downloads
			}
			fclose($fp);
		}
		else
		{
			include_once('functions/error_functions.php');
			trigger_error("limesurvey_getfile: Insufficent rights", E_USER_ERROR);
		}
	}
	else
	{
		include_once('functions/error_functions.php');
		trigger_error("limesurvey_getfile: LimeSurvey is not activated in this context", E_USER_ERROR);
	}
}
else
{
	include_once('functions/error_functions.php');
	trigger_error("limesurvey_getfile: Parameters missing", E_USER_ERROR);
}