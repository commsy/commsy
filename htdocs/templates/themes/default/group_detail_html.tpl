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
				<div class="user_profil_blocks">
					{* formal data *}
					{if !empty($detail.content.first_block)}
						<table>
							{if !empty($detail.content.first_block.fullname)}
								<tr>
									<td class="label"><h4>___USER_TITLE___</h4></td>
									<td>{$detail.content.first_block.fullname}</td>
								</tr>
							{/if}
							{if !empty($detail.content.first_block.birthday)}
								<tr>
									<td class="label"><h4>___USER_BIRTHDAY___</h4></td>
									<td>{$detail.content.first_block.birthday}</td>
								</tr>
							{/if}
						</table>
					{/if}
					
					{if !empty($detail.content.second_block)}
						<table>
							{if !empty($detail.content.second_block.email)}
								<tr>
									<td class="label"><h4>___USER_EMAIL___</h4></td>
									<td>
										{if $detail.content.hidden.email == true}
											___USER_EMAIL_HIDDEN___
										{else}
											{$detail.content.second_block.email}
										{/if}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.second_block.telephone)}
								<tr>
									<td class="label"><h4>___USER_TELEPHONE___</h4></td>
									<td>
										{$detail.content.second_block.telephone}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.second_block.cellularphone)}
								<tr>
									<td class="label"><h4>___USER_CELLULARPHONE___</h4></td>
									<td>
										{$detail.content.second_block.cellularphone}
									</td>
								</tr>
							{/if}
						</table>
					{/if}
					
					{if !empty($detail.content.third_block)}
						<table>
							{if !empty($detail.content.third_block.street)}
								<tr>
									<td class="label"><h4>___USER_STREET___</h4></td>
									<td>
										{$detail.content.third_block.street}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.third_block.city)}
								<tr>
									<td class="label"><h4>___USER_CITY___</h4></td>
									<td>
										{$detail.content.third_block.city}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.third_block.room)}
								<tr>
									<td class="label"><h4>___USER_ROOM___</h4></td>
									<td>
										{$detail.content.third_block.room}
									</td>
								</tr>
							{/if}
						</table>
					{/if}
					
					{if !empty($detail.content.fourth_block)}
						<table>
							{if !empty($detail.content.fourth_block.organisation)}
								<tr>
									<td class="label"><h4>___USER_ORGANISATION___</h4></td>
									<td>
										{$detail.content.fourth_block.organisation}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.fourth_block.position)}
								<tr>
									<td class="label"><h4>___USER_POSITION___</h4></td>
									<td>
										{$detail.content.fourth_block.position}
									</td>
								</tr>
							{/if}
						</table>
					{/if}
				</div>
				
				<div id="user_profil_picture">
					{if !empty($detail.content.picture.src)}
						<img alt="___USER_PICTURE_UPLOADFILE___" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$detail.content.picture.src}"/>
					{else}
						<img alt="___USER_PICTURE_UPLOADFILE___" src="{$basic.tpl_path}img/user_unknown.gif" title="{i18n tag=USER_PICTURE_NO_PICTURE param1=$detail.content.first_block}"/>
					{/if}
				</div>
				
				<div class="user_profil_blocks">
					{if !empty($detail.content.messenger_block)}
						<table>
							{if !empty($detail.content.messenger_block.icq)}
								<tr>
									<td class="label"><h4>___USER_ICQ___</h4></td>
									<td>
										{$detail.content.messenger_block.icq}(<img style="vertical-align:middle; margin-bottom:5px;" src="{$detail.content.indicators.icq}" alt="ICQ Online Status Indicator"/>)
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.messenger_block.jabber)}
								<tr>
									<td class="label"><h4>___USER_JABBER___</h4></td>
									<td>
										{$detail.content.messenger_block.jabber}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.messenger_block.msn)}
								<tr>
									<td class="label"><h4>___USER_MSN___</h4></td>
									<td>
										{$detail.content.messenger_block.msn}(<img style="vertical-align:middle; margin-bottom:5px;" src="{$detail.content.indicators.msn}" alt="MSN Online Status Indicator"/>)
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.messenger_block.skype)}
								<tr>
									<td class="label"><h4>___USER_SKYPE___</h4></td>
									<td>
										{$detail.content.messenger_block.skype}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.content.messenger_block.yahoo)}
								<tr>
									<td class="label"><h4>___USER_YAHOO___</h4></td>
									<td>
										{$detail.content.messenger_block.yahoo}
									</td>
								</tr>
							{/if}
						</table>
					{/if}
					
					{if !empty($detail.content.homepage)}
						<table>
							<tr>
								<td class="label"><h4>___USER_HOMEPAGE___</h4></td>
								<td>
									{$detail.content.homepage|truncate:60:"...":true}
								</td>
							</tr>
						</table>
					{/if}
				</div>
			</p>
			<div class="clear"> </div>
		</div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
				{if $detail.config.show_configuration}
					<div class="detail_description">
						<h4>___USER_PREFERENCES___({i18n tag=COMMON_READABLE_ONLY_USER param1=$detail.content.first_block.fullname})</h4>
						{*
							$html .= '<div style="float:right">';
					         $html .= $this->getAccountActionsAsHTML($item);
					         $html .= '</div>';
					    *}
					    
					    <table>
					    	<tr>
								<td class="label"><h4>___COMMON_ACCOUNT___</h4></td>
								<td>
									{$detail.configcontent.user_id}
								</td>
							</tr>
							
							{if !empty($detail.configcontent.auth_source)}
								<tr>
									<td class="label"><h4>___USER_AUTH_SOURCE___</h4></td>
									<td>
										{$detail.configcontent.auth_source}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.status)}
								<tr>
									<td class="label"><h4>___COMMON_STATUS___</h4></td>
									<td>
										{$detail.configcontent.status}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.contact)}
								<tr>
									<td class="label"><h4>___ROOM_CONTACT_SINGULAR___</h4></td>
									<td>
										{if $detail.configcontent.contact == 'common_yes'}
											___COMMON_YES___
										{else}
											___COMMON_NO___
										{/if}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.language)}
								<tr>
									<td class="label"><h4>___USER_LANGUAGE___</h4></td>
									<td>
										{if $detail.configcontent.language == 'browser'}
											___BROWSER___
										{else}
											{$detail.configcontent.language}
										{/if}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.visbility)}
								<tr>
									<td class="label"><h4>___ACCOUNT_VISIBLE_PROPERTY___</h4></td>
									<td>
										{if $detail.configcontent.visbility == 'always'}
											___VISIBLE_ALWAYS___
										{else}
											___VISIBLE_ONLY_LOGGED___
										{/if}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.mailing)}
								<tr>
									<td class="label"><h4>___ACCOUNT_EMAIL_MEMBERSHIP___</h4></td>
									<td>
										{if $detail.configcontent.mailing == 'yes'}
											___COMMON_YES___
										{elseif $detail.configcontent.mailing == 'no'}
											___COMMON_NO___
										{else}
											___COMMON_MESSAGETAG_ERROR___
										{/if}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.mailing_room)}
								<tr>
									<td class="label"><h4>___USER_MAIL_ROOM___</h4></td>
									<td>
										{if $detail.configcontent.mailing_room == 'yes'}
											___COMMON_YES___
										{elseif $detail.configcontent.mailing_room == 'no'}
											___COMMON_NO___
										{else}
											___COMMON_MESSAGETAG_ERROR___
										{/if}
									</td>
								</tr>
							{/if}
							
							{if !empty($detail.configcontent.mailing_material)}
								<tr>
									<td class="label"><h4>___ACCOUNT_EMAIL_MATERIAL___</h4></td>
									<td>
										{if $detail.configcontent.mailing_material == 'yes'}
											___COMMON_YES___
										{elseif $detail.configcontent.mailing_material == 'no'}
											___COMMON_NO___
										{else}
											___COMMON_MESSAGETAG_ERROR___
										{/if}
									</td>
								</tr>
							{/if}
							
							
							
							
					    </table>
						
		{*

         $html .='<div class="detail_content" style=" margin-top: 5px; border-top:1px solid #B0B0B0; border-left:0px solid #B0B0B0; border-right:0px solid #B0B0B0; border-bottom:0px solid #B0B0B0;">'.LF;
         $html .= $this->_getSubItemAsHTML($current_item,1).LF;
         $html .='</div>'.LF;
         $html .='<div style="clear:both;">'.LF;
         $html .='</div>'.LF;
         $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
         *}

						
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->

	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="clear"> </div>
{/block}

{block name=default_room_portlets}
	<div class="portlet_rc">
		<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
		<h2>
			{if $netnavigation.linked_items.is_community}
				___COMMON_ATTACHED_INSTITUTIONS___ ({$netnavigation.linked_items.count})
			{else}
				___COMMON_ATTACHED_GROUPS___ ({$netnavigation.linked_items.count})
			{/if}
		</h2>

		<div class="clear"> </div>

		<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		<div class="portlet_rc_body">
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
		</div>
	</div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}