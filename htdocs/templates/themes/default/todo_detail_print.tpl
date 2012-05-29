{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<!-- Ende fade_in_ground -->

		{include file="include/detail_linked_html.tpl"}

		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p id="ic_rating">
				{if $room.assessment}
					{include file="include/detail_assessment_include_html.tpl"}
				{/if}
			</p>
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
			<div class="clear"> </div>
		</div>

		<div id="item_legend">
			<div class="detail_content">
				{* formal data *}
				<table class="detail_content_table">
					<tr>
						<td class="label"><h4>___TODO_VALIDITY_DATE___:</h4></td>
						<td>
							{if $detail.content.formal.date == 'no_end'}
								___TODO_NO_END_DATE_LONG___
							{else}
								{$detail.content.formal.date}
							{/if}
						</td>
					</tr>
					<tr>
						<td class="label"><h4>___TODO_STATUS___:</h4></td>
						<td>
							{$detail.content.formal.status}
						</td>
					</tr>

					{if !empty($detail.content.formal.management)}
						{if !empty($detail.content.formal.management[0])}
							<tr>
								<td class="label"><h4>___TODO_MINUTES___:</h4></td>
								<td>
									{$detail.content.formal.management[0]}
								</td>
							</tr>
						{/if}
						{if !empty($detail.content.formal.management[1])}
							<tr>
								<td class="label"><h4>___TODO_DONE_MINUTES___:</h4></td>
								<td>
									{$detail.content.formal.management[1]}
								</td>
							</tr>
						{/if}
					{/if}

					<tr>
						<td class="label"><h4>___TODO_PROCESSORS___:</h4></td>
						<td>
							{if !empty($detail.content.formal.members)}
								{$detail.content.formal.members}
							{else}
								___TODO_NO_PROCESSOR___
							{/if}
						</td>
					</tr>

					{if !empty($detail.content.formal.files)}
						<tr>
							<td class="label"><h4>___MATERIAL_FILES___:</h4></td>
							<td>
								{$detail.content.formal.files}
							</td>
						</tr>
					{/if}

					<tr>
						<td class="label"><h4>___TODO_STEPS___:</h4></td>
						<td>
							{if $detail.content.steps}
								{foreach $detail.content.steps as $step}
									{$step@iteration}. <a href="#step{$step.item_id}">{$step.title}</a>
									{foreach $step.formal.files as $file}
										{$file.icon}
									{/foreach}
									{if !$step@last}
										<br/>
									{/if}
								{/foreach}
							{else}
								___TODO_NO_STEPS___
							{/if}
						</td>
					</tr>
				</table>
				<div class="clear"> </div>
			</div>
		</div>

		<div class="detail_content_print"> <!-- Start item_legend -->
			{if !empty($detail.content.description)}
				<div class="detail_description">
					{$detail.content.description}
				</div>
			{/if}
		</div> <!-- Ende item_legend -->
		{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	{foreach $detail.content.steps as $step}

		<div class="item_body_print"> <!-- Start item body -->
			<a name="step_article_{$step.item_id}"></a>
			<a name="step{$step.item_id}"></a>

			<!-- Start fade_in_ground -->
			<!-- Ende fade_in_ground -->

			<div class="item_post">
				<div class="row_{if $step@iteration is odd}odd{else}even{/if}_no_hover ">
					<div class="column_80">
						<p>
							<a href="" title="{$step.linktext}">
								{if !empty($step.picture)}
									<img width="62" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$step.picture}" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.image.linktext}" />
								{else}
									<img width="62" src="{$basic.tpl_path}img/user_unknown.gif" alt="{i18n tag=USER_PICTURE_NO_PICTURE param1=$step.image.linktext}" />
								{/if}
							</a>
						</p>
					</div>

					<div class="column_585">
						<div class="post_content">
							<h4>{*{if $article.noticed == 'new' or $article.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*} {$step.title}
							</h4>
				<span>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$step.moredetails.last_modificator_status user_name=$step.moredetails.last_modificator id=$step.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$step.moredetails.last_modification_date}
				</span>

							{if !empty($step.formal)}
								<table>
									{if !empty($step.formal.time)}
										<tr>
											<td class="label"><h4>___TODO_DONE_MINUTES___: </h4></td>
											<td>
												{$step.formal.time}
											</td>
										</tr>
									{/if}

									{if !empty($step.formal.files)}
										<tr>
											<td class="label"><h4>___MATERIAL_FILES___: </h4></td>
											<td>
												{foreach $step.formal.files as $file}
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
								{$step.description}
							</div>
						</div>
					</div>
					<div class="column_27">
						<p class="jump_up_down">
							{if !$step@first}<a href="#step_article_{$detail.content.steps[$step@index - 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="&lt;" /></a>{/if}
							{if !$step@last}<a href="#step_article_{$detail.content.steps[$step@index + 1].item_id}"><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="&gt;" /></a>{/if}
						</p>
					</div>
					<div class="clear"> </div>
				</div>
			</div>
			{include file="include/detail_moredetails_print.tpl" data=$step.moredetails}

		</div> <!-- Ende item body -->
		<div class="clear"> </div>
	{/foreach}

	<div class="item_actions">&nbsp;</div>

	<div class="item_body"> <!-- Start item body -->
	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	{include file='include/annotation_include_html.tpl'}

	<div class="clear"> </div>
{/block}
