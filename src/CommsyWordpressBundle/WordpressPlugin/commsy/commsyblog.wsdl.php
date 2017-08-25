<?php header("Content-Type: text/xml"); ?><<?php echo '?'?>xml version="1.0"<?php echo '?'?>>
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/" xmlns:tns="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" name="commsy_blog" targetNamespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>">
  <types>
    <xsd:schema targetNamespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
  </types>
  <portType name="commsy_blogPort">
  	<operation name="existsBlog">
      <documentation>existsBlog</documentation>
      <input message="tns:existsBlogIn"/>
      <output message="tns:existsBlogOut"/>
    </operation>
    <operation name="createBlog">
      <documentation>@param array $user</documentation>
      <input message="tns:createBlogIn"/>
      <output message="tns:createBlogOut"/>
    </operation>
    <operation name="deleteBlog">
      <documentation>deleteBlog</documentation>
      <input message="tns:deleteBlogIn"/>
      <output message="tns:deleteBlogOut"/>
    </operation>
    <operation name="insertPost">
      <documentation>@param array $post Data to insert</documentation>
      <input message="tns:insertPostIn"/>
      <output message="tns:insertPostOut"/>
    </operation>
    <operation name="insertFile">
      <documentation>@param array $file</documentation>
      <input message="tns:insertFileIn"/>
      <output message="tns:insertFileOut"/>
    </operation>
    <operation name="getPostExists">
      <documentation>@param int $postId</documentation>
      <input message="tns:getPostExistsIn"/>
      <output message="tns:getPostExistsOut"/>
    </operation>
    <operation name="getSkins">
      <documentation>@return array</documentation>
      <input message="tns:getSkinsIn"/>
      <output message="tns:getSkinsOut"/>
    </operation>
    <operation name="insertOption">
      <documentation>@param string $name</documentation>
      <input message="tns:insertOptionIn"/>
    </operation>
    <operation name="updateOption">
      <documentation>@param string $name</documentation>
      <input message="tns:updateOptionIn"/>
    </operation>
    <operation name="getOption">
      <documentation>@param string $name</documentation>
      <input message="tns:getOptionIn"/>
      <output message="tns:getOptionOut"/>
    </operation>
    <operation name="getUserRole">
      <documentation>@param int $blog_id</documentation>
      <input message="tns:getUserRoleIn"/>
      <output message="tns:getUserRoleOut"/>
    </operation>
  </portType>
  <binding name="commsy_blogBinding" type="tns:commsy_blogPort">
    <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
    <operation name="existsBlog">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#existsBlog"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="createBlog">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#createBlog"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="deleteBlog">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#deleteBlog"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="insertPost">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#insertPost"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="insertFile">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#insertFile"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="getPostExists">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#getPostExists"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="getSkins">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#getSkins"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="insertOption">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#insertOption"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="updateOption">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#updateOption"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="getOption">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#getOption"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
    <operation name="getUserRole">
      <soap:operation soapAction="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>#getUserRole"/>
      <input>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </input>
      <output>
        <soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
      </output>
    </operation>
  </binding>
  <service name="commsy_blogService">
    <port name="commsy_blogPort" binding="tns:commsy_blogBinding">
      <soap:address location="http://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"] ?>"/>
    </port>
  </service>
  <message name="existsBlogIn">
    <part name='session_id' type='xsd:string'/>
    <part name="blogId" type="xsd:anyType"/>
  </message>
  <message name="existsBlogOut">
    <part name="retour" type="xsd:boolean"/>
  </message>
  <message name="createBlogIn">
    <part name='session_id' type='xsd:string'/>
    <part name="user" type="soap-enc:Array"/>
    <part name="blog" type="soap-enc:Array"/>
  </message>
  <message name="createBlogOut">
    <part name="return" type="soap-enc:Array"/>
  </message>
  <message name="deleteBlogIn">
    <part name='session_id' type='xsd:string'/>
    <part name="blogId" type="xsd:anyType"/>
  </message>
  <message name="deleteBlogOut">
    <part name="retour" type="xsd:boolean"/>
  </message>
  <message name="insertPostIn">
    <part name='session_id' type='xsd:string'/>
    <part name="post" type="soap-enc:Array"/>
    <part name="user" type="soap-enc:Array"/>
    <part name="blogId" type="xsd:int"/>
    <part name="category" type="xsd:string"/>
    <part name="postId" type="xsd:string"/>
  </message>
  <message name="insertPostOut">
    <part name="return" type="xsd:int"/>
  </message>
  <message name="insertFileIn">
    <part name='session_id' type='xsd:string'/>
    <part name="file" type="soap-enc:Array"/>
    <part name="blogId" type="xsd:int"/>
  </message>
  <message name="insertFileOut">
    <part name="return" type="xsd:string"/>
  </message>
  <message name="getPostExistsIn">
    <part name='session_id' type='xsd:string'/>
    <part name="postId" type="xsd:int"/>
    <part name="blogId" type="xsd:int"/>
  </message>
  <message name="getPostExistsOut">
    <part name="return" type="xsd:boolean"/>
  </message>
  <message name="getSkinsIn">
    <part name='session_id' type='xsd:string'/>
  </message>
  <message name="getSkinsOut">
    <part name="return" type="soap-enc:Array"/>
  </message>
  <message name="insertOptionIn">
    <part name='session_id' type='xsd:string'/>
    <part name="name" type="xsd:string"/>
    <part name="value" type="xsd:string"/>
    <part name="blogId" type="xsd:int"/>
  </message>
  <message name="updateOptionIn">
    <part name='session_id' type='xsd:string'/>
    <part name="name" type="xsd:string"/>
    <part name="value" type="xsd:string"/>
    <part name="blogId" type="xsd:int"/>
  </message>
  <message name="getOptionIn">
    <part name='session_id' type='xsd:string'/>
    <part name="name" type="xsd:string"/>
    <part name="blogId" type="xsd:int"/>
  </message>
  <message name="getOptionOut">
    <part name="return" type="xsd:string"/>
  </message>
  <message name="getUserRoleIn">
    <part name='session_id' type='xsd:string'/>
    <part name="blog_id" type="xsd:int"/>
    <part name="user_id" type="xsd:string"/>
  </message>
  <message name="getUserRoleOut">
    <part name="retour" type="xsd:string"/>
  </message>
</definitions>