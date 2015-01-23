{extends file="room_list_html.tpl"}

{block name=room_list_header}
		{if $room.assessment}
			{$w = 295}
		{else}
			{$w = 335}
		{/if}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_{$w}"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		<h3 class="w_85">___DISCUSSION_ARTICLES___</h3>
		{if $list.sorting_parameters.sort_latest == "up"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_latest_link}" id="sort_up"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_latest == "down"}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_latest_link}" id="sort_down"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
		{else}
		 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_latest_link}" class="sort_none">___COMMON_MODIFIED_AT___</a></h3>
		{/if}
		<h3 class="w_135">___COMMON_ENTERED_BY___</h3>
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
	{foreach $discussion.list_content.items as $item }
		{if $room.assessment}
			{$sep = "discussion_assessment"}
		{else}
			{$sep = "discussion"}
		{/if}
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
								{if $item.noticed.article_info.count_new}
								<span class="content">___COMMON_NEW_ARTICLES___: {$item.noticed.article_info.count_new}
								{foreach $item.noticed.article_info.article_new_items as $article_item}
								   <br/>
								   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$article_item.ref_iid}#article{$article_item.iid}">{$article_item.title|truncate:25:'...':true}</a> ({$article_item.date})
								   </span>
								{/foreach}
								</span>
							{/if}
							{if $item.noticed.article_info.count_changed}
								<span class="content">___COMMON_CHANGED_ARTICLES___: {$item.noticed.article_info.count_changed}
								{foreach $item.noticed.article_info.article_changed_items as $article_item}
								   <br/>
								   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$article_item.ref_iid}#article{$article_item.iid}">{$article_item.title|truncate:25:'...':true}</a> ({$article_item.date})
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
			{if $room.assessment}
				{$w = 244}
			{else}
				{$w = 280}
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
						<a href="#" class="attachment">{$item.attachment_count}</a>
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

			<div class="column_95">
				<p>
					 {if $item.article_unread > 0}
<!--					 	<span class="strong">{$item.article_unread}</span> -->
					 	{$item.article_unread}
					 {else}
					 	{$item.article_unread}
					 {/if}
					  / {$item.article_count}
				</p>
			</div>

			<div class="column_90">
				<p>{$item.date}</p>
			</div>
			<div class="column_145">
				<p>
					{$item.modificator}
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