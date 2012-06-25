<div id="annotations_expand" {if !$detail.is_annotations_bar_visible}class="hidden"{/if}>
	<div class="fade_in_ground_annotations">
		<div class="markup">
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
					<a link="edit_expand_annotation_{$annotation@index}" class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
				</div>

				<div class="item_body"> <!-- Start item body -->
					<!-- Start fade_in_ground -->
					<div id="edit_expand_annotation_{$annotation@index}" class="hidden">
						<div class="fade_in_ground_actions">
							{if $annotation.actions.edit}
								<a  href="commsy.php?cid={$environment.cid}&mod=annotation&fct=edit&iid={$detail.content.item_id}&mode=annotate" title="___COMMON_EDIT_ITEM___">___COMMON_EDIT_ITEM___</a> |
							{/if}
							{if $annotation.actions.delete}
								<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit{params params=$annotation.actions.deleteparams}}" title="___COMMON_DELETE_ITEM___">___COMMON_DELETE_ITEM___</a>
							{/if}
						</div>
					</div>
					<!-- Ende fade_in_ground -->

					<a name="annotation{$annotation.item_id}"></a>
					<a name="annotation_{$annotation@index}"></a>
					<div class="item_post">
						<div class="row_{if $annotation@iteration is odd}odd{else}even{/if}_no_hover">

							<div class="column_80">
								<p>
									<a href="" title="{$annotation.creator}">
										{if $annotation.image}
											<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$annotation.image}" alt="___USER_PICTURE_UPLOADFILE___" />
										{else}
											<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="___USER_PICTURE_UPLOADFILE___" />
										{/if}
									</a>
								</p>
							</div>

							<div class="column_585">
								<div class="post_content">
									<h4>
										{$annotation.pos_number}. {$annotation.title}
										{if $article.noticed != ''}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}
									</h4>
									<div class="annotation_credits">
										___COMMON_LAST_MODIFIED_BY_UPPER___ {$annotation.modifier} ___DATES_ON_DAY___ {$annotation.modification_date}
									</div>
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
										<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.user_picture}" alt="___USER_PICTURE_UPLOADFILE___" />
									{else}
										<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="___USER_PICTURE_UPLOADFILE___" />
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
									{if isset($detail.content.version)}<input type="hidden" value="{$detail.content.version}" name="version"/>{/if}
									<input id="pn_title" type="text" name="form_data[title]" />
									<div class="editor_content">
										<div id="description_annotation" class="ckeditor"></div>
										<input type="image" id="disc_article_submit" name="form_data[option][new]" src="{$basic.tpl_path}img/btn_go.gif" alt="___ANNOTATION_SAVE_BUTTON___" />
									</div>
								</div>
							</form>
						</div>
						<div class="clear"> </div>
					</div>
				</div>
			</div> <!-- Ende item body -->
			<div class="clear"> </div>
		</div>
	</div>
</div>