{extends file="room_html.tpl"}

{block name=outer_room_site_actions}
   	{block name=room_site_actions}{/block}
 {/block}


{block name=room_site_actions}
{*	<a href="" title="Ansicht in Reihen"><img src="{$basic.tpl_path}img/btn_row_view.gif" alt="Reihen" /></a>
    <a href="" title="Ansicht in Portlets"><img src="{$basic.tpl_path}img/btn_portlet_view.gif" alt="Portlets" /></a>*}
{/block}

{block name=room_navigation_rubric_title}
	{if $environment.room_type_group}
		___COMMON_HOME_GROUPROOM___
	{else}
		___COMMON_HOME_PROJECTROOM___
	{/if}
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		{if $room.informationbox.show}
			<div class="content_item"> <!-- Start content_item -->
                <h2 style="border-bottom:0px;">
                	<a href="commsy.php?cid={$environment.cid}&mod={$room.informationbox.rubric}&fct=detail&iid={$room.informationbox.iid}">{$room.informationbox.title}</a>
                </h2>
                <div class="clear"> </div>
                <div class="row_even" style="padding:10px;">
                	<div style="max-height: 220px; height:auto !important; height: 220px; overflow:auto; ">
                		<p>
                		<div class="user_profil_blocks">
		                   {if $room.informationbox.rubric == "date"}
		                   <table class="detail_content_table">
								<tr>
									<td class="label"><h4>___DATES_DATETIME___</h4></td>
									<td style="font-size:12px;">{$room.informationbox.date}{if $room.informationbox.time}, {$room.informationbox.time}{/if}</td>
								</tr>
		                    </table>
		                    {/if}
		                </div>
                		</p>
                		<div class="clear"></div>
                	
                		<div class="detail_description">
                			{$room.informationbox.content}
                		</div>
               		</div>
                	<div class="clear"> </div>
               		<div style="float:right;">
               			<a href="commsy.php?cid={$environment.cid}&mod={$room.informationbox.rubric}&fct=detail&iid={$room.informationbox.iid}">___COMMON_INFOBOX_FURTHER_INFORMATION___</a>
               		</div>
                	<div class="clear"> </div>
               </div>
             </div>
		{/if}
		{foreach $room.home_content as $rubric}
			<div class="content_item"> <!-- Start content_item -->
            	<div class="ci_head_actions">
	            	{if $rubric@key != 'user' and $rubric.rights.new}
		            	<a class="open_popup" data-custom="iid: 'NEW', module: '{$rubric@key}'"	href="#" title="___COMMON_NEW_ITEM___">
		            		<img src="{$basic.tpl_path}img/btn_add_new_home.gif" alt="___COMMON_NEW_ITEM___" />
		            	</a>
					{/if}
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
                	<a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=index">___COMMON_{$rubric@key|upper}_INDEX___</a>
                	<span>
                		({$rubric.message_tag})
                	</span>
                </h2>

                <div class="clear"> </div>

                <div class="list_wrap{if $rubric.hidden} hidden{/if}">
	                {foreach $rubric.items as $item}
	                	<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_home{else}even_sep_home{/if}">
							<div class="column_new_home">
								{if $item.noticed.show_info and !$environment.is_guest}
									<a class="new_item">
									{if $item.noticed.status == "new" and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
									<img title="" class="new_item" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
									{elseif $item.noticed.status == "new"}
									<img title="" class="new_item" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
									{elseif $item.noticed.status == "modified"  and ($item.noticed.annotation_info.count_new or $item.noticed.annotation_info.count_changed)}
									<img title="" class="new_item" src="{$basic.tpl_path}img/flag_neu_2_a.gif" alt="*" /></a>
									{elseif $item.noticed.status == "modified"}
									<img title="" class="new_item" src="{$basic.tpl_path}img/flag_neu_2.gif" alt="*" /></a>
									{elseif $item.noticed.annotation_info.count_new}
									<img title="" class="new_item" src="{$basic.tpl_path}img/flag_neu_a.gif" alt="*" /></a>
									{elseif $item.noticed.annotation_info.count_changed}
									<img title="" class="new_item" src="{$basic.tpl_path}img/flag_neu_2_a.gif" alt="*" /></a>
									{/if}
									<div class="tooltip">
										<div class="tooltip_inner">
											<div class="tooltip_title">
												<div class="header">___COMMON_CHANGE_INFORAMTION___</div>
											</div>
											<div class="tooltip_content">
												<span class="content">{$item.noticed.item_info}</span>
												{if isset($item.noticed.section_info.count_new) &&$item.noticed.section_info.count_new}
													<span class="content">___COMMON_NEW_SECTIONS___: {$item.noticed.section_info.count_new}
													{foreach $item.noticed.section_info.section_new_items as $section_item}
													   <br/>
													   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$section_item.ref_iid}#section{$section_item.iid}">{$section_item.title|truncate:25:'...':true}</a> ({$section_item.date})
													   </span>
													{/foreach}
													</span>
												{/if}
												{if isset($item.noticed.section_info.count_changed) && $item.noticed.section_info.count_changed}
													<span class="content">___COMMON_CHANGED_SECTIONS___: {$item.noticed.section_info.count_changed}
													{foreach $item.noticed.section_info.section_changed_items as $section_item}
													   <br/>
													   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$section_item.ref_iid}#section{$section_item.iid}">{$section_item.title|truncate:25:'...':true}</a> ({$section_item.date})
													   </span>
													{/foreach}
													</span>
												{/if}
												{if isset($item.noticed.article_info.count_new) && $item.noticed.article_info.count_new}
													<span class="content">___COMMON_NEW_ARTICLES___: {$item.noticed.article_info.count_new}
													{foreach $item.noticed.article_info.article_new_items as $article_item}
													   <br/>
													   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$article_item.ref_iid}#article{$article_item.iid}">{$article_item.title|truncate:25:'...':true}</a> ({$article_item.date})
													   </span>
													{/foreach}
													</span>
												{/if}
												{if isset($item.noticed.article_info.count_changed) && $item.noticed.article_info.count_changed}
													<span class="content">___COMMON_CHANGED_ARTICLES___: {$item.noticed.article_info.count_changed}
													{foreach $item.noticed.article_info.article_changed_items as $article_item}
													   <br/>
													   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$article_item.ref_iid}#article{$article_item.iid}">{$article_item.title|truncate:25:'...':true}</a> ({$article_item.date})
													   </span>
													{/foreach}
													</span>
												{/if}
												{if isset($item.noticed.annotation_info.count_new) && $item.noticed.annotation_info.count_new}
													<span class="content">___COMMON_NEW_ANNOTATIONS___: {$item.noticed.annotation_info.count_new}
													{foreach $item.noticed.annotation_info.anno_new_items as $anno_item}
													   <br/>
													   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$anno_item.ref_iid}#annotation{$anno_item.iid}">{$anno_item.title|truncate:25:'...':true}</a> ({$anno_item.date})
													   </span>
													{/foreach}
													</span>
												{/if}
												{if isset($item.noticed.annotation_info.count_changed) && $item.noticed.annotation_info.count_changed}
													<span class="content">___COMMON_CHANGED_ANNOTATIONS___: {$item.noticed.annotation_info.count_changed}
													{foreach $item.noticed.annotation_info.anno_changed_items as $anno_item}
													   <br/>
													   <span>- <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&{$environment.params}&iid={$anno_item.ref_iid}#annotation{$anno_item.iid}">{$anno_item.title|truncate:25:'...':true}</a> ({$anno_item.date})
													   </span>
													{/foreach}
													</span>
												{/if}
											</div>
										</div>
									</div>
								{/if}
							</div>
	                    	<div class="column_home_320">
	                        	{if $rubric@key == 'discussion'}
		                        	<p class="column_addon">
		                        		{$item.column_1_addon}
		                        	</p>
	                        	{/if}
	                        	{if $rubric@key == 'date' && $item.column_1_addon}
	                        		<p class="column_addon">
	                        			<img title="" src="{$basic.tpl_path}img/calendar_recurring.gif" alt="" />
		                        	</p>
	                        	{/if}
	                        	<p>
									{if $rubric@key == 'project'}
										{if $item.may_enter}
											<a href="commsy.php?cid={$item.iid}&mod=home&fct=index"><img title="" src="{$basic.tpl_path}img/door_open_small.gif" alt="" /></a>
										{else}
											<img title="" src="{$basic.tpl_path}img/door_closed_small.gif" alt="" />
										{/if}
									{/if}

									{if $rubric@key != 'material' or !$environment.is_guest or $item.worldpublic or $room.room_information.material_guests == 'open'}
		                            	<a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&iid={$item.iid}">{$item.column_1}</a>
									{else}
										{$item.column_1}
									{/if}
	                            </p>
	                        </div>
							<div class="column_45">
		                        {if $rubric@key == 'material' or $rubric@key == 'announcement' or $rubric@key == 'discussion'  or $rubric@key == 'todo' or $rubric@key == 'date' or $rubric@key == 'topic'}
								{if $item.attachment_count > 0}
									<p>
										<a href="#" class="attachment{if $item.attachment_count == 0}_none_overlay{/if}">{$item.attachment_count}</a>
									</p>
									<div class="tooltip tooltip_with_400">
										<div class="tooltip_inner tooltip_inner_with_400">
											<div class="tooltip_title">
												<div class="header">___COMMON_ATTACHED_FILES___</div>
											</div>
											<div class="scrollable">
												<div class="tooltip_content">
													<ul>
													{foreach $item.attachment_infos as $file}
														<li>
															{if $rubric@key != 'material' or !$environment.is_guest or $item.worldpublic or $room.room_information.material_guests == 'open'}
																<a class="{if $file.lightbox}lightbox_{$item.iid}{/if}" href="{$file.file_url}" target="blank">
															{/if}
																{$file.file_icon} {$file.file_name}
															{if $rubric@key != 'material' or !$environment.is_guest or $item.worldpublic}
																</a>
															{/if}
															({$file.file_size} KB)
														</li>
													{/foreach}
													</ul>
												</div>
											</div>
										</div>
									</div>
								{else}
								<p>&nbsp;</p>
								{/if}
								{else}
								<p>&nbsp;</p>
								{/if}
							</div>
                        	<div class="column_140">
                        		{if $rubric@key == 'announcement'}
                        			{if $room.room_information.announcement_date == 'yes'}
                        				<p></p>
                        			{else}
                        				<p>{$item.column_2}</p>
                        			{/if}
                        		{else}
                        			<p>{$item.column_2}</p>
                        		{/if}
	                        	{if $rubric@key == 'todo'}
	                        	  {$item.column_2_addon}
								{/if}
                            </div>
	                        <div class="column_194">
		                        	{if $rubric@key == 'material' or $rubric@key == 'announcement' or $rubric@key == 'discussion'}
	                        			<p>
		                            		<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.user_iid}">{$item.column_3}</a>
	                            		</p>
		                        	{else}
	                        			{if $rubric@key == 'todo'}
	                        	  			{$item.column_3}
										{else}
		                        			<p>
		                        	  			{$item.column_3}
	    	                    			</p>
										{/if}
		                            {/if}
	                        </div>
	                        <div class="column_20">
    	                        {if $rubric@key == 'date' and {$item.color}}
	                        		<div style="width:10px; height:10px; margin-top:7px; margin-left:1px; background-color:{$item.color}; border:1px solid #cccccc;">&nbsp;</div>
	                        	{/if}
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
				data="url:'commsy.php?cid={$environment.cid}&mod=search&fct=index&name=selected&seltag_{$level}={$node.item_id}&seltag=yes'">{$node.title}
			{if $node.children|count > 0}	{* recursive call *}
				{tag_tree nodes=$node.children level=$level+1}
			{/if}
		{/foreach}
		</ul>
	{/function}
{/block}

{block name=sidebar_buzzwordbox_buzzword}
	<a href="commsy.php?cid={$environment.cid}&mod=search&fct=index&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
{/block}