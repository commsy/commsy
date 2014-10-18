{extends file="room_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_USER___</h4>
	<br>
	{*<div> <h4>___COMMON_RESTRICTIONS___</h4></div>
	{foreach $list.restriction_text_parameters as $params}
		{$params.name},
	{/foreach}
	<br>*}
	
{/block}

{block name=room_detail_content}

	<div id="main_navigation_print" style="border:1px solid #676767;"><h1>{$detail.content.first_block.fullname}</h1></div>
	<div class="item_body_print"> <!-- Start item body -->

		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{* TODO *}
		</div>
		<!-- Ende fade_in_ground -->

		{*<h2>
			{if !empty($detail.content.first_block.fullname)}
				{$detail.content.first_block.fullname}
			{/if}
		</h2>*}
		
		
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
			{if !empty($detail.content.description)}
				<div class="detail_description_print">
					{embed param1=$detail.content.description}
				</div>
			{/if}
	</div>
	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}