{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a class="linked" href="#"><span class="ref_to_ia"> &nbsp; </span></a>
			<a class="detail" href="#"><span class="details_ia"> &nbsp; </span></a>
			{if $room.workflow}
				<a class="workflow" href="#"><span class="workflow_ia"> &nbsp; </span></a>
			{/if}
			<a class="annotations" href="#"><span class="ref_to_anno"> &nbsp; </span></a>
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
				{if $room.workflow}
					<div class="action_count linked_count_workflow" >{$item.linked_count}</div>
				{else}
					<div class="action_count linked_count" >{$item.linked_count}</div>
				{/if}
			{else}
			<div class="action_count linked_count" >&nbsp;</div>
			{/if}
		</div>
	</div>

	<div class="item_body"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO: add missing actions *}
			{if $detail.actions.edit}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_EDIT_ITEM___</a> |
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_DELETE_ITEM___</a> |
			{/if}
			{if $detail.actions.mail}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_EMAIL_TO___</a> |
			{/if}
			{if $detail.actions.copy}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_ITEM_COPY_TO_CLIPBOARD___</a> |
			{/if}
			{if $detail.actions.workflow_read}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&workflow_read=true">___ITEM_WORKFLOW_MARK_READ___</a> |
			{/if}
			{if $detail.actions.workflow_unread}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.item_id}&workflow_not_read=true">___ITEM_WORKFLOW_MARK_NOT_READ___</a> |
			{/if}
			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.item_id}">___COMMON_DOWNLOAD___</a>
		</div>
		<!-- Ende fade_in_ground -->

	    {include file="include/detail_linked_html.tpl"}

		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>


		<div id="item_credits">
			<p id="ic_rating">
				{if $room.workflow}
					<img class="workflow" src="{$basic.tpl_path}img/workflow_traffic_light_{$detail.content.workflow.light}.png" alt="{$detail.content.workflow.title}" title="{$detail.content.workflow.title}">
				{/if}
				{if $room.workflow && $room.assessment}
					&nbsp;&nbsp;
				{/if}
				{if $room.assessment}
					{include file="include/detail_assessment_include_html.tpl"}
				{/if}
			</p>
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
			<div class="clear"> </div>

		</div>

		<div id="item_legend"> <!-- Start item_legend -->
				{* formal data *}
				{if !empty($detail.content.formal)}
					<div class="detail_content">
						<table class="detail_content_table">
							{foreach $detail.content.formal as $formal}
								<tr>
									<td><h4>{$formal[0]}:</h4></td>
									<td>{$formal[1]}</td>
								</tr>
							{/foreach}

							{if $detail.content.sections}
								<tr>
									<td><h4>___MATERIAL_SECTIONS___:</h4></td>
									<td>
									{foreach $detail.content.sections as $section}
										{$section@iteration}. <a href="#section{$section.iid}">{$section.title}</a>
										{foreach $section.formal.files as $file}
											{$file.icon}
										{/foreach}
										{if !$section@last}
											<br/>
										{/if}
									{/foreach}
									</td>
								</tr>
							{/if}
						</table>
					</div>
				{/if}

				{if $detail.content.description}
				<div class="detail_content">
					<div class="detail_description">
						{$detail.content.description}
					</div>
				</div>
				{/if}
		</div> <!-- Ende item_legend -->
		{if $room.workflow}
		   {include file="include/detail_workflow_html.tpl" data=$detail.content.workflow}
		{/if}
		{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>


	{foreach $detail.content.sections as $section}
		<div class="item_actions">
			<a class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href="#"><span class="details_ia"> &nbsp; </span></a>
		</div>

		<div class="item_body"> <!-- Start item body -->
			<a name="mat_section_{$section@index}"></a>
			<a name="section{$section.iid}"></a>

			<!-- Start fade_in_ground -->
			<div class="fade_in_ground_actions hidden">
				actions
			</div>
			<!-- Ende fade_in_ground -->

			<div class="item_post">
				<div class="row_{if $section@iteration is odd}odd{else}even{/if}_no_hover padding_left_10px">
					<div class="column_655">
						<div class="post_content">
							<h4>
								{$section.title}

							{*{if $section.noticed == 'new' or $section.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*}
							</h4>
							<span>
							___COMMON_LAST_MODIFIED_BY_UPPER___
							{build_user_link status=$section.moredetails.last_modificator_status user_name=$section.moredetails.last_modificator id=$section.moredetails.last_modificator_id}
							___DATES_ON_DAY___  {$section.moredetails.last_modification_date}
							</span>
							{if !empty($section.formal)}
								<table>
									{if !empty($section.formal.files)}
										<tr>
											<td class="label"><h4>___MATERIAL_FILES___: </h4></td>
											<td>
												{foreach $section.formal.files as $file}
													{$file.name}
													{if !$file@last }
														<br/>
													{/if}
												{/foreach}
											</td>
										</tr>
									{/if}
								</table>

								<div class="clear"> </div>
							{/if}

							<div class="editor_content">
								{$section.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{if !$section@first}<a href="#mat_section_{$section@index - 1}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
							{if !$section@last}<a href="#mat_section_{$section@index + 1}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
						</p>
					</div>
					<div class="clear"> </div>
				</div>
			</div>
			{include file="include/detail_moredetails_html.tpl" data=$section.moredetails}

		</div> <!-- Ende item body -->
		<div class="clear"> </div>

	{/foreach}

	{include file='include/annotation_include_html.tpl'}

	<div class="clear"> </div>
{/block}

{include file='include/forward_information_include_html.tpl'}