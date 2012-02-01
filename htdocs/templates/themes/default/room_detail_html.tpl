{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="___COMMON_LIST_PRINTVIEW___">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>
	
	{if $detail.actions.new}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid=NEW" title="___COMMON_NEW_ITEM___">
	    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
	    </a>
    {/if}
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

{block name=room_right_portlets prepend}
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
{/block}

{block  name=sidebar_tagbox_title}
	___COMMON_ATTACHED_TAGS___
{/block}

{block  name=sidebar_buzzwordbox_title}
	___COMMON_ATTACHED_BUZZWORDS___
{/block}

{*
{block name=room_detail_footer}
{/block}
*}