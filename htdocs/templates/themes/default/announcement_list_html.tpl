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
	<span>(___COMMON_ENTRIES___: {$announcement.page_text_fragments.count_entries})</span>
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		<div class="content_item"> <!-- Start content_item -->
			<div class="table_head">
				{if $announcement.list_parameters.sort == "title"}
				 	<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$environment.params}&sort=title_rev" id="sort_up"><strong>___COMMON_TITLE___</strong></a></h3>
				{/if}
				{if $announcement.list_parameters.sort == "title_rev"}
					<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$environment.params}&sort=title" id="sort_down"><strong>___COMMON_TITLE___</strong></a></h3>
				{/if}
				{if $announcement.list_parameters.sort != "title_rev" and $announcement.list_parameters.sort != "title"}
					<h3 class="w_380"><a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$environment.params}&sort=title" class="sort_none"><strong>___COMMON_TITLE___</strong></a></h3>
				{/if}
				<h3 class="w_80"><a href="" id="sort_up">___COMMON_MODIFIED_AT___</a></h3> <!-- id="sort_down" ist ebenfalls vorhanden -->
				<h3 class="w_135"><a href="" class="sort_none">___COMMON_ENTERED_BY___</a></h3>
				<h3><a href="" class="sort_none">___COMMON_ASSESSMENT_INDEX___</a></h3>

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

		<div class="content_item"> <!-- Start content_item -->
			<div class="item_info">
				<div class="ii_left">
					<p>Eintr&auml;ge pro Seite 
						<a href=""><strong>20</strong></a>|<a href="">50</a>|<a href="">alle</a>
					</p>
				</div>

				<div class="ii_right">
					<div id="item_navigation">
					    {if $announcement.browsing_parameters.browse_start != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$announcement.browsing_parameters.browse_start}"><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
						{else}
						   <img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" />
						{/if}
					    {if $announcement.browsing_parameters.browse_left != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$announcement.browsing_parameters.browse_left}"><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
						{else}
						   <img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" />
						{/if}
						Seite 1/12
					    {if $announcement.browsing_parameters.browse_right != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$announcement.browsing_parameters.browse_right}"><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
						{else}
						   <img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" />
						{/if}
					    {if $announcement.browsing_parameters.browse_end != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$announcement.browsing_parameters.browse_end}"><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
						{else}
						   <img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" />
						{/if}
					</div>
				</div>

				<div class="clear"> </div>
			</div>

			<div class="clear"> </div>
		</div> <!-- Ende content_item -->
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