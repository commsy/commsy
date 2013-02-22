{extends file="discussion_detail_print.tpl"}

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

	<div id="discussion_tree_progressbar_wrap">
		<div>___DISCUSSION_THREADED_LOADING___</div>
		<div>
			<span id="discussion_tree_progressbar_percent"></span>%
		</div>
		<div id="discussion_tree_progressbar"></div>
	</div>

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
	<a name="article{$article.item_id}"></a>
	<div class="item_post">
		<div class="row_{if $article@iteration is odd}odd{else}even{/if}_no_hover">
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
{/block}