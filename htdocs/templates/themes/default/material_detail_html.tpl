{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href=""><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href=""><span class="details_ia"> &nbsp; </span></a>
			<a class="linked" href=""><span class="ref_to_ia"> &nbsp; </span></a>
			<a class="annotations" href="#"><span class="edit_set"> &nbsp; </span></a>
		</div>
	</div>
	
	<div class="item_body"> <!-- Start item body -->
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>
		
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO: add missing actions *}
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
			{if $detail.actions.new}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_NEW_ITEM___</a> |
			{/if}
			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}">___COMMON_DOWNLOAD___</a>
		</div>
		<!-- Ende fade_in_ground --> 
		
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
			
			{include file="include/detail_linked_html.tpl"}
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
	
	{include file="include/detail_moredetails_html.tpl"}
	
	{foreach $detail.content.sections as $section}
		<div class="item_actions">
			<a class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href="#"><span class="details_ia"> &nbsp; </span></a>
		</div>
		
		<div class="item_body"> <!-- Start item body -->
			<a name="mat_section_{$section@index}"></a>
			
			<!-- Start fade_in_ground -->
			<div class="fade_in_ground_actions hidden">
				actions
			</div>
			<!-- Ende fade_in_ground --> 
			
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
		
		{include file="include/detail_moredetails_html.tpl"}
	{/foreach}
	
	{include file='include/annotation_include_html.tpl'}
	
	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}