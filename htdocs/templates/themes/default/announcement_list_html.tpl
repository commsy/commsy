{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="Ansicht drucken">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="drucken" />
	</a>
    <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid=NEW" title="neue Diskussion anlegen">
    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="neu" />
    </a>
{/block}

{block name=room_navigation_rubric_title}
	___COMMON_{$room.rubric|upper}_INDEX___
	<span>(___COMMON_ENTRIES___: {$list.page_text_fragments.count_entries})</span>
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		<div class="content_item"> <!-- Start content_item -->
			<div class="table_head">
				{if $list.sorting_parameters.sort_title == "up"}
				 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
				{elseif $list.sorting_parameters.sort_title == "down"}
				 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
				{else}
				 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_title_link}" class="sort_none">___COMMON_TITLE___</a></h3>
				{/if}
				{if $list.sorting_parameters.sort_modified == "up"}
				 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_up"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
				{elseif $list.sorting_parameters.sort_modified == "down"}
				 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" id="sort_down"><strong>___COMMON_MODIFIED_AT___</strong></a></h3>
				{else}
				 	<h3 class="w_80"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modified_link}" class="sort_none">___COMMON_MODIFIED_AT___</a></h3>
				{/if}
				{if $list.sorting_parameters.sort_modificator == "up"}
				 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_up"><strong>___COMMON_ENTERED_BY___</strong></a></h3>
				{elseif $list.sorting_parameters.sort_modificator == "down"}
				 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" id="sort_down"><strong>___COMMON_ENTERED_BY___</strong></a></h3>
				{else}
				 	<h3 class="w_135"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_modificator_link}" class="sort_none">___COMMON_ENTERED_BY___</a></h3>
				{/if}
				{if $list.sorting_parameters.sort_assessment == "up"}
				 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_up"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
				{elseif $list.sorting_parameters.sort_assessment == "down"}
				 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" id="sort_down"><strong>___COMMON_ASSESSMENT_INDEX___</strong></a></h3>
				{else}
				 	<h3><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.sorting_parameters.sort_assessment_link}" class="sort_none">___COMMON_ASSESSMENT_INDEX___</a></h3>
				{/if}

				<div class="clear"> </div>
			</div>

			{foreach $announcement.list_content.items as $item }
				<div class="{if $item@iteration is odd}row_odd{else}row_even{/if}"> <!-- Start Reihe -->
					<div class="column_20">
						<p>
							<input type="checkbox" name="" value="" />
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
							<p>{$item.modification_date}</p>
						</div>

						<div class="column_155">
							<p>
								<a href="">{$item.creator}</a>
							</p>
						</div>
					</div>

					<div class="seperator">
						<div class="column_100">
							<p>
								<img src="{$basic.tpl_path}img/star_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_non_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_non_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_non_active.gif" alt="*" />
							</p>
						</div>
					</div>

					<div class="clear"> </div>
				</div> <!-- Ende Reihe -->
			{/foreach}
		</div> <!-- Ende content_item -->

		<div class="content_item"> <!-- Start content_item -->
			<div class="item_info">
				<div class="ii_left">
				 	<div id="item_action">
				 		<input type="checkbox" name="" value="" /> ___ALL___

				 		<select name="index_view_action" size="1">
					 		<option value="-1">Aktion w&auml;hlen</option>
					 		<option disabled="disabled">------------------------------</option>
					 		<option value="1">___COMMON_LIST_ACTION_MARK_AS_READ___</option>
					 		<option value="2">___COMMON_LIST_ACTION_COPY___</option>
					 		<option value="download">___COMMON_LIST_ACTION_DOWNLOAD___</option>
					 		<option disabled="disabled">------------------------------</option>
					 		<option disabled="disabled">___COMMON_LIST_ACTION_DELETE___</option>
					 	</select>

					 	<input type="image" src="{$basic.tpl_path}img/btn_go.gif" alt="___COMMON_LIST_ACTION_BUTTON_GO___" />
					 </div>
				</div>

				<div class="ii_right">
					<p>0 Eintr&auml;ge ausgew&auml;hlt</p>
				</div>

				<div class="clear"> </div>
			</div>
		</div> <!-- Ende content_item -->
		{include file="room_list_footer_html.tpl"}
	</div>
{/block}

{block name=room_right_portlets prepend}
	<div class="portlet_rc">
		<a href="" title="schlie&szlig;en" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>Einschr&auml;nkungen der Liste</h2>

		<div class="clear"> </div>

		<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		<div class="portlet_rc_body">
			<div class="change_view">
				Gruppe
				<select name="" size="1">
					<option>Gruppe w&auml;hlen</option>
				</select>
			</div>

			<div class="change_view">
				Thema
				<select name="" size="1">
					<option>Gruppe w&auml;hlen</option>
				</select>
			</div>
		</div>
	</div>
{/block}