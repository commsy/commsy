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

{block name=room_right_portlets prepend}
	<div class="portlet_rc">
		<h2 id="item_navigation">
			<a href=""><img src="{$basic.tpl_path}img/btn_ar_start2.gif" alt="Start" /></a>
			<a href=""><img src="{$basic.tpl_path}img/btn_ar_left2.gif" alt="zur&uuml;ck" /></a>
			<span>___COMMON_{$room.rubric|upper}_INDEX___</span>
			<a href=""><img src="{$basic.tpl_path}img/btn_ar_right2.gif" alt="weiter" /></a>
			<a href=""><img src="{$basic.tpl_path}img/btn_ar_end2.gif" alt="Ende" /></a>
		</h2>
		<div class="clear"> </div>
		
		<div id="dis_navigation">
			{block name=room_right_portlets_navigation}{/block}
		</div>
		<a href="" class="context_nav">zur Listenansicht</a>
	</div>
{/block}

{*
{block name=room_detail_footer}
{/block}
*}