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
				{foreach $detail.assessment as $assessment}
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
				{* formal data *}
				{if !empty($detail.content.formal)}
					<table>
						{foreach $detail.content.formal as $formal}
							<tr>
								<td><h4>{$formal[0]}</h4></td>
								<td>{$formal[1]}</td>
							</tr>
						{/foreach}
					</table>
				{/if}
				
				{if !empty($detail.content.description)}
					<div class="detail_description">
						{$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->
		
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	{foreach $detail.content.sections as $section}
		<div class="item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
		</div>
		
		<div class="item_body"> <!-- Start item body -->
			<a name="mat_section_{$section@index}"></a>
			<div class="item_post">
				<div class="row_{if $section@iteration is odd}odd{else}even{/if}_no_hover {if $section@iteration is odd}odd{else}even{/if}_sep_disdetail">
				
					<div class="column_27">
						<p></p>
					</div>
				
					<div class="column_563">
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
							{if !$section@first}<a href="#mat_section_{$section@index - 1}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
							{if !$section@last}<a href="#mat_section_{$section@index + 1}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
						</p>
					</div>
					<div class="column_45">
						<p>
							<a href="" class="attachment">{$section.num_attachments}</a>
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
		<h2>
			___COMMON_ANNOTATIONS___
			{if $detail.annotations|@count == 1}
				(___COMMON_ONE_ANNOTATION___)
			{elseif $detail.annotations|@count == 0}
				(___COMMON_NO_ANNOTATIONS___)
			{else}
				({i18n tag=COMMON_X_ANNOTATIONS param1=$detail.annotations|@count})
			{/if}
		</h2>
		<div class="clear"> </div>
	</div> <!-- Ende item body -->
	
	<div class="clear"> </div>
	
	{foreach $detail.annotations as $annotation}
		<div class="item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
		</div>
		
		<div class="item_body"> <!-- Start item body -->
			<a name="annotation_{$annotation@index}"></a>
			<div class="item_post">
				<div class="row_{if $annotation@iteration is odd}odd{else}even{/if}_no_hover {if $annotation@iteration is odd}odd{else}even{/if}_sep_disdetail">
				
					<div class="column_80">
						<p>
							<a href="" title="{$annotation.creator}">
								{if $annotation.image}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$annotation.image.picture}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$annotation.creator}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$article.creator}" />
								{/if}
							</a>
						</p>
					</div>
				
					<div class="column_510">
						<div class="post_content">
							<h4>
								{$annotation.pos_number}. {$annotation.title}
							
							{*{if $article.noticed == 'new' or $article.noticed == 'changed'}*}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{*{/if}*}
							</h4>
							{*<span><a href="">{$article.creator}</a>, {$article.modification_date}</span>*}
							<div class="editor_content">
								{$annotation.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{if !$annotation@first}<a href="#annotation_{$annotation@index - 1}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
							{if !$annotation@last}<a href="#annotation_{$annotation@index + 1}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
						</p>
					</div>
					<div class="column_45">
						<p>
							<a href="" class="attachment">{$annotation.num_attachments}</a>
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
					<form action="commsy.php?cid={$environment.cid}&mod=annotation&fct=edit&ref_iid={$detail.item_id}&mode=annotate&iid=NEW" method="post">
						<div class="post_content">
							<h4>{$annotation@total + 1}. </h4>
							<input type="hidden" value="" name="iid"/>
							<input type="hidden" value="{$detail.item_id}" name="material_id"/>
							<input type="hidden" value="1" name="ref_position"/>
							<input type="hidden" value="{$detail.item_id}" name="ref_iid"/>
							<input type="hidden" value="{$detail.content.version}" name="version"/>
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
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}