{extends file="material_detail_html.tpl"}

{block name="site"}
	<div id="popup_wrapper">
		<div id="popup_stack">
			<div id="popup_frame">
				<div id="popup_inner" class="scrollPopup">

					<div id="popup_pagetitle">
						<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
						<a id="popup_detail_print" href="commsy.php?cid={$own.id}&mod=material&fct=detail&iid={$detail.item_id}&mode=print" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
							<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
						</a>
						<h2>___WIDGET_HEADER_DETAIL_VIEW___ - {$detail.content.title}</h2>
						<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						{block name=layout_content}
						{/block}
					</div>
				</div>


				<div class="clear"></div>
			</div>
		</div>
	</div>
{/block}

{block name=layout_content}
	<div id="maincontent">
       	{block name=room_main_content}
       		<div id="content_with_actions"> <!-- Start content_with_actions -->
				<div class="content_item"> <!-- Start content_item -->
					{block name=room_detail_content}{/block}
				</div> <!-- Ende content_item -->
				{*{block name=room_detail_footer}{/block}*}
			</div> <!-- Ende content_with_actions -->
    	{/block}
	</div>
{/block}