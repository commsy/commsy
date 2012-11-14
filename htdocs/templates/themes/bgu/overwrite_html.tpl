{extends file="layout_html.tpl"}

{assign  var="username" value=$environment.username}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/bgu_styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/styles_cs.css" />
{/block}

{block name=logo_area}
<div id="logged_in">Angemeldet als: <a href="#" id="tm_user">{$environment.username}</a></div>
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


{block name=header}
{assign  var="intranet_url" value="intranet.bgu-frankfurt.de"}
<!--    <div id ="wrapper-outer">
	<img id="background-img" class="hintergrundbild" alt="" src="templates/themes/bgu/img/hintergrund.jpg"/>
    <div id ="cs_wrapper">-->
    <div id="wrapper">
		<!-- start header -->
		<div id="cs_header" class="cf">
		</div>

		<!-- end header -->
		<div  class="cf suppage-banner">
			<ul class="smallicon-set alignleft">
				<li><a href="http://{$intranet_url}/index.php?id=553" title="Orga-Handbuch" class="smallicon1">Orga-Handbuch</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=506" title="Standards" class="smallicon2">Standards </a></li>
				<li><a href="http://{$intranet_url}/index.php?id=507" title="Formulare" class="smallicon3">Formulare </a></li>
				<li><a href="http://{$intranet_url}/index.php?id=455" title="Notfallpläne" class="smallicon4">Sicherheit</a></li>
			</ul>
			<ul class="smallicon-set last alignleft">
				<li><a href="http://{$intranet_url}/index.php?id=457" title="Auftrag erteilen" class="smallicon5">Auftrag erteilen</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=458" title="Projekträume" class="smallicon7">Projekträume</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=453" title="Who is Who" class="smallicon6">Who is Who</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=456" title="Speiseplan" class="smallicon8">Speiseplan</a></li>
			</ul>
			<ul class="smallicon-set last alignleft">
				<li><a href="http://{$intranet_url}/index.php?id=460" title="Mein BGU" class="smallicon9">Mein BGU</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=503" title="Abteilungen" class="smallicon10">Abteilungen</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=521" title="Betriebsrat" class="smallicon11">Betriebsrat</a></li>
				<li><a href="http://{$intranet_url}/index.php?id=454" title="Wissenschaft &amp; Forschung" class="smallicon12">Wissenschaft &amp; Forschung</a></li>
			</ul>
		</div>
		{block name=top_menu}{/block}
{/block}

{block name=user_area}
{/block}

{block name=logout}
<div id="tm_icons_bar">
	{if !$environment.is_guest}
		<a href="http://{$intranet_url}/?logintype=logout" title="___LOGOUT___">
			<img alt="" src="templates/themes/bgu/img/logout_small.png"/>
		</a>
	{/if}
	{if $environment.is_guest}<a href="commsy.php?cid={$environment.pid}&mod=home&fct=index&room_id={$environment.cid}&login_redirect=1" class="tm_user" style="width:70px;" title="___MYAREA_LOGIN_BUTTON___">___MYAREA_LOGIN_BUTTON___</a>{/if}
	<div class="clear"></div>
</div>
{/block}

{block name=widgets}

<div id="search" style="float:right; width:250px;">
    {*<span class="sa_sep"><a href="" id="sa_active">___CAMPUS_SEARCH_ONLY_THIS_ROOM___</a></span>*}
    {*<span class="sa_sep"><a href="">alle meine R&auml;ume</a></span>*}
    {*<span id="sa_options"><a href=""><img src="{$basic.tpl_path}img/sa_dropdown.gif" alt="O" /></a></span>*}

    <div id="commsy_search" style="width:212px; height:21px;">
    	<form action="commsy.php?cid={$environment.cid}&mod=search&fct=index" method="post" style="cursor: text;" onclick="document.getElementById('search_input').focus();">
    		{if $environment.module != 'home' && $environment.module != 'search'}
    			<input type="hidden" name="form_data[selrubric]" value="{$environment.module}"/>
    		{elseif isset($environment.post.form_data.selrubric) && !empty($environment.post.form_data.selrubric)}
    			<input type="hidden" name="form_data[selrubric]" value="{$environment.post.form_data.selrubric}"/>
    		{/if}
        	<input style="padding:2px 5px; width:200px; font-size:10pt; font-weight: normal; border: 1px solid #E5E9EB; z-index: 5;" name="form_data[keywords]" onfocus="if (this.value=='{if $environment.module != 'search'}{if $environment.module === 'home'}___CAMPUS_SEARCH_INDEX___{else}___COMMON_SEARCHFIELD___{/if}{else}{show var=$search.parameters.search}{/if}') this.value='';" id="search_input" type="text" value="{if $environment.module != 'search'}{if $environment.module === 'home'}___CAMPUS_SEARCH_INDEX___{else}___COMMON_SEARCHFIELD___{/if}{else}{show var=$search.parameters.search}{/if}" />

        	{if $environment.with_indexed_search}
        		<input disabled="disabled" style="padding:2px 5px; width:200px; font-size:10pt; font-weight: normal; border: 1px solid #E5E9EB; z-index:1;" id="search_suggestion" type="text" value="" />
        	{/if}
        	<input id="search_submit" type="submit" class="search_button" value="" />
        </form>
    </div>
</div>

	{if !$environment.is_guest}
		<div id="tm_icons_bar">
			<a href="#" id="tm_clipboard" title="___MYAREA_MY_COPIES___">&nbsp;</a>
			{if ($environment.count_copies > 0)}
				<span id="tm_clipboard_copies">{$environment.count_copies}</span>
			{/if}
			<div class="clear"></div>
		</div>
	{/if}


<!--		<div class="csc-default" id="c3448">
      <form method="get" name="commsy_searchform" action="index.php">
	    <input type="hidden" name="id" value="549">
		<input type="hidden" name="cid" value="176">
		<input type="hidden" name="mod" value="search">
		<input type="hidden" name="fct" value="index">

        <div id="bgu_i_search">
          <input type="text" id="bgu_i_searchinput" onfocus="if(this.value === 'Suche im Orga-Handbuch') this.value = '';" onblur="if(this.value === '') this.value = 'Suche im Orga-Handbuch';" value="Suche im Orga-Handbuch" size="20" name="search">
          <input type="image" alt="Suchen" src="http://bgu-commsy.effective-webwork.de/images/commsyicons/22x22/search.png">
        </div>
      </form>
    </div>
	-->


{/block}


{block name=room_search}
<!--	<div id="search_area">
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
	            		<input disabled="disabled" style="z-index:1" id="search_suggestion" type="text" value="" />
	            	{/if}
	            	<input id="search_submit" type="submit" class="search_button" value="___COMMON_GO_BUTTON2___!" />
	            </form>
	        </div>
	    </div>
	</div>
-->
{/block}

			{block name=breadcrumb}
			<a href="http://{$intranet_url}/index.php?id=531" id="home_link" title="Home">
				Home
			</a>
			<div id="tm_breadcrumb">
				<a href="#" id="tm_bread_crumb">___COMMON_GO_BUTTON___: {$room.room_information.room_name}</a>
			</div>
			{if $environment.is_moderator}
				<div id="tm_icons_left_bar">
					<a href="#" id="tm_settings" title="___COMMON_CONFIGURATION___">&nbsp;</a>
					{if ($environment.count_new_accounts >0)}
						<span id="tm_settings_count_new_accounts">{$environment.count_new_accounts}</span>
					{/if}
					<div class="clear"></div>
				</div>
			{/if}
			{/block}


