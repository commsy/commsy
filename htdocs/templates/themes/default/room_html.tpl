{extends file="layout_html.tpl"}

{block name=meta_area}
	<div id="meta_area_content">

                <div id="breadcrumb">
                    <span><a href="" class="mm_right">CommSy Projekt</a></span>
                    <span><a href="" class="mm_dropdown">CommSy Community</a></span>
                </div>

                <div id="meta_menu">
                	{* login / logout *}
                	{if $environment.is_guest}
                    	login maske
                    {else}
                    	<span class="mm_bl"><a href="" id="mm_logout">Abmelden</a></span>
                    	<span class="mm_br mm_bl"><a href="" class="mm_dropdown">Mein CommSy</a></span>
                    	{if $environment.is_moderator}
                    		<span class="mm_br mm_bl"><a href="" class="mm_dropdown">Admin</a></span>
                    	{/if}
                    	<span class="mm_br">___COMMON_WELCOME___, {$environment.username|truncate:12}</span>
                    {/if}
                </div>

                <div class="clear"> </div>
            </div>
{/block}

{block name=layout_content}
	<div id="columnset"> <!-- Start columnset -->

            <div id="left_column"> <!-- Start left_column -->

                <div id="main_navigation">
                    <ul>
                    	<!--  <li id="active"><a href="commsy.php?cid={$environment.cid}&mod=home&fct=index"><span id="ho_act"></span><br/>Home</a></li>-->
                    	{foreach $room.rubric_information as $rubric}
                    		<li {if $rubric.active}id="active"{else}class="non_active"{/if}>
                    			<a href="commsy.php?cid={$environment.cid}&mod={$rubric.name}&fct=index">
                    				<span id="{if $rubric.active}{$rubric.span_prefix}_act{else}{$rubric.span_prefix}_non_act{/if}"></span><br/>
                    				{if $rubric.translate}___COMMON_{$rubric.name|upper}_INDEX___{else}{$rubric.name}{/if}
                    			</a>
                    		</li>
                    	{/foreach}
                    </ul>
                    <div class="clear"> </div>


                    <div id="site_actions">
                    	{block name=room_site_actions}{/block}
                    </div>

                    <h1>{block name=room_navigation_rubric_title}{/block}</h1>

                    <div class="clear"> </div>
                </div>

                <div id="maincontent">
                	{block name=room_main_content}{/block}
                </div>

            </div> <!-- Ende left_column -->

            <div id="right_column"> <!-- Start right_column -->
            	{block name=room_right_info_addon}
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
				{/block}


           		<div id="rc_portlet_area">
           			{block name=room_right_portlets}
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
									{function name=tag_tree level=0}
										<ul>
										{foreach $nodes as $node}
											<li	id="node_{$node.item_id}"
												{if $node.children|count > 0}class="folder"{/if}
												data="url:'commsy.php?cid={$environment.cid}&mod=campus_search&fct=index&{$level}_seltag={$node.item_id}&seltag=yes'">{$node.title}
											{if $node.children|count > 0}	{* recursive call *}
												{tag_tree nodes=$node.children level=$level+1}
											{/if}
										{/foreach}
										</ul>
									{/function}

									{* call function *}
									{tag_tree nodes=$room.tags}
								</div>
							</div>
						</div>
					{/block}
           		</div>
            </div> <!-- Ende right_column -->

            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}