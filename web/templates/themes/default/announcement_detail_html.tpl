{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a title="___COMMON_ACTION_EDIT___" class="edit {if $detail.is_action_bar_visible}item_actions_glow{/if}" data-custom="expand: 'edit_expand'" href="#"><span class="edit_set{if $detail.is_action_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_LINKED___" class="linked {if $detail.is_reference_bar_visible}item_actions_glow{/if}" data-custom="expand: 'linked_expand'" href="#"><span class="ref_to_ia{if $detail.is_reference_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_DETAILS___" class="detail {if $detail.is_details_bar_visible}item_actions_glow{/if}" data-custom="expand: 'detail_expand'" href="#"><span class="details_ia{if $detail.is_details_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_ANNOTATIONS___" class="annotations  {if $detail.is_annotations_bar_visible}item_actions_glow{/if}" data-custom="expand: 'annotations_expand'" href="#"><span class="ref_to_anno{if $detail.is_annotations_bar_visible}_ok{/if}"> &nbsp; </span></a>
			{if $detail.annotations|@count}
			<div class="action_count anno_count" >{$detail.annotations|@count}
			</div>
			{if $detail.annotations_changed == 'new'}
					<img title="*" class="new_item_detail_annotation" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" />
			{elseif $detail.annotations_changed == 'changed'}
					<img title="*" class="new_item_detail_annotation" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" />
			{else}
					<img title="*" class="new_item_detail_annotation" src="{$basic.tpl_path}img/spacer.gif" alt="*" />
			{/if}
			{else}
			<div class="action_count anno_count" >&nbsp;</div>
			<img title="*" class="new_item_detail_annotation" src="{$basic.tpl_path}img/spacer.gif" alt="*" />
			{/if}
			{if $item.linked_count}
			<div class="action_count linked_count" >{$item.linked_count}</div>
			{else}
			<div class="action_count linked_count" >&nbsp;</div>
			{/if}
		</div>
	</div>

	<div class="item_body"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div id="edit_expand" {if !$detail.is_action_bar_visible}class="hidden"{/if}>
			<div class="fade_in_ground_actions">
				{if $detail.actions.edit}
					<a id="action_edit" class="open_popup" data-custom="iid: {$detail.content.item_id}, module: '{$environment.module}'" href="#">___COMMON_EDIT_ITEM___</a> |
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
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'delete', delType: 'announcement'" href="#">___COMMON_DELETE_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span> |
				{/if}
				{if $detail.actions.mail}
					<a class="popup_send" data-custom="iid: {$detail.content.item_id}, module: 'send'" href="#">___COMMON_EMAIL_TO___</a> |
				{/if}
				{if $detail.actions.copy}
					<a class="ajax_action" data-custom="iid: {$detail.content.item_id}, action: 'addToClipboard'" href="#">___COMMON_ITEM_COPY_TO_CLIPBOARD___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_ITEM_COPY_TO_CLIPBOARD___</span> |
				{/if}
				<a href="commsy.php?cid={$environment.cid}&mod=download&fct=action&iid={$detail.content.item_id}" target="_blank">___COMMON_DOWNLOAD___</a>
				
				{include file="include/detail_actions_plugins_html.tpl"}
				
			</div>
		</div>
		<!-- Ende fade_in_ground -->

	    {include file="include/detail_linked_html.tpl"}

		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<div id="ic_rating">
				{if $room.assessment}
					{include file="include/detail_assessment_include_html.tpl"}
				{/if}
			</div>
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
			<div class="clear"> </div>
		</div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="detail_content">
				{* formal data *}
				<table class="detail_content_table">
					{if !empty($detail.content.formal)}
						{foreach $detail.content.formal as $formal}
							<tr>
								<td><h4>{$formal[0]}:</h4></td>
								<td>{$formal[1]}</td>
							</tr>
						{/foreach}
					{/if}
					{if !empty($detail.content.files)}
						<tr>
							<td class="label"><h4>___MATERIAL_FILES___</h4></td>
							<td>
								{foreach $detail.content.files as $file}
									{$file}
								{/foreach}
							</td>
						</tr>
					{/if}
				</table>
				{if !empty($detail.content.description)}
					<div class="detail_description">
						{embed param1=$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->

		<div id="detail_expand" {if !$detail.is_details_bar_visible}class="hidden"{/if}>
			{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		</div>
	</div> <!-- Ende item body -->
	<div class="clear"> </div>


	{include file='include/annotation_include_html.tpl'}

	<div class="clear"> </div>
{/block}
