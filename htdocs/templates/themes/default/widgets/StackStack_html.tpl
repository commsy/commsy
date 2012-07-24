<div class="{literal}${baseClass}{/literal}">
	<h2>___PRIVATEROOM_MY_ENTRIES_LIST_BOX___</h2>
	
	<div>
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
	
	<div>
		___COMMON_SEARCH_IN_ENTRIES___
		<input data-dojo-attach-event="onkeyup:onChangeSearch"></input>
	</div>
	
	
	<ul data-dojo-attach-point="itemList">
	</ul>
</div>