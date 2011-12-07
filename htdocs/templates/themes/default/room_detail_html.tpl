{extends file="room_html.tpl"}

{block name=room_site_actions}
{/block}

{block name=room_navigation_rubric_title}
	___COMMON_{$room.rubric|upper}_INDEX___
	<strong>___COMMON_{$room.rubric|upper}___ 2 ___COMMON_OF___ 226</strong>
{/block}

{block name="room_main_content"}
	<div id="content_with_actions"> <!-- Start content_with_actions -->
		<div class="content_item"> <!-- Start content_item -->
			{block name=room_detail_content}{/block}
		</div> <!-- Ende content_item -->
		{block name=room_detail_footer}{/block}
	</div> <!-- Ende content_with_actions -->
{/block}

{*
{block name=room_detail_footer}
{/block}
*}