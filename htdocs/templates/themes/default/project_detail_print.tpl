{extends file="room_detail_print.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href=""><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href=""><span class="details_ia"> &nbsp; </span></a>
		</div>
	</div>

	<div class="item_body_print"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO: add missing actions *}
			{if $detail.actions.edit}
				<a id="action_edit" href="">___COMMON_EDIT_ITEM___</a> |
			{else}
				<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_DELETE_ITEM___</a> |
			{else}
				<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span> |
			{/if}
			{if $detail.actions.mail}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___EMAIL_CONTACT_MODERATOR_TEXT___</a> |
			{/if}
			{if $detail.content.room_user_status == 'closed'}
				<a id="action_member" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&account=member">___CONTEXT_JOIN___</a>
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
						{$detail.content.description}
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
		{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a title="{$entry.title}" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title|truncate:25:'...':true}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}