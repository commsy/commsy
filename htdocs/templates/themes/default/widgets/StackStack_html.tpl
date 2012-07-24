<div class="{literal}${baseClass}{/literal} ">
							<div id="innerWidgetArea">
			                    <div class="crt_content_500" >
	<div class="widget_head">
		<div style="float:right; margin-right:10px;padding-top:5px;">
			___COMMON_SEARCH___
			<input data-dojo-attach-event="onkeyup:onChangeSearch"></input>
		</div>
		<h3 class="pop_widget_h3"> ___PRIVATEROOM_MY_ENTRIES_LIST_BOX___</h3>
	</div>
	<div class="widget_body">
	{*	<div>
			___COMMON_PAGE_ENTRIES___:
			<span data-dojo-attach-event="onclick:onClickPaging20" data-dojo-attach-point="paging20"><strong>20</strong></span> |
			<span data-dojo-attach-event="onclick:onClickPaging50" data-dojo-attach-point="paging50">50</span>
		</div>

		<div>
			<div>___COMMON_PAGE___: <span data-dojo-attach-point="currentPageNode"></span> / <span  data-dojo-attach-point="maxPageNode"></span></div>
			<span data-dojo-attach-event="onclick:onClickPagingFirst">&lt;&lt;</span>
			<span data-dojo-attach-event="onclick:onClickPagingPrev">&lt;</span>
			<span data-dojo-attach-event="onclick:onClickPagingNext">&gt;</span>
			<span data-dojo-attach-event="onclick:onClickPagingLast">&gt;&gt;</span>
		</div>
*}


		<ul data-dojo-attach-point="itemList">
		</ul>
	</div>
	</div>
</div>