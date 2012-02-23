{extends file="room_list_html.tpl"}

{block name=room_navigation_rubric_title}
	___COMMON_SEARCH_RESULTS___:
	<span>{$room.search_content|count}</span>
{/block}

{block name=room_list_header}
	<div class="table_head">
	{*
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_modified == "up"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_up"><strong>___DATES_TIME___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_modified == "down"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_down"><strong>___DATES_TIME___</strong></a></h3>
		{else}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" class="sort_none">___DATES_TIME___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_modificator == "up"}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_up"><strong>___DATES_PLACE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_modificator == "down"}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_down"><strong>___DATES_PLACE___</strong></a></h3>
		{else}
	 		<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" class="sort_none">___DATES_PLACE___</a></h3>
		{/if}
		<h3></h3>
		<div class="clear"> </div>
		*}
	</div>
{/block}


{block name=room_list_content}
	{foreach $room.search_content as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_announcement{else}even_sep_announcement{/if}"> <!-- Start Reihe -->
			<div class="column_20">
				<p>
				{*
				{if $item.noticed != ''}
					<a href="" class="new_item_2"><img title="{$item.noticed}" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
         			<input class="new_item_2" type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{else}
         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{/if}
				*}
				</p>
			</div>
			<div class="column_304">
				<p>
					 <a href="commsy.php?cid={$environment.cid}&mod={$item.type}&fct=detail&iid={$item.iid}">{$item.title}</a>
				</p>
			</div>
			<div class="column_45">
				<p>
					<a href="" class="attachment">{$item.num_files}</a>
				</p>
			</div>
			<div class="column_90">
				<p>{$item.type}</p>
			</div>
			<div class="column_155">
				<p>
					<div class="progressbar">
						<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
						<span class="percent hidden">{$item.count}</span>
					</div>
				</p>
			</div>
			<div class="column_100">
				<p>
				</p>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

{block name=room_right_portlets prepend}
	<div class="portlet_rc">
		<a href="" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="btn_head_rc">
			<img src="{$basic.tpl_path}img/{*{if $h}*}btn_open_rc.gif{*{else}btn_close_rc.gif{/if}*}" alt="{*{if $h}*}___COMMON_SHOW___{*{else}___COMMON_HIDE___{/if}*}" />
		</a>
		<h2>
			Suche
		</h2>

		<div class="clear"></div>
		<div class="portlet_rc_body{*{if $h} hidden{/if}*}">
			Begriffe:
			{foreach $room.search_sidebar.search_words as $word}
				{$word}
			{/foreach}
			
			<div class="clear"></div>
			
			<input type="checkbox" value="kategorisiert" name="kat"/>
			<label for="kat">kategorisiert</label>
		</div>
	</div>
{/block}

{block name=sidebar_tagbox_treefunction}
	{function name=tag_tree level=0}
		<ul>
		{foreach $nodes as $node}
			<li	id="node_{$node.item_id}"
				{if $node.children|count > 0}class="folder"{/if}
				data="url:'commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&name=selected&seltag_{$level}={$node.item_id}&seltag=yes'">{$node.title}
			{if $node.children|count > 0}	{* recursive call *}
				{tag_tree nodes=$node.children level=$level+1}
			{/if}
		{/foreach}
		</ul>
	{/function}
{/block}

{block name=sidebar_buzzwordbox_buzzword}
	<a href="commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
{/block}