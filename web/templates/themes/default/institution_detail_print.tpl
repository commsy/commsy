{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO: add missing actions *}
			{if $detail.actions.edit}
				<a id ="action_edit" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_EDIT_ITEM___</a> |
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DELETE_ITEM___</a> |
			{/if}
			{if $detail.actions.member == 'member'}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&institution_option=2">___GROUP_LEAVE___</a> |
			{else}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&institution_option=1">___GROUP_ENTER___</a> |
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
				{if $detail.content.show_picture}
					<div id="group_profil_picture">
						<img alt="Portrait" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$detail.content.picture}" />
					</div>
				{/if}
				{if !empty($detail.content.description)}
					<div class="detail_description_print">
					{embed param1=$detail.content.description}
					</div>
				{/if}
				<div class="clear"> </div>


				<div class="detail_description_print">
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
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}
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