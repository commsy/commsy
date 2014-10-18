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
					{if $list.sorting_parameters.sort_status == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_status_link}" id="sort_up"><strong>___TODO_STATUS___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_status == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_status_link}" id="sort_down"><strong>___TODO_STATUS___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_status_link}" class="sort_none">___TODO_STATUS___</a></h3>
            		{/if}
				</td>
				<td class="table_head_2">
					{if $list.sorting_parameters.sort_date == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_date_link}" id="sort_up"><strong>___TODO_DATE___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_date == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_date_link}" id="sort_down"><strong>___TODO_DATE___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_date_link}" class="sort_none">___TODO_DATE___</a></h3>
            		{/if}
				</td>
				<td class="table_head_2">
					<h3 class="w_65">___COMMON_TIME___</h3>
				</td>
				<td class="table_head_2">
					<h3 class="w_65">___TODO_PROCESSORS___</h3>
				</td>
				{if $room.assessment}
					<td class="table_head_2">
            			{if $list.sorting_parameters.sort_assessment == "up"}
            				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_up"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
            			{elseif $list.sorting_parameters.sort_assessment == "down"}
            				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_down"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
            			{else}
            				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" class="sort_none">___COMMON_ASSESSMENT_INDEX___</a></h3>
            			{/if}
            		</td>
        		{/if}
			</tr>
		</thead>
		<tbody>
			{foreach $todo.list_content.items as $item }
				<tr>
					{*<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
    					{if $item.noticed.show_info}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" />
    					{/if}
    				</td>*}
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
						<div class="print_title">
            				<p>
            					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
            				</p>
            			</div>
            			<div class="print_files_icon">
            				{*<p>
            					<a href="" class="attachment{if $item.attachment_count == 0}_none_overlay{/if}">{$item.attachment_count}</a>
            				</p>*}
            			</div>
					</td>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
						<p>{$item.status}</p>
					</td>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
						<p>{$item.process_date}</p>
					</td>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
						<p>{$item.process}</p>
					</td>
					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
						<p>
            				{if ($item.processors|@count) > 0}
            					{foreach $item.processors as $processor}
            						{if $processor.is_user && $processor.visible && $processor.as_link}
            							<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$processor.item_id}">{$processor.linktext}</a>{if !$processor@last}, {/if}
            						{else}
            							{$processor.linktext}{if !$processor@last}, {/if}
            						{/if}
            					{/foreach}
            				{else}
            					___TODO_NO_PROCESSOR_SHORT___
            				{/if}
        				</p>
					</td>
					{if $room.assessment}
    					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
    						<p>
            					{foreach $item.assessment_array as $star_text}
            						<img src="{$basic.tpl_path}img/star_{$star_text}.gif" alt="*" />
            					{/foreach}
            				</p>
    					</td>			
    				{/if}		
				</tr>
			{/foreach}
		</tbody>
	</table>
{/block}

