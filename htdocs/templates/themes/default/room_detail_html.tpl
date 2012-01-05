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
	<strong>___COMMON_{$room.rubric|upper}___ {$detail.browsing_information.position} ___COMMON_OF___ {$detail.browsing_information.count_all}</strong>
{/block}

{block name="room_main_content"}
	<div id="content_with_actions"> <!-- Start content_with_actions -->
		<div class="content_item"> <!-- Start content_item -->
			{block name=room_detail_content}{/block}
		</div> <!-- Ende content_item -->
		{block name=room_detail_footer}{/block}
	</div> <!-- Ende content_with_actions -->
{/block}

{block name=room_right_portlets}
	<div class="portlet_rc">
		<h2 id="item_navigation">
		{if $detail.browsing_information.paging.first.active}
			<a href="commsy.php?cid={$environment.cid}&mod={$detail.browsing_information.paging.first.module}&fct={$environment.function}{params params=$detail.browsing_information.paging.first.params}">
				<img src="{$basic.tpl_path}img/btn_ar_start2.gif" alt="___COMMON_BROWSE_START_DESC___" />
			</a>
		{else}
			<img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="___COMMON_BROWSE_START_DESC___" />
		{/if}
		{if $detail.browsing_information.paging.prev.active}
			<a href="commsy.php?cid={$environment.cid}&mod={$detail.browsing_information.paging.prev.module}&fct={$environment.function}{params params=$detail.browsing_information.paging.prev.params}">
				<img src="{$basic.tpl_path}img/btn_ar_left2.gif" alt="___COMMON_BROWSE_LEFT_DESC___" />
			</a>
		{else}
			<img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="___COMMON_BROWSE_LEFT_DESC___" />
		{/if}
		
		<span>___COMMON_{$room.rubric|upper}_INDEX___</span>
		
		{if $detail.browsing_information.paging.next.active}
			<a href="commsy.php?cid={$environment.cid}&mod={$detail.browsing_information.paging.next.module}&fct={$environment.function}{params params=$detail.browsing_information.paging.next.params}">
				<img src="{$basic.tpl_path}img/btn_ar_right2.gif" alt="___COMMON_BROWSE_RIGHT_DESC___" />
			</a>
		{else}
			<img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="___COMMON_BROWSE_RIGHT_DESC___" />
		{/if}
		{if $detail.browsing_information.paging.last.active}
			<a href="commsy.php?cid={$environment.cid}&mod={$detail.browsing_information.paging.last.module}&fct={$environment.function}{params params=$detail.browsing_information.paging.last.params}">
				<img src="{$basic.tpl_path}img/btn_ar_end2.gif" alt="___COMMON_BROWSE_END_DESC___" />
			</a>
		{else}
			<img src="{$basic.tpl_path}img/btn_ar_end2.gif" alt="___COMMON_BROWSE_END_DESC___" />
		{/if}
		</h2>
		<div class="clear"> </div>
		
		<div id="dis_navigation">
			{block name=room_right_portlets_navigation}{/block}
		</div>
		
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&back_to_index=true" class="context_nav">___COMMON_BACK_TO_LIST___</a>
	</div>
	
	<div class="portlet_rc">
		<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>___COMMON_ATTACHED_BUZZWORDS___</h2>

		<div class="clear"> </div>
		<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		<div class="portlet_rc_body">
			{foreach $room.buzzwords as $buzzword}
				<a href="commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">
					{$buzzword.name}
				</a>
			{foreachelse}
				___COMMON_NONE___
			{/foreach}
		</div>
	</div>

	<div class="portlet_rc">
		<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>___COMMON_ATTACHED_TAGS___</h2>

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
							data="url:'commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&{$level}_seltag={$node.item_id}&seltag=yes'">{if $node.match}<b>{/if}{$node.title}{if $node.match}</b>{/if}
						{if $node.children|count > 0}	{* recursive call *}
							{tag_tree nodes=$node.children level=$level+1}
						{/if}
					{foreachelse}
						___COMMON_NONE___
					{/foreach}
					</ul>
				{/function}

				{* call function *}
				{tag_tree nodes=$room.tags}
			</div>
		</div>
	</div>
{/block}

{*
{block name=room_detail_footer}
{/block}
*}