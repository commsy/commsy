{extends file="room_list_html.tpl"}


{block name=room_site_actions}

	<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&mode=print{params params=$print.params_array}" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>

    {if $index.actions.new}
		<a class="open_popup" data-custom="iid: 'NEW', module: '{$environment.module}'" href="#" title="___COMMON_NEW_ITEM___">
	    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
	    </a>
    {/if}

{/block}


{block name=room_list_header}
	<div class="table_head">
		{if $room.assessment}
			{$w = 220}
		{else}
			{$w = 250}
		{/if}
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_status == "up"}
		 	<h3 class="w_110"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_status_link}" id="sort_up"><strong>___TODO_STATUS___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_status == "down"}
		 	<h3 class="w_110"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_status_link}" id="sort_down"><strong>___TODO_STATUS___</strong></a></h3>
		{else}
		 	<h3 class="w_110"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_status_link}" class="sort_none">___TODO_STATUS___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_date == "up"}
		 	<h3 class="w_85"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_date_link}" id="sort_up"><strong>___TODO_DATE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_date == "down"}
		 	<h3 class="w_85"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_date_link}" id="sort_down"><strong>___TODO_DATE___</strong></a></h3>
		{else}
		 	<h3 class="w_85"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_date_link}" class="sort_none">___TODO_DATE___</a></h3>
		{/if}
		<h3 class="w_65">___COMMON_TIME___</h3>
		<h3 class="w_110">___TODO_PROCESSORS___</h3>
		{if $room.assessment}
			{if $list.sorting_parameters.sort_assessment == "up"}
				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_up"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
			{elseif $list.sorting_parameters.sort_assessment == "down"}
				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_down"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
			{else}
				<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" class="sort_none">___COMMON_ASSESSMENT_INDEX___</a></h3>
			{/if}
		{/if}

		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
		{if $room.assessment}
			{$sep = "todo_assessment"}
		{else}
			{$sep = "todo"}
		{/if}
	{foreach $todo.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_{$sep}{else}even_sep_{$sep}{/if}"> <!-- Start Reihe -->
			<div class="column_new_list">
				{if $item.noticed.show_info && $item.activated}
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
					<div class="tooltip">
						<div class="tooltip_inner">
							<div class="tooltip_title">
								<div class="header">___COMMON_CHANGE_INFORAMTION___</div>
							</div>
							<div class="tooltip_content">
								<span class="content">{$item.noticed.item_info}</span>
								{if $item.noticed.step_info.count_new}
									<span class="content">___COMMON_NEW_STEPS___: {$item.noticed.step_info.count_new}
									{foreach $item.noticed.step_info.step_new_items as $step_item}
									   <br/>
									   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$step_item.ref_iid}#step{$step_item.iid}">{$step_item.title|truncate:25:'...':true}</a> ({$step_item.date})
									   </span>
									{/foreach}
									</span>
								{/if}
								{if $item.noticed.step_info.count_changed}
									<span class="content">___COMMON_CHANGED_STEPS___: {$item.noticed.step_info.count_changed}
									{foreach $item.noticed.step_info.step_changed_items as $step_item}
									   <br/>
									   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$step_item.ref_iid}#step{$step_item.iid}">{$step_item.title|truncate:25:'...':true}</a> ({$step_item.date})
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
							</div>
						</div>
					</div>
				{/if}
			</div>
			<div class="column_list_20">
				<p>
					{if !$item.activated && $item.creator_id != $environment.current_user_id}
						<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1" disabled="disabled"/>
        				<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
					{else}
         				<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1" {if array_key_exists($item.iid, $environment.post.form_data.attach)}checked="checked"{/if}/>
        				<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
        			{/if}
				</p>
			</div>
			{if !$room.assessment}
				{$w = 200}
			{else}
				{$w = 164}
			{/if}
			<div class="column_{$w}">
				<p>
					{if $item.activated}
						<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
					{else}
						{if $environment.is_moderator || $environment.user_item_id == $item.creator_id}
							<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
						{else}
							{$item.title}
						{/if}
						<br/>{$item.activated_text}
					{/if}
				</p>
			</div>
			<div class="column_45">
				{if $item.attachment_count > 0 && $item.activated}
					<p>
						<a href="#" class="attachment{if $item.attachment_count == 0}_none_overlay{/if}">{$item.attachment_count}</a>
					</p>
					<div class="tooltip tooltip_with_400">
						<div class="tooltip_inner tooltip_inner_with_400">
							<div class="tooltip_title">
								<div class="header">___COMMON_ATTACHED_FILES___</div>
							</div>
							<div class="scrollable">
								<div class="tooltip_content">
									<ul>
									{foreach $item.attachment_infos as $file}
										<li>
											<a class="{if $file.lightbox}lightbox_{$item.iid}{/if}" href="{$file.file_url}" target="blank">
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
				{else}
					<p>&nbsp;</p>
				{/if}
			</div>
			<div class="column_120">
				<p>{$item.status}</p>
			</div>
			<div class="column_100">
				<p>{$item.process_date}</p>
			</div>
			<div class="column_65">
				{$item.process}
			</div>
			{if !$room.assessment}
				{$w = 184}
			{else}
				{$w = 120}
			{/if}
			<div class="column_{$w}">
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
			</div>
			{if $room.assessment}
			<div class="column_90">
				<p>
					{foreach $item.assessment_array as $star_text}
						<img src="{$basic.tpl_path}img/star_{$star_text}.gif" alt="*" />
					{/foreach}
				</p>
			</div>
			{/if}
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

