{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="___COMMON_LIST_PRINTVIEW___">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>
    <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid=NEW" title="___COMMON_NEW_ITEM___">
    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
    </a>
{/block}

{block name=room_navigation_rubric_title}
	___COMMON_{$room.rubric|upper}_INDEX___
	<span>(___COMMON_ENTRIES___: {$list.page_text_fragments.count_entries})</span>
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		<form action="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.all}" method="post">
			<input type="hidden" name="option" value="___COMMON_LIST_ACTION_BUTTON_GO___">
			<div class="content_item"> <!-- Start content_item -->
				{block name=room_list_header}{/block}
				{block name=room_list_content}{/block}
			</div> <!-- Ende content_item -->
			{block name=room_list_footer}{/block}
		</form>
	</div>
{/block}

{block name=room_list_footer}
	<div class="content_item"> <!-- Start content_item -->
		<div class="item_info">
			<div class="ii_left">
			 	<div id="item_action">
			 		<input type="checkbox" name="" value="" /> ___ALL___
			 		<select name="index_view_action" size="1">
			 			{foreach $list.actions as $action}
			 				{if $action.disabled}
			 					<option class="disabled" disabled="disabled">{$action.display}</option>
			 				{else}
			 					<option{if $action.id} id="{$action.id}"{/if}{if $action.selected} selected="selected"{/if} value="{$action.value}">{$action.display}</option>
			 				{/if}

			 			{/foreach}
				 	</select>
					<input type="image" id="delete_confirmselect_option" name="option" src="{$basic.tpl_path}img/btn_go.gif" alt="___COMMON_LIST_ACTION_BUTTON_GO___" />
				 </div>
			</div>
				<div class="ii_right">
				<p>{i18n tag=COMMON_SELECTED param1='<span id="selected_items"></span>'}</p>
			</div>
				<div class="clear"> </div>
		</div>
	</div> <!-- Ende content_item -->
	<div class="content_item"> <!-- Start content_item -->
		<div class="item_info">
			<div class="ii_left">
				<p>___COMMON_PAGE_ENTRIES___
					{if $list.list_entries_parameter.20 == 'disabled'}
						<a href=""><strong>20</strong></a>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.20}">20</a>
					{/if}
					|
					{if $list.list_entries_parameter.50 == 'disabled'}
						<a href=""><strong>50</strong></a>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.50}">50</a>
					{/if}
					|
					{if $list.list_entries_parameter.all == 'disabled'}
						<a href=""><strong>___COMMON_ALL_ENTRIES___</strong></a>
					{else}
					   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.all}">___COMMON_ALL_ENTRIES___</a>
					{/if}
				</p>
			</div>
			<div class="ii_right">
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



{block name=room_right_portlets}
	{if $list.restriction_text_parameters}
    <div class="portlet_rc">
		<h2>___COMMON_RESTRICTIONS___</h2>
		<div class="clear"> </div>
		<div class="portlet_rc_body">
			{foreach $list.restriction_text_parameters as $restriction}
				{$restriction.name}
			   	<span style="padding-right:8px;">
			   		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$restriction.link_parameter}"><img src="{$basic.tpl_path}img/cross.gif" alt="x" border="0"/></a>
			   	</span>
			{/foreach}
		</div>
	</div>
	{/if}
    {if $list.perspective_rubric_entries}
    <div class="portlet_rc">
		<a href="" title="___COMMON_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="___COMMON_CLOSE___" /></a>
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
    		{/foreach}
		</div>
	</div>
	{/if}
	<div class="portlet_rc">
		<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>___COMMON_BUZZWORD_BOX___</h2>
		<div class="clear"> </div>
		<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		<div class="portlet_rc_body">
			{foreach $room.buzzwords as $buzzword}
				{if $buzzword.to_item_id != $buzzword.selected_id}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&{$list.restriction_buzzword_link_parameters}&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
				{else}
					<a href="" class="keywords_s{$buzzword.class_id}" style="font-weight:bold;">{$buzzword.name}</a>
				{/if}
			{/foreach}
		</div>
	</div>

	<div class="portlet_rc">
		<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>___COMMON_TAG_BOX___</h2>
		<div class="clear"> </div>
		<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		<div class="portlet_rc_body">
			<div id="tag_tree">
				{* Tags Function *}
				{function name=tag_tree level=0}
					<ul>
						{foreach $nodes as $node}
							<li	id="node_{$node.item_id}"
								{if $node.children|count > 0}class="folder"{/if}
								data="url:'commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.restriction_tag_link_parameters}&seltag_{$level}={$node.item_id}&seltag=yes'">{$node.title}
								{if $node.children|count > 0}	{* recursive call *}
									{tag_tree nodes=$node.children level=$level+1}
								{/if}
						{/foreach}
					</ul>
				{/function}
				{* call function *}
				{tag_tree nodes=$room.tags}
			</div>
		</div>
	</div>

{/block}

