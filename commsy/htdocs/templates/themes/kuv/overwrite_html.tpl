{extends file="layout_html.tpl"}

{block name=header}
	{block name=warning}{/block}
	{block name=room_overlay}{/block}
	{block name=external_top_menu}{/block}
    <div id="wrapper">
    
    	<div id="kuv_header">
    		<a id="kuv_logo" href="http://www.k-uv.de">
    			<img src="templates/themes/kuv/img/logo_kuv.gif" alt="KUV"/>
    		</a>
    		
    		<div id="header_right">
    			<div id="portal_controls">
    				___COMMON_WELCOME___, {$environment.username|truncate:20}
    				{if !$environment.is_guest}<a id="btn_logout" href="commsy.php?cid={$environment.cid}&mod=context&fct=logout&iid={$environment.user_item_id}" title="___LOGOUT___">Logout</a>{/if}
    			</div>
    		</div>
    		
    		<div class="clear"></div>
    	</div>
    	
    	{block name=top_menu}
    	{/block}
    	
{/block}

{block name=logout}{/block}

{block name=body_end}
	<div id="kuv_footer">© 2013 KUV - Klinikverbund der gesetzlichen Unfallversicherung</div>

	</body>
{/block}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/kuv/schema.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/kuv/kuv.css" />
{/block}