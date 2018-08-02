{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_270"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_NAME___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_270"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_NAME___</strong></a></h3>
		{else}
		 	<h3 class="w_270"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_NAME___</a></h3>
		{/if}
		<h3 class="w_150">___ROOM_CONTACT___</h3>
		<h3 class="w_110">___COMMON_REFERENCED_ENTRIES___</h3>
		{if $list.sorting_parameters.sort_activity == "up"}
		 	<h3 class="w_110"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_activity_link}" id="sort_up"><strong>___COMMON_ACTIVITY___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_activity == "down"}
		 	<h3 class="w_110"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_activity_link}" id="sort_down"><strong>___COMMON_ACTIVITY___</strong></a></h3>
		{else}
		 	<h3 class="w_110"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_activity_link}" class="sort_none">___COMMON_ACTIVITY___</a></h3>
		{/if}

		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $project.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_project{else}even_sep_project{/if}"> <!-- Start Reihe -->
			<div class="column_new_list">
				{if $item.noticed.show_info}
					<a class="new_item_2">
					{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
					{elseif $item.noticed.status == "new"}
					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
					{elseif $item.noticed.status == "modified"}
					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
					{/if}
					<div class="tooltip">
						<div class="tooltip_inner">
							<div class="tooltip_title">
								<div class="header">___COMMON_CHANGE_INFORAMTION___</div>
							</div>
							<div class="tooltip_content">
								<span class="content">{$item.noticed.item_info}</span>
							</div>
						</div>
					</div>
				{/if}
			</div>
			<div class="column_list_20">
				<p>
         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1" {if array_key_exists($item.iid, $environment.post.form_data.attach)}checked="checked"{/if}/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				</p>
			</div>
			<div class="column_260">
				<p>
					 {if $item.may_enter}
					 	<a href="commsy.php?cid={$item.iid}&mod=home&fct=index"><img title="" src="{$basic.tpl_path}img/door_open_small.gif" alt="" /></a>
					 {else}
					 	<img title="" src="{$basic.tpl_path}img/door_closed_small.gif" alt="" />
					 {/if}
					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
				</p>
			</div>
			<div class="column_160">
				<p>
					{foreach $item.contacts as $contact}
						{$contact}{if !$contact@last}, {/if}
					{/foreach}
				</p>
			</div>
			<div class="column_120">
				<p>
					{$item.members_count} {if $item.members_count == 1}___COMMON_USER___{else}___COMMON_USERS___{/if}
				</p>
			</div>
			<div class="column_160">
				<p>
				{$item.activity}
				</p>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

