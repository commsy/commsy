{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_295"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_295"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_295"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
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
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_discussion{else}even_sep_discussion{/if}"> <!-- Start Reihe -->
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
					</span>

         			<input class="new_item_2" type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{else}
         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{/if}
				</p>
			</div>
			<div class="column_244">
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
				<p>
					{$item.article_count} ({$item.article_unread} ___COMMON_UNREAD___)
				</p>
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
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}