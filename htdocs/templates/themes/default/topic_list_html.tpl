{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_335"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_335"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_335"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		<h3 class="w_130">___COMMON_MODIFIED_AT___</h3>
		<h3 class="w_155">___COMMON_REFERENCED_ENTRIES___</h3>

		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $topic.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_date{else}even_sep_date{/if}"> <!-- Start Reihe -->
			<div class="column_new_list">
				{if $item.noticed.show_info && $item.activated}
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
					{if !$item.activated && $item.creator_id != $environment.current_user_id}
						<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        				<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
					{else}
         				<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1" {if array_key_exists($item.iid, $environment.post.form_data.attach)}checked="checked"{/if}/>
        				<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
        			{/if}
         			
				</p>
			</div>
			<div class="column_280">
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
			<div class="column_145">
				<p>{$item.date}</p>
			</div>
			<div class="column_184">
				<p>
					{$item.linked_entries} {if $item.linked_entries == 1}___COMMON_ENTRY___{else}___COMMON_ENTRIES___{/if}
				</p>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

