{extends file="room_detail_print.tpl"}

{block name=room_detail_content}

    <div class="item_body_print"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{if $detail.actions.edit}
				<a id="action_edit" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}" title="___COMMON_EDIT_ITEM___">___COMMON_EDIT_ITEM___</a> |
			{/if}
			{if $detail.actions.date_leave}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.item_id}&date_option=2" title="___DATE_LEAVE___">___DATE_LEAVE___</a> |
			{/if}

			{if $detail.actions.date_participate}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.item_id}&date_option=1" title="___DATE_ENTER___">___DATE_ENTER___</a> |
			{/if}
			{if $detail.actions.delete}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail{params params=$detail.actions.delparams}" title="___COMMON_DELETE_ITEM___">___COMMON_DELETE_ITEM___</a> |
			{/if}
			{if $detail.actions.mail}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=mail&iid={$detail.content.item_id}" alt="___COMMON_EMAIL_TO___">___COMMON_EMAIL_TO___</a> |
			{/if}
			{if $detail.actions.copy}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail&iid={$detail.content.item_id}&add_to_{$environment.module}_clipboard={$detail.content.item_id}" title="___COMMON_ITEM_COPY_TO_CLIPBOARD___">___COMMON_ITEM_COPY_TO_CLIPBOARD___</a> |
			{/if}
			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=detail{params params=$detail.actions.downloadparams}" title="___COMMON_DOWNLOAD___">___COMMON_DOWNLOAD___</a>
		</div>
		<!-- Ende fade_in_ground -->

	    {include file="include/detail_linked_print.tpl"}

        <h2>
            {$detail.content.title}
        </h2>
        <div class="clear"> </div>

        <div id="item_credits">
            <p id="ic_rating"></p>
			<p>
				___COMMON_LAST_MODIFIED_BY_UPPER___
				{build_user_link status=$detail.content.moredetails.last_modificator_status user_name=$detail.content.moredetails.last_modificator id=$detail.content.moredetails.last_modificator_id}
				___DATES_ON_DAY___  {$detail.content.moredetails.last_modification_date}
			</p>
			<div class="clear"> </div>
		</div>

		<div class="detail_content detail_margin">
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
			<div class="detail_content">
				{if !empty($detail.content.description)}
					<div class="detail_description_print">
						{$detail.content.description}
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
