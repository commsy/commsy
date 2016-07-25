{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a title="___COMMON_ACTION_EDIT___" class="edit {if $detail.is_action_bar_visible}item_actions_glow{/if}" data-custom="expand: 'edit_expand'" href="#"><span class="edit_set{if $detail.is_action_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_LINKED___" class="linked {if $detail.is_reference_bar_visible}item_actions_glow{/if}" data-custom="expand: 'linked_expand'" href="#"><span class="ref_to_ia{if $detail.is_reference_bar_visible}_ok{/if}"> &nbsp; </span></a>
			<a title="___COMMON_ACTION_DETAILS___" class="detail {if $detail.is_details_bar_visible}item_actions_glow{/if}" data-custom="expand: 'detail_expand'" href="#"><span class="details_ia{if $detail.is_details_bar_visible}_ok{/if}"> &nbsp; </span></a>
			{if $item.linked_count}
				<div class="action_count linked_count_without_annotation" >{$item.linked_count}</div>
			{else}
				<div class="action_count linked_count_without_annotation" >&nbsp;</div>
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
				{if $detail.export_to_wiki}
					<a class="ajax_action" data-custom="iid: {$detail.item_id}, action: 'exportToWiki'" href="#">___ITEM_EXPORT_TO_WIKI___</a> |
				{/if}
				{if $detail.actions.delete}
					<a class="open_popup" data-custom="iid: {$detail.content.item_id}, module: 'delete', delType: 'discussion'" href="#">___COMMON_DELETE_ITEM___</a> |
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

		<h2>{$detail.content.discussion.title}</h2>
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
			{if !empty($detail.content.formal)}
				<div class="detail_content" style="min-height:10px; margin-bottom:1px;">
					<table class="detail_content_table">
						{foreach $detail.content.formal as $formal}
							<tr>
								<td><h4>{$formal[0]}:</h4></td>
								<td>{$formal[1]}</td>
							</tr>
						{/foreach}
					</table>
				</div>
			{/if}
			{block name=discussion_short_articles}
				{section name="articles_short" start=0 loop=$detail.content.disc_articles}
					{$article = $detail.content.disc_articles[articles_short]}
					{$iteration = $smarty.section.articles_short.iteration}
					<div class="row_{if $iteration is odd}odd{else}even{/if} {if $iteration is odd}odd{else}even{/if}_sep_discussion_detail">
						<div class="column_320">
							<p>
								{$smarty.section.articles_short.index + 1}.
								{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="NEU"/>{/if}
								<a href="#disc_article_{$article.item_id}">{$article.subject|truncate:35:"...":true}</a>
							</p>
						</div>
						<div class="column_45">
							{if $article.num_attachments > 0}
								<p>
									<a href="" class="attachment">{$article.num_attachments}</a>
								</p>
								<div class="tooltip tooltip_with_400">
									<div class="tooltip_wrapper">
										<div class="tooltip_inner tooltip_inner_with_400">
											<div class="tooltip_title">
												<div class="header">___COMMON_ATTACHED_FILES___</div>
											</div>
											<div class="scrollable">
												<div class="tooltip_content">
													<ul>
													{foreach $article.attachment_infos as $file}
														<li>
															<a class="{if $file.lightbox}lightbox_{$item.iid}{/if}" href="{$file.file_url}" target="blank">
																{$file.file_icon} {$file.file_name}
															</a>
															({$file.file_size} KB)
														</li>
													{/foreach}
													</ul>
												</div>
											</div>
										</div>
									</div>
								</div>
							{else}
								<p>&nbsp;</p>
							{/if}
						</div>
						<div class="column_165">
							<p>
								<a href="">{$article.creator}</a>
							</p>
						</div>
						<div class="column_140">
							<p>{$article.modification_date}</p>
						</div>
						<div class="clear"> </div>
					</div>
				{/section}
			{/block}
		</div> <!-- Ende item_legend -->

		<div id="detail_expand" {if !$detail.is_details_bar_visible}class="hidden"{/if}>
			{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>


	{block name=discussion_articles}
		{foreach $detail.content.disc_articles as $article}
			<div class="item_actions">
				<a class="edit" data-custom="expand: 'edit_expand_article_{$article@index}'" href="#"><span class="edit_set"> &nbsp; </span></a>
				<a class="detail" data-custom="expand: 'detail_expand_article_{$article@index}'" href="#"><span class="details_ia"> &nbsp; </span></a>
			</div>

			<div class="item_body"> <!-- Start item body -->
				<a name="disc_article_{$article.item_id}"></a>
				<a name="article{$article.item_id}"></a>

				<!-- Start fade_in_ground -->
				<div id="edit_expand_article_{$article@index}" {if !$detail.is_action_bar_visible}class="hidden"{/if}>
					<div class="fade_in_ground_actions">
						{if $article.actions.edit}
							<a id="action_edit" class="open_popup" data-custom="iid: {$article.item_id}, module: 'discarticle'" href="#">___COMMON_EDIT_ITEM___</a> |
						{else}
							{if $article.actions.locked}
							<img id="edit_attention" class="tooltip_toggle" src="{$basic.tpl_path}img/attention.gif" />
							<div class="tooltip">
								<div class="tooltip_inner">
									<div class="tooltip_title">
										<div class="header">___ITEM_LOCKING_TITLE___</div>
									</div>
									<div class="tooltip_content">
										<span class="content">{i18n tag=ITEM_LOCKING_DESC param1=$article.actions.locked_user_name param2=$article.actions.locked_date}</span>
									</div>
								</div>
							</div>
							{/if}
							<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
						{/if}
						{if $article.actions.delete}
							<a class="open_popup" data-custom="iid: {$article.item_id}, module: 'delete', delType: 'discarticle'" href="#">___COMMON_DELETE_ITEM___</a>
						{else}
						<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_DELETE_ITEM___</span>
						{/if}
					</div>
				</div>
				<!-- Ende fade_in_ground -->
					<div class="item_post">
						<div class="row_{if $article@iteration is odd}odd{else}even{/if}_no_hover">
							<div class="column_80">
								<p>
									<a href="" title="{$article.creator}">
										{if $article.custom_image}
											<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$article.modificator_image}" alt="___USER_PICTURE_UPLOADFILE___" />
										{else}
											<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="___USER_PICTURE_UPLOADFILE___" />
										{/if}
									</a>
								</p>
							</div>


							<div class="column_585_nopadding">
								<div class="post_content">
									<h4>{$article@iteration}.
										{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$article.subject}
									</h4>
									<span>
									___COMMON_LAST_MODIFIED_BY_UPPER___
									{build_user_link status=$article.moredetails.last_modificator_status user_name=$article.moredetails.last_modificator id=$article.moredetails.last_modificator_id}
									___DATES_ON_DAY___  {$article.moredetails.last_modification_date}
									</span>
									{if !empty($article.formal)}
										<table>
											{if !empty($article.formal.files)}
												<tr>
													<td class="label"><h4>___MATERIAL_FILES___: </h4></td>
													<td>
														{foreach $article.formal.files as $file}
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
										{$article.description}
									</div>
								</div>
							</div>
							<div class="column_27">
								<p class="jump_up_down">
									{if !$article@first}<a href="#disc_article_{$detail.content.disc_articles[$article@index - 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
									{if !$article@last}<a href="#disc_article_{$detail.content.disc_articles[$article@index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
								</p>
							</div>
							<div class="clear"> </div>
						</div>
					</div>

				<div id="detail_expand_article_{$article@index}" class="hidden">
					{include file="include/detail_moredetails_html.tpl" data=$article.moredetails}
				</div>
			</div> <!-- Ende item body -->
			<div class="clear"> </div>
		{/foreach}
	{/block}

	{block name=discussion_inline_answer}
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
								<input class="open_popup" type="submit" data-custom="module: 'discarticle', iid: 'NEW', discussion_id: {$detail.item_id}" value="___COMMON_NEW_DISCARTICLE_EDIT___" />
							{else}
								<a name="discarticle_new"></a>
								<form action="commsy.php?cid={$environment.cid}&mod=discarticle&fct=edit" method="post" enctype="multipart/form-data">
									<div class="post_content">
										<h4>{$detail.content.new_num}. </h4>
										<input type="hidden" value="" name="iid"/>
										<input type="hidden" value="{$detail.item_id}" name="discussion_id"/>
										<input type="hidden" value="1" name="ref_position"/>
										<input id="pn_title" type="text" name="form_data[title]"{if $detail.exception == "discarticle"} class="missing"{/if}/>
		
										<div class="editor_content">
											<div id="description" class="ckeditor">
												{if isset($detail.discarticle_description)}
													{$detail.discarticle_description}
												{/if}
											</div>
										</div>
		
										{*
										<div id="files_finished"></div>
		
										<div class="uploader">
										   <input class="fileSelector"></input>
		
										   <div class="fileList"></div>
										</div>
										*}
		
										<input type="submit" id="disc_article_submit" name="form_data[option][new]" value="___COMMON_NEW_DISCARTICLE_EDIT___" />
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
	{/block}

	<div class="clear"> </div>
{/block}
