{extends file="layout_html.tpl"}

	            {block name=logo_area}
	            <div id="logo_area">
	                {if !empty($environment.logo)}
	                	<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.logo}" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
	                {else}
	                	<img src="templates/themes/kuv/img/Logo-KUV-4ctransparent.png" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
	            	{/if}
	            	{if $environment.show_room_title}
	            		<span>{$environment.room_title|truncate:50:"...":true}</span>
	            	{/if}
	            </div>
				{/block}





{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/kuv/schema.css" />
{/block}