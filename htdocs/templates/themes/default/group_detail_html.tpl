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
		<h2>
			{$detail.content.title}
		</h2>
		<div class="clear"> </div>

		<div id="item_credits">
			<p id="ic_rating"></p>
			<p>
				{if !empty($detail.content.description)}
					{$detail.content.description}
				{/if}
				
				<div class="user_profil_blocks">
					{if $detail.content.show_picture}
						<div id="user_profil_picture">
							<img alt="Portrait" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$detail.content.picture}" />
						</div>
					{/if}
				</div>
				
				<div class="user_profil_blocks">
					<h4>___GROUP_MEMBERS___</h4>
					{if !empty($detail.content.members)}
						<table>
							<tr>
								<td>
									<ul>
										{section name=members_col1 loop=$detail.content.members start=0 step=3}
											{$member=$detail.content.members[members_col1]}
											
											<li>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.linktext}">{$member.linktext}</a>
											</li>
										{/section}
									</ul>
								</td>
								<td>
									<ul>
										{section name=members_col2 loop=$detail.content.members start=1 step=3}
											{$member=$detail.content.members[members_col2]}
											
											<li>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.linktext}">{$member.linktext}</a>
											</li>
										{/section}
									</ul>
								</td>
								<td>
									<ul>
										{section name=members_col3 loop=$detail.content.members start=2 step=3}
											{$member=$detail.content.members[members_col3]}
											
											<li>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.linktext}">{$member.linktext}</a>
											</li>
										{/section}
									</ul>
								</td>
							</tr>
						</table>
					{else}
						___COMMON_NONE___
					{/if}
				</div>
			</p>
			<div class="clear"> </div>
		</div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
				
			</div>
		</div> <!-- Ende item_legend -->

	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}