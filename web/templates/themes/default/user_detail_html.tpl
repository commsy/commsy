{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a title="___COMMON_ACTION_EDIT___" class="edit {if $detail.is_action_bar_visible}item_actions_glow{/if}" data-custom="expand: 'edit_expand'" href="#"><span class="edit_set{if $detail.is_action_bar_visible}_ok{/if}"> &nbsp; </span></a>
			{if $detail.config.show_configuration}
				<a title="___COMMON_ACTION_USER_DETAILS___" lass="detail {if $detail.is_details_bar_visible}item_actions_glow{/if}" data-custom="expand: 'detail_expand'" href="#"><span class="details_ia{if $detail.is_details_bar_visible}_ok{/if}"> &nbsp; </span></a>
			{/if}
		</div>
	</div>

	<div class="item_body"> <!-- Start item body -->
		<div id="edit_expand" {if !$detail.is_action_bar_visible}class="hidden"{/if}>
			<div class="fade_in_ground_actions">
				{if $detail.actions.edit}
					<a id="action_edit" class="open_popup" data-custom="iid: {$detail.item_id}, module: '{$environment.module}'" href="#">___COMMON_EDIT_ITEM___</a> |
				    {if $detail.config.show_leave === true}<a class="open_popup" data-custom="iid: {$detail.item_id}, module: 'userParticipation'" href="#">___COMMON_CLOSE_PARTICIPATION___</a> |{/if}
				{else}
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_EDIT_ITEM___</span> |
					<span title="___COMMON_NO_ACTION___" class="disabled_actions">___COMMON_CLOSE_PARTICIPATION___</span> |
				{/if}
				<a href="commsy.php?cid={$environment.cid}&mod=download&fct=action&iid={$detail.item_id}" target="_blank">___COMMON_DOWNLOAD___</a>

            {include file="include/detail_actions_plugins_html.tpl"}

			</div>
		</div>

		<h2>
			{if !empty($detail.content.first_block.fullname)}
				{$detail.content.first_block.fullname}
			{/if}
		</h2>
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

		<div class="detail_content">
				<div class="user_profil_blocks">
					{* formal data *}
					{if !empty($detail.content.first_block)}
						<table>
			{*
							{if !empty($detail.content.first_block.title)}
								<tr>
									<td class="label"><h4>___USER_TITLE___</h4></td>
									<td>{$detail.content.first_block.title}</td>
								</tr>
							{/if}
			*}
							{if !empty($detail.content.first_block.birthday)}
								<tr>
									<td class="label"><h4>___USER_BIRTHDAY___</h4></td>
									<td>{$detail.content.first_block.birthday}</td>
								</tr>
							{/if}
						</table>
					{/if}

					{*if !empty($detail.content.second_block)*}
						<table>
							{*if !empty($detail.content.second_block.email)*}
								<tr>
									<td class="label"><h4>___USER_EMAIL___</h4></td>
									<td>
										{if $detail.content.hidden.email == true}
											___USER_EMAIL_HIDDEN___ <a class="open_popup" data-custom="module: 'mailtouser', iid: {$detail.item_id}" href="#">___USER_EMAIL_HIDE_SEND___</a>
										{else}
											<a href="mailto:{$detail.content.second_block.email}">{$detail.content.second_block.email}</a>
										{/if}
									</td>
								</tr>
							{*/if*}

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
					{*/if*}

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
										{$detail.content.messenger_block.icq}{*(<img style="vertical-align:middle; margin-bottom:5px;" src="{$detail.content.indicators.icq}" alt="ICQ Online Status Indicator"/>)*}
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
										{$detail.content.messenger_block.msn}{*(<img style="vertical-align:middle; margin-bottom:5px;" src="{$detail.content.indicators.msn}" alt="MSN Online Status Indicator"/>)*}
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
									<a href="{$detail.content.homepage}" target="_blank" title="{$detail.content.homepage}" href="">{$detail.content.homepage|truncate:60:"...":true}</a>
								</td>
							</tr>
						</table>
					{/if}
				</div>
			</p>
			<div class="clear"> </div>
				{if !empty($detail.content.description)}
					<div class="detail_description">
						{embed param1=$detail.content.description}
					</div>
				{/if}
	</div>

	{if $detail.config.show_configuration}
		<h2>___USER_PREFERENCES___({i18n tag=COMMON_READABLE_ONLY_USER param1=$detail.content.first_block.fullname})</h2>
		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
					<div class="user_account_blocks">
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

							{if !empty($detail.configcontent.mailing_delete_entries)}
								<tr>
									<td class="label"><h4>___DELETE_ENTRY_WANT_MAIL___</h4></td>
									<td>
										{if $detail.configcontent.mailing_delete_entries == 'yes'}
											___COMMON_YES___
										{elseif $detail.configcontent.mailing_delete_entries == 'no'}
											___COMMON_NO___
										{else}
											___COMMON_MESSAGETAG_ERROR___
										{/if}
									</td>
								</tr>
							{/if}

                     {if !empty($detail.configcontent.plugin_array)}
                        {foreach from=$detail.configcontent.plugin_array item=plugin_item}
                        <tr>
                           <td class="label"><h4>{$plugin_item.title}</h4></td>
                           <td>
                              {$plugin_item.desc}
                           </td>
                        </tr>
                        {/foreach}
                     {/if}
                     
                     {if $detail.config.show_configuration or $environment.is_moderator}
	                     {if !empty($detail.content.expired_password)}
	                     <tr>
							<td class="label"><h4>___USER_EXPIRED_PASSWORD___</h4></td>
							<td>
								{$detail.content.expired_password}
							</td>
						</tr>
						{/if}
						
						{if !empty($detail.content.agb_acceptance)}
						<tr>
							<td class="label"><h4>___USER_ACCEPTED_AGB___</h4></td>
							<td>
								{$detail.content.agb_acceptance}
							</td>
						</tr>
						{/if}
					{/if}
					    </table>

					</div>
			</div>
		</div> <!-- Ende item_legend -->

		<div id="detail_expand" {if !$detail.is_details_bar_visible}class="hidden"{/if}>
			{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}
		</div>
	{else}
		{if $environment.is_moderator}
		<h2>___USER_PREFERENCES___({i18n tag=COMMON_READABLE_ONLY_USER param1=$detail.content.first_block.fullname})</h2>
		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd">
					<div class="user_account_blocks">
					    <table>
					    {if $detail.config.show_configuration or $environment.is_moderator}
		                     {if !empty($detail.content.expired_password)}
		                     <tr>
								<td class="label"><h4>___USER_EXPIRED_PASSWORD___</h4></td>
								<td>
									{$detail.content.expired_password}
								</td>
							</tr>
							{/if}
							
							{if !empty($detail.content.agb_acceptance)}
							<tr>
								<td class="label"><h4>___USER_ACCEPTED_AGB___</h4></td>
								<td>
									{$detail.content.agb_acceptance}
								</td>
							</tr>
							{/if}
						{/if}
					    </table>
					 </div>
				</div>
			</div>
		{/if}
	{/if}
	
	

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}