<?php
   header("Content-type: application/x-javascript");

   // init
   chdir('../../../../../../..');
   include_once('etc/cs_constants.php');
   include_once('etc/cs_config.php');
   include_once('functions/misc_functions.php');
   include_once('classes/cs_environment.php');
   $environment = new cs_environment();
   $text1 = ' ';
   $text2 = ' ';

   // only if cid is set
   if ( !empty($_GET['cid'])
		and $_GET['cid'] > 99
      ) {
	  // transform POST_VARS and GET_VARS --- move into page object, if exist
	  include_once('functions/text_functions.php');
	  $_GET = encode(FROM_GET,$_GET);
	   
	  // multi master implementation
	  $db = $environment->getConfiguration('db');
	  if ( count($db) > 1 ) {
	   	 if ( !empty($_COOKIE['db_pid']) ) {
	   		$environment->setDBPortalID($_COOKIE['db_pid']);
	     } elseif ( !empty($_GET['db_pid']) ) {
	   	    $environment->setDBPortalID($_GET['db_pid']);
	     }
      }
	  // multi master implementation - END
	  
	  // set current context
	  $environment->setCurrentContextID($_GET['cid']);
	  
	  // get plugins from plugins
	  $text1 = plugin_hook_output_all('getTextFormatingInformationAsHTML','',BRLF);
	  if ( !empty($text1) ) {
		 $text1 = BRLF.$text1; 
	  } else {
		 $text1 = ' ';
	  }
   }

   // text
   $translator = $environment->getTranslationObject();
   $text = $translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT_JS',$text1,$text2);
   $text = str_replace("'","\'",$text);
   $text = str_replace(array("\r\n", "\r", "\n"),'',$text);
?>
/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( "CommSyAbout",
{
	init: function( editor )
	{
		editor.addCommand( "CommSyAbout", new CKEDITOR.dialogCommand( "CommSyAbout" ) );
		
		editor.ui.addButton( "CommSyAbout",
		{
			label:		"CommSy Formatierungsmöglichkeiten",
			command:	"CommSyAbout",
			icon:		"../../src/commsy/ckeditor/plugins/about/icon.png"
		} );
		
		CKEDITOR.dialog.add( "CommSyAbout", function( api )
		{
			return {
				title : 'CommSy Formatierungsmöglichkeiten',
				minWidth : 950,
				minHeight : 550,
				contents : [
					{
						id : 'tab1',
						label : 'CommSy Formatierungsmöglichkeiten',
						title : 'CommSy Formatierungsmöglichkeiten',
						expand : false,
						padding : 0,
						elements :
						[
							{
								type : 'html',
								html : '<?php echo($text);?>'
							}
						]
					}
				],
				buttons : []
			};
		} );
	}
} );