{extends file="room_list_print.tpl"}

{block name=room_list_content}

	<table width="100%" cellpadding="2" cellspacing="0" class="print_table_border" style="background-color:#DADADA;border: 1px solid #676767;">
		<thead>
			<tr>
				{*<td class="table_head_2"></td>*}
				<td class="table_head_2">
					{if $list.sorting_parameters.sort_title == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_title == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
            		{/if}
				</td>
				<td class="table_head_2">
					<h3>___COMMON_MODIFIED_AT___</h3>
				</td>
				<td class="table_head_2">
					<h3>___COMMON_REFERENCED_ENTRIES___</h3>
				</td>
			</tr>
		</thead>
		<tbody>
			{foreach $topic.list_content.items as $item }
				<tr>
					{*<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
    					{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
    					{elseif $item.noticed.status == "new"}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
    					{elseif $item.noticed.status == "modified"  and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2_a.gif" alt="*" /></a>
    					{elseif $item.noticed.status == "modified"}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
    					{elseif $item.noticed.annotation_info.count_new}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
    					{elseif $item.noticed.annotation_info.count_changed}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2_a.gif" alt="*" /></a>
    					{/if}
    				</td>*}
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
						<div class="print_title">
            				<p>
            					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
            				</p>
            			</div>
            			{*<div class="print_files_icon">
            				<p>
            					<a href="" class="attachment{if $item.attachment_count == 0}_none_overlay{/if}">{$item.attachment_count}</a>
            				</p>
            			</div>*}
            			<div class="clear"></div>
					</td>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
						<p>{$item.date}</p>
					</td>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
						<p>
        					{$item.linked_entries} {if $item.linked_entries == 1}___COMMON_ENTRY___{else}___COMMON_ENTRIES___{/if}
        				</p>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/block}

