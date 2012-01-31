{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_title == "up"}
		 	<h3 class="w_255"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___USER_NAME___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_title == "down"}
		 	<h3 class="w_255"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___USER_NAME___</strong></a></h3>
		{else}
		 	<h3 class="w_255"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___USER_NAME___</a></h3>
		{/if}
		<h3 class="w_135">___USER_TELEPHONE___</h3>
		{if $list.sorting_parameters.sort_modificator == "up"}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_up"><strong>___USER_EMAIL___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_modificator == "down"}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_down"><strong>___USER_EMAIL___</strong></a></h3>
		{else}
	 		<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" class="sort_none">___USER_EMAIL___</a></h3>
		{/if}
		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $user.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_user{else}even_sep_user{/if}"> <!-- Start Reihe -->
			<div class="column_20">
				<p>
				{if $item.noticed != ''}
					<a href="" class="new_item_2"><img title="{$item.noticed}" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
         			<input class="new_item_2" type="checkbox" name="attach[{$item.iid}]" value="1"/>
        			<input type="hidden" name="shown[{$item.iid}]" value="1"/>
				{else}
         			<input type="checkbox" name="attach[{$item.iid}]" value="1"/>
        			<input type="hidden" name="shown[{$item.iid}]" value="1"/>
				{/if}
				</p>
			</div>
			<div class="column_244">
				<p>
					 <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&{$environment.params}&iid={$item.iid}">{$item.title}</a>
				</p>
			</div>
			<div class="column_145">
				<p>
					{if !empty($item.phone)}{$item.phone}{/if}
					{if !empty($item.phone) && !empty($item.handy)}<br/>{/if}
					{if !empty($item.handy)}{$item.handy}{/if}
				</p>
			</div>
			<div class="column_194">
				<p>
					{if empty($item.mail)}
						___USER_EMAIL_HIDDEN___
					{else}
						<a href="mailto:{$item.mail}">{$item.mail|truncate:29:"...":true}</a>
					{/if}
				</p>
			</div>
			<div class="column_120">
				<p></p>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

