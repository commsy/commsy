{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->

		{*{include file="include/detail_linked_html.tpl"}*}

		<div id="main_navigation_print" style="border:1px solid #676767;"><h1>{$detail.content.discussion.title}</h1></div>
		{*<h2>{$detail.content.discussion.title}</h2>*}
		
		<div style="background-color:#E3E3E3;border-left:1px solid #676767;border-right:1px solid #676767;">
			<div style="font-size:10px;padding: 0px 10px;">
				{*{foreach $list.restriction_text_parameters as $params}
					{$params.name},
				{/foreach}
				<br>*}
				<div {if in_array("linked_expand",$detail.printcookie)}class="hidden"{/if}>
					___COMMON_ATTACHED_BUZZWORDS___: 
					{foreach $room.buzzwords as $buzzword}
						{block name=sidebar_buzzwordbox_buzzword}
							<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&selbuzzword={$buzzword.to_item_id}">{$buzzword.name}</a>{if !$buzzword@last}, {/if}
						{/block}
					{foreachelse}
						___COMMON_NONE___
					{/foreach}
				</div>
				<div {if in_array("linked_expand",$detail.printcookie)}class="hidden"{/if}>
					___COMMON_ATTACHED_TAGS___:
					{foreach $item.tags as $tag}
						<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&name=selected&seltag_{$tag.level}={$tag.item_id}&seltag=yes">{$tag.title}</a>{if !$tag@last}, {/if}
					{foreachelse}
						___COMMON_NONE___
					{/foreach}
				</div>
				<div {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
					___COMMON_REFNUMBER___: {$detail.content.moredetails.item_id}
					<br>
					{if isset($detail.content.moredetails.read_since_modification_percentage)}
						___COMMON_READ_SINCE_MODIFICATION___:
						{*<div class="progressbar">*}
							<!--  <img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" /> -->
							
							<span class="value">{$detail.content.moredetails.read_since_modification_count}</span>
							<span> - </span>
							<span class="percent">{$detail.content.moredetails.read_since_modification_percentage}%</span>					
						{*</div>*}
					{/if}
					<br>
					___COMMON_CREATED_BY___:
					{build_user_link status=$detail.content.moredetails.creator_status user_name=$detail.content.moredetails.creator id=$detail.content.moredetails.creator_id} - {$detail.content.moredetails.creation_date}
					<br>
					{if !empty($detail.content.moredetails.modifier)}
						___COMMON_EDIT_BY___:
						{foreach $detail.content.moredetails.modifier as $modifier}
							{build_user_link status=$modifier.status user_name=$modifier.name id=$modifier.id}{if !$modifier@last}, {/if}
						{/foreach}
					<br><br>
					{/if}
					
				</div>
			___COMMON_LAST_MODIFIED_BY_UPPER___
			{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
			___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
		</div>
		</div>
		
		<div class="clear"> </div>

		<div id="item_credits" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767;margin-bottom:0px;">
			<p id="ic_rating">
				{if $room.assessment}
					{include file="include/detail_assessment_include_print.tpl"}
				{/if}
			</p>
			{*<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>*}
			<div class="clear"> </div>

		</div>

		<div id="item_legend" style="border-bottom:none;"> <!-- Start item_legend -->
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
			{*{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}*}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>



	{foreach $detail.content.disc_articles as $article}

		<div class="item_body_print" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767;border-bottom:1px solid;margin-bottom:0px;"> <!-- Start item body -->
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
									{embed param1=$article.description}
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
