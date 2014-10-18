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
					<a id="action_edit" class="open_popup" data-custom="iid: {$detail.content.item_id}, module: '{$environment.module}'" href="#">___COMMON_EDIT_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
				{/if}
				{if $detail.actions.delete}
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'delete', delType: 'project'" href="#">___COMMON_DELETE_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span> |
				{/if}
				{if $detail.actions.mail}
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'mailtomod', mailType:'project'" href="#">___EMAIL_CONTACT_MODERATOR_TEXT___</a> |
				{/if}

		{if $detail.content.room_user_status == 'closed'}
		{*			<a id="action_member" class="open_popup" data-custom="iid: {$detail.item_id}, module: 'join'" href="#">___CONTEXT_JOIN___</a>
		*}
					<!-- <a href="commsy.php?cid={$environment.pid}&mod=home&fct=index&room_id={$detail.item_id}&account=member">___CONTEXT_JOIN___</a> -->
					<a class="open_popup" data-custom="iid: {$detail.item_id}, module: 'userContextJoin'" href="#">___CONTEXT_JOIN___</a>
				{elseif $detail.content.room_user_status == 'requested'}
					<span title="___ACCOUNT_NOT_ACCEPTED_YET___" class="disabled_action">___CONTEXT_JOIN___</span>
				{elseif $detail.content.room_user_status == 'rejected'}
					<span title="___ACCOUNT_NOT_ACCEPTED___" class="disabled_action">___CONTEXT_JOIN___</span>
				{elseif $detail.content.room_user_status == 'guest'}
					<span title="___ACCOUNT_NOT_ACCEPTED_AS_GUEST___" class="disabled_action">___CONTEXT_JOIN___</span>
				{elseif $detail.content.room_user_status == 'open'}
					<span title="___ACCOUNT_GET_MEMBERSHIP_ALREADY___" class="disabled_action">___CONTEXT_JOIN___</span>
				{/if}

			</div>
		</div>
		<!-- Ende fade_in_ground -->

		<h2>
			{$detail.content.title}
		</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
		</div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
				<div id="project_door_picture">
					{if $detail.content.room_user_status == 'open'}
						<a href="commsy.php?cid={$detail.item_id}&mod=home&fct=index">
							<img alt="" src="{$basic.tpl_path}/img/door_open_large.gif" />
						</a>
					{else}
						<img alt="" src="{$basic.tpl_path}/img/door_closed_large.gif" />
					{/if}
				</div>
				{if !empty($detail.content.description)}
					<div class="detail_description">
						{embed param1=$detail.content.description}
						<h4 style="margin-top:20px;">___USER_STATUS_CONTACT___</h4>
						{if !empty($detail.content.moderator_array)}
							<ul>
								{foreach $detail.content.moderator_array as $moderator}
									<li>
										{$moderator.name}
									</li>
								{/foreach}
							</ul>
						{/if}
					</div>
				{/if}
				<div class="clear"> </div>
			</div>
		</div> <!-- Ende item_legend -->

		<div id="detail_expand" {if !$detail.is_details_bar_visible}class="hidden"{/if}>
			{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a title="{$entry.title}" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title|truncate:25:'...':true}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}