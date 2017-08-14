{extends file="room_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_MATERIAL___</h4>
	<br>
	{*<div> <h4>___COMMON_RESTRICTIONS___</h4></div>
	{foreach $list.restriction_text_parameters as $params}
		{$params.name},
	{/foreach}
	<br>*}
	
{/block}


{block name=room_detail_content}
	<div id="main_navigation_print" style="border:1px solid #676767;"><h1>{$detail.content.title}</h1></div>
	<div class="item_body_print"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<!-- Ende fade_in_ground -->
		
		{include file="include/detail_linked_print.tpl"}
		
		{*<h2>{$detail.content.title}</h2>*}
		<div style="background-color:#E3E3E3;border-left:1px solid #676767;border-right:1px solid #676767;">
			<div style="font-size:10px;padding: 0px 10px;">
				{*<div> <h4>___COMMON_RESTRICTIONS___</h4></div>
				{foreach $list.restriction_text_parameters as $params}
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
				{if $room.workflow}
				<div {if in_array("workflow_expand",$detail.printcookie)}class="hidden"{/if}>
					<div></div><br>
					<div>
						___COMMON_STATUS___: 
						{if !empty($detail.content.workflow.light)}
							<img class="workflow" src="{$basic.tpl_path}img/workflow_traffic_light_{$detail.content.workflow.light}.png" alt="{$detail.content.workflow.title}" title="{$detail.content.workflow.title}"> {$detail.content.workflow.title}
			            {/if}
			            &nbsp;&nbsp;&nbsp;
						___MATERIAL_WORKFLOW_VALID_UNTIL___:
						{if $detail.content.workflow.validity_date == ''}
							___COMMON_NO_ENTRY___
						{else}
							{$detail.content.workflow.validity_date}
						{/if}
						&nbsp;&nbsp;&nbsp;
						___MATERIAL_WORKFLOW_RESUBMISSION_UNTIL___:
						{if $detail.content.workflow.resubmission_date == ''}
							___COMMON_NO_ENTRY___
						{else}
							{$detail.content.workflow.resubmission_date}
						{/if}
					</div>
					<div>
						___COMMON_MARK_READ_SINCE_MODIFICATION___
						{if $detail.content.workflow.read_since_modification_count_text}
							{$detail.content.workflow.read_since_modification_count_text}
						{else}
							___COMMON_NO_ENTRY___
						{/if}
					</div>
				</div>
				{/if}
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
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
		</div>
		<div class="clear"> </div>


		<div id="item_credits_2">
			<p id="ic_rating">
				{if $room.workflow && !empty($detail.content.workflow.light)}
					<img class="workflow" src="{$basic.tpl_path}img/workflow_traffic_light_{$detail.content.workflow.light}.png" alt="{$detail.content.workflow.title}" title="{$detail.content.workflow.title}">
				{/if}
				{if $room.workflow && $room.assessment}
					&nbsp;&nbsp;
				{/if}
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

		<div id="item_legend"> <!-- Start item_legend -->
				{* formal data *}
				<div class="detail_content_print" style="background-color: #FFFFFF">
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
										{embed param1=$detail.content.description}
									</td>
								</tr>
							{/if}
						</table>
				{/if}

				{if $detail.content.description && (!isset($detail.content.sections) || empty($detail.content.sections))}
					<div class="detail_description_print">
						{embed param1=$detail.content.description}
					</div>
				{/if}
				</div>
		</div> <!-- Ende item_legend -->
		
		<div id="workflow_expand" {if in_array("workflow_expand",$detail.printcookie)}class="hidden"{/if}>
		   {*{include file="include/detail_workflow_print.tpl" data=$detail.content.workflow}*}
		</div>
		
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{*{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}*}
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

		<div id="main_navigation_print"><h1>{$section.title}</h1></div>
			<div style="background-color:#E3E3E3;border:1px solid #676767;">
				<div style="font-size:10px;padding: 0px 10px;padding-top:12px;">

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
						
						<span>
							___COMMON_LAST_MODIFIED_BY_UPPER___
							{build_user_link status=$section.moredetails.last_modificator_status user_name=$section.moredetails.last_modificator id=$section.moredetails.last_modificator_id}
							___DATES_ON_DAY___  {$section.moredetails.last_modification_date}
						</span>
				
					</div>
				</div>
				<div>
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

							<div class="editor_content" style="background-color: #FFFFFF;padding: 5px 10px;">
								{embed param1=$section.description}
							</div>
					</div>
			</div>
			<div class="item_post">
				<div class="row_{if $section@iteration is odd}odd{else}even{/if}_no_hover padding_left_10px">
					<div class="column_655">
						<div class="post_content">
							
							{*<span>
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

							<div class="editor_content" style="background-color: #FFFFFF;">
								{$section.description}
							</div>
						</div>
					</div>*}
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
				{*{include file="include/detail_moredetails_print.tpl" data=$section.moredetails}*}
			</div>

		</div> <!-- Ende item body -->
		<div class="clear"> </div>

	{/foreach}

	{include file='include/annotation_include_print.tpl'}

	<div class="clear"> </div>
{/block}

{include file='include/forward_information_include_html.tpl'}