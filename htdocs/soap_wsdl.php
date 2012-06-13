<?php header("Content-Type: text/xml"); ?>
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

<message name='getPortalRoomListIN'>
  <part name='session_id' type='xsd:string'/>
  <part name='portal_id' type='xsd:integer'/>
</message>
<message name='getPortalRoomListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>
<message name='getPortalListIN'>
</message>
<message name='getPortalListOUT'>
  <part name='xml_list' type='xsd:string'/>
</message>

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
  
  <operation name='getPortalRoomList'>
    <input message='tns:getPortalRoomListIN'/>
    <output message='tns:getPortalRoomListOUT'/>
  </operation>
  <operation name='getPortalList'>
    <input message='tns:getPortalListIN'/>
    <output message='tns:getPortalListOUT'/>
  </operation>
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