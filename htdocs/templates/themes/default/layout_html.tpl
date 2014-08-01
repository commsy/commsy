{* include template functions *}
{include file="include/functions.tpl" inline}

{block name="site"}
	<?xml version="1.0" encoding="utf-8"?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">

	<head>
	    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	    <meta name="robots" content="index, follow" />
	    <meta name="revisit-after" content="7 days" />
	    <meta name="language" content="German, de, deutsch" />
	    <meta name="author" content="" />
	    <meta name="page-topic" content="" />
	    <meta name="keywords" content="" />
	    <meta name="description" content="" />
	    <meta name="copyright" content="" />
	    
	    {block name="favicon"}
	        <link rel="icon" href="commsy8.ico" type="image/x-icon">
            <link rel="shortcut icon" href="commsy8.ico" type="image/x-icon">
	    {/block}

	    <!-- CSS -->
	    <link rel="stylesheet" type="text/css" media="screen" href="js/src/dijit/themes/tundra/tundra.css" />
		 <link rel="stylesheet" type="text/css" media="screen" href="js/src/cbtree/themes/tundra/tundra.css" />
	 	 <link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/form/resources/UploaderFileList.css" />
		 <link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/image/resources/Lightbox.css" />
		 <link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/widget/ColorPicker/ColorPicker.css" />
		 <link rel="stylesheet" type="text/css" media="screen" href="js/src/dojox/calendar/themes/tundra/Calendar.css" />
       <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}cs_dojo.css" />

		{block name="css"}
		    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
		    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles_addon.css" />
		{/block}

	    <link rel="stylesheet" type="text/css" media="print" href="{$basic.tpl_path}styles.css" />

	    <link rel="stylesheet" type="text/css" media="screen" href="js/3rdParty/projekktor-1.3.09/themes/maccaco/projekktor.style.css" />

		{block name="js"}
			<!-- SCRIPTS -->

			<script type="text/javascript" src="js/3rdParty/projekktor-1.3.09/jquery-1.9.1.min.js"></script>
			<!--<script type="text/javascript" src="js/3rdParty/projekktor-1.3.09/projekktor-1.3.09.min.js"></script>-->
			
			<script type="text/javascript" src="js/3rdParty/projekktor-1.3.09/projekktor-universal.min.js"></script>



			{literal}
			<script type="text/javascript">

				function getCookie(cname) {
				    var name = cname + "=";
				    var ca = document.cookie.split(';');
				    for(var i=0; i<ca.length; i++) {
				        var c = ca[i];
				        while (c.charAt(0)==' ') c = c.substring(1);
				        if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
				    }
				    return "";
				}

				var clickAlternativeMethod = function(player) {
		        	// get player height width from projekktor
		        	var height = $('.commsyPlayer .projekktor').height();
		        	var width = $('.commsyPlayer .projekktor').width();

		        	$('.commsyPlayer .projekktor').replaceWith('Alte Abspielmethode');
		        	console.log(player);
		        	console.log(player.getItem());

		        	var videoFile = player.getItem().file[0];

		        	var OSName="Unknown OS";
					if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
					if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
					if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
					if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

					// try to get file extension
					var regEx = /\/.*\.(.{3,})\?/;
					var match = videoFile.src.match(regEx);

		   			var videoUrl = videoFile.src;

		   			var SID = "&SID=" + getCookie("SID");
					
					var content = '';

					if(match[1] == "mov" || match[1] == "mpeg" || match[1] == "mp4" || match[1] == "wav"){

						content += '<object width="' + width + '" heigth="'+ height +'" codebase="http://www.apple.com/qtactivex/qtplugin.cab" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" type="video/quicktime"><param value="' + videoUrl + SID + '" name="src">';
						content += '<param value="true" name="controller">';
						content += '<param value="high" name="quality">';
						content += '<param value="tofit" name="scale">';
						content += '<param value="#000000" name="bgcolor">';
						content += '<param value="opaque" name="wmode">';
						content += '<param value="true" name="autoplay">';
						content += '<param value="false" name="loop">';
						content += '<param value="true" name="devicefont">';
						content += '<param value="mov" name="class">';
						content += '<embed width="' + width + '" height="' + height + '" pluginspage="http://www.apple.com/quicktime/download/" class="mov" type="video/quicktime" devicefont="true" loop="false" wmode="opaque" bgcolor="#000000" controller="true" scale="tofit" quality="high" src="' + videoUrl + SID + '" controller="true">';
						content += '</object>';

					} else if(match[1] == "avi" || match[1] == "wmv" || match[1] == "wma") {
						content += '<object height="' + height + '" width="' + width + '" type="application/x-oleobject" standby="Loading Microsoft Windows Media Player components..." codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" id="MediaPlayer18">';
						content += '<param value="' + videoUrl + SID + '" name="fileName">';
						content += '<param value="true" name="autoStart">';
						content += '<param value="true" name="showControls">';
						content += '<param value="true" name="showStatusBar">';
						content += '<param value="opaque" name="wmode">';
						content += '<embed width="' + width + '" height="' + height + '" showstatusbar="1" showcontrols="1" autostart="true" wmode="opaque" name="MediaPlayer18" src="' + videoUrl + SID +'" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" type="application/x-mplayer2">';
						content += '</object>';
					}

					

					$('.commsyPlayer').append(content);
		        	//alert("Test");
		        	// Try to find out which embedding is useful
		        	 

		        	
		        	
		        }

				$( document ).ready(function() {

					//projekktor
					projekktor('video', {
				        poster: 'http://www.projekktor.com/wp-content/manual/intro.png',
				        playerFlashMP4: 'js/3rdParty/projekktor-1.3.09/swf/StrobeMediaPlayback/StrobeMediaPlayback.swf', // paths to StrobeMediaPlayback.swf on your serwer
                    	playerFlashMP3: 'js/3rdParty/projekktor-1.3.09/swf/StrobeMediaPlayback/StrobeMediaPlayback.swf',
				        // playerFlashMP4: 'js/3rdParty/projekktor-1.3.09/swf/jarisplayer/jarisplayer.swf',
				        // playerFlashMP3: 'js/3rdParty/projekktor-1.3.09/swf/jarisplayer/jarisplayer.swf',        
				        useYTIframeAPI: false,
				        platforms: ['browser', 'android', 'ios', 'native', 'vlc', 'flash'],
				        ratio: 16/9,
				        controls: true,
				        //playlist: [{0:{src:'js/3rdParty/projekktor-1.3.09/media/TestVideo.avi', type:"video/x-msvideo"}}] 
				    }, function(player) {
				        player.addListener('displayReady', function(val, ref) {
				            var model = ref.getModel();            
				            $('#platform').html( (model=='VIDEOVLC') ? "Yeah!!1 VLC Web Plugin detected and in use." : "DÂ´oh... model " + model + " in use right now.");            
				        });
				        player.addListener('state', function(val, ref) {

				        	if($('.alt_play_method')){
				        		$('.alt_play_method').click(function(){clickAlternativeMethod(player);});
				        	}

				            //$('.commsyPlayer').append(val);            
				        });
				        var count=0;
				        $.each($p.mmap, function() {
				            count++;
				            $('<p/>').html(this.type + "(." + this.ext + ")").appendTo('#uglylist');
				        });
				        if (count>24) {
				            $('#platform').html("Seems youÂ´ve da VLC Web Plugin installed, dude.");
				        }
				    }); 


				});
			</script>
			{/literal}

            {if $environment.c_js_mode === "layer"}
                <script src="js/src/layerConfig.js{if isset($javascript.version) && !empty($javascript.version)}?{$javascript.version}{/if}"></script>
            {else}
                <script src="js/src/sourceConfig.js"></script>
            {/if}
	
			<script>
				{if isset($javascript.variables_as_json) && !empty($javascript.variables_as_json)}var from_php = '{$javascript.variables_as_json}';{/if}
				{if isset($javascript.locale) && !empty($javascript.locale)}dojoConfig.locale = '{$javascript.locale}';{/if}
				{if isset($javascript.version) && !empty($javascript.version)}dojoConfig.cacheBust = '{$javascript.version}';{/if}
			</script>
			<script src="js/3rdParty/ckeditor_4.4.3/ckeditor.js"></script>
	
            {if $environment.c_js_mode === "layer"}
                <script src="js/build/release/dojo/dojo.js{if isset($javascript.version) && !empty($javascript.version)}?{$javascript.version}{/if}"></script>
                <script>
                    require(["layer/commsy", "commsy/main"]);
                </script>
    
            {else}
                <script src="js/src/dojo/dojo.js{if isset($javascript.version) && !empty($javascript.version)}?{$javascript.version}{/if}"></script>
                <script>
                    require(["commsy/main"]);
                </script>
            {/if}
	
	        <script type="text/javascript" src="javascript/swfobject.js"></script>
	        {if $environment.c_jsmath_enable}
	            <script type="text/javascript"> jsMath = {literal}{Controls: {cookie: {scale: 120}}}{/literal} </script>
	            <script type="text/javascript" src="{$environment.c_jsmath_url}/jsMath.js"></script>
	        {/if}
        {/block}

	    <title>{$environment.room_title} - ___{$environment.module_name}___</title>

	</head>
{block name=body_begin}
	<body class="tundra">
{/block}
		{block name=header}
			{block name=warning}{/block}
			{block name=top_menu}{/block}
	    	{block name=room_overlay}{/block}
			{block name=external_top_menu}{/block}
		    <div id="wrapper">
		{/block}

	        <div id="header"> <!-- Start header -->
	            {block name=logo_area}
	            <div id="logo_area">
	                {if !empty($environment.logo)}
	                	<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.logo}" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
	                {else}
	                	<img src="{$basic.tpl_path}img/spacer.gif" style="width:1px;" alt="" />
	            	{/if}
	            	{if $environment.show_room_title}
	            		<span>{$environment.room_title|truncate:50:"...":true}</span>
	            	{/if}
	            </div>
				{/block}

	            {block name=room_search}
	            <div id="search_area">
	                <div id="search_navigation">
	                    {*<span class="sa_sep"><a href="" id="sa_active">___CAMPUS_SEARCH_ONLY_THIS_ROOM___</a></span>*}
	                    {*<span class="sa_sep"><a href="">alle meine R&auml;ume</a></span>*}
	                    {*<span id="sa_options"><a href=""><img src="{$basic.tpl_path}img/sa_dropdown.gif" alt="O" /></a></span>*}

	                    {*<div class="clear"> </div>*}

	                    <div id="commsy_search">
	                    	<form action="commsy.php?cid={$environment.cid}&mod=search&fct=index" method="post">
	                    		{if $environment.module != 'home' && $environment.module != 'search'}
	                    			<input type="hidden" name="form_data[selrubric]" value="{$environment.module}"/>
	                    		{elseif isset($environment.post.form_data.selrubric) && !empty($environment.post.form_data.selrubric)}
	                    			<input type="hidden" name="form_data[selrubric]" value="{$environment.post.form_data.selrubric}"/>
	                    		{/if}
	                        	<input name="form_data[keywords]"{if $environment.with_indexed_search != true} onclick="javascript:document.getElementById('search_input').value=''"{/if} id="search_input" type="text" value="{if $environment.module != 'search'}{if $environment.module === 'home'}___CAMPUS_SEARCH_INDEX___{else}___COMMON_SEARCHFIELD___{/if}{else}{show var=$search.parameters.search}{/if}" />
	                        	{if $environment.with_indexed_search}
	                        		<input id="search_suggestion" type="text" value="" />
	                        	{/if}
	                        	<input id="search_submit" type="submit" class="search_button" value="___COMMON_GO_BUTTON2___!" />
	                        </form>
	                    </div>
	                </div>
	            </div>
	            {/block}

	            <div class="clear"> </div>
	        </div> <!-- Ende header -->

	        {block name=layout_content}{/block}

	        <div id="footer"> <!-- Start footer -->
	            <div id="footer_left">
	                <p>CommSy
	                {if !empty($environment.commsy_version)}
	                   {$environment.commsy_version}
	                {else}
	                   8.0
	                {/if}
	                {if !empty($environment.commsy_version_addon)}
	                   - {$environment.commsy_version_addon}
	                {/if}
	                </p>
	            </div>

	            <div id="footer_right">
	                <p>
	                    <span>{$smarty.now|date_format:"%d."} {$translation.act_month_long} {$smarty.now|date_format:"%Y, %H:%M"}</span>
                    	{if !empty($environment.show_moderator_link)}
	                 	   <a href="#" class="open_popup" data-custom="module: 'mailtomod'">___MAIL_TO_MODERATOR_HEADLINE___</a>
                    	{/if}
                    	{if !empty($environment.show_service_link)}
	                    	<div style="padding-left:10px;">{$environment.service_link}</div>
                    	{/if}
	                </p>
	            </div>

	            <div class="clear"> </div>
	        </div> <!-- Ende footer -->


	        <!-- hier Google Adwords -->


	    </div> <!-- Ende wrapper -->
	    <div id="popup_uploader"></div>
{block name=body_end}
      {if !empty($basic.html_before_body_ends)}
         {$basic.html_before_body_ends}
      {/if}
	</body>
{/block}

	</html>
{/block}