{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

	<div class="item_body_print"> <!-- Start item body -->
	    
		{include file="include/detail_linked_print.tpl"}
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>

		<div id="item_credits_print">
			<p id="ic_rating">
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
			<div class="detail_content_print">
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
						{$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->

	
	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div id="moredetails_print">
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}
		</div>
	</div>
	{include file='include/annotation_include_print.tpl'}

	<div class="clear"> </div>
{/block}
