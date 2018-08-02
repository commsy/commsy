{extends file="room_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_TOPIC___</h4>
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
		
		<div style="background-color:#E3E3E3;border-left:1px solid #676767;border-right:1px solid #676767;">
			<div style="font-size:10px;padding: 0px 10px;">
				{*{foreach $list.restriction_text_parameters as $params}
					{$params.name},
				{/foreach}
				<br>*}
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
		
		{*<h2>{$detail.content.title}</h2>*}
		<div class="clear"> </div>

		<div id="item_credits">
			{*<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>*}
		</div>


		<div class="detail_content" style="border:1px solid #676767;"> <!-- Start item_legend -->
			{if !empty($detail.content.description)}
				<div class="detail_description_print">
					{embed param1=$detail.content.description}
				</div>
			{/if}
				{* files *}
				{if !empty($detail.content.files)}
				<table>
					<tr>
						<td class="label"><h4>___MATERIAL_FILES___</h4></td>
						<td>
							{foreach $detail.content.files as $file}
								{$file}
							{/foreach}
						</td>
					</tr>
				</table>
				{/if}

				{if $detail.content.path_shown}
					<div class="padding_left_bottom_10px">
						<h4>___TOPIC_PATH___</h4>
						<ul>
							{foreach $detail.content.path_items as $item}
								<li class="no_style">
									{$item@iteration}.
									{if $item.not_activated}
										<a href="commsy.php?cid={$environment.cid}&mod={$item.mod}&fct=detail&iid={$item.iid}&path={$detail.content.item_id}" title="{$item.title}">{$item.link_text}</a>
									{else}
										<a href="commsy.php?cid={$environment.cid}&mod={$item.mod}&fct=detail&iid={$item.iid}&path={$detail.content.item_id}" title="{$item.type} - {$item.title}">{$item.title}</a>
									{/if}
								</li>
							{/foreach}
						</ul>
					</div>
				{/if}
		</div>
		<div class="clear"> </div>


		{*{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}*}

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}
