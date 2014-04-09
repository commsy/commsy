{extends file="room_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_GROUP___</h4>
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
		{*<h2>
			{$detail.content.title}
		</h2>*}
		<div class="clear"> </div>

		<div id="item_credits">
			{*<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>*}
		</div>

		<div id="item_legend" style="border-left:1px solid #676767;border-right:1px solid #676767;"> <!-- Start item_legend -->
			<div class="row_odd">
				{if $detail.content.show_picture}
					<div id="group_profil_picture">
						<img alt="Portrait" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$detail.content.picture}" />
					</div>
				{/if}
				{if !empty($detail.content.description)}
					<div class="detail_description_print">
					{embed param1=$detail.content.description}
					</div>
				{/if}
				<div class="clear"> </div>


				<div class="detail_description_print">
					<h4>___GROUP_MEMBERS___</h4>
					{if !empty($detail.content.members)}
						<table class="no_padding">
							<tr>
								<td>
									<div class="group_member">
										{section name=members_col1 loop=$detail.content.members start=0 step=3}
											{$member=$detail.content.members[members_col1]}
											<div class="group_member_picture">
												{if !empty($member.picture)}
													<img class="group_member_picture" alt="" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$member.picture}" />
												{else}
													<img class="group_member_picture" alt="___USER_PICTURE_UPLOADFILE___" src="images/commsyicons/common/user_unknown.gif" title=""/>
												{/if}
											</div>
											<div>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.firstname}.' '.{$member.lastname}">{$member.firstname}<br/>{$member.lastname}</a>
											</div>
											<div class="clear"> </div>
										{/section}
									</div>
								</td>
								<td>
									<div class="group_member">
										{section name=members_col2 loop=$detail.content.members start=1 step=3}
											{$member=$detail.content.members[members_col2]}
											<div class="group_member_picture">
												{if !empty($member.picture)}
													<img class="group_member_picture" alt="" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$member.picture}" />
												{else}
													<img class="group_member_picture" alt="___USER_PICTURE_UPLOADFILE___" src="images/commsyicons/common/user_unknown.gif" title=""/>
												{/if}
											</div>
											<div>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.firstname}.' '.{$member.lastname}">{$member.firstname}<br/>{$member.lastname}</a>
											</div>
											<div class="clear"> </div>
										{/section}
									</div>
								</td>
								<td>
									<div class="group_member">
										{section name=members_col3 loop=$detail.content.members start=2 step=3}
											{$member=$detail.content.members[members_col3]}
											<div class="group_member_picture">
												{if !empty($member.picture)}
													<img class="group_member_picture" alt="" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$member.picture}" />
												{else}
													<img class="group_member_picture" alt="___USER_PICTURE_UPLOADFILE___" src="images/commsyicons/common/user_unknown.gif" title=""/>
												{/if}
											</div>
											<div>
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.iid}" title="{$member.firstname}.' '.{$member.lastname}">{$member.firstname}<br/>{$member.lastname}</a>
											</div>
											<div class="clear"> </div>
										{/section}
									</div>
								</td>
							</tr>
						</table>
					{else}
						___COMMON_NONE___
					{/if}
				</div>
			</div>
		</div> <!-- Ende item_legend -->
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{*{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}*}
		</div>

	</div> <!-- Ende item body -->
	<div class="clear"> </div>

	<div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a title="{$entry.title}" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title|truncate:25:'...':true}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}