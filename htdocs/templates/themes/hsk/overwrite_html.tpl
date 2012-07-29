{extends file="layout_html.tpl"}

{block name="css"}
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/hsk/styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/hsk/styles_cs.css" />
 {/block}



			{block name=header}
				{block name=warning}{/block}
				{block name=top_menu}{/block}
		    	{block name=room_overlay}{/block}

         <div id="hsk_header">
            <a id="hsk_logo" href="" title="Intranet Startseite"><img src="templates/themes/hsk/images/hsk_logo.gif" alt="HSK" /></a>
            <a id="rhoen_logo" href="http://www.rhoen-klinikum-ag.com" target="_blank" title="Rh&ouml;n-Klinikum"><img src="templates/themes/hsk/images/rhoen_logo.gif" alt="Rh&ouml;n-Klinikum" /></a>

            <div class="clear"> </div>
        </div>

        <div id="hsk_main_navigation">


		{assign var="typo_link" value="http://hsk-intranet.effective-webwork.de"}
			<ul>
				<li><a id="mn_home" target="_self" href="{$typo_link}/index.php?id=2214"><span id="button_home">Home</span></a></li>
				<li><a id="mn_emergency" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2246"><span id="button_emergency">Notfallpl&auml;ne</span></a></li>
				<li><a class="mn_item" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2223">Aktuelles</a>
					<ul>
						<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2218">Neuigkeiten</a></li>
						<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2142">Veranstaltungen</a></li>
						<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2220">Foren</a></li></ul></li>
						<li><a class="mn_item" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2224">Kontakte</a>
							<ul>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2247">Telefonliste</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2229">Klinik-&Uuml;bersicht</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2230">Kooperationspartner</a></li>
							</ul>
						</li>
						<li><a class="mn_item" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2225">Organisation/Prozesse</a>
							<ul>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2258">Notfall</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2231">Patientenprozesse</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2232">F&uuml;hrungs- und Mitarbeiterprozesse</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2233">Unterst&uuml;tzende Prozesse</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2234">Prozesslandkarte</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2253">Dokumentenablage</a></li>
							</ul>
						</li>
						<li><a class="mn_item" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2226">Angebote</a>
							<ul>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2237">Auftrag erteilen</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2109">Speiseplan</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2168">Kinderbetreuung</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2240">Online-Bibliothek</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2241">Sportangebote</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2155">HSK-Service</a></li>
							</ul>
						</li>
						<li><a class="mn_item" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2227">Karriere</a>
							<ul>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2111">Interne Stellenangebote</a></li>
								<li><a onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2041">Fortbildungsangebote</a></li>
							</ul>
						</li>
						<li><a class="mn_item" onfocus="blurLink(this);" target="_self" href="{$typo_link}/index.php?id=2108">Betriebsrat</a></li>
					</ul>

            <div class="clear"> </div>
        </div>

    </div>
    <!-- 1. wrapper - Ende -->
 			    <div id="wrapper">
			{/block}
