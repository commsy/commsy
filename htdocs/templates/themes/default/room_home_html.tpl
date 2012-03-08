{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="Ansicht in Reihen"><img src="{$basic.tpl_path}img/btn_row_view.gif" alt="Reihen" /></a>
    <a href="" title="Ansicht in Portlets"><img src="{$basic.tpl_path}img/btn_portlet_view.gif" alt="Portlets" /></a>
{/block}

{block name=room_navigation_rubric_title}
	&Uuml;bersicht Projektraum (Home)
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		{foreach $room.home_content as $rubric}
			<div class="content_item"> <!-- Start content_item -->
            	<div class="ci_head_actions">
	            	<a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=edit&iid=NEW" title="___HOME_SMARTY_ACTION_NEW___">
	            		<img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="___HOME_SMARTY_ACTION_NEW___" />
	            	</a>
					{if $rubric.items|count == 0}
						<a class="open_close">
							<div class="disabled"></div>
						</a>
					{else}
						<a href="" class="open_close" title="{if $rubric.hidden}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}">
							<img src="{$basic.tpl_path}img/{if $rubric.hidden}btn_ci_open.gif{else}btn_ci_close.gif{/if}" alt="{if $rubric.hidden}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" />
						</a>
					{/if}
                </div>
                <h2>
                	___COMMON_{$rubric@key|upper}_INDEX___
                	<span>
                		({$rubric.message_tag})
                	</span>
                </h2>

                <div class="clear"> </div>

                <div class="list_wrap{if $rubric.hidden} hidden{/if}">

	                {foreach $rubric.items as $item}
	                	<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_home{else}even_sep_home{/if}">
	                    	<div class="column_320">
	                        	{if $rubric@key == 'discussion'}
		                        	<p class="column_addon">
		                        		{$item.column_1_addon}
		                        	</p>
	                        	{/if}
	                        	<p>
								{if $item.noticed.show_info}
									<a class="new_item">
									{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
									<img title="" class="new_item_home_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
									{elseif $item.noticed.status == "new"}
									<img title="" class="new_item_home_2" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
									{elseif $item.noticed.status == "modified"  and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
									<img title="" class="new_item_home_2" src="{$basic.tpl_path}img/flag_neu_2_a.gif" alt="*" /></a>
									{elseif $item.noticed.status == "modified"}
									<img title="" class="new_item_home_2" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
									{elseif $item.noticed.annotation_info.count_new}
									<img title="" class="new_item_home_2" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
									{elseif $item.noticed.annotation_info.count_changed}
									<img title="" class="new_item_home_2" src="{$basic.tpl_path}img/flag_neu_2_a.gif" alt="*" /></a>
									{/if}
									<span class="tooltip">
										<span class="header">___COMMON_CHANGE_INFORAMTION___</span><br/>
										<span class="content">{$item.noticed.item_info}</span>
										{if $item.noticed.annotation_info.count_new}
											<span class="content">___COMMON_NEW_ANNOTATIONS___: {$item.noticed.annotation_info.count_new}
											{foreach $item.noticed.annotation_info.anno_new_items as $anno_item}
											   <br/>
											   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$anno_item.ref_iid}#annotation{$anno_item.iid}">{$anno_item.title|truncate:25:'...':true}</a> ({$anno_item.date})
											   </span>
											{/foreach}
											</span>
										{/if}
										{if $item.noticed.annotation_info.count_changed}
											<span class="content">___COMMON_CHANGED_ANNOTATIONS___: {$item.noticed.annotation_info.count_changed}
											{foreach $item.noticed.annotation_info.anno_changed_items as $anno_item}
											   <br/>
											   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$anno_item.ref_iid}#annotation{$anno_item.iid}">{$anno_item.title|truncate:25:'...':true}</a> ({$anno_item.date})
											   </span>
											{/foreach}
											</span>
										{/if}
									</span>

				         			<input class="new_item_2" type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
				        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
								{else}
				         			<input type="checkbox" name="form_data[attach][{$item.iid}]" value="1"/>
				        			<input type="hidden" name="form_data[shown][{$item.iid}]" value="1"/>
								{/if}
		                            <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&iid={$item.iid}">{$item.column_1}</a>
	                            </p>
	                        </div>
							<div class="column_45">
								<p>
									<a href="" class="attachment{if $item.attachment_count == 0}_none_overlay{/if}">{$item.attachment_count}</a>
								</p>
								{if $item.attachment_count > 0}
									<div class="tooltip">
										<div class="scrollable">
											<ul>
											{foreach $item.attachment_infos as $file}
												<li>
													<a href="{$file.file_url}" target="blank"{if $file.lightbox} rel="lightbox"{/if}>
														{$file.file_icon} {$file.file_name}
													</a>
													({$file.file_size} KB)
												</li>
											{/foreach}
											</ul>
										</div>
									</div>
								{/if}
							</div>
                        	<div class="column_140">
                            	<p>{$item.column_2}</p>
                            </div>
	                        <div class="column_194">
	                        	<p>
		                        	{if $rubric@key == 'material' or $rubric@key == 'announcement' or $rubric@key == 'discussion'}
		                            	<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.user_iid}">{$item.column_3}</a>
		                        	{else}
		                        		{$item.column_3}
		                            {/if}
	                            </p>
	                        </div>
		                    <div class="clear"> </div>
	                    </div>
	                {/foreach}

                </div>
            </div> <!-- Ende content_item -->
		{/foreach}
	</div>
{/block}


{block name=sidebar_tagbox_treefunction}
	{function name=tag_tree level=0}
		<ul>
		{foreach $nodes as $node}
			<li	id="node_{$node.item_id}"
				{if $node.children|count > 0}class="folder"{/if}
				data="url:'commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&name=selected&seltag_{$level}={$node.item_id}&seltag=yes'">{$node.title}
			{if $node.children|count > 0}	{* recursive call *}
				{tag_tree nodes=$node.children level=$level+1}
			{/if}
		{/foreach}
		</ul>
	{/function}
{/block}

{block name=sidebar_buzzwordbox_buzzword}
	<a href="commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
{/block}