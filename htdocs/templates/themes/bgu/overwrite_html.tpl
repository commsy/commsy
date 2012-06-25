{extends file="layout_html.tpl"}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/bgu_styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/bgu/styles_cs.css" />
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
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=553" title="Orga-Handbuch" class="smallicon1">Orga-Handbuch</a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=506" title="Standards" class="smallicon2">Standards </a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=507" title="Formulare" class="smallicon3">Formulare </a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=455" title="Notfallpläne" class="smallicon4">NotfallplÃ¤ne</a></li>
			</ul>
			<ul class="smallicon-set last alignleft">
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=457" title="Auftrag erteilen" class="smallicon5">Auftrag erteilen</a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=458" title="Projekträume" class="smallicon7">Projekträume</a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=453" title="Who is Who" class="smallicon6">Who is Who</a></li>
				<li><a href="http://172.17.1.25/ctsplan.php?fma=2" title="Speiseplan" class="smallicon8">Speiseplan</a></li>
			</ul>
			<ul class="smallicon-set last alignleft">
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=460" title="Mein BGU" class="smallicon9">Mein BGU</a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=503" title="Abteilungen" class="smallicon10">Abteilungen</a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=521" title="Betriebsrat" class="smallicon11">Betriebsrat</a></li>
				<li><a href="http://bgu-intranet.effective-webwork.de/index.php?id=454" title="Wissenschaft &amp; Forschung" class="smallicon12">Wissenschaft &amp; Forschung</a></li>
			</ul>
		</div>
		{block name=top_menu}{/block}
{/block}
