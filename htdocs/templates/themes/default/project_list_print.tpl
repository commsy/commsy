{extends file="room_list_print.tpl"}

{block name=room_list_content}
	<table width="100%" cellpadding="2" cellspacing="0" class="print_table_border">
		<thead>
			<tr>
				<td class="table_head"></td>
				<td class="table_head">
            		{if $list.sorting_parameters.sort_title == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_NAME___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_title == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_NAME___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_NAME___</a></h3>
            		{/if}
            	<td class="table_head">
					<h3>___ROOM_CONTACT___</h3>
				</td>
				<td class="table_head">
					<h3>___COMMON_REFERENCED_ENTRIES___</h3>
				</td>
				<td class="table_head">
            		{if $list.sorting_parameters.sort_activity == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_activity_link}" id="sort_up"><strong>___COMMON_ACTIVITY___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_activity == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_activity_link}" id="sort_down"><strong>___COMMON_ACTIVITY___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_activity_link}" class="sort_none">___COMMON_ACTIVITY___</a></h3>
            		{/if}
            	</td>
            </tr>
        </thead>
        <tbody>
			{foreach $project.list_content.items as $item }
				<tr>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
						{if $item.noticed.show_info}
        					<a class="new_item_2">
        				{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
        					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
        				{elseif $item.noticed.status == "new"}
        					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
        				{elseif $item.noticed.status == "modified"}
        					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
        				{/if}
        			</td>
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
        				<p>
        					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
        				</p>
        			</td>
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
        				<p>
        					{foreach $item.contacts as $contact}
        						{$contact}{if !$contact@last}, {/if}
        					{/foreach}
        				</p>
        			</td>
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
        				<p>
        					{$item.members_count} {if $item.members_count == 1}___COMMON_USER___{else}___COMMON_USERS___{/if}
        				</p>
        			</td>
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
        				<p>
        				{$item.activity}
        				</p>
        			</td>
        		{/foreach}
        	</tr>
        </tbody>
    </table>
{/block}

