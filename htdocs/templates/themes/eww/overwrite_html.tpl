{extends file="layout_html.tpl"}

	        <div id="header"> <!-- Start header -->
	            {block name=logo_area}
	            <div id="logo_area">
	            	{if $environment.show_room_title}
	            		<span>{$environment.room_title|truncate:50:"...":true}</span>
	            	{/if}
	            </div>
				{/block}

{block name="css"}
	 <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/eww/schema.css" />
{/block}