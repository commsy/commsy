<div class="{literal}${baseClass}{/literal} widget_full">
	<a class="hidden" href="#" data-dojo-attach-point="createNewPortfolioNode"></a>
	
	{* tab container *}
    <div data-dojo-type="dijit.layout.TabContainer" class="minHeightPopup">
    
    	<div id="myPortfolioTabNode" data-dojo-type="dijit.layout.TabContainer" title="___CS_BAR_PORTFOLIO___" nested="true">
    		<div id="startPortfolio" data-dojo-type="dijit.layout.ContentPane" title="Start" closable="false">
    			Einleitung / Übersicht...
    		</div>
    		<div id="newPortfolioNode" data-dojo-type="dijit.layout.ContentPane" title="+"></div>
    	
    	</div>
    	
    	<div id="activatedPortfolioTabNode" data-dojo-type="dijit.layout.TabContainer" title="___CS_BAR_PORTFOLIO_ACTIVATED___" nested="true"></div>
    
    </div>
</div>