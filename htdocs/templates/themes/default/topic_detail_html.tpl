{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
			<a href=""><span class="ref_to_ia"> &nbsp; </span></a>
		</div>
	</div>
	
	<div class="item_body"> <!-- Start item body -->
		<h2>{$detail.content.title}</h2>
		<div class="clear"> </div>
		
		<div id="item_credits">
			<p id="ic_rating">
			</p>
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
		
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="clear"> </div>
{/block}

{block name=default_room_portlets append}
	<div class="portlet_rc">
		<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>___COMMON_NETNAVIGATION_ENTRIES___ ({$netnavigation.linked_items.count})</h2>

		<div class="clear"> </div>

		{if $netnavigation.linked_items.edit}
			<a href="{$netnavigation.linked_items.edit_link}" title="___COMMON_ITEM_ATTACH___" class="btn_body_rc">
				<img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_ITEM_ATTACH___" />
			</a>
		{/if}
		
		<div class="portlet_rc_body">
			{if empty($netnavigation.linked_items.items)}
				___COMMON_NONE___
			{else}
				<ul>
					{foreach $netnavigation.linked_items.items as $item}
						<li>
							<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$item.module}&fct=detail&iid={$item.linked_iid}" title="{$item.link_creator_text}">
								<img src="{$item.img}" title="{$item.link_creator_text}"/>
							</a>
							<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$item.module}&fct=detail&iid={$item.linked_iid}" title="{$item.link_creator_text}">
								{$item.title|truncate:35:"...":true}
							</a>
						</li>
					{/foreach}
				</ul>
			{/if}
		</div>
	</div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}