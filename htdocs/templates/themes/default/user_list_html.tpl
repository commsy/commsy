{extends file="room_list_html.tpl"}

{block name=room_list_header}
	<div class="table_head">
		{if $list.sorting_parameters.sort_name == "up"}
		 	<h3 class="w_255"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_name_link}" id="sort_up"><strong>___USER_NAME___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_name == "down"}
		 	<h3 class="w_255"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_name_link}" id="sort_down"><strong>___USER_NAME___</strong></a></h3>
		{else}
		 	<h3 class="w_255"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_name_link}" class="sort_none">___USER_NAME___</a></h3>
		{/if}
		<h3 class="w_172">___USER_TELEPHONE___</h3>
		{if $list.sorting_parameters.sort_email == "up"}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_email_link}" id="sort_up"><strong>___USER_EMAIL___</strong></a></h3>
		{elseif $list.sorting_parameters.sort_email == "down"}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_email_link}" id="sort_down"><strong>___USER_EMAIL___</strong></a></h3>
		{else}
		 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_email_link}" class="sort_none">___USER_EMAIL___</a></h3>
		{/if}
		<div class="clear"> </div>
	</div>
{/block}


{block name=room_list_content}
	{foreach $user.list_content.items as $item }
		<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_user{else}even_sep_user{/if}"> <!-- Start Reihe -->
			<div class="column_20">
				<p>
				{if $item.noticed.show_info}
					<a class="new_item_2">
					{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
					{elseif $item.noticed.status == "new"}
					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
					{elseif $item.noticed.status == "modified"}
					<img title="" class="new_item_2" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
					{/if}
					<span class="tooltip">
						<span class="header">___COMMON_CHANGE_INFORAMTION___</span><br/>
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
			<div class="column_184">
				<p>
					{if !empty($item.phone)}{$item.phone}{/if}
					{if !empty($item.phone) && !empty($item.handy)}<br/>{/if}
					{if !empty($item.handy)}{$item.handy}{/if}
				</p>
			</div>
			<div class="column_260">
				<p>
					{if empty($item.mail)}
						___USER_EMAIL_HIDDEN___
					{else}
						<a href="mailto:{$item.mail}">{$item.mail|truncate:35:"...":true}</a>
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

