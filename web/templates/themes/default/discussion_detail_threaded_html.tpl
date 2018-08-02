{extends file="discussion_detail_html.tpl"}

{block name=discussion_short_articles}
	<a id="discussionShortExpandAll" href="#">___DISCUSSION_THREADED_SHOW_ALL___</a>
	<a id="discussionShortCollapseAll" class="hidden" href="#">___DISCUSSION_THREADED_HIDE_ALL___</a>

	<div id="discussion_tree">
		<img src="{$basic.tpl_path}img/ajax_loader.gif" />
	</div>
{/block}
{assign var="article_index" value=0}

{block name=discussion_articles}
	{* create a recursive functions for displaying threaded discussions *}

	{function name=threaded_discussion level=0}
		{* build entries *}
		{foreach $articles as $article}

			{assign var="index" value=$article.index}
			{if ($article.position|strlen) > 1}
				{assign var="discarticle_padding" value= ((($article.position|strlen/2)|ceil)-1)*39}
				{assign var="discarticle_content_width" value= 585 - ((($article.position|strlen/2)|ceil)-1)*39}
				{assign var="discarticle_body_width" value= 695 - ((($article.position|strlen/2)|ceil)-1)*39}
			{/if}
			{if ((($article.position|strlen/2)|ceil)-1) > 10}
				{assign var="discarticle_padding" value= 390}
				{assign var="discarticle_content_width" value= 195}
				{assign var="discarticle_body_width" value= 305}
			{/if}


			<div class="item_actions" >
				<a class="edit item_actions_glow" data-custom="expand: 'edit_expand_article_{$article.item_id}'" href="#"><span class="edit_set{if $detail.is_action_bar_visible}_ok{/if}"> &nbsp; </span></a>
				<a class="detail" data-custom="expand: 'detail_expand_article_{$article.item_id}'" href="#"><span class="details_ia"> &nbsp; </span></a>
			</div>

			<div class="item_body"> <!-- Start item body -->
				<a name="disc_article_{$article.item_id}"></a>
				<a name="article{$article.item_id}"></a>

				<!-- Start fade_in_ground -->
				<div id="edit_expand_article_{$article.item_id}" {if !$detail.is_action_bar_visible}class="hidden"{/if}>
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
							<a class="open_popup" data-custom="iid: {$article.item_id}, module: 'delete', delType: 'discarticle'" href="#">___COMMON_DELETE_ITEM___</a> |
						{/if}
						{if $article.actions.answer}
							<a href="#" class="open_popup" data-custom="iid: 'NEW', module: 'discarticle', answerTo: {$article.item_id}">___DISCARTICLE_ANSWER_NEW___</a>
						{/if}
					</div>
				</div>
				<!-- Ende fade_in_ground -->
					<div class="item_post">
						<div class="row_{if $index+1 is odd}odd{else}even{/if}_no_hover" style="padding-left:{$discarticle_padding}px;">
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
							<div class="column_585_nopadding" style="width:{$discarticle_content_width}px">
								<div class="post_content">
									<h4 class="float-left">{if !empty($article.position)}{$article.position}.{/if}
										{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$article.subject}
									</h4>

									<div class="clear"></div>
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
									{*
									{if $index > 0}<a href="#disc_article_{$detail.content.disc_articles[$index - 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
									{if $index < $detail.content.numArticles - 1}<a href="#disc_article_{$detail.content.disc_articles[$index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
									*}
								</p>
							</div>
							<div class="clear"> </div>
						</div>
					</div>

				<div id="detail_expand_article_{$article.item_id}" class="hidden">
					{include file="include/detail_moredetails_html.tpl" data=$article.moredetails}
				</div>
			</div> <!-- Ende item body -->
			<div class="clear"> </div>

			{if $article.children|count > 0}	{* recursive call *}
				{threaded_discussion articles=$article.children level=level+1 }
			{/if}

		{/foreach}
	{/function}

	{* call function *}
	{threaded_discussion articles=$detail.content.disc_articles}
{/block}

{block name=discussion_inline_answer}{/block}