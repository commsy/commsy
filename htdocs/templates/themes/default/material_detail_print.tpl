{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<!-- Ende fade_in_ground -->
		
		{include file="include/detail_linked_print.tpl"}
		
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>


		<div id="item_credits">
			<p id="ic_rating">
				{if $room.workflow}
					<img class="workflow" src="{$basic.tpl_path}img/workflow_traffic_light_{$detail.content.workflow.light}.png" alt="{$detail.content.workflow.title}" title="{$detail.content.workflow.title}">
				{/if}
				{if $room.workflow && $room.assessment}
					&nbsp;&nbsp;
				{/if}
				{if $room.assessment}
					{include file="include/detail_assessment_include_print.tpl"}
				{/if}
			</p>
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
			<div class="clear"> </div>

		</div>

		<div id="item_legend"> <!-- Start item_legend -->
				{* formal data *}
				<div class="detail_content_print">
				{if !empty($detail.content.formal) || $detail.content.sections}
						<table class="detail_content_table">
							{foreach $detail.content.formal as $formal}
								<tr>
									<td><h4>{$formal[0]}:</h4></td>
									<td>{$formal[1]}</td>
								</tr>
							{/foreach}

							{if $detail.content.sections}
								<tr>
									<td><h4>___MATERIAL_SECTIONS___:</h4></td>
									<td>
									{foreach $detail.content.sections as $section}
										{$section@iteration}. <a href="#section{$section.iid}">{$section.title}</a>
										{foreach $section.formal.files as $file}
											{$file.icon}
										{/foreach}
										{if !$section@last}
											<br/>
										{/if}
									{/foreach}
									</td>
								</tr>
								<tr>
									<td><h4>___MATERIAL_ABSTRACT___:</h4></td>
									<td>
										{$detail.content.description}
										{$detail.printcookie}
										{if in_array("detail_expand",$detail.printcookie)}
										   if abfrage ging durch :)
										{else}
											if ging nicht durch 
										{/if}
									</td>
								</tr>
							{/if}
						</table>
				{/if}

				{if $detail.content.description && (!isset($detail.content.sections) || empty($detail.content.sections))}
					<div class="detail_description_print">
						{$detail.content.description}
					</div>
				{/if}
				</div>
		</div> <!-- Ende item_legend -->
		{if $room.workflow}
		   {include file="include/detail_workflow_print.tpl" data=$detail.content.workflow}
		{/if}
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	{foreach $detail.content.sections as $section}

		<div class="item_body_print"> <!-- Start item body -->
			<a name="mat_section_{$section@index}"></a>
			<a name="section{$section.iid}"></a>
			<a name="anchor{$section.iid}"></a>

			<!-- Start fade_in_ground -->
			<!-- Ende fade_in_ground -->

			<div class="item_post">
				<div class="row_{if $section@iteration is odd}odd{else}even{/if}_no_hover padding_left_10px">
					<div class="column_655">
						<div class="post_content">
							<h4>
								{$section.title}

							{*{if $section.noticed == 'new' or $section.noticed == 'changed'}<img src="{$basic.tpl_path}img/flag_neu.gif" alt="___COMMON_NEW___"/>{/if}*}
							</h4>
							<span>
							___COMMON_LAST_MODIFIED_BY_UPPER___
							{build_user_link status=$section.moredetails.last_modificator_status user_name=$section.moredetails.last_modificator id=$section.moredetails.last_modificator_id}
							___DATES_ON_DAY___  {$section.moredetails.last_modification_date}
							</span>
							{if !empty($section.formal)}
								<table>
									{if !empty($section.formal.files)}
										<tr>
											<td class="label"><h4>___MATERIAL_FILES___: </h4></td>
											<td>
												{foreach $section.formal.files as $file}
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
					<div class="clear"> </div>
				</div>
			</div>

			<div id="detail_expand_section_{$section@index}" {if in_array("detail_expand_section_{$section@index}",$detail.printcookie)}class="hidden"{/if}>
				{include file="include/detail_moredetails_print.tpl" data=$section.moredetails}
			</div>

		</div> <!-- Ende item body -->
		<div class="clear"> </div>

	{/foreach}

	{include file='include/annotation_include_print.tpl'}

	<div class="clear"> </div>
{/block}

{include file='include/forward_information_include_html.tpl'}