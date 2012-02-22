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
                </div>
            </p>
            <div class="clear"> </div>
        </div>

    </div> <!-- Ende item body -->
    <div class="clear"> </div>
    {include file='include/annotation_include_html.tpl'}
    <div class="clear"> </div>
{/block}