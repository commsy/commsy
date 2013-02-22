{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{if $detail.actions.edit}
				<a id="action_edit" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_EDIT_ITEM___</a> |
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
			{if $detail.actions.new}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_NEW_ITEM___</a> |
			{/if}
			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DOWNLOAD___</a>
		</div>
		<!-- Ende fade_in_ground -->

		{include file="include/detail_linked_html.tpl"}

		<h2>{$detail.content.discussion.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p id="ic_rating">
				{if $room.assessment}
					{include file="include/detail_assessment_include_print.tpl"}
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
			{block name=discussion_short_articles}
				{section name="articles_short" loop=$detail.content.disc_articles}
					{$article = $detail.content.disc_articles[articles_short]}
					{$iteration = $smarty.section.articles_short.iteration}
					<div class="row_{if $iteration is odd}odd{else}even{/if} {if $iteration is odd}odd{else}even{/if}_sep_discussion_detail">
						<div class="column_320">
							<p>
								{$article.position}.
								{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="NEU"/>{/if}
								<a href="#disc_article_{$article.item_id}">{$article.subject|truncate:35:"...":true}</a>
							</p>
						</div>
						<div class="column_45">
							<p>
								<a href="" class="attachment">{$article.num_attachments}</a>
							</p>
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
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>



	{foreach $detail.content.disc_articles as $article}

		<div class="item_body_print"> <!-- Start item body -->
			<a name="disc_article_{$article.item_id}"></a>
			<a name="article{$article.item_id}"></a>

			<!-- Start fade_in_ground -->
			<div class="fade_in_ground_actions hidden">
				{if $article.actions.edit}
					<a id="action_edit" href="commsy.php?cid={$environment.cid}&mod={$article.actions.edit_module}&fct=edit&iid={$article.item_id}">___COMMON_EDIT_ITEM___</a> |
				{/if}
				{if $article.actions.delete}
					<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.discussion.item_id}&action=delete&discarticle_iid={$article.item_id}&discarticle_action=delete">___COMMON_DELETE_ITEM___</a>
				{/if}
			</div>
			<!-- Ende fade_in_ground -->

			{block name=discussion_articles}
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
							</p>
						</div>
						<div class="clear"> </div>
					</div>
				</div>
				<div id="detail_expand_article_{$article@index}" {if in_array("detail_expand_article_{$article@index}",$detail.printcookie)}class="hidden"{/if}>
					{include file="include/detail_moredetails_print.tpl" data=$article.moredetails}
				</div>
			{/block}
		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	{/foreach}

	<div class="item_actions">&nbsp;</div>

	 <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}
