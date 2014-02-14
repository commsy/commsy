{* include template functions *}
{include file="include/functions.tpl" inline}

{block name="site"}
	{if !isset($ajax.onlyContent)}
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

		    <!-- CSS -->
		    <link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dijit/themes/tundra/tundra.css" />
			<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/cbtree/themes/tundra/tundra.css" />
		 	<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dojox/form/resources/UploaderFileList.css" />
			<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dojox/image/resources/Lightbox.css" />
			<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dojox/widget/ColorPicker/ColorPicker.css" />

			{block name="css"}
			    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
			    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles_addon.css" />
			{/block}

			<!-- SCRIPTS -->
			<script>
				{if isset($javascript.variables_as_json) && !empty($javascript.variables_as_json)}var from_php = '{$javascript.variables_as_json}';{/if}
			</script>

			<script src="javascript/commsy8_dojo/config.js"></script>
			<script src="javascript/commsy8_dojo/libs/dojo/dojo.js"></script>
			<script src="javascript/commsy8_dojo/main.js"></script>

			<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}cs_dojo.css" />


		    <title>{$environment.room_title} - ___{$environment.module_name}___</title>

		</head>

		<body class="tundra">
    		<div class="wrapper">
			{block name=header}
				{block name=warning}{/block}
				{block name=top_menu}{/block}
		    	{block name=room_overlay}{/block}
			    <div id="wrapper">
			{/block}



		        <div id="header"> <!-- Start header -->
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
		                        	<input name="form_data[keywords]" onclick="javascript:document.getElementById('search_input').value=''" id="search_input" type="text" value="{if $environment.module != 'search'}{if $environment.module === 'home'}___CAMPUS_SEARCH_INDEX___{else}___COMMON_SEARCHFIELD___{/if}{/if}" />
		                        	{if $environment.with_indexed_search}
		                        		<input id="search_suggestion" type="text" value="" />
		                        	{/if}
		                        	<input id="search_submit" type="submit" class="search_button" value="___COMMON_GO_BUTTON2___!" />
		                        </form>
		                    </div>
		                </div>
		            </div>

		            <div class="clear"> </div>
		        </div> <!-- Ende header -->

		        {block name=layout_content}{/block}

		        <div id="footer"> <!-- Start footer -->
		            <div id="footer_left">
		                <p>CommSy 8.0</p>
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
		</body>

		</html>
	{else}
		{block name=layout_content}{/block}
	{/if}
{/block}