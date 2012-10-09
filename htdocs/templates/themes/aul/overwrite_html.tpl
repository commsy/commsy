{extends file="layout_html.tpl"}

{assign  var="username" value=$environment.username}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/aul/aul_styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/aul/styles_cs.css" />
{/block}




{block name=header}
{assign  var="intranet_url" value="intranet.bgu-frankfurt.de"}
<!--    <div id ="wrapper-outer">
	<img id="background-img" class="hintergrundbild" alt="" src="templates/themes/bgu/img/hintergrund.jpg"/>
    <div id ="cs_wrapper">-->
    <div id="aul_header" style="border-top: 5px solid #323232;">
    	<div class="wrapper">

            <a href="" title="Startseite"><img src="templates/themes/aul/img/mob_logo.gif" alt="Mobilit&auml;t durch Partnerschaften" id="aul_portal_logo" /></a>
            <div class="clear"></div>

            <ul id="aul_main_navigation">
                <li><a href="http://www.mobilitaetsagentur-hamburg.de" id="aul_home_normal" target="_blank">&nbsp;</a></li>
                <li><a href="http://www.mobilitaetsagentur-hamburg.de/teilnehmer" class="aul_mn_normal" target="_blank">Teilnehmer</a></li>
                <li><a href="http://www.mobilitaetsagentur-hamburg.de/akteure-der-berufsbildung" class="aul_mn_normal" target="_blank">Akteure der Berufsbildung</a></li>
                <li><a href="http://www.mobilitaetsagentur-hamburg.de/europaeische-partner" class="aul_mn_normal" target="_blank">Europ&auml;ische Partner</a></li>
                <li><a href="http://www.mobilitaetsagentur-hamburg.de/interkulturelles-training" class="aul_mn_normal" target="_blank">Interkulturelles Training</a></li>
                <li><a href="http://www.mobilitaetsagentur-hamburg.de/mobilitaet-durch-partnerschaften" class="aul_mn_normal" target="_blank">Mobilit&auml;t durch Partnerschaften</a></li>
                <li><a href="http://www.mobilitaetsagentur-hamburg.de/aktuelles" class="aul_mn_normal" target="_blank">Aktuelles</a></li>
            </ul>

            <div class="clear"></div>

        </div>
        
    </div>
    <div id="wrapper">
    {block name=top_menu}{/block}
		
{/block}
