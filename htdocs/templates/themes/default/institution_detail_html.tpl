	{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a title="___COMMON_ACTION_EDIT___" class="edit {if $detail.is_action_bar_visible}item_actions_glow{/if}" data-custom="expand: 'edit_expand'" href="#"><span class="edit_set{if $detail.is_action_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_DETAILS___" class="detail  {if $detail.is_details_bar_visible}item_actions_glow{/if}" data-custom="expand: 'detail_expand'" href="#"><span class="details_ia{if $detail.is_details_bar_visible}_ok{/if}"> &nbsp; </span></a>
		</div>
	</div>

	<div class="item_body"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div id="edit_expand" {if !$detail.is_action_bar_visible}class="hidden"{/if}>
			<div class="fade_in_ground_actions">
				{* TODO: add missing actions *}
				{if $detail.actions.edit}
					<a id ="action_edit" class="open_popup" data-custom="iid: {$detail.item_id}, module: '{$environment.module}'" href="#">___COMMON_EDIT_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
				{/if}
				{if $detail.actions.delete}
					<a class="open_popup" data-custom="iid: {$detail.item_id}, module: 'delete', delType: 'institution'" href="#"}">___COMMON_DELETE_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span> |
				{/if}
				{if $detail.actions.member == 'member'}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&institution_option=2">___GROUP_LEAVE___</a> |
				{else}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&institution_option=1">___GROUP_ENTER___</a> |
				{/if}
				<a href="commsy.php?cid={$environment.cid}&mod=download&fct=action&iid={$detail.item_id}" target="_blank">___COMMON_DOWNLOAD___</a>

            {include file="include/detail_actions_plugins_html.tpl"}

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
				{if $detail.content.show_picture}
					<div id="group_profil_picture">
						<img alt="Portrait" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$detail.content.picture}" />
					</div>
				{/if}
				{if !empty($detail.content.description)}
					<div class="detail_description">
					{embed param1=$detail.content.description}
					</div>
				{/if}
				<div class="clear"> </div>


				<div class="detail_description">
					<h4>___GROUP_MEMBERS___</h4>
					{if !empty($detail.content.members)}
						<table class="no_padding">
							<tr>
								<td>
									<div class="group_member">
										{section name=members_col1 loop=$detail.content.members start=0 step=3}
											{$member=$detail.content.members[members_col1]}
											<div class="group_member_picture">
												{if !empty($member.picture)}
													<img class="group_member_picture" alt="" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$member.picture}" />
												{else}
													<img class="group_member_picture" alt="___USER_PICTURE_UPLOADFILE___" src="images/commsyicons/common/user_unknown.gif" title=""/>
												{/if}
											</div>
											<div>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.firstname}.' '.{$member.lastname}">{$member.firstname}<br/>{$member.lastname}</a>
											</div>
											<div class="clear"> </div>
										{/section}
									</div>
								</td>
								<td>
									<div class="group_member">
										{section name=members_col2 loop=$detail.content.members start=1 step=3}
											{$member=$detail.content.members[members_col2]}
											<div class="group_member_picture">
												{if !empty($member.picture)}
													<img class="group_member_picture" alt="" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$member.picture}" />
												{else}
													<img class="group_member_picture" alt="___USER_PICTURE_UPLOADFILE___" src="images/commsyicons/common/user_unknown.gif" title=""/>
												{/if}
											</div>
											<div>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.firstname}.' '.{$member.lastname}">{$member.firstname}<br/>{$member.lastname}</a>
											</div>
											<div class="clear"> </div>
										{/section}
									</div>
								</td>
								<td>
									<div class="group_member">
										{section name=members_col3 loop=$detail.content.members start=2 step=3}
											{$member=$detail.content.members[members_col3]}
											<div class="group_member_picture">
												{if !empty($member.picture)}
													<img class="group_member_picture" alt="" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$member.picture}" />
												{else}
													<img class="group_member_picture" alt="___USER_PICTURE_UPLOADFILE___" src="images/commsyicons/common/user_unknown.gif" title=""/>
												{/if}
											</div>
											<div>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.firstname}.' '.{$member.lastname}">{$member.firstname}<br/>{$member.lastname}</a>
											</div>
											<div class="clear"> </div>
										{/section}
									</div>
								</td>
							</tr>
						</table>
					{else}
						___COMMON_NONE___
					{/if}
				</div>
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