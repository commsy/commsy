{extends file="layout_html.tpl"}

  {block name=logo_area}
  <div id="logo_area">
      {if !empty($environment.logo)}
      	<img src="templates/themes/kuv/img/Logo-KUV-4c transparent.png" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
             {else}
             	<img src="{$basic.tpl_path}img/spacer.gif" style="width:1px;" alt="" />
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