<div class="fade_in_ground_annotations_print">
	<div class="item_body_print" style="border:1px solid #DBDBDB;"> <!-- Start item body -->
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

		<div class="item_body_print" style="border:1px solid #DBDBDB;"> <!-- Start item body -->
			<!-- Start fade_in_ground -->
			<!-- Ende fade_in_ground -->

			<a name="annotation{$annotation.item_id}"></a>
			<a name="annotation_{$annotation@index}"></a>
			<div class="item_post" style="background-color:#FFFFFF">
				<div class="row_{if $annotation@iteration is odd}odd{else}even{/if}_no_hover">

					<div class="column_80">
						<p>
							{if $annotation.image}
								<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$annotation.image}" alt="___USER_PICTURE_UPLOADFILE___" />
							{else}
								<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="___USER_PICTURE_UPLOADFILE___" />
							{/if}
						</p>
					</div>

					<div>
						<div class="post_content" style="background-color:#FFFFFF">
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
					</div>
					<div class="clear"> </div>
				</div>
			</div>
		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	{/foreach}

	<div class="item_actions">&nbsp;</div>

	<div class="item_body_print"> <!-- Start item body -->
		<div class="item_post">
			<div id="item_postnew">
				<div class="column_80">
					
				</div>
				<div class="clear"> </div>
			</div>
		</div>
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
</div>