{extends file="room_print.tpl"}

{block name=room_site_actions}
	<a href="" title="___COMMON_LIST_PRINTVIEW___">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>

    {if $index.actions.new}
		<a id="create_new" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid=NEW" title="___COMMON_NEW_ITEM___">
	    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
	    </a>
    {/if}
{/block}

{block name=room_navigation_rubric_title}
	___COMMON_{$room.rubric|upper}_INDEX___
	<span>(___COMMON_ENTRIES___: {$list.page_text_fragments.count_entries})</span>
{/block}

{block name=header_content_print}
	{*<div><h2>{$environment.room_title}</h2></div>*}
	<br>
	<div> <h4>___COMMON_RESTRICTIONS___</h4></div>
	{foreach $list.restriction_text_parameters as $params}
		{$params.name},
	{/foreach}
	<br>
	<br>
{/block}

{block name=room_main_content}
	<div id="main_navigation_print"><h1>{block name=room_navigation_rubric_title}{/block}</h1></div>
	<div id="full_width_content">
		<form action="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.all}" method="post">
			<input type="hidden" name="option" value="___COMMON_LIST_ACTION_BUTTON_GO___">
			<div class="content_item"> <!-- Start content_item -->
				{block name=room_list_content}{/block}
			</div> <!-- Ende content_item -->
			{block name=room_list_footer}{/block}
		</form>
	</div>
{/block}

{block name=room_list_footer}
	<div class="content_item"> <!-- Start content_item -->
		
	</div> <!-- Ende content_item -->
	<div class="content_item"> <!-- Start content_item -->
		<div class="item_info print_info_border">
			<div class="ii_left">
				<p>___COMMON_PAGE_ENTRIES___
					{if $list.list_entries_parameter.20 == 'disabled'}
						<strong>20</strong>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.20}">20</a>
					{/if}
					|
					{if $list.list_entries_parameter.50 == 'disabled'}
						<strong>50</strong>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.50}">50</a>
					{/if}
					|
					{if $list.list_entries_parameter.all == 'disabled'}
						<strong>___COMMON_ALL_ENTRIES___</strong>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.all}">___COMMON_ALL_ENTRIES___</a>
					{/if}
				</p>
			</div>
			<div class="ii_right_print">
				<div id="item_navigation">
				    {if $list.browsing_parameters.browse_start != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_start}"><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
					{/if}
				    {if $list.browsing_parameters.browse_left != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_left}"><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
					{/if}
					___COMMON_PAGE___ {$list.browsing_parameters.actual_page_number} / {$list.browsing_parameters.page_numbers}
				    {if $list.browsing_parameters.browse_right != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_right}"><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
					{/if}
				    {if $list.browsing_parameters.browse_end != "disabled"}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_end}"><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
					{else}
					   <a><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
					{/if}
				</div>
			</div>
			<div class="clear"> </div>
		</div>
		<div class="clear"> </div>
	</div> <!-- Ende content_item -->
{/block}



{block name=room_right_portlets prepend}
	{if $list.restriction_text_parameters}
	    <div class="portlet_rc">
			<h2>___COMMON_RESTRICTIONS___</h2>
			<div class="clear"> </div>
			<div class="portlet_rc_body">
				{foreach $list.restriction_text_parameters as $restriction}
					<span class="restriction" title="{$restriction.name}">{$restriction.name|truncate:25:'...':true}
				   		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$restriction.link_parameter}"><img src="{$basic.tpl_path}img/cross.gif" alt="x" border="0"/></a>
				   	</span>
				{/foreach}
			</div>
		</div>
	{/if}
    {if $list.perspective_rubric_entries && $environment.module != 'group'}
	    <div class="portlet_rc">
<!--
			<a href="" title="___COMMON_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="___COMMON_CLOSE___" /></a>
-->
			<h2>___COMMON_REFERENCED_ENTRIES___</h2>
			<div class="clear"> </div>
				<div class="portlet_rc_body">
	    		{foreach $list.perspective_rubric_entries as $rubric}
					<div class="change_view">
						<form action="{$rubric.action}" method="get" name="{$rubric.name}_form">
							<input type="hidden" name="cid" value="{$environment.cid}"/>
							<input type="hidden" name="mod" value="{$environment.module}"/>
							<input type="hidden" name="fct" value="{$environment.function}"/>
							{foreach $rubric.hidden as $hidden_value}
							<input type="hidden" name="{$hidden_value.name}" value="{$hidden_value.value}"/>
							{/foreach}

							{if isset($rubric.custom) && $rubric.custom == true}
								{$rubric.tag}
							{else}
								___COMMON_{$rubric.tag}_INDEX___
							{/if}

							<select name="sel{$rubric.name}" size="1" onChange="javascript:document.{$rubric.name}_form.submit()">
								{if !isset($rubric.custom) || $rubric.custom != true}
									<option value="0">*___COMMON_NO_SELECTION___</option>
									<option class="disabled" disabled="disabled" value="-2">------------------------------------------------------</option>
								{/if}
								{foreach $rubric.items as $item}
									<option
										{if isset($item.disabled) && $item.disabled == true}
											class="disabled" disabled="disabled"
										{/if}

										value="{$item.id}"
										{if $item.id == $item.selected}
											selected="selected"
										{/if}
									>
										{$item.name}
									</option>
								{/foreach}
								{if !isset($rubric.custom) || $rubric.custom != true}
									<option class="disabled" disabled="disabled" value="-2">------------------------------------------------------</option>
									<option value="-1">*___COMMON_NOT_LINKED___</option>
								{/if}
							</select>
						</form>
					</div>
	    		{/foreach}
			</div>
		</div>
	{/if}
{/block}