{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="Ansicht in Reihen"><img src="{$basic.tpl_path}/img/btn_row_view.gif" alt="Reihen" /></a>
    <a href="" title="Ansicht in Portlets"><img src="{$basic.tpl_path}/img/btn_portlet_view.gif" alt="Portlets" /></a>
{/block}

{block name=room_navigation_rubric_title}
	&Uuml;bersicht Projektraum (Home)
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		{foreach $room.home_content as $rubric}
			<div class="content_item"> <!-- Start content_item -->
            	<div class="ci_head_actions">
	            	<a href="" title="___HOME_SMARTY_{$rubric@key|upper}_ACTION_NEW___">
	            		<img src="{$basic.tpl_path}/img/btn_ci_add.gif" alt="___HOME_SMARTY_ACTION_NEW___" />
	            	</a>
                    <a href="" class="open_close" title="___HOME_SMARTY_ACTION_CLOSE___">
                    	<img src="{$basic.tpl_path}/img/btn_ci_close.gif" alt="___HOME_SMARTY_ACTION_CLOSE___" />
                    </a>
                </div>
                <h2>
                	___COMMON_{$rubric@key|upper}_INDEX___
                	<span>
                		({$rubric.items|count} ___HOME_SMARTY_{$rubric@key|upper}_DESCRIPTION___ {$rubric.count_all})
                	</span>
                </h2>
                
                <div class="clear"> </div>
                
                {foreach $rubric.items as $item}
                	<div class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
                    	<div class="column_430">
                        	<p>
                            	<a href="">{$item.title}</a>
                            </p>
                        </div>
                        <div class="seperator">
                        	<div class="column_120">
                            	<p>{$item.modification_date}</p>
                            </div>
	                        <div class="column_184">
	                        	<p>
	                            	<a href="">{$item.creator}</a>
	                            </p>
	                        </div>
	                    </div>
	                    <div class="clear"> </div>
                    </div>
                {/foreach}
            </div> <!-- Ende content_item -->
		{/foreach}
	
	
                        {*
                        
                        
                       
                        
                       
                        
                        <div class="content_item"> <!-- Start content_item -->
                            <div class="ci_head_actions">
                                <a href="" title="neue Gruppe erstellen"><img src="{$basic.tpl_path}/img/btn_ci_add.gif" alt="neu" /></a>
                                <a href="" class="open_close" title="&ouml;ffnen"><img src="{$basic.tpl_path}/img/btn_ci_open.gif" alt="&ouml;ffnen" /></a>
                            </div>
                            <h2>Gruppen<span>(alle 10 Gruppen)</span></h2>
                            
                            <div class="clear"> </div>
                        </div> <!-- Ende content_item -->
                        
                        <div class="content_item"> <!-- Start content_item -->
                            <div class="ci_head_actions">
                                <a href="" title="neues Thema erstellen"><img src="{$basic.tpl_path}/img/btn_ci_add.gif" alt="neu" /></a>
                                <a href="" class="open_close" title="&ouml;ffnen"><img src="{$basic.tpl_path}/img/btn_ci_open.gif" alt="&ouml;ffnen" /></a>
                            </div>
                            <h2>Themen<span>(alle 4 Themen)</span></h2>
                            
                            <div class="clear"> </div>
                        </div> <!-- Ende content_item -->        
                        
                        
                        *}                                                   
                        
                    </div>
{/block}

{block name=room_right_column}
	<div id="info_addon">
		<div id="info_area">
			<div id="infos_left">
				<h2>Rauminfos:</h2>
				<p>
					___ACTIVITY_NEW_ENTRIES___: {$room.room_information.new_entries}
				</p>
				<p>
					___ACTIVITY_PAGE_IMPRESSIONS___: {$room.room_information.page_impressions}
				</p>
			</div>
			
			<div id="infos_right">
				<div id="info_bar">
					<p>999</p>
				</div>
			</div>
			
			<div class="clear"> </div>
		</div>
		
		<div id="addon_area">
			<p>
				<a href="" title="Wiki"><img src="{$basic.tpl_path}img/addon_wiki.png" alt="Wiki" /></a>
				<a href="" title="RSS"><img src="{$basic.tpl_path}img/addon_rss.png" alt="RSS" /></a>
				<a href="" title="Chat"><img src="{$basic.tpl_path}img/addon_chat.png" alt="Chat" /></a>
				<a href="" title="Wordpress"><img src="{$basic.tpl_path}img/addon_wordpress.png" alt="Wordpress" /></a>
			</p>
			<div class="clear"> </div>
		</div>
		
		<div class="clear"> </div>
	</div>
	
	<div id="rc_portlet_area">
		<div class="portlet_rc">
			<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
			<h2>___COMMON_BUZZWORD_BOX___</h2>
			
			<div class="clear"> </div>
			<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
			<div class="portlet_rc_body">
				{foreach $room.buzzwords as $buzzword}
					<a href="commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
				{/foreach}
			</div>
		</div>
		
		<div class="portlet_rc">
			<a href="" title="___HOME_SMARTY_ACTION_CLOSE___" class="btn_head_rc"><img src="{$basic.tpl_path}img/btn_close_rc.gif" alt="close" /></a>
			<h2>___COMMON_TAG_BOX___</h2>
			
			<div class="clear"> </div>
			
			<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
			<div class="portlet_rc_body">
				<div id="tag_tree">
					{* Tags Function *}
					{function name=tag_tree}
						<ul>
						{foreach $nodes as $node}
							<li	id="node_{$node.item_id}"
								{if $node.children|count > 0}class="folder"{/if}
								data="url:'commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&seltag={$node.item_id}'">{$node.title}
							{if $node.children|count > 0}	{* recursive call *}
								{tag_tree nodes=$node.children}
							{/if}
						{/foreach}
						</ul>
					{/function}
					
					{* call function *}
					{tag_tree nodes=$room.tags}
				</div>
			</div>
		</div>
	</div>
{/block}