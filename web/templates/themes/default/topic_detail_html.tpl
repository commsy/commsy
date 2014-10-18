{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a title="___COMMON_ACTION_EDIT___" class="edit {if $detail.is_action_bar_visible}item_actions_glow{/if}" data-custom="expand: 'edit_expand'" href="#"><span class="edit_set{if $detail.is_action_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_DETAILS___" class="detail {if $detail.is_details_bar_visible}item_actions_glow{/if}" data-custom="expand: 'detail_expand'" href="#"><span class="details_ia{if $detail.is_details_bar_visible}_ok{/if}"> &nbsp; </span></a>
		</div>
	</div>

	<div class="item_body"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<div id="edit_expand" {if !$detail.is_action_bar_visible}class="hidden"{/if}>
			<div class="fade_in_ground_actions">
				{* TODO: add missing actions *}
				{if $detail.actions.edit}
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: '{$environment.module}'" href="#">___COMMON_EDIT_ITEM___</a> |
				{else}
					{if $detail.actions.locked}
						<img id="edit_attention" class="tooltip_toggle" src="{$basic.tpl_path}img/attention.gif" />
						<div class="tooltip">
							<div class="tooltip_inner">
								<div class="tooltip_title">
									<div class="header">___ITEM_LOCKING_TITLE___</div>
								</div>
								<div class="tooltip_content">
									<span class="content">{i18n tag=ITEM_LOCKING_DESC param1=$detail.actions.locked_user_name param2=$detail.actions.locked_date}</span>
								</div>
							</div>
						</div>
					{/if}
					
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
				{/if}
				{if $detail.actions.delete}
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'delete', delType: 'topic'" href="#">___COMMON_DELETE_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span> |
				{/if}
				<a href="commsy.php?cid={$environment.cid}&mod=download&fct=action&iid={$detail.content.item_id}" target="_blank">___COMMON_DOWNLOAD___</a>
            
            {include file="include/detail_actions_plugins_html.tpl"}
            
			</div>
		</div>
		<!-- Ende fade_in_ground -->

		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
		</div>


		<div class="detail_content"> <!-- Start item_legend -->
			{if !empty($detail.content.formal)}
				<table class="detail_content_table">
					{foreach $detail.content.formal as $formal}
						<tr>
							<td><h4>{$formal[0]}:</h4></td>
							<td>{$formal[1]}</td>
						</tr>
					{/foreach}
				</table>
			{/if}
			{if !empty($detail.content.description)}
				<div class="detail_description">
					{embed param1=$detail.content.description}
				</div>
			{/if}
				{* files *}
				{if !empty($detail.content.files)}
				<table>
					<tr>
						<td class="label"><h4>___MATERIAL_FILES___</h4></td>
						<td>
							{foreach $detail.content.files as $file}
								{$file}
							{/foreach}
						</td>
					</tr>
				</table>
				{/if}

				{if $detail.content.path_shown}
					<div class="padding_left_bottom_10px">
						<h4>___TOPIC_PATH___</h4>
						<ul>
							{foreach $detail.content.path_items as $item}
								<li class="no_style">
									{$item@iteration}.
									{if $item.not_activated}
										<a href="commsy.php?cid={$environment.cid}&mod={$item.mod}&fct=detail&iid={$item.iid}&path={$detail.content.item_id}" title="{$item.title}">{$item.link_text}</a>
									{else}
										<a href="commsy.php?cid={$environment.cid}&mod={$item.mod}&fct=detail&iid={$item.iid}&path={$detail.content.item_id}" title="{$item.type} - {$item.title}">{$item.title}</a>
									{/if}
								</li>
							{/foreach}
						</ul>
					</div>
				{/if}
		</div>
		<div class="clear"> </div>

		<div id="detail_expand" {if !$detail.is_details_bar_visible}class="hidden"{/if}>
			{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}
