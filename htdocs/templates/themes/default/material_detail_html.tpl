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
				{foreach $detail.content.assessments as $assessment}
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
				{if empty($detail.content.description)}___COMMON_NONE___{else}{$detail.content.description}{/if}
			</div>
		</div> <!-- Ende item_legend -->
		
		
		
		
		<div class="item_body"> <!-- Start item body -->
			<a name="disc_article_{$article.item_id}"></a>
			<div class="item_post">
				<div class="row_{if $section@iteration is odd}odd{else}even{/if} {if $section@iteration is odd}odd{else}even{/if}_sep_disdetail">
					<div class="column_590">
						<div class="post_content">
							<h4>
								{$detail.content.title}
							
							{*{if $article.noticed == 'new' or $article.noticed == 'changed'}*}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{*{/if}*}
							</h4>
							{*<span><a href="">{$article.creator}</a>, {$article.modification_date}</span>*}
							<div class="editor_content">
								{$detail.content.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{*{if !$article@first}<a href="#disc_article_{$detail.content.disc_articles[$article@index - 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}*}
							{*{if !$article@last}<a href="#disc_article_{$detail.content.disc_articles[$article@index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}*}
						</p>
					</div>
					<div class="column_45">
						<p>
							<a href="" class="attachment">{*{$article.num_attachments}*}</a>
						</p>
					</div>
					<div class="clear"> </div>
				</div>
			</div>
		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	{foreach $detail.content.sections as $section}
		<div class="item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
		</div>
		
		<div class="item_body"> <!-- Start item body -->
			<a name="disc_article_{$article.item_id}"></a>
			<div class="item_post">
				<div class="row_{if $section@iteration is odd}odd{else}even{/if} {if $section@iteration is odd}odd{else}even{/if}_sep_disdetail">
					<div class="column_590">
						<div class="post_content">
							<h4>
								{$section.title}
							
							{*{if $article.noticed == 'new' or $article.noticed == 'changed'}*}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{*{/if}*}
							</h4>
							{*<span><a href="">{$article.creator}</a>, {$article.modification_date}</span>*}
							<div class="editor_content">
								{$section.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{*{if !$article@first}<a href="#disc_article_{$detail.content.disc_articles[$article@index - 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}*}
							{*{if !$article@last}<a href="#disc_article_{$detail.content.disc_articles[$article@index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}*}
						</p>
					</div>
					<div class="column_45">
						<p>
							<a href="" class="attachment">{*{$article.num_attachments}*}</a>
						</p>
					</div>
					<div class="clear"> </div>
				</div>
			</div>
		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	{/foreach}
	{*
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
							<input type="hidden" value="{$detail.item_id}" name="material_id">
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
	*}
	
	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry@iteration}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}