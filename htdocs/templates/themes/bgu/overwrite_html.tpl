{extends file="layout_html.tpl"}

{assign  var="username" value=$environment.username}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/bgu_styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/styles_cs.css" />
{/block}

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


{block name=header}
	{assign  var="intranet_url" value="intranet.bgu-frankfurt.de"}
	
	<!-- Start headandnav -->
    <div id="headandnav">
    
        <div id="header">
            <a id="bgu_logo" href="http://intranet.bgu-frankfurt.de/index.php?id=531" title="Startseite">Mitarbeiterportal der BGU Frankfurt</a>
            
            {if !$environment.is_guest}
            	<a href="http://{$intranet_url}/?logintype=logout" id="btn_logout" title="___LOGOUT___">
            		<img src="templates/themes/bgu/img/btn_logout.png" alt="___LOGOUT___"/>
            	</a>
            {/if}
            
            {if $environment.is_guest}
            	<a href="commsy.php?cid={$environment.pid}&mod=home&fct=index&room_id={$environment.cid}&login_redirect=1" class="tm_user" style="width:70px;" title="___MYAREA_LOGIN_BUTTON___">___MYAREA_LOGIN_BUTTON___</a>
            {/if}
            
            <div id="portal_controls">
                Angemeldet als: {$environment.username}
            </div>
            
            <div class="clear"> </div>
        </div>
        
        <div id="nav_area">
            <ul>
                <li><a href="http://{$intranet_url}/index.php?id=553"><img src="templates/themes/bgu/img/projektraeume_s.png" alt="" /><span class="tooltip">Orga-Handbuch</span></a></li>
                <li id="nav_active"><a href="http://{$intranet_url}/index.php?id=506"><img src="templates/themes/bgu/img/standards_s.png" alt="" /><span class="tooltip">Standards</span></a></li>
                <!--<li><a href=""><img src="templates/themes/bgu/img/formulare_s.png" alt="" /><span class="tooltip">Formulare</span></a></li>-->
                <li><a href="http://{$intranet_url}/index.php?id=904"><img src="templates/themes/bgu/img/mail_s.png" alt="" /><span class="tooltip">Webmailer</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=455"><img src="templates/themes/bgu/img/notfallplaene_s.png" alt="" /><span class="tooltip">Notfallpl&auml;ne</span></a></li>
            </ul>
            <ul>
                <li><a href="http://{$intranet_url}/index.php?id=457"><img src="templates/themes/bgu/img/auftragerteilen_s.png" alt="" /><span class="tooltip">Auftrag erteilen</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=458"><img src="templates/themes/bgu/img/commsy_s.png" alt="" /><span class="tooltip">Projektr&auml;ume</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=453"><img src="templates/themes/bgu/img/whoiswho_s.png" alt="" /><span class="tooltip">Who is Who</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=456"><img src="templates/themes/bgu/img/speiseplaene_s.png" alt="" /><span class="tooltip">Speiseplan</span></a></li>
            </ul>
            <ul>
                <li><a href="http://{$intranet_url}/index.php?id=460"><img src="templates/themes/bgu/img/meinbgu_s.png" alt="" /><span class="tooltip">Mein BGU</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=503"><img src="templates/themes/bgu/img/prozesse_s.png" alt="" /><span class="tooltip">Abteilungen</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=521"><img src="templates/themes/bgu/img/betriebsrat_s.png" alt="" /><span class="tooltip">Betriebsrat</span></a></li>
                <li><a href="http://{$intranet_url}/index.php?id=454"><img src="templates/themes/bgu/img/mikroskop_s.png" alt="" /><span class="tooltip">Wissenschaft</span></a></li>
            </ul>
            <div class="clear"> </div>
        </div>

    </div>
    <!-- Ende headandnav -->
    

    <div id="wrapper">
    
    {block name=top_menu}{/block}
{/block}

{block name=user_area}
{/block}

{block name=logout}
{/block}

{block name=widgets}
	<div id="search" style="float:right; width:260px;">
		
		{if $environment.module != 'search'}
	        {if $environment.module === 'home'}
	        	{assign var="defaultValue" value="___CAMPUS_SEARCH_INDEX___"}
	        {else}
	        	{assign var="defaultValue" value="___COMMON_SEARCHFIELD___"}
	        {/if}
	        {assign var="systemValue" value=true}
	    {else}
	    	{assign var="defaultValue" value=$search.parameters.search}
        {/if}
		
		<form id="indexedsearch" action="commsy.php?cid={$environment.cid}&mod=search&fct=index" method="post">
			<!-- hidden -->
			{if $environment.module != 'home' && $environment.module != 'search'}
    			<input type="hidden" name="form_data[selrubric]" value="{$environment.module}"/>
    		{elseif isset($environment.post.form_data.selrubric) && !empty($environment.post.form_data.selrubric)}
    			<input type="hidden" name="form_data[selrubric]" value="{$environment.post.form_data.selrubric}"/>
    		{/if}
			
			<div id="searchbox_main">
				<!-- search suggestions -->
	        	{if $environment.with_indexed_search}
	        		<input id="search_suggestion" type="text"/>
	        	{/if}
	        	
				<!-- main search input -->
	            <input id="search_input" class="searchbox-sword" name="form_data[keywords]" type="text" onblur="if(this.value === '') this.value = '{show var=$defaultValue}';" onfocus="if(this.value === '___CAMPUS_SEARCH_INDEX___' || this.value === '___COMMON_SEARCHFIELD___') this.value = '';" value="{show var=$defaultValue}" />
	        	
	        	<!-- submit input -->
	        	<input class="searchbox-button" type="image" value="" src="templates/themes/bgu/img/btn_search.png"/>
			</div>
            
            <div class="clear"> </div>
        </form>
        <div class="clear"> </div>
	</div>

	{if !$environment.is_guest}
		<div id="tm_icons_bar">
			<a href="#" id="tm_clipboard" title="___MYAREA_MY_COPIES___">&nbsp;</a>
			{if ($environment.count_copies > 0)}
				<span id="tm_clipboard_copies">{$environment.count_copies}</span>
         {else}
            <span id="tm_clipboard_copies"></span>
			{/if}
			<div class="clear"></div>
		</div>
	{/if}

{/block}


{block name=room_search}
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
