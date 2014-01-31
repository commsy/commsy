{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO: add missing actions *}
			{if $detail.actions.edit}
				<a id ="action_edit" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EDIT_ITEM___</a> |
			{/if}
			{if $detail.actions.delete}
				<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'delete', delType: 'topic'" href="#"">___COMMON_DELETE_ITEM___</a> |
			{/if}
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
			{if !empty($detail.content.description)}
				<div class="detail_description_print">
					{$detail.content.description}
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


		{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}
