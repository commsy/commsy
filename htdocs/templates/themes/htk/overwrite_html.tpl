{extends file="layout_html.tpl"}

	            {block name=logo_area}
	            <div id="logo_area">
	                {if !empty($environment.logo)}
	                	<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.logo}" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
	                {else}
	                	<img src="templates/themes/htk/img/htk_logo.gif" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
	            	{/if}
	            	{if $environment.show_room_title}
	            		<span>{$environment.room_title|truncate:50:"...":true}</span>
	            	{/if}
	            </div>
				{/block}



{block name=external_top_menu}
{assign  var="intranet_url" value="intranet.bgu-frankfurt.de"}

    <div id="external_header">
        <div class="external_wrapper">

            <div class="clear"> </div>
        </div>
    </div>

    <div id="external_main_navigation">
        <div class="external_wrapper">
            <ul><li><a href="" target="_self">Mitteilung der Leitung</a></li>
           		<li><a href="" target="_self">Betriebsrat aktuell</a></li>
            	<li><a {if ($environment.cid == "1643009" and $environment.params|strstr:"seltag=1643015")} {elseif $environment.cid =="1643009" } id="mn_active" {/if} href="commsy.php?cid=1643009" target="_self">Dokumentenablage</a></li>
           		<li><a href="" target="_self">Fachabteilungen</a></li>
             	<li><a {if ($environment.cid !="1643009")} id="mn_active" {/if} href="commsy.php?cid=1485855" target="_self">Projekträume</a></li>
            	<li><a {if ($environment.cid == "1643009" and $environment.params|strstr:"seltag=1643015")} id="mn_active" {/if} href="commsy.php?cid=1643009&mod=material&fct=index&seltag=1643015" target="_self">Dienstanweisungen</a></li>
            	</ul>

            <div class="clear"> </div>
        </div>
    </div>


{/block}


{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/htk/schema.css" />
{/block}