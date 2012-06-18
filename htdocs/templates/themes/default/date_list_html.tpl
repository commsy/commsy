{extends file="room_list_html.tpl"}

{block name=room_site_actions}
	<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&mode=print" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>

    {if $index.actions.new}
		<a id="create_new" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid=NEW" title="___COMMON_NEW_ITEM___">
	    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
	    </a>
    {/if}
    {if $index.actions.user}
		<a id="own_user" href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$index.actions.user_iid}" title="___COMMON_OWN_USER___">
	    	<img src="{$basic.tpl_path}img/btn_own_user.gif" alt="___COMMON_OWN_USER___" />
	    </a>
    {/if}
    
    <a href="commsy.php?cid={$environment.cid}&mod=date&fct=index&mode=calendar" title="Ansicht in Portlets"><img src="{$basic.tpl_path}img/btn_portlet_view.gif" alt="Portlets" /></a>
{/block}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_335"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_335"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_335"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_time == "up"}
		 	<h3 class="w_130"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_time_link}" id="sort_up"><strong>___DATES_TIME___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_time == "down"}
		 	<h3 class="w_130"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_time_link}" id="sort_down"><strong>___DATES_TIME___</strong></a></h3>
		{else}
		 	<h3 class="w_130"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_time_link}" class="sort_none">___DATES_TIME___</a></h3>
		{/if}
		{if $list.sorting_parameters.sort_place== "up"}
		 	<h3 class="w_155"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_place_link}" id="sort_up"><strong>___DATES_PLACE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_place == "down"}
		 	<h3 class="w_155"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_place_link}" id="sort_down"><strong>___DATES_PLACE___</strong></a></h3>
		{else}
	 		<h3 class="w_155"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_place_link}" class="sort_none">___DATES_PLACE___</a></h3>
		{/if}

		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $date.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_date{else}even_sep_date{/if}"> <!-- Start Reihe -->
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
         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
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
				<p>
					<a href="#" class="attachment{if $item.attachment_count == 0}_none_overlay{/if}">{$item.attachment_count}</a>
				</p>
				{if $item.attachment_count > 0 && $item.activated}
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
				{/if}
			</div>
			<div class="column_140">
				<p>
					{$item.date}{if !empty($item.time) && $item.show_time}, {$item.time}{/if}
				</p>
			</div>
			<div class="column_224">
				<p>
					{$item.place}
				</p>
			</div>
			{if !empty($item.color)}
				<div class="column_20">
					<p>
						<span class="date_list_color" style="background-color:{$item.color}">&nbsp;</span>
					</p>
				</div>
			{/if}
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

