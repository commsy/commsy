{extends file="discussion_detail_html.tpl"}

{block name=discussion_short_articles}
	<div id="discussion_tree">
		<img src="{$basic.tpl_path}img/ajax_loader.gif" />
	</div>
{/block}

{block name=discussion_articles}
	{* create a recursive functions for displaying threaded discussions *}
	
	{function name=threaded_discussion level=0}
		{* build entries *}
		{foreach $articles as $article}
		
			{*
				TODO @ Matthias: Die Variable {$level} ist der Offset für das Einrücken der Artikel
				
				level
				-------------------
				0
					1
						2
						2
					1
					1
				etc...
				-------------------
				
				greetz Christoph
			*}
			
			{assign var="index" value=$article.index}
			
			<div class="item_actions">
				<a class="edit" data-custom="expand: 'edit_expand_article_{$index}'" href="#"><span class="edit_set"> &nbsp; </span></a>
				<a class="detail" data-custom="expand: 'detail_expand_article_{$index}'" href="#"><span class="details_ia"> &nbsp; </span></a>
			</div>
	
			<div class="item_body"> <!-- Start item body -->
				<a name="disc_article_{$article.item_id}"></a>
				<a name="article{$article.item_id}"></a>
	
				<!-- Start fade_in_ground -->
				<div id="edit_expand_article_{$index}" class="hidden">
					<div class="fade_in_ground_actions">
						{if $article.actions.edit}
							<a id="action_edit" class="open_popup" data-custom="iid: {$article.item_id}, module: 'discarticle'" href="#">___COMMON_EDIT_ITEM___</a> |
						{/if}
						{if $article.actions.delete}
							<a class="open_popup" data-custom="iid: {$article.item_id}, module: 'delete', delType: 'discarticle'" href="#">___COMMON_DELETE_ITEM___</a>
						{/if}
					</div>
				</div>
				<!-- Ende fade_in_ground -->
					<div class="item_post">
						<div class="row_{if $index+1 is odd}odd{else}even{/if}_no_hover">
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
									<h4 class="float-left">{$article.position}.
										{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$article.subject}
									</h4>
									<div class="float-right">
										<a href="#" class="open_popup" data-custom="iid: 'NEW', module: 'discarticle', answerTo: {$article.item_id}">___DISCARTICLE_ANSWER_NEW___</a>
									</div>
									
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
									{if $index < !$article@last}<a href="#disc_article_{$detail.content.disc_articles[$index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
								*}
								</p>
							</div>
							<div class="clear"> </div>
						</div>
					</div>
	
				<div id="detail_expand_article_{$index}" class="hidden">
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