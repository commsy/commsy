{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href="#"><span class="edit_set"> &nbsp; </span></a>
			<a class="detail" href="#"><span class="details_ia"> &nbsp; </span></a>
		</div>
	</div>
	
	<div class="item_body"> <!-- Start item body -->
		
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO *}
		</div>
		<!-- Ende fade_in_ground -->
		
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>
		
		<div id="item_credits">
			<p id="ic_rating">
			</p>
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
			<div class="clear"> </div>
		</div>
		
		<div id="item_credits">
			<p>
				<div class="user_profil_blocks">
					{* files *}
					{if !empty($detail.content.files)}
					<table>
						<tr>
							<td class="label"><h4>___MATERIAL_FILES___</h4></td>
							<td>
								{$detail.content.files}
							</td>
						</tr>
					</table>
					{/if}
					
					{if $detail.content.path_shown}
						<h3>___TOPIC_PATH___</h3>
						
						<ul>
							{foreach $detail.content.path_items as $item}
								<li>
									{$item@iteration}.
									{if $item.not_activated}
										<a href="commsy.php?cid={$environment.cid}&mod={$item.mod}&fct=detail&iid={$item.iid}&path={$detail.content.item_id}" title="{$item.title}">{$item.link_text}</a>
									{else}
										<a href="commsy.php?cid={$environment.cid}&mod={$item.mod}&fct=detail&iid={$item.iid}&path={$detail.content.item_id}" title="{$item.type} - {$item.title}">{$item.title}</a>
									{/if}
								</li>
							{/foreach}
						</ul>
					{/if}
				</div>
			</p>
			<div class="clear"> </div>
		</div>
		
		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">				
				{if !empty($detail.content.description)}
					<div class="detail_description">
						{$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->
		{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}