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
		<h2>{$detail.content.discussion.title}</h2>
		<div class="clear"> </div>
		
		<div id="item_credits">
			<p id="ic_rating">
				{foreach $detail.content.discussion.assessments as $assessment}
					<img src="{$basic.tpl_path}img/star_{$assessment}.gif" alt="*" />
				{/foreach}
			</p>
			<p>
				___COMMON_CREATED_BY_UPPER___ <a href="">{$detail.content.discussion.creator}</a> ___DATES_ON_DAY___  {$detail.content.discussion.creation_date}
			</p>
			<div class="clear"> </div>
		</div>
		
		<div id="item_legend"> <!-- Start item_legend -->
			{section name="articles_short" loop=$detail.content.disc_articles start=-10 max=10}
				{$article = $detail.content.disc_articles[articles_short]}
				{$iteration = $smarty.section.articles_short.iteration}
				<div class="row_{if $iteration is odd}odd{else}even{/if} {if $iteration is odd}odd{else}even{/if}_sep_390">
					<div class="column_320">
						<p>
							{$article.position}.
							{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="NEU"/>{/if}
							<a href="">{$article.subject}</a>
						</p>
					</div>
					<div class="column_45">
						<p>
							<a href="" class="attachment">{$article.num_attachments}</a>
						</p>
					</div>
					<div class="column_155">
						<p>
							<a href="">{$article.creator}</a>
						</p>
					</div>
					<div class="column_155">
						<p>{$article.modification_date}</p>
					</div>
					<div class="clear"> </div>
				</div>
			{/section}
		</div> <!-- Ende item_legend -->
	
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	{foreach $detail.content.disc_articles as $article}
		<div class="item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
		</div>
		
		<div class="item_body"> <!-- Start item body -->
			<a name="disc_article_{$article.item_id}"></a>
			<div class="item_post">
				<div class="row_{if $article@iteration is odd}odd{else}even{/if} {if $article@iteration is odd}odd{else}even{/if}_sep_disdetail">
					<div class="column_80">
						<p>
							<a href="" title="{$article.creator}">
								{if $article.custom_image}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$article.modificator_image}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$article.creator}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$article.creator}" />
								{/if}
							</a>
						</p>
					</div>
					
					<div class="column_510">
						<div class="post_content">
							<h4>{$article@iteration}.
								{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if} {$article.subject}
							</h4>
							<span><a href="">{$article.creator}</a>, {$article.modification_date}</span>
							<div class="editor_content">
								{$article.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{if !$article@first}<a href=""><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
							{if !$article@last}<a href=""><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
						</p>
					</div>
					<div class="column_45">
						<p>
							<a href="" class="attachment">{$article.num_attachments}</a>
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
					<form action="commsy.php?cid={$environment.cid}&mod=discarticle&fct=edit" method="post">
						<div class="post_content">
							<h4>{$detail.content.new_num}. </h4>
							<input type="hidden" value="" name="iid">
							<input type="hidden" value="{$detail.item_id}" name="discussion_id">
							<input type="hidden" value="1" name="ref_position">
							<input id="pn_title" type="text" name="form_data[title]" />
							<div class="editor_content">
								<div id="ckeditor"></div>
								<input type="hidden" id="ckeditor_content" name="form_data[description]" value=""/>
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
	
	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry@iteration}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}