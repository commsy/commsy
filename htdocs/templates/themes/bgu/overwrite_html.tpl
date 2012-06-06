{extends file="layout_html.tpl"}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}jquery-ui-custom-theme/jquery-ui-1.8.17.custom.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/bgu_styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/styles_cs.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}ui.dynatree.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}uploadify.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}jquery-lightbox/jquery.lightbox-0.5.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}jquery.colorpicker.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}jquery.contextMenu.css" />
{/block}

{block name=header}
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
				<li><a href="#" title="Orga-Handbuch" class="smallicon1">Orga-Handbuch</a></li>
				<li><a href="#" title="Standards" class="smallicon2">Standards </a></li>
				<li><a href="#" title="Formulare" class="smallicon3">Formulare </a></li>
				<li><a href="#" title="Notfallpläne" class="smallicon4">NotfallplÃ¤ne</a></li>
			</ul>
			<ul class="smallicon-set last alignleft">
				<li><a href="#" title="Auftrag erteilen" class="smallicon5">Auftrag erteilen</a></li>
				<li><a href="#" title="Projekträume" class="smallicon7">Projekträume</a></li>
				<li><a href="#" title="Who is Who" class="smallicon6">Who is Who</a></li>
				<li><a href="#" title="Speiseplan" class="smallicon8">Speiseplan</a></li>
			</ul>
			<ul class="smallicon-set last alignleft">
				<li><a href="#" title="Mein BGU" class="smallicon9">Mein BGU</a></li>
				<li><a href="#" title="Abteilungen" class="smallicon10">Abteilungen</a></li>
				<li><a href="#" title="Betriebsrat" class="smallicon11">Betriebsrat</a></li>
				<li><a href="#" title="Wissenschaft &amp; Forschung" class="smallicon12">Wissenschaft &amp; Forschung</a></li>
			</ul>
		</div>
		{block name=top_menu}{/block}
{/block}
