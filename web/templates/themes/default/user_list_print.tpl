{extends file="room_list_print.tpl"}

{block name=room_list_content}
	<table width="100%" cellpadding="2" cellspacing="0" class="print_table_border" style="background-color:#DADADA;border: 1px solid #676767;">
		<thead>
			<tr>
				{*<td class="table_head_2"></td>*}
				<td class="table_head_2">
					{if $list.sorting_parameters.sort_name == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_name_link}" id="sort_up"><strong>___USER_NAME___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_name == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_name_link}" id="sort_down"><strong>___USER_NAME___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_name_link}" class="sort_none">___USER_NAME___</a></h3>
            		{/if}
				</td>
				<td class="table_head_2">
					<h3>___USER_TELEPHONE___</h3>
            	</td>
            	<td class="table_head_2">
            		{if $list.sorting_parameters.sort_email == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_email_link}" id="sort_up"><strong>___USER_EMAIL___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_email == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_email_link}" id="sort_down"><strong>___USER_EMAIL___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_email_link}" class="sort_none">___USER_EMAIL___</a></h3>
            		{/if}
            	</td>
            </tr>
        </thead>
        <tbody>
        	{foreach $user.list_content.items as $item }
            	<tr>
    				{*<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
    					{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
        					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
        				{elseif $item.noticed.status == "new"}
        					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
        				{elseif $item.noticed.status == "modified"}
        					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
        				{/if}
    				</td>*}
    				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
    					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
    				</td>
    				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
        				{if !empty($item.phone)}{$item.phone}{/if}
        				{if !empty($item.phone) && !empty($item.handy)}<br/>{/if}
        				{if !empty($item.handy)}{$item.handy}{/if}
    				</td>
    				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
    					{if empty($item.mail)}
    						___USER_EMAIL_HIDDEN___
    					{else}
    						<a href="mailto:{$item.mail}">{$item.mail|truncate:35:"...":true}</a>
    					{/if}
    				</td>
    			</tr>
    		{/foreach}
        </tbody>
    </table>
{/block}