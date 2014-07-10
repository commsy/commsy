<?php
   header("Content-Type: text/xml");
   chdir('..');
   $plugin_config_file = 'etc/commsy/plugin.php';
   $soap_functions_array = array();
   if ( file_exists($plugin_config_file) ) {
      include_once($plugin_config_file);
      include_once('etc/cs_constants.php');
      include_once('etc/cs_config.php');
      include_once('functions/misc_functions.php');
      include_once('classes/cs_environment.php');
      $environment = new cs_environment();
      
      if ( !empty($_GET['plugin']) ) {
      	// full wsdl only for plugin
      	$plugin_name = $_GET['plugin'];
      	$plugin_class = $environment->getPluginClass($plugin_name);
      	if ( !empty($plugin_class)
      		  and method_exists($plugin_class, 'getFullWSDL') 
      	   ) {
      		$wsdl = $plugin_class->getFullWSDL();
      		unset($plugin_class);
      		if ( !empty($wsdl) ) {
      		   echo($wsdl);
      		   exit();
      		}
      	}
      } else {
      	// merge plugin soap functions into CommSy soap functions
         $soap_functions_array = plugin_hook_output_all('getSOAPAPIArray',array(),'ARRAY');
      }
   }
   
   // soap_functions from classes
   if ( !isset($environment) ) {
   	include_once('etc/cs_constants.php');
   	include_once('etc/cs_config.php');
   	include_once('functions/misc_functions.php');
   	include_once('classes/cs_environment.php');
   	$environment = new cs_environment();
   }
   	
   $connection_obj = $environment->getCommSyConnectionObject();
   if ( !empty($connection_obj) ) {
   	$soap_functions_array_from_class = $connection_obj->getSoapFunctionArray();
   	if ( !empty($soap_functions_array_from_class) ) {
   	   $soap_functions_array = array_merge($soap_functions_array,$soap_functions_array_from_class);
   	}
   }
?>
<<?php echo('?'); ?>xml version ='1.0' encoding ='UTF-8'?>
<definitions name='CommSy'
  targetNamespace='<?php echo('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']); ?>'
  xmlns:tns='<?php echo('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']); ?>'
  xmlns:soap='http://schemas.xmlsoap.org/wsdl/soap/'
  xmlns:xsd='http://www.w3.org/2001/XMLSchema'
  xmlns:soapenc='http://schemas.xmlsoap.org/soap/encoding/'
  xmlns:wsdl='http://schemas.xmlsoap.org/wsdl/'
  xmlns='http://schemas.xmlsoap.org/wsdl/'>

<message name='getActiveRoomListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
  <part name='count' type='xsd:integer'/>
</message>
<message name='getActiveRoomListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getActiveRoomListForUserIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
  <part name='count' type='xsd:integer'/>
</message>
<message name='getActiveRoomListForUserOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='createUserIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
  <part name='firstname' type='xsd:string'/>
  <part name='lastname' type='xsd:string'/>
  <part name='mail' type='xsd:string'/>
  <part name='user_id' type='xsd:string'/>
  <part name='user_pwd' type='xsd:string'/>
  <part name='agb' type='xsd:boolean'/>
  <part name='send_email' type='xsd:boolean'/>
</message>
<message name='createUserOUT'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='getGuestSessionIN'>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getGuestSessionOUT'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='getCountRoomsIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getCountRoomsOUT'>
  <part name='session_id' type='xsd:integer'/>
</message>
<message name='getCountUserIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getCountUserOUT'>
  <part name='count_user' type='xsd:integer'/>
</message>
<message name='authenticateIN'>
  <part name='user_id' type='xsd:string'/>
  <part name='password' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
  <part name='auth_source_id' type='xsd:integer'/>
</message>
<message name='authenticateOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='authenticateWithLoginIN'>
  <part name='user_id' type='xsd:string'/>
  <part name='password' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
  <part name='auth_source_id' type='xsd:integer'/>
</message>
<message name='authenticateWithLoginOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='IMSIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='ims_xml' type='xsd:string'/>
</message>
<message name='IMSOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getMaterialListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getMaterialListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getPrivateRoomMaterialListIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='getPrivateRoomMaterialListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getFileListFromMaterialIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='material_id' type='xsd:integer'/>
</message>
<message name='getFileListFromMaterialOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getSectionListFromMaterialIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='material_id' type='xsd:integer'/>
</message>
<message name='getSectionListFromMaterialOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getFileListFromItemIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='item_id' type='xsd:integer'/>
</message>
<message name='getFileListFromItemOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getFileItemIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='file_id' type='xsd:integer'/>
</message>
<message name='getFileItemOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='deleteFileItemIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='file_id' type='xsd:integer'/>
</message>
<message name='deleteFileItemOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='addPrivateRoomMaterialListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='material_list_xml' type='xsd:string'/>
</message>
<message name='addPrivateRoomMaterialListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='addFileForMaterialIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='material_id' type='xsd:integer'/>
  <part name='file_item_xml' type='xsd:string'/>
</message>
<message name='addFileForMaterialOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='linkFileToMaterialIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='material_id' type='xsd:integer'/>
  <part name='file_id' type='xsd:integer'/>
</message>
<message name='linkFileToMaterialOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='addMaterialLimitIN'>
  <part name='key' type='xsd:string'/>
  <part name='value' type='xsd:integer'/>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='addMaterialLimitOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='getBuzzwordListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getBuzzwordListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getLabelListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getLabelListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getGroupListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getGroupListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getTopicListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getTopicListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getUserInfoIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
</message>
<message name='getUserInfoOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getRSSUrlIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='getRSSUrlOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getRoomListIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='getRoomListOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getAuthenticationForWikiIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='user_id' type='xsd:string'/>
</message>
<message name='getAuthenticationForWikiOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='savePosForItemIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='item_id' type='xsd:integer'/>
  <part name='x' type='xsd:integer'/>
  <part name='y' type='xsd:integer'/>
</message>
<message name='savePosForItemOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='savePosForLinkIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='item_id' type='xsd:integer'/>
  <part name='label_id' type='xsd:integer'/>
  <part name='x' type='xsd:integer'/>
  <part name='y' type='xsd:integer'/>
</message>
<message name='savePosForLinkOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='refreshSessionIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='refreshSessionOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='logoutIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='logoutOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='authenticateViaSessionIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='authenticateViaSessionOUT'>
  <part name='user_id' type='xsd:string'/>
</message>
<message name='wordpressAuthenticateViaSessionIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='wordpressAuthenticateViaSessionOUT'>
  <part name='user_id' type='xsd:string'/>
</message>
<message name='changeUserEmailIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='email' type='xsd:string'/>
</message>
<message name='changeUserEmailOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='changeUserEmailAllIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='email' type='xsd:string'/>
</message>
<message name='changeUserEmailAllOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='changeUserIdIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='user_id' type='xsd:string'/>
</message>
<message name='changeUserIdOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='setUserExternalIdIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='external_id' type='xsd:string'/>
</message>
<message name='setUserExternalIdOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='changeUserNameIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='firstname' type='xsd:string'/>
  <part name='lastname' type='xsd:string'/>
</message>
<message name='changeUserNameOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='updateLastloginIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='tool' type='xsd:string'/>
  <part name='room_id' type='xsd:integer'/>
</message>
<message name='updateLastloginOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='createMembershipBySessionIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
  <part name='agb' type='xsd:boolean'/>
</message>
<message name='createMembershipBySessionOUT'>
  <part name='result' type='xsd:boolean'/>
</message>
<message name='getAGBFromRoomIN'>
  <part name='context_id' type='xsd:integer'/>
  <part name='language' type='xsd:string'/>
</message>
<message name='getAGBFromRoomOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getStatisticsIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='start_date' type='xsd:string'/>
  <part name='end_date' type='xsd:string'/>
</message>
<message name='getStatisticsOUT'>
  <part name='result' type='xsd:string'/>
</message>

<message name='authenticateForAppIN'>
  <part name='user_id' type='xsd:string'/>
  <part name='password' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
  <part name='auth_source_id' type='xsd:integer'/>
</message>
<message name='authenticateForAppOUT'>
  <part name='result' type='xsd:string'/>
</message>
<message name='getPortalRoomListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getPortalRoomListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getPortalRoomListByCountAndSearchIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='start' type='xsd:integer'/>
  <part name='count' type='xsd:integer'/>
  <part name='search' type='xsd:string'/>
</message>
<message name='getPortalRoomListByCountAndSearchOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getUserInformationIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getUserInformationOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getPortalConfigIN'>
  <part name='portal_id' type='xsd:string'/>
</message>
<message name='getPortalConfigOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getAuthSourcesIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getAuthSourcesOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getBarInformationIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getBarInformationOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getPortalListIN'>
</message>
<message name='getPortalListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getDatesListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getDatesListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getDateDetailsIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
  <part name='item_id' type='xsd:integer'/>
</message>
<message name='getDateDetailsOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveDateIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='title' type='xsd:string'/>
  <part name='place' type='xsd:string'/>
  <part name='description' type='xsd:string'/>
  <part name='startingDate' type='xsd:string'/>
  <part name='startingTime' type='xsd:string'/>
  <part name='endingDate' type='xsd:string'/>
  <part name='endingTime' type='xsd:string'/>
  <part name='uploadFiles' type='xsd:string'/>
  <part name='deleteFiles' type='xsd:string'/>
</message>
<message name='saveDateOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='deleteDateIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
</message>
<message name='deleteDateOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getMaterialsListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getMaterialsListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getMaterialDetailsIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
  <part name='item_id' type='xsd:integer'/>
</message>
<message name='getMaterialDetailsOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveMaterialIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='title' type='xsd:string'/>
  <part name='description' type='xsd:string'/>
  <part name='uploadFiles' type='xsd:string'/>
  <part name='deleteFiles' type='xsd:string'/>
</message>
<message name='saveMaterialOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='deleteMaterialIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
</message>
<message name='deleteMaterialOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveSectionIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='title' type='xsd:string'/>
  <part name='description' type='xsd:string'/>
  <part name='number' type='xsd:string'/>
  <part name='uploadFiles' type='xsd:string'/>
  <part name='deleteFiles' type='xsd:string'/>
  <part name='material_item_id' type='xsd:string'/>
</message>
<message name='saveSectionOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='deleteSectionIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
</message>
<message name='deleteSectionOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getDiscussionListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getDiscussionListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getDiscussionDetailsIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
  <part name='item_id' type='xsd:integer'/>
</message>
<message name='getDiscussionDetailsOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='deleteDiscussionIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
</message>
<message name='deleteDiscussionOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveDiscussionArticleIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='title' type='xsd:string'/>
  <part name='description' type='xsd:string'/>
  <part name='uploadFiles' type='xsd:string'/>
  <part name='deleteFiles' type='xsd:string'/>
  <part name='discussion_item_id' type='xsd:string'/>
  <part name='answerTo' type='xsd:string'/>
</message>
<message name='saveDiscussionArticleOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveDiscussionWithInitialArticleIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='title' type='xsd:string'/>
  <part name='item_id_article' type='xsd:string'/>
  <part name='title_article' type='xsd:string'/>
  <part name='description_article' type='xsd:string'/>
  <part name='uploadFiles' type='xsd:string'/>
  <part name='deleteFiles' type='xsd:string'/>
</message>
<message name='saveDiscussionWithInitialArticleOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveDiscussionIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='title' type='xsd:string'/>
</message>
<message name='saveDiscussionOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='deleteDiscussionArticleIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
</message>
<message name='deleteDiscussionArticleOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getUserListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getUserListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='saveUserIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:string'/>
  <part name='item_id' type='xsd:string'/>
  <part name='name' type='xsd:string'/>
  <part name='firstname' type='xsd:string'/>
  <part name='email' type='xsd:string'/>
  <part name='phone1' type='xsd:string'/>
  <part name='phone2' type='xsd:string'/>
</message>
<message name='saveUserOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='uploadFileIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
  <part name='file_id' type='xsd:string'/>
  <part name='file_data' type='xsd:string'/>
</message>
<message name='uploadFileOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getRoomReadCounterIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='context_id' type='xsd:integer'/>
</message>
<message name='getRoomReadCounterOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getModerationUserListIN'>
  <part name='session_id' type='xsd:string'/>
</message>
<message name='getModerationUserListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='activateUserIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='activate_user_id' type='xsd:integer'/>
</message>
<message name='activateUserOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>

<?php
// message - dynamisch
foreach ( $soap_functions_array as $key => $in_out ) {
   echo("<message name='".$key."IN'>\n");
   if ( !empty($in_out['in']) ) {
      foreach ( $in_out['in'] as $name => $type ) {
         echo("<part name='".$name."' type='xsd:".$type."'/>\n");
      }
   }
   echo('</message>'."\n");
   echo("<message name='".$key."OUT'>\n");
   if ( !empty($in_out['out']) ) {
      foreach ( $in_out['out'] as $name => $type ) {
         echo("<part name='".$name."' type='xsd:".$type."'/>\n");
      }
   }
   echo('</message>'."\n");
}
echo("\n");
?>

<portType name='CommSyPortType'>
  <operation name='getGuestSession'>
    <input message='tns:getGuestSessionIN'/>
    <output message='tns:getGuestSessionOUT'/>
  </operation>
  <operation name='getActiveRoomList'>
    <input message='tns:getActiveRoomListIN'/>
    <output message='tns:getActiveRoomListOUT'/>
  </operation>
  <operation name='getActiveRoomListForUser'>
    <input message='tns:getActiveRoomListForUserIN'/>
    <output message='tns:getActiveRoomListForUserOUT'/>
  </operation>
  <operation name='createUser'>
    <input message='tns:createUserIN'/>
    <output message='tns:createUserOUT'/>
  </operation>
  <operation name='getCountRooms'>
    <input message='tns:getCountRoomsIN'/>
    <output message='tns:getCountRoomsOUT'/>
  </operation>
  <operation name='getCountUser'>
    <input message='tns:getCountUserIN'/>
    <output message='tns:getCountUserOUT'/>
  </operation>
  <operation name='authenticate'>
    <input message='tns:authenticateIN'/>
    <output message='tns:authenticateOUT'/>
  </operation>
  <operation name='authenticateWithLogin'>
    <input message='tns:authenticateWithLoginIN'/>
    <output message='tns:authenticateWithLoginOUT'/>
  </operation>
  <operation name='IMS'>
    <input message='tns:IMSIN'/>
    <output message='tns:IMSOUT'/>
  </operation>
  <operation name='getMaterialList'>
    <input message='tns:getMaterialListIN'/>
    <output message='tns:getMaterialListOUT'/>
  </operation>
  <operation name='getPrivateRoomMaterialList'>
    <input message='tns:getPrivateRoomMaterialListIN'/>
    <output message='tns:getPrivateRoomMaterialListOUT'/>
  </operation>
  <operation name='getSectionListFromMaterial'>
    <input message='tns:getSectionListFromMaterialIN'/>
    <output message='tns:getSectionListFromMaterialOUT'/>
  </operation>
  <operation name='getFileListFromMaterial'>
    <input message='tns:getFileListFromMaterialIN'/>
    <output message='tns:getFileListFromMaterialOUT'/>
  </operation>
  <operation name='getFileListFromItem'>
    <input message='tns:getFileListFromItemIN'/>
    <output message='tns:getFileListFromItemOUT'/>
  </operation>
  <operation name='getFileItem'>
    <input message='tns:getFileItemIN'/>
    <output message='tns:getFileItemOUT'/>
  </operation>
  <operation name='deleteFileItem'>
    <input message='tns:deleteFileItemIN'/>
    <output message='tns:deleteFileItemOUT'/>
  </operation>
  <operation name='addPrivateRoomMaterialList'>
    <input message='tns:addPrivateRoomMaterialListIN'/>
    <output message='tns:addPrivateRoomMaterialListOUT'/>
  </operation>
  <operation name='addFileForMaterial'>
    <input message='tns:addFileForMaterialIN'/>
    <output message='tns:addFileForMaterialOUT'/>
  </operation>
  <operation name='linkFileToMaterial'>
    <input message='tns:linkFileToMaterialIN'/>
    <output message='tns:linkFileToMaterialOUT'/>
  </operation>
  <operation name='addMaterialLimit'>
    <input message='tns:addMaterialLimitIN'/>
    <output message='tns:addMaterialLimitOUT'/>
  </operation>
  <operation name='getBuzzwordList'>
    <input message='tns:getBuzzwordListIN'/>
    <output message='tns:getBuzzwordListOUT'/>
  </operation>
  <operation name='getLabelList'>
    <input message='tns:getLabelListIN'/>
    <output message='tns:getLabelListOUT'/>
  </operation>
  <operation name='getGroupList'>
    <input message='tns:getGroupListIN'/>
    <output message='tns:getGroupListOUT'/>
  </operation>
  <operation name='getTopicList'>
    <input message='tns:getTopicListIN'/>
    <output message='tns:getTopicListOUT'/>
  </operation>
  <operation name='getUserInfo'>
    <input message='tns:getUserInfoIN'/>
    <output message='tns:getUserInfoOUT'/>
  </operation>
  <operation name='getRSSUrl'>
    <input message='tns:getRSSUrlIN'/>
    <output message='tns:getRSSUrlOUT'/>
  </operation>
  <operation name='getRoomList'>
    <input message='tns:getRoomListIN'/>
    <output message='tns:getRoomListOUT'/>
  </operation>
  <operation name='getAuthenticationForWiki'>
    <input message='tns:getAuthenticationForWikiIN'/>
    <output message='tns:getAuthenticationForWikiOUT'/>
  </operation>
  <operation name='savePosForItem'>
    <input message='tns:savePosForItemIN'/>
    <output message='tns:savePosForItemOUT'/>
  </operation>
  <operation name='savePosForLink'>
    <input message='tns:savePosForLinkIN'/>
    <output message='tns:savePosForLinkOUT'/>
  </operation>
  <operation name='refreshSession'>
    <input message='tns:refreshSessionIN'/>
    <output message='tns:refreshSessionOUT'/>
  </operation>
  <operation name='logout'>
    <input message='tns:logoutIN'/>
    <output message='tns:logoutOUT'/>
  </operation>
  <operation name='authenticateViaSession'>
    <input message='tns:authenticateViaSessionIN'/>
    <output message='tns:authenticateViaSessionOUT'/>
  </operation>
  <operation name='wordpressAuthenticateViaSession'>
    <input message='tns:wordpressAuthenticateViaSessionIN'/>
    <output message='tns:wordpressAuthenticateViaSessionOUT'/>
  </operation>
  <operation name='changeUserEmail'>
    <input message='tns:changeUserEmailIN'/>
    <output message='tns:changeUserEmailOUT'/>
  </operation>
  <operation name='changeUserEmailAll'>
    <input message='tns:changeUserEmailAllIN'/>
    <output message='tns:changeUserEmailAllOUT'/>
  </operation>
  <operation name='changeUserId'>
    <input message='tns:changeUserIdIN'/>
    <output message='tns:changeUserIdOUT'/>
  </operation>
  <operation name='setUserExternalId'>
    <input message='tns:setUserExternalIdIN'/>
    <output message='tns:setUserExternalIdOUT'/>
  </operation>
  <operation name='changeUserName'>
    <input message='tns:changeUserNameIN'/>
    <output message='tns:changeUserNameOUT'/>
  </operation>
  <operation name='updateLastlogin'>
    <input message='tns:updateLastloginIN'/>
    <output message='tns:updateLastloginOUT'/>
  </operation>
  <operation name='createMembershipBySession'>
    <input message='tns:createMembershipBySessionIN'/>
    <output message='tns:createMembershipBySessionOUT'/>
  </operation>
  <operation name='getAGBFromRoom'>
    <input message='tns:getAGBFromRoomIN'/>
    <output message='tns:getAGBFromRoomOUT'/>
  </operation>
  <operation name='getStatistics'>
    <input message='tns:getStatisticsIN'/>
    <output message='tns:getStatisticsOUT'/>
  </operation>
  
  <operation name='authenticateForApp'>
    <input message='tns:authenticateForAppIN'/>
    <output message='tns:authenticateForAppOUT'/>
  </operation>
  <operation name='getPortalRoomList'>
    <input message='tns:getPortalRoomListIN'/>
    <output message='tns:getPortalRoomListOUT'/>
  </operation>
  <operation name='getPortalRoomListByCountAndSearch'>
    <input message='tns:getPortalRoomListByCountAndSearchIN'/>
    <output message='tns:getPortalRoomListByCountAndSearchOUT'/>
  </operation>
   <operation name='getUserInformation'>
    <input message='tns:getUserInformationIN'/>
    <output message='tns:getUserInformationOUT'/>
  </operation>
  <operation name='getPortalConfig'>
    <input message='tns:getPortalConfigIN'/>
    <output message='tns:getPortalConfigOUT'/>
  </operation>
  <operation name='getAuthSources'>
    <input message='tns:getAuthSourcesIN'/>
    <output message='tns:getAuthSourcesOUT'/>
  </operation>
  <operation name='getBarInformation'>
    <input message='tns:getBarInformationIN'/>
    <output message='tns:getBarInformationOUT'/>
  </operation>
  <operation name='getPortalList'>
    <input message='tns:getPortalListIN'/>
    <output message='tns:getPortalListOUT'/>
  </operation>
  <operation name='getDatesList'>
    <input message='tns:getDatesListIN'/>
    <output message='tns:getDatesListOUT'/>
  </operation>
  <operation name='getDateDetails'>
    <input message='tns:getDateDetailsIN'/>
    <output message='tns:getDateDetailsOUT'/>
  </operation>
  <operation name='saveDate'>
    <input message='tns:saveDateIN'/>
    <output message='tns:saveDateOUT'/>
  </operation>
  <operation name='deleteDate'>
    <input message='tns:deleteDateIN'/>
    <output message='tns:deleteDateOUT'/>
  </operation>
  <operation name='getMaterialsList'>
    <input message='tns:getMaterialsListIN'/>
    <output message='tns:getMaterialsListOUT'/>
  </operation>
  <operation name='getMaterialDetails'>
    <input message='tns:getMaterialDetailsIN'/>
    <output message='tns:getMaterialDetailsOUT'/>
  </operation>
  <operation name='saveMaterial'>
    <input message='tns:saveMaterialIN'/>
    <output message='tns:saveMaterialOUT'/>
  </operation>
  <operation name='deleteMaterial'>
    <input message='tns:deleteMaterialIN'/>
    <output message='tns:deleteMaterialOUT'/>
  </operation>
  <operation name='saveSection'>
    <input message='tns:saveSectionIN'/>
    <output message='tns:saveSectionOUT'/>
  </operation>
  <operation name='deleteSection'>
    <input message='tns:deleteSectionIN'/>
    <output message='tns:deleteSectionOUT'/>
  </operation>
  <operation name='getDiscussionList'>
    <input message='tns:getDiscussionListIN'/>
    <output message='tns:getDiscussionListOUT'/>
  </operation>
  <operation name='getDiscussionDetails'>
    <input message='tns:getDiscussionDetailsIN'/>
    <output message='tns:getDiscussionDetailsOUT'/>
  </operation>
  <operation name='deleteDiscussion'>
    <input message='tns:deleteDiscussionIN'/>
    <output message='tns:deleteDiscussionOUT'/>
  </operation>
  <operation name='saveDiscussionArticle'>
    <input message='tns:saveDiscussionArticleIN'/>
    <output message='tns:saveDiscussionArticleOUT'/>
  </operation>
  <operation name='saveDiscussionWithInitialArticle'>
    <input message='tns:saveDiscussionWithInitialArticleIN'/>
    <output message='tns:saveDiscussionWithInitialArticleOUT'/>
  </operation>
  <operation name='saveDiscussion'>
    <input message='tns:saveDiscussionIN'/>
    <output message='tns:saveDiscussionOUT'/>
  </operation>
  <operation name='deleteDiscussionArticle'>
    <input message='tns:deleteDiscussionArticleIN'/>
    <output message='tns:deleteDiscussionArticleOUT'/>
  </operation>
  <operation name='getUserList'>
    <input message='tns:getUserListIN'/>
    <output message='tns:getUserListOUT'/>
  </operation>
  <operation name='saveUser'>
    <input message='tns:saveUserIN'/>
    <output message='tns:saveUserOUT'/>
  </operation>
  <operation name='uploadFile'>
    <input message='tns:uploadFileIN'/>
    <output message='tns:uploadFileOUT'/>
  </operation>
  <operation name='getRoomReadCounter'>
    <input message='tns:getRoomReadCounterIN'/>
    <output message='tns:getRoomReadCounterOUT'/>
  </operation>
  <operation name='getModerationUserList'>
    <input message='tns:getModerationUserListIN'/>
    <output message='tns:getModerationUserListOUT'/>
  </operation>
  <operation name='activateUser'>
    <input message='tns:activateUserIN'/>
    <output message='tns:activateUserOUT'/>
  </operation>

<?php
// operation port type - dynamisch
foreach ( $soap_functions_array as $key => $in_out ) {
   echo("   <operation name='".$key."'>\n");
   echo("      <input message='tns:".$key."IN'/>\n");
   echo("      <output message='tns:".$key."OUT'/>\n");
   echo("   </operation>\n");
}
echo("\n");
?>
</portType>

<binding name='CommSyBinding' type='tns:CommSyPortType'>
  <soap:binding style='rpc'
    transport='http://schemas.xmlsoap.org/soap/http'/>
  <operation name='getGuestSession'>
    <soap:operation soapAction='urn:xmethodsCommSy#getGuestSession'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getActiveRoomList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getActiveRoomList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getActiveRoomListForUser'>
    <soap:operation soapAction='urn:xmethodsCommSy#getActiveRoomListForUser'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='createUser'>
    <soap:operation soapAction='urn:xmethodsCommSy#createUser'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getCountRooms'>
    <soap:operation soapAction='urn:xmethodsCommSy#getCountRooms'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getCountUser'>
    <soap:operation soapAction='urn:xmethodsCommSy#getCountUser'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='authenticate'>
    <soap:operation soapAction='urn:xmethodsCommSy#authenticate'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='authenticateWithLogin'>
    <soap:operation soapAction='urn:xmethodsCommSy#authenticateWithLogin'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='IMS'>
    <soap:operation soapAction='urn:xmethodsCommSy#IMS'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getMaterialList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getMaterialList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getPrivateRoomMaterialList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getPrivateRoomMaterialList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getSectionListFromMaterial'>
    <soap:operation soapAction='urn:xmethodsCommSy#getSectionListFromMaterial'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getFileListFromMaterial'>
    <soap:operation soapAction='urn:xmethodsCommSy#getFileListFromMaterial'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getFileListFromItem'>
    <soap:operation soapAction='urn:xmethodsCommSy#getFileListFromItem'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getFileItem'>
    <soap:operation soapAction='urn:xmethodsCommSy#getFileItem'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='deleteFileItem'>
    <soap:operation soapAction='urn:xmethodsCommSy#deleteFileItem'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='addPrivateRoomMaterialList'>
    <soap:operation soapAction='urn:xmethodsCommSy#addPrivateRoomMaterialList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='addFileForMaterial'>
    <soap:operation soapAction='urn:xmethodsCommSy#addFileForMaterial'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='linkFileToMaterial'>
    <soap:operation soapAction='urn:xmethodsCommSy#linkFileToMaterial'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>

  <operation name='addMaterialLimit'>
    <soap:operation soapAction='urn:xmethodsCommSy#addMaterialLimit'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getBuzzwordList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getBuzzwordList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getLabelList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getLabelList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getGroupList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getGroupList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getTopicList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getTopicList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getUserInfo'>
    <soap:operation soapAction='urn:xmethodsCommSy#getUserInfo'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getRSSUrl'>
    <soap:operation soapAction='urn:xmethodsCommSy#getRSSUrl'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getRoomList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getRoomList'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getAuthenticationForWiki'>
    <soap:operation soapAction='urn:xmethodsCommSy#getAuthenticationForWiki'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='savePosForItem'>
    <soap:operation soapAction='urn:xmethodsCommSy#savePosForItem'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='savePosForLink'>
    <soap:operation soapAction='urn:xmethodsCommSy#savePosForLink'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='refreshSession'>
    <soap:operation soapAction='urn:xmethodsCommSy#refreshSession'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='logout'>
    <soap:operation soapAction='urn:xmethodsCommSy#logout'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='authenticateViaSession'>
    <soap:operation soapAction='urn:xmethodsCommSy#authenticateViaSession'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='wordpressAuthenticateViaSession'>
    <soap:operation soapAction='urn:xmethodsCommSy#wordpressAuthenticateViaSession'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='changeUserEmail'>
    <soap:operation soapAction='urn:xmethodsCommSy#changeUserEmail'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='changeUserEmailAll'>
    <soap:operation soapAction='urn:xmethodsCommSy#changeUserEmailAll'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='changeUserId'>
    <soap:operation soapAction='urn:xmethodsCommSy#changeUserId'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='setUserExternalId'>
    <soap:operation soapAction='urn:xmethodsCommSy#setUserExternalId'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='changeUserName'>
    <soap:operation soapAction='urn:xmethodsCommSy#changeUserName'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='updateLastlogin'>
    <soap:operation soapAction='urn:xmethodsCommSy#updateLastlogin'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='createMembershipBySession'>
    <soap:operation soapAction='urn:xmethodsCommSy#createMembershipBySession'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getAGBFromRoom'>
    <soap:operation soapAction='urn:xmethodsCommSy#getAGBFromRoom'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getStatistics'>
    <soap:operation soapAction='urn:xmethodsCommSy#getStatistics'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  
  <operation name='authenticateForApp'>
    <soap:operation soapAction='urn:xmethodsCommSy#authenticateForApp'/>
    <input>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </input>
    <output>
      <soap:body use='encoded' namespace='urn:xmethodsCommSy'
        encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
    </output>
  </operation>
  <operation name='getPortalRoomList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getPortalRoomList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getPortalRoomListByCountAndSearch'>
    <soap:operation soapAction='urn:xmethodsCommSy#getPortalRoomListByCountAndSearch'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getUserInformation'>
    <soap:operation soapAction='urn:xmethodsCommSy#getUserInformation'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getPortalConfig'>
    <soap:operation soapAction='urn:xmethodsCommSy#getPortalConfig'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getAuthSources'>
    <soap:operation soapAction='urn:xmethodsCommSy#getAuthSources'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getBarInformation'>
    <soap:operation soapAction='urn:xmethodsCommSy#getBarInformation'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getPortalList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getPortalList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getDatesList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getDatesList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getDateDetails'>
    <soap:operation soapAction='urn:xmethodsCommSy#getDateDetails'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveDate'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveDate'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='deleteDate'>
    <soap:operation soapAction='urn:xmethodsCommSy#deleteDate'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getMaterialsList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getMaterialsList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getMaterialDetails'>
    <soap:operation soapAction='urn:xmethodsCommSy#getMaterialDetails'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveMaterial'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveMaterial'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='deleteMaterial'>
    <soap:operation soapAction='urn:xmethodsCommSy#deleteMaterial'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveSection'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveSection'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='deleteSection'>
    <soap:operation soapAction='urn:xmethodsCommSy#deleteSection'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getDiscussionList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getDiscussionList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getDiscussionDetails'>
    <soap:operation soapAction='urn:xmethodsCommSy#getDiscussionDetails'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='deleteDiscussion'>
    <soap:operation soapAction='urn:xmethodsCommSy#deleteDiscussion'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveDiscussionArticle'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveDiscussionArticle'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='deleteDiscussionArticle'>
    <soap:operation soapAction='urn:xmethodsCommSy#deleteDiscussionArticle'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveDiscussionWithInitialArticle'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveDiscussionWithInitialArticle'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveDiscussion'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveDiscussion'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getUserList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getUserList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='saveUser'>
    <soap:operation soapAction='urn:xmethodsCommSy#saveUser'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='uploadFile'>
    <soap:operation soapAction='urn:xmethodsCommSy#uploadFile'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getRoomReadCounter'>
    <soap:operation soapAction='urn:xmethodsCommSy#getRoomReadCounter'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='getModerationUserList'>
    <soap:operation soapAction='urn:xmethodsCommSy#getModerationUserList'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>
  <operation name='activateUser'>
    <soap:operation soapAction='urn:xmethodsCommSy#activateUser'/>
      <input>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </input>
      <output>
        <soap:body use='encoded' namespace='urn:xmethodsCommSy'
          encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>
      </output>
  </operation>

<?php 
// operation binding - dynamisch
foreach ( $soap_functions_array as $key => $in_out ) {
   echo("   <operation name='".$key."'>\n");
   echo("      <soap:operation soapAction='urn:xmethodsCommSy#".$key."'/>\n");
   echo("      <input>\n");
   echo("         <soap:body use='encoded' namespace='urn:xmethodsCommSy' encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>\n");
   echo("      </input>\n");
   echo("      <output>\n");
   echo("         <soap:body use='encoded' namespace='urn:xmethodsCommSy' encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'/>\n");
   echo("      </output>\n");
   echo("   </operation>\n");
}
?>

</binding>

<service name='CommSyService'>
  <port name='CommSyPort' binding='tns:CommSyBinding'>
    <soap:address location='<?php
$soap_url = 'http://';
$soap_url .= $_SERVER['HTTP_HOST'];
$soap_url .= str_replace('soap_wsdl.php','soap.php',$_SERVER['PHP_SELF']);
echo($soap_url);
?>'/>
  </port>
</service>
</definitions>