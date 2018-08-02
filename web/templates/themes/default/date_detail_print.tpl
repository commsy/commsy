{extends file="room_detail_print.tpl"}

{block name=header_content_print}
	{*<div style="padding-bottom: 7px;"><h2>{$environment.room_title}</h2></div>*}
	<h4>___COMMON_DATE___</h4>
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
		
	    {*{include file="include/detail_linked_print.tpl"}*}

        {*<h2>
            {$detail.content.title}
        </h2>*}
        
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

        <div id="item_credits" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767;margin-bottom:0px;">
            <p id="ic_rating"></p>
			{*<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>*}
			<div class="clear"> </div>
		</div>

		<div class="detail_content detail_margin" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767">
            <p>
                <div class="user_profil_blocks">
                    {* formal data *}
                    <table>
						{if $detail.content.privat}
                    		<tr>
                                <td class="label"><h4>___COMMON_PRIVATE_DATE___</h4></td>
                                <td>___COMMON_NOT_ACCESSIBLE___</td>
                            </tr>
                    	{/if}

						<tr>
							<td class="label"><h4>___DATES_DATETIME___</h4></td>
							<td>{$detail.content.datetime}</td>
						</tr>

						{if !empty($detail.content.place)}
                            <tr>
                                <td class="label"><h4>___DATES_PLACE___</h4></td>
                                <td>{$detail.content.place}</td>
                            </tr>
                        {/if}

                        {if !empty($detail.content.color)}
                            <tr>
                                <td class="label"><h4>___DATES_COLOR___</h4></td>
								{* TODO: *}
                                <td><img id="color_box" src="images/spacer.gif" style="background-color: {$detail.content.color};"/></td>
                            </tr>
                        {/if}

						{if !empty($detail.files)}
							<tr>
								<td class="label"><h4>___MATERIAL_FILES___</h4></td>
								<td>
									{foreach $detail.files as $file}
										{$file}
									{/foreach}
								</td>
							</tr>
						{/if}

						<tr>
							<td class="label"><h4>___DATE_PARTICIPANTS___</h4></td>
							<td>
								{foreach $detail.content.member as $member}
									{if $member.is_user}
										{if $member.visible}
											{if $member.as_link}
												<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$member.item_id}" title="{$member.linktext|truncate:35:'...':true}">{$member.linktext|truncate:35:'...':true}</a>
											{else}
												{$member.linktext}
											{/if}
										{/if}
									{else}
										{if $member.as_link}
											{* TODO: disabled style? *}
											<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}" title="{$member.linktext|truncate:35:'...':true}">
												___USER_STATUS_REJECTED___
											</a>
										{else}
											{$member.linktext}
										{/if}
									{/if}

									{if !$member@last}, {/if}
								{foreachelse}
									___TODO_NO_PROCESSOR___
								{/foreach}
							</td>
						</tr>
                    </table>
                </div>
            </p>
            <div class="clear"> </div>
        </div>

		<div id="item_legend"> <!-- Start item_legend -->
			<div class="detail_content" style="background-color: #FFFFFF;border-right:1px solid #676767;border-left:1px solid #676767">
				{if !empty($detail.content.description)}
					<div class="detail_description_print">
						{embed param1=$detail.content.description}
					</div>
				{/if}
			</div>
		</div> <!-- Ende item_legend -->
		<div id="detail_expand" {if in_array("detail_expand",$detail.printcookie)}class="hidden"{/if}>
			{include file="include/detail_moredetails_print.tpl" data=$detail.content.moredetails}
		</div>

    </div> <!-- Ende item body -->
    <div class="clear"> </div>
    {include file='include/annotation_include_print.tpl'}
    <div class="clear"> </div>
{/block}
