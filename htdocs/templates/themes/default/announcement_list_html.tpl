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
{/block}


{block name=room_list_content}
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
						{foreach $item.assessment_array as $star_text}
							<img src="{$basic.tpl_path}img/star_{$star_text}.gif" alt="*" />
						{/foreach}
					</p>
				</div>
			</div>
			<div class="clear"> </div>
		</div> <!-- Ende Reihe -->
	{/foreach}
{/block}

{block name=room_right_portlets prepend}
    <div class="portlet_rc">
		<a href="" title="schlie&szlig;en" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>Einschr&auml;nkungen der Liste</h2>
		<div class="clear"> </div>
    	{foreach $list.perspective_rubric_entries as $rubric}
			<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
			<div class="portlet_rc_body">
				<div class="change_view">
					<form action="{$rubric.action}" method="get" name="{$rubric.name}_form">
						<input type="hidden" name="cid" value="{$environment.cid}"/>
						<input type="hidden" name="mod" value="{$environment.module}"/>
						<input type="hidden" name="fct" value="{$environment.function}"/>
						{foreach $rubric.hidden as $hidden_value}
						<input type="hidden" name="{$hidden_value.name}" value="{$hidden_value.value}"/>
						{/foreach}
						___COMMON_{$rubric.tag}_INDEX___
						<select name="sel{$rubric.name}" size="1" onChange="javascript:document.{$rubric.name}_form.submit()">
							<option value="0">*___COMMON_NO_SELECTION___</option>
   							<option class="disabled" disabled="disabled" value="-2">------------------------------</option>
    						{foreach $rubric.items as $item}
								<option value="{$item.id}"
									{if $item.id == $item.selected}
										selected="selected"
									{/if}
								>
									{$item.name}
								</option>
    						{/foreach}
   							<option class="disabled" disabled="disabled" value="-2">------------------------------</option>
							<option value="-1">*___COMMON_NOT_LINKED___</option>
						</select>
					</form>
				</div>
			</div>
    	{/foreach}
	</div>
{/block}
