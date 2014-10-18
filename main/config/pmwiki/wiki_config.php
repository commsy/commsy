<?php if (!defined('PmWiki')) exit();

include_once('commsy_config.php');
if ( !empty($COMMSY_WIKI_TITLE) ) {
   $WikiTitle = $COMMSY_WIKI_TITLE;
} else {
   $WikiTitle = 'CommSyWiki';
}
if ( !empty($COMMSY_SKIN) ) {
   $Skin = $COMMSY_SKIN;
}
if ( !empty($COMMSY_LANGUAGE) ) {
   XLPage($COMMSY_LANGUAGE,'PmWikiDe.XLPage');
}

	#***********************************
	#******FOXFORUM Einstellungen*******
	#***********************************
	$EnableRelativePageVars = 1;
	# needed for creating other Forum groups, modify as needed

	# needed for adding new page store with ready wiki pages in cookbook/fox/templates.d/
	include_once("$FarmD/cookbook/fox/foxtemplates.php");
	# main form processor added here so other Forum groups can be created
	# from FoxTemplates/CreateNewForum page
	include_once("$FarmD/cookbook/fox/fox.php");
	# add delete links // uncomment next line if foxdelete is used outside Forum group, i.e other Fox forms //
	include_once("$FarmD/cookbook/fox/foxdelete.php");
	# add edit links // uncomment next line if foxedit is used outside Forum group, i.e other Fox forms //
	include_once("$FarmD/cookbook/fox/foxedit.php");
	include_once("$FarmD/cookbook/guibuttons.php");

	# set forum type:
	# set to 'standard' for standard one page per topic type forum.
	# set to 'extended' for one post per page (ForumX) type forum.
	$FoxForum['type'] = 'standard';
	#$FoxForum['type'] = 'extended';
	## add foxforum for this group if it was not included in config.php
	## (it should really be included in config.php for the page library to work properly)
	include_once("$FarmD/cookbook/fox/foxforum.php");
	# default form called by foxedit links
	$FoxEditPTVSectionForm = 'FoxTemplates.EditMessageForm';
	$DisplayTopicTemplate = 'FoxTemplates.DisplayTopicTemplate#classic';
	
	## GUI Buttons: set to 0 if you don't want GUIButtons
	## default is 1 set in foxforum.php loading guibuttons.php
	# $EnableGuiButtons = 0; //
	## Smileys:  you can disable smiley buttons and markup by setting it to 0, default is 1
	## they are part of GuiButtons.
	#$EnableSmileyGuiButtons = 0;
	#$EnableSmileyMarkup = 0;
	## set $FoxAuth to 'read' if you want users with no edit permission to be able to post in this group
	$FoxAuth = 'read';
	## allow posting of directives of form (:...:) if logged in
	if(CondAuth($pagename,'edit')) $EnablePostDirectives = true;
	# set to 1 if you  want access code (simple non-graphical captcha)
	# for instance if access is restricted to logged-in users.
	# you then need to modify the form pages as well, to remove the access code boxes
	# It is better to use Cookbook/Captcha
	# $EnableFoxForumPostCaptchaRequired = 1;
	## page will be deleted if empty
	$DeleteKeyPattern = "^\\s*$";
	## line breaks will be honoured
	$HTMLPNewline = '<br />';
	
   $EnablePostCaptchaRequired = 1;
   if (CondAuth($pagename,'edit') || CondAuth($pagename,'admin')){
      $EnablePostCaptchaRequired = 0;
   }
   
	#***********************************
	#**Ende FOXFORUM Einstellungen******
	#***********************************

include_once("$FarmD/cookbook/slimbox.php");

include_once("$FarmD/cookbook/pagetoc.php");
?>