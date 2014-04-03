{extends file="discussion_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_DISCUSSION___</h4>
	<br>
	{*<div> <h4>___COMMON_RESTRICTIONS___</h4></div>
	{foreach $list.restriction_text_parameters as $params}
		{$params.name},
	{/foreach}
	<br>*}
	
{/block}

{block name=discussion_short_articles}
	{* Threaded Short Discussion Function *}
	{function name=short_discussion_threaded level=0}
		<ul>
			{foreach $nodes as $node}
				<li	id="node_{$node.item_id}"
					{if $node.children|count > 0}class="folder"{/if}
					data="url:'#1234567891234567865432456789765434567896543'">
					<div>
					<div class="discussion_threaded_tree_subject" style="color: black;">{$node.subject}</div>
					<div class="discussion_threaded_tree_creator" style="color: black;">{$node.creator}</div>
					<div class="discussion_threaded_tree_date" style="color: black;">{$node.modification_date}</div></div>
				{if $node.children|count > 0}	{* recursive call *}
					{short_discussion_threaded nodes=$node.children level=$level+1}
				{/if}
				</li>
			{/foreach}
		</ul>
	{/function}

	{*<div id="discussion_tree_progressbar_wrap">
		<div>___DISCUSSION_THREADED_LOADING___</div>
		<div>
			<span id="discussion_tree_progressbar_percent"></span>%
		</div>
		<div id="discussion_tree_progressbar"></div>
	</div>*}

	{*
	 $html = '<div id="discussion_tree_progressbar_wrap">' . LF;
      $html .= '<div style="float: left; width: 180px;">' . $this->_translator->getMessage("DISCUSSION_THREADED_LOADING") . '</div>' . LF;
      $html .= '<div style="float: right; width: 50px; text-align: center;">' . LF;
      $html .= '<span id="discussion_tree_progressbar_percent"></span>' . LF;
      $html .= '%</div>' . LF;
      $html .= '<div id="discussion_tree_progressbar" style="margin-left: 180px; margin-right: 50px;"></div>' . LF;
      $html .= '</div>' . LF;

      *}

	<div id="discussion_tree" class="hidden">
		{* call function *}
		{short_discussion_threaded nodes=$detail.content.disc_articles}
	</div>
{/block}

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

			<div class="item_body"> <!-- Start item body -->
				<a name="disc_article_{$article.item_id}"></a>
				<a name="article{$article.item_id}"></a>

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
										{embed param1=$article.description}
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