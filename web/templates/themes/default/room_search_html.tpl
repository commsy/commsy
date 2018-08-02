{extends file="room_list_html.tpl"}

{block name=room_navigation_rubric_title}
	___COMMON_SEARCH_RESULTS___:
	<span>{$room.search_content.count_all}</span>
{/block}

{block name=room_site_actions}
	{*<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&mode=print" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>*}
{/block}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_310"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_310"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_310"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}

		{if $list.sorting_parameters.sort_rubric == "up"}
		 	<h3 class="w_60"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_rubric_link}" id="sort_up"><strong>___COMMON_RUBRIC___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_rubric == "down"}
		 	<h3 class="w_60"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_rubric_link}" id="sort_down"><strong>___COMMON_RUBRIC___</strong></a></h3>
		{else}
		 	<h3 class="w_60"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_rubric_link}" class="sort_none">___COMMON_RUBRIC___</a></h3>
		{/if}

		{if $list.sorting_parameters.sort_modified == "up"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_up"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_modified == "down"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_down"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
		{else}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" class="sort_none">___COMMON_MODIFIED_AT___</a></h3>
		{/if}

		{if $list.sorting_parameters.sort_modificator == "up"}
		 	<h3 class="w_150"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_up"><strong>___COMMON_ENTERED_BY___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_modificator == "down"}
		 	<h3 class="w_150"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_down"><strong>___COMMON_ENTERED_BY___</strong></a></h3>
		{else}
		 	<h3 class="w_150"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" class="sort_none">___COMMON_ENTERED_BY___</a></h3>
		{/if}

		{if $search.indexed_search == true}
			{if $list.sorting_parameters.sort_relevanz == "up"}
			 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_relevanz_link}" id="sort_up"><strong>___SEARCH_RELEVANZ___</strong></a></h3>
			{elseif $list.sorting_parameters.sort_relevanz == "down"}
			 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_relevanz_link}" id="sort_down"><strong>___SEARCH_RELEVANZ___</strong></a></h3>
			{else}
		 		<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_relevanz_link}" class="sort_none">___SEARCH_RELEVANZ___</a></h3>
			{/if}
		{/if}
		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $room.search_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_search{else}even_sep_search{/if}"> <!-- Start Reihe -->
			<div class="column_280">
				<p>
					 <a href="commsy.php?cid={$environment.cid}&mod={$item.type}&fct=detail&iid={$item.item_id}&search_path=true">{$item.title}</a>
				</p>
			</div>
			<div class="column_45">
				{if $item.attachment_count > 0  && $item.activated}
					<p>
						<a href="#" class="attachment">{$item.attachment_count}</a>
					</p>
					<div class="tooltip tooltip_with_400">
						<div class="tooltip_inner tooltip_inner_with_400">
							<div class="tooltip_title">
								<div class="header">___COMMON_ATTACHED_FILES___</div>
							</div>
							<div class="scrollable">
								<div class="tooltip_content">
									<ul>
									{foreach $item.attachment_infos as $file}
										<li>
											{if !$environment.is_guest or $item.worldpublic}
												<a class="{if $file.lightbox}lightbox_{$item.iid}{/if}" href="{$file.file_url}" target="blank">
											{/if}
												{$file.file_icon} {$file.file_name}
											{if !$environment.is_guest or $item.worldpublic}
												</a>
											{/if}
											({$file.file_size} KB)
										</li>
									{/foreach}
									</ul>
								</div>
							</div>
						</div>
					</div>
				{else}
					<p>&nbsp;</p>
				{/if}
			</div>
			<div class="column_65">
				<p><img src="{$basic.tpl_path}img/netnavigation/{$item.type}.png" title="___COMMON_{$item.type|upper}_INDEX___"/></p>
			</div>
			<div class="column_90">
				<p>{$item.modification_date_print}</p>
			</div>
			<div class="column_155">
				<p>{$item.modificator}</p>
			</div>
			<div class="column_90">
				{if $search.indexed_search == true}
					<div class="progressbar searchProgressbar">
						<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
						<span class="percent hidden">{$item.relevanz}</span>
					</div>
				{/if}
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

{block name=room_list_footer}
	<div class="content_item"> <!-- Start content_item -->
		<div class="item_info">
			<div class="ii_left">
				<p>___COMMON_PAGE_ENTRIES___
					{if $list.list_entries_parameter.20 == 'disabled'}
						<strong>20</strong>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.20}">20</a>
					{/if}
					|
					{if $list.list_entries_parameter.50 == 'disabled'}
						<strong>50</strong>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.50}">50</a>
					{/if}
					|
					{if $list.list_entries_parameter.all == 'disabled'}
						<strong>___COMMON_ALL_ENTRIES___</strong>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.all}">___COMMON_ALL_ENTRIES___</a>
					{/if}
				</p>
			</div>
			<div class="ii_right">
				<div id="item_navigation">
				    {if $list.browsing_parameters.browse_start != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_start}"><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
					{/if}
				    {if $list.browsing_parameters.browse_left != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_left}"><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
					{/if}
					___COMMON_PAGE___ {$list.browsing_parameters.actual_page_number} / {$list.browsing_parameters.page_numbers}
				    {if $list.browsing_parameters.browse_right != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_right}"><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
					{/if}
				    {if $list.browsing_parameters.browse_end != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_end}"><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
					{/if}
				</div>
			</div>
			<div class="clear"> </div>
		</div>
		<div class="clear"> </div>
	</div> <!-- Ende content_item -->
{/block}


{block name=sidebar_tagbox_treefunction}
	{function name=tag_tree level=0}
		<ul>
		{foreach $nodes as $node}
			<li	id="node_{$node.item_id}"
				{if $node.children|count > 0}class="folder"{/if}
				data="url:'commsy.php?cid={$environment.cid}&mod=search&fct=index{search_params params=$search.parameters key=seltag value=$node.item_id}'">{$node.title}
			{if $node.children|count > 0}	{* recursive call *}
				{tag_tree nodes=$node.children level=$level+1}
			{/if}
		{/foreach}
		</ul>
	{/function}
{/block}

{block name=sidebar_buzzwordbox_buzzword}
	<a href="commsy.php?cid={$environment.cid}&mod=search&fct=index{search_params params=$search.parameters key=selbuzzword value=$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
{/block}