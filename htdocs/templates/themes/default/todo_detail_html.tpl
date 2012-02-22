{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
			<a href=""><span class="ref_to_ia"> &nbsp; </span></a>
		</div>
	</div>
	
	<div class="item_body"> <!-- Start item body -->
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>
		
		<div id="item_credits">
			<p id="ic_rating">
			</p>
			<p>
				<div class="user_profil_blocks">
					{* formal data *}
					<table>
						<tr>
							<td class="label"><h4>___TODO_VALIDITY_DATE___</h4></td>
							<td>
								{if $detail.content.formal.date == 'no_end'}
									___TODO_NO_END_DATE_LONG___
								{else}
									{$detail.content.formal.date}
								{/if}
							</td>
						</tr>
						<tr>
							<td class="label"><h4>___TODO_STATUS___</h4></td>
							<td>
								{$detail.content.formal.status}
							</td>
						</tr>
						
						{if !empty($detail.content.formal.management)}
							{if !empty($detail.content.formal.management[0])}
								<tr>
									<td class="label"><h4>___TODO_MINUTES___</h4></td>
									<td>
										{$detail.content.formal.management[0]}
									</td>
								</tr>
							{/if}
							{if !empty($detail.content.formal.management[1])}
								<tr>
									<td class="label"><h4>___TODO_DONE_MINUTES___</h4></td>
									<td>
										{$detail.content.formal.management[1]}
									</td>
								</tr>
							{/if}
						{/if}
						
						<tr>
							<td class="label"><h4>___TODO_PROCESSORS___</h4></td>
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
								<td class="label"><h4>___MATERIAL_FILES___</h4></td>
								<td>
									{$detail.content.formal.files}
								</td>
							</tr>
						{/if}
						
						<tr>
							<td class="label"><h4>___TODO_STEPS___</h4></td>
							<td>
								{if !empty($detail.content.formal.steps)}
									{$detail.content.formal.steps}
								{else}
									___TODO_NO_STEPS___
								{/if}
							</td>
						</tr>
					</table>
				</div>
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
	
	{foreach $detail.content.steps as $step}
		<div class="item_actions">
			<a class="edit" href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
		</div>

		<div class="item_body"> <!-- Start item body -->
			<a name="step_article_{$step.item_id}"></a>
			<div class="edit_overlay">
			{*
				{if $article.actions.edit}
					<a href="commsy.php?cid={$environment.cid}&mod={$article.actions.edit_module}&fct=edit&iid={$article.item_id}">___COMMON_EDIT_ITEM___</a>
				{/if}
				{if $article.actions.delete}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.discussion.item_id}&action=delete&discarticle_iid={$article.item_id}&discarticle_action=delete">___COMMON_DELETE_ITEM___</a>
				{/if}
			*}
			</div>
			<div class="item_post">
				<div class="row_{if $step@iteration is odd}odd{else}even{/if}_no_hover {if $step@iteration is odd}odd{else}even{/if}_sep_disdetail">
					<div class="column_80">
						<p>
							<a href="" title="{$step.image.linktext}">
								{if !empty($step.image)}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$step.image.picture}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.image.linktext}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.image.linktext}" />
								{/if}
							</a>
						</p>
					</div>

					<div class="column_510">
						<div class="post_content">
							{if !empty($step.formal)}
								<div class="user_profil_blocks">
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
													{$step.formal.files}
												</td>
											</tr>
										{/if}
									</table>
								</div>
								
								<div class="clear"> </div>
							{/if}
						
							<h4>{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$step.title}
							</h4>
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