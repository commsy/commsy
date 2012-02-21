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
		{foreach $room.search_content as $rubric}
			<div class="content_item"> <!-- Start content_item -->
	            <div class="ci_head_actions">
	                <a href="" class="open_close" title="{*{if $rubric.hidden}*}___COMMON_SHOW___{*{else}___COMMON_HIDE___{/if}*}">
	                	<img src="{$basic.tpl_path}img/{*{if $rubric.hidden}*}btn_ci_open.gif{*{else}btn_ci_close.gif{/if}*}" alt="{*{if $rubric.hidden}*}___COMMON_SHOW___{*{else}___COMMON_HIDE___{/if}*}" />
	                </a>
	            </div>
	            <h2>
	            	___COMMON_{$rubric@key|upper}_INDEX___
	                <span>
	                	({$rubric|count})
	                </span>
	            </h2>
            
	            <div class="clear"> </div>
	            <div class="list_wrap{if $rubric.hidden} hidden{/if}">
		            {foreach $rubric as $result}
		            	<div class="{if $result@iteration is odd}row_odd{else}row_even{/if} {if $result@iteration is odd}odd_sep_home{else}even_sep_home{/if}">
		                    <div class="column_380">
		                        {if $rubric@key == 'discussion'}
			                        <p class="column_addon">
			                        	{*{$item.column_1_addon}*}
			                        </p>
		                        {/if}
		                        <p>
									{*{if $item.noticed != ''}
										<a href="" class="new_item"><img title="{$item.noticed}" class="new_item" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
		         					{/if}*}
			                        <a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&iid={$result.item_id}">{$result.title}</a>
		                        </p>
		                    </div>
	                        <div class="column_140">
	                            <p>{$result.count}</p>
	                        </div>
		                    <div class="column_194">
		                        <p>
		                        	{$result.type}
		                        	{*
			                        {if $rubric@key == 'material' or $rubric@key == 'announcement' or $rubric@key == 'discussion'}
			                            <a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.user_iid}">{$item.column_3}</a>
			                        {else}
			                        	{$item.column_3}
			                        {/if}
			                        *}
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

{block name=room_right_portlets prepend}
	<div class="portlet_rc">
		<a href="" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="btn_head_rc">
			<img src="{$basic.tpl_path}img/{*{if $h}*}btn_open_rc.gif{*{else}btn_close_rc.gif{/if}*}" alt="{*{if $h}*}___COMMON_SHOW___{*{else}___COMMON_HIDE___{/if}*}" />
		</a>
		<h2>
			Suche
		</h2>

		<div class="clear"></div>
		<div class="portlet_rc_body{*{if $h} hidden{/if}*}">
			Begriffe:
			{foreach $room.search_sidebar.search_words as $word}
				{$word}
			{/foreach}
			
			<div class="clear"></div>
			
			<input type="checkbox" value="kategorisiert" name="kat"/>
			<label for="kat">kategorisiert</label>
		</div>
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