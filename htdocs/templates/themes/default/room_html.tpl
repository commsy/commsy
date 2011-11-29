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
            	{block name=room_right_column}{/block}
            </div> <!-- Ende right_column -->
            
            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}