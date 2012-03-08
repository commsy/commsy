{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
		{else}
		 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
		{/if}
		<h3 class="w_80">___COMMON_MODIFIED_AT___</h3>
		<h3 class="w_135">___COMMON_ENTERED_BY___</h3>
		
		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $topic.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if}"> <!-- Start Reihe -->
			<div class="column_20">
				<p>
				{if $item.noticed != ''}
					<a href="" class="new_item_2"><img title="{$item.noticed}" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
         			<input class="new_item_2" type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{else}
         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
				{/if}
				</p>
			</div>
			<div class="column_304">
				<p>
					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
				</p>
			</div>
			<div class="column_45">
				<p>
					<a href="" class="attachment">{$item.attachment_count}</a>
				</p>
			</div>
			<div class="seperator">
				<div class="column_90">
					<p>{$item.date}</p>
				</div>
				<div class="column_155">
					<p>
						{$item.modificator}
					</p>
				</div>
			</div>
			<div class="seperator">
				<div class="column_100">
					<p>{*
						{foreach $item.assessment_array as $star_text}
							<img src="{$basic.tpl_path}img/star_{$star_text}.gif" alt="*" />
						{/foreach}*}
					</p>
				</div>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

