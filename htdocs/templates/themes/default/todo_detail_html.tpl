{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a class="linked" href="#"><span class="ref_to_ia"> &nbsp; </span></a>
			<a class="detail" href="#"><span class="details_ia"> &nbsp; </span></a>
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
		<div class="fade_in_ground_actions hidden">
			{if $detail.actions.edit}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EDIT_ITEM___</a> |
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DELETE_ITEM___</a> |
			{/if}
			{if $detail.actions.mail}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EMAIL_TO___</a> |
			{/if}
			{if $detail.actions.copy}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_ITEM_COPY_TO_CLIPBOARD___</a> |
			{/if}
			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DOWNLOAD___</a>
		</div>
		<!-- Ende fade_in_ground -->

		{include file="include/detail_linked_html.tpl"}

		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p id="ic_rating">
			</p>
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
					<tr>
						<td class="label"><h4>___TODO_VALIDITY_DATE___:</h4></td>
						<td>
							{if $detail.content.formal.date == 'no_end'}
								___TODO_NO_END_DATE_LONG___
							{else}
								{$detail.content.formal.date}
							{/if}
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
							{if !empty($detail.content.formal.steps)}
								{$detail.content.formal.steps}
							{else}
								___TODO_NO_STEPS___
							{/if}
						</td>
					</tr>
				</table>
				<div class="clear"> </div>
			</div>
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
		{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	{foreach $detail.content.steps as $step}
		<div class="item_actions">
			<a class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href="#"><span class="details_ia"> &nbsp; </span></a>
		</div>

		<div class="item_body"> <!-- Start item body -->
			<a name="step_article_{$step.item_id}"></a>
			<a name="step{$step.item_id}"></a>

			<!-- Start fade_in_ground -->
			<div class="fade_in_ground_actions hidden">
				actions
			</div>
			<!-- Ende fade_in_ground -->

			<div class="item_post">
				<div class="row_{if $step@iteration is odd}odd{else}even{/if}_no_hover {if $step@iteration is odd}odd{else}even{/if}_sep_disdetail">
					<div class="column_80">
						<p>
							<a href="" title="{$step.linktext}">
								{if !empty($step.picture)}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$step.picture}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.image.linktext}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.image.linktext}" />
								{/if}
							</a>
						</p>
					</div>

					<div class="column_510">
						<div class="post_content">
							<h4>{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$step.title}
							</h4>
							{if !empty($step.formal)}
								<table>
									{if !empty($step.formal.time)}
										<tr>
											<td class="label"><h4>___TODO_DONE_MINUTES___</h4></td>
											<td>
												{$step.formal.time}
											</td>
										</tr>
									{/if}

									{if !empty($step.formal.files)}
										<tr>
											<td class="label"><h4>___MATERIAL_FILES___</h4></td>
											<td>
												{foreach $step.formal.files as $file}
													{$file.name}
													{if !$file.last }
														<br/>
													{/if}
												{/foreach}
											</td>
										</tr>
									{/if}
								</table>

								<div class="clear"> </div>
							{/if}

							<span><a href="">{*{$article.creator}*}</a>{*	, {$article.modification_date}*}</span>
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
					<div class="column_45">
						<p>
							<a href="" class="attachment">{$step.num_files}</a>
						</p>
					</div>
					<div class="clear"> </div>
				</div>
			</div>
			{include file="include/detail_moredetails_html.tpl" data=$step.moredetails}

		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	{/foreach}

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
					<form action="commsy.php?cid={$environment.cid}&mod=step&fct=edit" method="post">
						<div class="post_content">
							<h4>{$step@total + 1}. </h4>
							<input type="hidden" value="" name="iid"/>
							<input type="hidden" value="{$detail.content.item_id}" name="todo_id"/>
							<input type="hidden" value="" name="ref_position"/>
							<input id="pn_title" type="text" name="form_data[title]" /> <br>
							___STEP_MINUTES___: <input type="text" size="4" name="form_data[minutes]" />
							<select size="1" name="form_data[time_type">
								<option value="1">___TODO_TIME_MINUTES___</option>
								<option value="2">___TODO_TIME_HOURS___</option>
								<option value="3">___TODO_TIME_DAYS___</option>
							</select>

							<div class="editor_content">
								<div id="ckeditor_second"></div>
								<input type="hidden" id="ckeditor_content_second" name="form_data[description]" value=""/>
								<input type="image" id="disc_article_submit" name="form_data[option][new]" src="{$basic.tpl_path}img/btn_go.gif" alt="___DISCARTICLE_SAVE_BUTTON___" />
							</div>
						</div>
					</form>
				</div>
				<div class="clear"> </div>
			</div>
		</div>
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