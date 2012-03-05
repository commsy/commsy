{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
    <div class="item_actions">
		<div id="top_item_actions">
			<a class="edit" href=""><span class="edit_set"> &nbsp; </span></a>
			<a class="linked" href=""><span class="ref_to_ia"> &nbsp; </span></a>
			<a class="detail" href=""><span class="details_ia"> &nbsp; </span></a>
			<a class="annotations" href="#"><span class="ref_to_anno"> &nbsp; </span></a>
			<div class="anno_count" >{$detail.annotations|@count}</div>
		</div>
	</div>

    <div class="item_body"> <!-- Start item body -->
		<!-- Start fade_in_ground -->
		<div class="fade_in_ground_actions hidden">
			{if $detail.actions.edit}
				<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid={$detail.content.item_id}" title="___COMMON_EDIT_ITEM___">___COMMON_EDIT_ITEM___</a> |
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

	    {include file="include/detail_linked_html.tpl"}
		
        <h2>
            {$detail.content.title}
        </h2>
        <div class="clear"> </div>

        <div id="item_credits">
            <p id="ic_rating"></p>
            <p>
                <div class="user_profil_blocks">
                    {* formal data *}
                    <table>
                    	{if $detail.content.private}
                    		<tr>
                                <td class="label"><h4>___COMMON_PRIVATE_DATE___</h4></td>
                                <td>___COMMON_NOT_ACCESSIBLE___</td>
                            </tr>
                    	{/if}
                        {if !empty($detail.content.timeline1)}
                            <tr>
                                <td class="label"><h4>___DATES_DATETIME___</h4></td>
                                <td>{$detail.content.timeline1}</td>
                            </tr>
                        {/if}
                        {if !empty($detail.content.timeline2)}
                            <tr>
                                <td class="label">&nbsp;</td>
                                <td>{$detail.content.timeline2}</td>
                            </tr>
                        {/if}
                        {if !empty($detail.content.place)}
                            <tr>
                                <td class="label"><h4>___DATES_PLACE___</h4></td>
                                <td>{$detail.content.place}</td>
                            </tr>
                        {/if}
                        {if !empty($detail.content.color)}
                            <tr>
                                <td class="label"><h4>___DATES_COLOR___</h4></td>
                                <td><img id="color_box" src="images/spacer.gif" style="background-color: {$detail.content.color};"/></td>
                            </tr>
                        {/if}
                        {if !empty($detail.content.member)}
                            <tr>
                                <td class="label"><h4>___DATE_PARTICIPANTS___</h4></td>
                                <td>{$detail.content.member}</td>
                            </tr>
                        {/if}
                        {if empty($detail.content.member)}
                            <tr>
                                <td class="label"><h4>___DATE_PARTICIPANTS___</h4></td>
                                <td>___TODO_NO_PROCESSOR___</td>
                            </tr>
                        {/if}
                        {if !empty($detail.content.member)}
                            <tr>
                                <td colspan="2">{$detail.content.description}</td>
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
                    </table>
                    {if !empty($detail.lastedit)}
                    	{$detail.lastedit}
                    {/if}
                </div>
            </p>
            <div class="clear"> </div>
        </div>
		{include file="include/detail_moredetails_html.tpl" data=$detail.content.moredetails}

    </div> <!-- Ende item body -->
    <div class="clear"> </div>
    {include file='include/annotation_include_html.tpl'}
    <div class="clear"> </div>
{/block}

{block name=room_right_portlets_navigation}
	{foreach $detail.forward_information as $entry}
		<a title="{$entry.title}" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&iid={$entry.item_id}">{$entry.position}. {if $entry.is_current}<strong>{/if}{$entry.title|truncate:25:'...':true}{if $entry.is_current}</strong>{/if}</a>
	{/foreach}
{/block}