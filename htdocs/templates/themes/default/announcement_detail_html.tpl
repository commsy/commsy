{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<a class="edit" href=""><span class="edit_set"> &nbsp; </span></a>
		<a class="linked" href=""><span class="ref_to_ia"> &nbsp; </span></a>
		<a class="detail" href=""><span class="details_ia"> &nbsp; </span></a>
		<a class="annotations" href="#"><span class="edit_set"> &nbsp; </span></a>
	</div>

	<div class="item_body"> <!-- Start item body -->
		<div class="edit_overlay">
			{if $detail.actions.edit}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EDIT_ITEM___</a></br>
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&action=delete&iid={$detail.content.item_id}">___COMMON_DELETE_ITEM___</a></br>
			{/if}
			{if $detail.actions.mail}
				<a href="commsy.php?cid={$environment.cid}&mod=rubric&fct=mail&iid={$detail.content.item_id}">___COMMON_EMAIL_TO___</a></br>
			{/if}
			{if $detail.actions.copy}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}&add_to_discussion_clipboard={$detail.content.item_id}">___COMMON_ITEM_COPY_TO_CLIPBOARD___</a></br>
			{/if}
			{if $detail.actions.new}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_NEW_ITEM___</a></br>
			{/if}
			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DOWNLOAD___</a>
		</div>
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p id="ic_rating">
				{foreach $detail.assessment as $assessment}
					<img src="{$basic.tpl_path}img/star_{$assessment}.gif" alt="*" />
				{/foreach}
			</p>
			<p>
				___COMMON_CREATED_BY_UPPER___ <a href="">{$detail.content.creator}</a> ___DATES_ON_DAY___  {$detail.content.creation_date}
			</p>
			<div class="clear"> </div>
		</div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
				{if !empty($detail.content.description)}
					<div class="detail_description">
						{$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	{include file='include/annotation_include_html.tpl'}

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}