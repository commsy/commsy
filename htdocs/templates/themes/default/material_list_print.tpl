{extends file="room_list_print.tpl"}

{block name=room_list_header}

	<table width="100%" cellpadding="2" cellspacing="0">
		<thead>
			<tr>
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
            		{if $list.sorting_parameters.sort_modified == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_up"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_modified == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_down"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" class="sort_none">___COMMON_MODIFIED_AT___</a></h3>
            		{/if}
            	</td>
            	<td class="table_head">
            		{if $list.sorting_parameters.sort_modificator == "up"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_up"><strong>___COMMON_ENTERED_BY___</strong></a></h3>
            		{elseif $list.sorting_parameters.sort_modificator == "down"}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_down"><strong>___COMMON_ENTERED_BY___</strong></a></h3>
            		{else}
            		 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" class="sort_none">___COMMON_ENTERED_BY___</a></h3>
            		{/if}
            	</td>
            	{if $room.workflow}
            		<td class="table_head">
            			{if $list.sorting_parameters.sort_workflow == "up"}
            				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_workflow_link}" id="sort_up"><strong>___COMMON_WORKFLOW_INDEX___</strong></a></h3>
            			{elseif $list.sorting_parameters.sort_workflow == "down"}
            				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_workflow_link}" id="sort_down"><strong>___COMMON_WORKFLOW_INDEX___</strong></a></h3>
            			{else}
            				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_workflow_link}" class="sort_none">___COMMON_WORKFLOW_INDEX___</a></h3>
            			{/if}
            		</td>
        		{/if}
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
			{foreach $material.list_content.items as $item }
        		{if $room.assessment && $room.workflow}
        			{$sep = "material_workflow_assessment"}
        		{elseif !$room.assessment && $room.workflow}
        			{$sep = "material_workflow"}
        		{elseif $room.assessment && !$room.workflow}
        			{$sep = "material_assessment"}
        		{elseif !$room.assessment && !$room.workflow}
        			{$sep = "material"}
        		{else}
        			{$sep = "material"}
        		{/if}
    			<tr>
    				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
    					{if !$room.assessment && !$room.workflow}
            				{$w = 364}
            			{elseif $room.assessment && !$room.workflow}
            				{$w = 324}
            			{elseif !$room.assessment && $room.workflow}
            				{$w = 324}
            			{else}
            				{$w = 244}
            			{/if}
            				<p>
            					{if $item.activated}
            						<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
            					{else}
            						{$item.title}</br>___COMMON_NOT_ACTIVATED___
            					{/if}
            				</p>
            				<p>
            					<a href="" class="attachment">{$item.attachment_count}</a>
            				</p>
            				{if $item.attachment_count > 0}
            					<div class="tooltip tooltip_with_400">
            						<div class="tooltip_inner">
            							<div class="tooltip_title">
            								<div class="header">___COMMON_ATTACHED_FILES___</div>
            							</div>
            							<div class="scrollable">
            								<div class="tooltip_content">
            									<ul>
            									{foreach $item.attachment_infos as $file}
            										<li>
            											<a href="{$file.file_url}" target="blank"{if $file.lightbox} rel="lightbox"{/if}>
            												{$file.file_icon} {$file.file_name}
            											</a>
            											({$file.file_size} KB)
            										</li>
            									{/foreach}
            									</ul>
            								</div>
            							</div>
            						</div>
            					</div>
            				{/if}
    				</td>
    				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}" style="border-left: #ffcccc double; border-width:medium">
            				<p>{$item.date}</p>
    				</td>
    				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
            				<p>
            					{$item.modificator}
            				</p>
    				</td>
    				{if $room.workflow}
    					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}" style="border-left: #ffcccc double medium">
        					<p>
        					   {if $item.workflow.light}
        						<img class="workflow" src="{$basic.tpl_path}img/workflow_traffic_light_{$item.workflow.light}.png" alt="{$item.workflow.title}" title="{$item.workflow.title}">
        						{/if}
        					</p>
    					</td>
    				{/if}
    				{if $room.assessment}
    					<td class="{if $item@iteration is odd}row_odd{else}row_even{/if}" style="border-left: #ffcccc double medium">   					
            				<p>
            					{foreach $item.assessment_array as $star_text}
            						<img src="{$basic.tpl_path}img/star_{$star_text}.gif" alt="*" />
            					{/foreach}
            				</p>			
    					</td>
    				{/if}
    			</tr>
    		{/foreach}
		</thead>
	</table>
{/block}


{block name=room_list_content}
{/block}

