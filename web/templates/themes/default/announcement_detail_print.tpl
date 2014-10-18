{extends file="room_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_ANNOUNCEMENT___</h4>
	<br>
	{*<div> <h4>___COMMON_RESTRICTIONS___</h4></div>
	{foreach $list.restriction_text_parameters as $params}
		{$params.name},
	{/foreach}
	<br>*}
	
{/block}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->
	    <div id="main_navigation_print" style="border:1px solid #676767;"><h1>{$detail.content.title}</h1></div>
		{*{include file="include/detail_linked_print.tpl"}*}
		{*<h2>{$detail.content.title}</h2>*}
		
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

		<div id="item_credits_print" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767">
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

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="detail_content_print" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767">
				{* formal data *}
				{if !empty($detail.content.formal)}
					<table class="detail_content_table">
						{foreach $detail.content.formal as $formal}
							<tr>
								<td><h4>{$formal[0]}:</h4></td>
								<td>{$formal[1]}</td>
							</tr>
						{/foreach}
					</table>
				{/if}
				{if !empty($detail.content.description)}
					<div class="detail_description_print">
						{embed param1=$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->

	
	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div id="moredetails_print">
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{*{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}*}
		</div>
	</div>
	{include file='include/annotation_include_print.tpl'}

	<div class="clear"> </div>
{/block}
