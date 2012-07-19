{extends file="room_list_html.tpl"}

{block name=room_navigation_rubric_title}
	___COMMON_SEARCH_RESULTS___:
	<span>{$room.search_content.count_all}</span>
{/block}

{block name=room_site_actions}
	<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&mode=print" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>
{/block}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_295"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_295"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_295"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
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
		
		{if $search.index_search == true}
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
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_announcement{else}even_sep_announcement{/if}"> <!-- Start Reihe -->
			<div class="column_20">
				<p>
					<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
				</p>
			</div>
			<div class="column_260">
				<p>
					 <a href="commsy.php?cid={$environment.cid}&mod={$item.type}&fct=detail&iid={$item.item_id}&search_path=true">{$item.title}</a>
				</p>
			</div>
			<div class="column_45">
				<p>
					<a href="" class="attachment">{$item.num_files}</a>
				</p>
			</div>
			<div class="column_65">
				<p><img src="{$basic.tpl_path}img/netnavigation/{$item.type}.png" title="___COMMON_{$item.type|upper}_INDEX___"/></p>
			</div>
			<div class="column_90">
				<p>{$item.modification_date}</p>
			</div>
			<div class="column_155">
				<p>{$item.modificator}</p>
			</div>
			<div class="column_120">
				<p>
					{if $search.index_search == true}
						<div class="progressbar">
							<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
							<span class="percent hidden">{$item.relevanz}</span>
						</div>
					{/if}
				</p>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
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