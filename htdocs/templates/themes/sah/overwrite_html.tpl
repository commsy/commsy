{extends file="layout_html.tpl"}

{block name="css"}
	 <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/sah/schema.css" />
{/block}

{block name=logo_area}
     <div id="logo_area">
         {if !empty($environment.logo)}
         	<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.logo}" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
         {else}
         	<img src="templates/themes/sah/img/logo.jpg" alt="" />
     	{/if}
     	{if $environment.show_room_title}
     		<span>{$environment.room_title|truncate:50:"...":true}</span>
     	{/if}
     </div>
{/block}
