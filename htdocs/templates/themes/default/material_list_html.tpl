{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $room.assessment && $room.workflow}
			{$w = 295}
		{elseif !$room.assessment && !$room.workflow}
			{$w = 380}
		{else}
			{$w = 380}
		{/if}
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_modified == "up"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_up"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_modified == "down"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_down"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
		{else}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" class="sort_none">___COMMON_MODIFIED_AT___</a></h3>
		{/if}
		<h3 class="w_135">___COMMON_ENTERED_BY___</h3>
		{if $room.assessment}
			{if $list.sorting_parameters.sort_assessment == "up"}
				<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_up"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
			{elseif $list.sorting_parameters.sort_assessment == "down"}
				<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_down"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
			{else}
				<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" class="sort_none">___COMMON_ASSESSMENT_INDEX___</a></h3>
			{/if}
		{/if}
		{if $room.workflow}
			<h3>___COMMON_WORKFLOW_INDEX___</h3>
		{/if}
		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $material.list_content.items as $item }
		{if $room.assessment && $room.workflow}
			{$sep = "date"}
		{elseif !$room.assessment && !$room.workflow}
			{$sep = "announcement"}
		{else}
			{$sep = "announcement"}
		{/if}
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_{$sep}{else}even_sep_{$sep}{/if}"> <!-- Start Reihe -->
			<div class="column_20">
				<p>
				{if $item.noticed.show_info}
					<a class="new_item_2">
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
					<span class="tooltip">
						<span class="header">___COMMON_CHANGE_INFORAMTION___</span><br/>
						<span class="content">{$item.noticed.item_info}</span>
						{if $item.noticed.section_info.count_new}
							<span class="content">___COMMON_NEW_SECTIONS___: {$item.noticed.section_info.count_new}
							{foreach $item.noticed.section_info.section_new_items as $section_item}
							   <br/>
							   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$section_item.ref_iid}#section{$section_item.iid}">{$section_item.title|truncate:25:'...':true}</a> ({$section_item.date})
							   </span>
							{/foreach}
							</span>
						{/if}
						{if $item.noticed.section_info.count_changed}
							<span class="content">___COMMON_CHANGED_SECTIONS___: {$item.noticed.section_info.count_changed}
							{foreach $item.noticed.section_info.section_changed_items as $section_item}
							   <br/>
							   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$section_item.ref_iid}#section{$section_item.iid}">{$section_item.title|truncate:25:'...':true}</a> ({$section_item.date})
							   </span>
							{/foreach}
							</span>
						{/if}
						{if $item.noticed.annotation_info.count_new}
							<span class="content">___COMMON_NEW_ANNOTATIONS___: {$item.noticed.annotation_info.count_new}
							{foreach $item.noticed.annotation_info.anno_new_items as $anno_item}
							   <br/>
							   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$anno_item.ref_iid}#annotation{$anno_item.iid}">{$anno_item.title|truncate:25:'...':true}</a> ({$anno_item.date})
							   </span>
							{/foreach}
							</span>
						{/if}
						{if $item.noticed.annotation_info.count_changed}
							<span class="content">___COMMON_CHANGED_ANNOTATIONS___: {$item.noticed.annotation_info.count_changed}
							{foreach $item.noticed.annotation_info.anno_changed_items as $anno_item}
							   <br/>
							   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$anno_item.ref_iid}#annotation{$anno_item.iid}">{$anno_item.title|truncate:25:'...':true}</a> ({$anno_item.date})
							   </span>
							{/foreach}
							</span>
						{/if}
					</span>

         			<input class="new_item_2" type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{else}
         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{/if}
				</p>
			</div>
			{if $room.assessment && $room.workflow}
				{$w = 244}
			{elseif !$room.assessment && !$room.workflow}
				{$w = 304}
			{else}
				{$w = 304}
			{/if}
			<div class="column_{$w}">
				<p>
					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
				</p>
			</div>
			<div class="column_45">
				<p>
					<a href="" class="attachment">{$item.attachment_count}</a>
				</p>
				{if $item.attachment_count > 0}
					<div class="tooltip">
						<div class="scrollable">
							<ul>
							{foreach $item.attachment_infos as $file}
								<li>
									<a href="{$file.file_url}" title="{$file.file_name}" target="blank"{if $file.lightbox} rel="lightbox"{/if}>
										{$file.file_icon} {$file.file_name|truncate:25:'...':true}
									</a>
									({$file.file_size} KB)
								</li>
							{/foreach}
							</ul>
						</div>
					</div>
				{/if}
			</div>
			<div class="column_90">
				<p>{$item.date}</p>
			</div>
			<div class="column_155">
				<p>
					{$item.modificator}
				</p>
			</div>
			<div class="column_100">
				<p>
					{foreach $item.assessment_array as $star_text}
						<img src="{$basic.tpl_path}img/star_{$star_text}.gif" alt="*" />
					{/foreach}
				</p>
			</div>
			{if $room.workflow && $item.workflow.show}
				<div class="column_100">
					<p>
						<img src="{$basic.tpl_path}img/workflow_traffic_light_{$item.workflow.light}" alt="{$item.workflow.title}" title="{$item.workflow.title}">
					</p>
				</div>
			{/if}
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

