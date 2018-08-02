{extends file="room_list_print.tpl"}

{block name=room_list_content}

	<table width="100%" cellpadding="2" cellspacing="0" class="print_table_border">
		<thead>
			<tr>
				{*<td class="table_head">
				</td>*}
				<td class="table_head">
					{if $list.sorting_parameters.sort_title == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_title == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
            		{/if}
				</td>
				<td class="table_head">
					<h3>___DISCUSSION_ARTICLES___</h3>
				</td>
				<td class="table_head">
					{if $list.sorting_parameters.sort_latest == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_latest_link}" id="sort_up"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_latest == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_latest_link}" id="sort_down"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_latest_link}" class="sort_none">___COMMON_MODIFIED_AT___</a></h3>
            		{/if}
				</td>
				<td class="table_head">
					<h3>___COMMON_ENTERED_BY___</h3>
				</td>
				{if $room.assessment}
					<td class="table_head">
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
			{foreach $discussion.list_content.items as $item }
    			<tr>
    				{*<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
    					{if $item.noticed.show_info}
    						<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" />
    					{/if}
    				</td>*}
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
        				<div class="print_title">
            				<p>
            					{if $item.activated}
            						<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
            					{else}
            						{$item.title}</br>___COMMON_NOT_ACTIVATED___
            					{/if}
            				</p>
            			</div>
            
            			<div class="print_files_icon">
            				<p>
            					<a href="" class="attachment">{$item.attachment_count}</a>
            				</p>
            			</div>
            		</td>
            		<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
            			<p>
        					 {if $item.article_unread > 0}
        <!--					 	<span class="strong">{$item.article_unread}</span> -->
        					 	{$item.article_unread}
        					 {else}
        					 	{$item.article_unread}
        					 {/if}
        					  / {$item.article_count}
        				</p>
        			</td>
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} print_border">
        				<p>{$item.date}</p>
        			</td>
        			<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
        				<p>{$item.modificator}</p>
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