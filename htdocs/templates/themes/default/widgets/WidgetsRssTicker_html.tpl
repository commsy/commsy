<div class="{literal}${baseClass}{/literal} widget_500">
	<div class="innerWidgetArea">
		<div class="widget_head">
			<h3 class="pop_widget_h3">___COMMON_RSS_TICKER___</h3>
		</div>
		<div class="widget_head">
			<input type="text" size="10" data-dojo-attach-point="newRssTitleNode" />
			<input type="text" size="32" data-dojo-attach-point="newRssAdressNode" />
			
			<input type="submit" value="___PORTLET_CONFIGURATION_RSS_ADD_BUTTON___" data-dojo-attach-event="onclick:onClickNewRss" />
			
			{*
			$html .= '<input type="submit" id="portlet_rss_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'">';
			*}
		</div>
		<div class="widget_body" data-dojo-attach-point="widgetBodyNode">
			<ul data-dojo-attach-point="listNode">
			</ul>
		</div>
	</div>
</div>