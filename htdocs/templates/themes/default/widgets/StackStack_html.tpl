<div class="{literal}${baseClass}{/literal} widget_620 float-left">
	<div class="innerWidgetArea">
		<div class="widget_head">
			<div style="float:right; margin-right:10px;padding-top:5px;">
				___COMMON_SEARCH___
				<input type="text" data-dojo-attach-point="searchNode" size="10"></input><input type="button" data-dojo-attach-event="onclick:onClickSearch" value="___COMMON_SEARCH_BUTTON___" />
			</div>
			<h3 class="pop_widget_h3">___PRIVATEROOM_MY_ENTRIES_LIST_BOX___</h3>
		</div>
		<div class="StackListRestrictionWrapper">
			<h3>___COMMON_RESTRICTIONS___</h3>
			<div class="StackListRestriction">
				<span class="float-left restrictionType">___COMMON_BUZZWORDS___:</span>
				<ul class="float-left" data-dojo-attach-point="buzzwordRestrictionsNode">
				</ul>
				<div class="clear"></div>
			</div>
			<div class="StackListRestriction">
				<span class="float-left restrictionType">___COMMON_TAGS___:</span>
				<ul class="float-left" data-dojo-attach-point="tagRestrictionsNode">
				</ul>
				<div class="clear"></div>
			</div>
		</div>
		<div class="stackList" data-dojo-attach-point="itemListNode"></div>
		<div class="widget_footer">
			___COMMON_PAGE_ENTRIES___:
			<span class="cursor_pointer" data-dojo-attach-event="onclick:onClickPaging20" data-dojo-attach-point="paging20"><strong>20</strong></span> |
			<span class="cursor_pointer" data-dojo-attach-event="onclick:onClickPaging50" data-dojo-attach-point="paging50">50</span>
			
			<div class="float-right">
				___COMMON_PAGE___: <span data-dojo-attach-point="currentPageNode"></span> / <span  data-dojo-attach-point="maxPageNode"></span>
				<span class="cursor_pointer" data-dojo-attach-event="onclick:onClickPagingFirst">&lt;&lt;</span> |
				<span class="cursor_pointer" data-dojo-attach-event="onclick:onClickPagingPrev">&lt;</span> |
				<span class="cursor_pointer" data-dojo-attach-event="onclick:onClickPagingNext">&gt;</span> |
				<span class="cursor_pointer" data-dojo-attach-event="onclick:onClickPagingLast">&gt;&gt;</span>
			</div>
			
			<div class="clear"></div>
		</div>
	</div>
</div>