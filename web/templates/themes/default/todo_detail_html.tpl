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
				{if $detail.actions.todo_leave}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.item_id}&todo_option=2" title="___TODO_LEAVE___">___TODO_LEAVE___</a> |
				{/if}

				{if $detail.actions.todo_participate}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.item_id}&todo_option=1" title="___TODO_ENTER___">___TODO_ENTER___</a> |
				{/if}
				{if $detail.actions.delete}
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'delete', delType: 'todo'" href="#">___COMMON_DELETE_ITEM___</a> |
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span> |
				{/if}
				{if $detail.actions.mail}
					<a class="popup_send" data-custom="iid: {$detail.item_id}, module: 'send'" href="#">___COMMON_EMAIL_TO___</a> |
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

		<div id="item_legend">
			<div class="detail_content">
				{* formal data *}
				<table class="detail_content_table">
					{if !empty($detail.content.formal.access)}
					{/if}
					<tr>
						<td class="label"><h4>___COMMON_RIGHTS___:</h4></td>
						<td>
							{$detail.content.formal.access}
						</td>
					</tr>
					<tr>
						<td class="label"><h4>___TODO_STATUS___:</h4></td>
						<td>
							{$detail.content.formal.status}
						</td>
					</tr>

					{if !empty($detail.content.formal.management)}
						{if !empty($detail.content.formal.management[0])}
							<tr>
								<td class="label"><h4>___TODO_MINUTES___:</h4></td>
								<td>
									{$detail.content.formal.management[0]}
								</td>
							</tr>
						{/if}
						{if !empty($detail.content.formal.management[1])}
							<tr>
								<td class="label"><h4>___TODO_DONE_MINUTES___:</h4></td>
								<td>
									{$detail.content.formal.management[1]}
								</td>
							</tr>
						{/if}
					{/if}

					<tr>
						<td class="label"><h4>___TODO_PROCESSORS___:</h4></td>
						<td>
							{if !empty($detail.content.formal.members)}
								{$detail.content.formal.members}
							{else}
								___TODO_NO_PROCESSOR___
							{/if}
						</td>
					</tr>

					{if !empty($detail.content.formal.files)}
						<tr>
							<td class="label"><h4>___MATERIAL_FILES___:</h4></td>
							<td>
								{$detail.content.formal.files}
							</td>
						</tr>
					{/if}

					<tr>
						<td class="label"><h4>___TODO_STEPS___:</h4></td>
						<td>
							{if $detail.content.steps}
								{foreach $detail.content.steps as $step}
									{$step@iteration}. <a href="#step{$step.item_id}">{$step.title}</a>
									{foreach $step.formal.files as $file}
										{$file.icon}
									{/foreach}
									{if !$step@last}
										<br/>
									{/if}
								{/foreach}
							{else}
								___TODO_NO_STEPS___
							{/if}
						</td>
					</tr>
				</table>
				<div class="clear"> </div>
			</div>
		</div>

		<div class="detail_content"> <!-- Start item_legend -->
			{if !empty($detail.content.description)}
				<div class="detail_description">
					{embed param1=$detail.content.description}
				</div>
			{/if}
		</div> <!-- Ende item_legend -->

		<div id="detail_expand" {if !$detail.is_details_bar_visible}class="hidden"{/if}>
			{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	{foreach $detail.content.steps as $step}
		<div class="item_actions">
			<a title="___COMMON_ACTION_EDIT___" class="edit" data-custom="expand: 'edit_expand_step_{$step.item_id}'" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_DETAILS___" class="detail" data-custom="expand: 'detail_expand_step_{$step.item_id}'" href="#"><span class="details_ia"> &nbsp; </span></a>
		</div>

		<div class="item_body"> <!-- Start item body -->
			<a name="step_article_{$step.item_id}"></a>
			<a name="step{$step.item_id}"></a>

			<!-- Start fade_in_ground -->
			<div id="edit_expand_step_{$step.item_id}" class="hidden">
				<div class="fade_in_ground_actions">
					{if $step.actions.edit}
						<a id="action_edit" class="open_popup" data-custom="iid: {$step.item_id}, module: 'step', ref_iid: {$detail.content.item_id}" href="#">___COMMON_EDIT_ITEM___</a> |
					{else}
						<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
					{/if}
					{if $step.actions.delete}
						<a class="open_popup" data-custom="iid: {$step.item_id}, module: 'delete', delType: 'step'" href="#">___COMMON_DELETE_ITEM___</a>
					{else}
						<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span>
					{/if}
				</div>
			</div>
			<!-- Ende fade_in_ground -->

			<div class="item_post">
				<div class="row_{if $step@iteration is odd}odd{else}even{/if}_no_hover ">
					<div class="column_80">
						<p>
							<a href="" title="{$step.linktext}">
								{if !empty($step.picture)}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$step.picture}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.creator}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.creator}" />
								{/if}
							</a>
						</p>
					</div>

					<div class="column_585">
						<div class="post_content">
							<h4>{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$step.title}
							</h4>

							<span>
							___COMMON_LAST_MODIFIED_BY_UPPER___
							{build_user_link status=$step.moredetails.last_modificator_status user_name=$step.moredetails.last_modificator id=$step.moredetails.last_modificator_id}
							___DATES_ON_DAY___  {$step.moredetails.last_modification_date}
							</span>

							{if !empty($step.formal)}
								<table>
									{if !empty($step.formal.time)}
										<tr>
											<td class="label"><h4>___TODO_DONE_MINUTES___: </h4></td>
											<td>
												{$step.formal.time}
											</td>
										</tr>
									{/if}

									{if !empty($step.formal.files)}
										<tr>
											<td class="label"><h4>___MATERIAL_FILES___: </h4></td>
											<td>
												{foreach $step.formal.files as $file}
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
								{$step.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{if !$step@first}<a href="#step_article_{$detail.content.steps[$step@index - 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
							{if !$step@last}<a href="#step_article_{$detail.content.steps[$step@index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
						</p>
					</div>
					<div class="clear"> </div>
				</div>
			</div>

			<div id="detail_expand_step_{$step.item_id}" class="hidden">
				{include file="include/detail_moredetails_html.tpl" data=$step.moredetails}
			</div>

		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	{/foreach}
	
	{if !$environment.is_read_only}
		<div class="item_actions">&nbsp;</div>
	
		<div class="item_body"> <!-- Start item body -->
			<div class="item_post">
				<div id="item_postnew">
					<div class="column_80">
						<p>
							<a href="" title="{$environment.username}">
								{if $environment.user_picture != ''}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.user_picture}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$article.creator}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$article.creator}" />
								{/if}
							</a>
						</p>
					</div>
	
					<div class="column_590">
						{if isset($popup.overflow) && $popup.overflow}
							<input class="open_popup" type="submit" data-custom="module: 'step', iid: 'NEW', ref_iid: {$detail.item_id}" value="___COMMON_NEW_STEP_EDIT___" />
						{else}
							<a name="step_new"></a>
							<form action="commsy.php?cid={$environment.cid}&mod=step&fct=edit" method="post" enctype="multipart/form-data">
								<div class="post_content">
									<h4>{$step@total + 1}. </h4>
									<input type="hidden" value="NEW" name="iid"/>
									<input type="hidden" value="{$detail.content.item_id}" name="todo_id"/>
									<input type="hidden" value="" name="ref_position"/>
									<input id="pn_title" type="text" name="form_data[title]"{if $detail.exception == "step"} class="missing"{/if}/> <br>
									___STEP_MINUTES___: <input type="text" size="4" name="form_data[minutes]" />
									<select size="1" name="form_data[time_type]">
										<option value="1">___TODO_TIME_MINUTES___</option>
										<option value="2">___TODO_TIME_HOURS___</option>
										<option value="3">___TODO_TIME_DAYS___</option>
									</select>
	
									<div class="editor_content">
										<div id="ckeditor_step" class="ckeditor">
											{if isset($detail.step_description)}
												{$detail.step_description}
											{/if}
										</div>
									</div>
									
									{*
									<div id="files_finished"></div>
				
									<div id="files_attached">
										{foreach $item.files as $file}
											<input type="checkbox" checked="checked" name="form_data[file_{$file@index}]" value="{$file.file_id}" />{$file.file_name}<br/>
										{/foreach}
									</div>
									
									<div class="uploader">
										   <input class="fileSelector"></input>
										   
										   <div class="fileList"></div>
									</div>
									*}
	
									<input class="popup_button" style="margin-bottom:10px;" type="submit" id="disc_article_submit" name="form_data[option][new]" value="___COMMON_NEW_STEP_EDIT___" />
								</div>
							</form>
						{/if}
					</div>
					<div class="clear"> </div>
				</div>
			</div>
		</div> <!-- Ende item body -->
	{/if}
	
	<div class="clear"> </div>
	
	{include file='include/annotation_include_html.tpl'}

	<div class="clear"> </div>
{/block}
