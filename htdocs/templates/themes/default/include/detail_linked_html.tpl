<!-- Start fade_in_ground -->
<div class="fade_in_ground_linked hidden">
	<div class="fi_moredetails">
		<div class="fi_md_info">
			<img src="{$basic.tpl_path}img/fi_item_link.gif" alt="Zuordnungen" />
		</div>
		
		<div class="fi_md_content">
			{if $room.sidebar_configuration.active.netnavigation}
				<div class="fi_mdc_item">
					<h4>
						{if isset($room.netnavigation.is_community)}
							{if $room.netnavigation.is_community}
								___COMMON_ATTACHED_INSTITUTIONS___ ({$room.netnavigation.count})
							{else}
								___COMMON_ATTACHED_GROUPS___ ({$room.netnavigation.count})
							{/if}
						{else}
							___COMMON_ATTACHED_ENTRIES___ ({$room.netnavigation.count})
						{/if}
					</h4>
					<ul>
						{foreach $room.netnavigation.items as $item}
							<li>
								<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$item.module}&fct=detail&iid={$item.linked_iid}" title="{$item.title}">
									<img src="{$item.img}" title="{$item.title}"/>
								</a>
								<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$item.module}&fct=detail&iid={$item.linked_iid}" title="{$item.title}">
									{$item.link_text|truncate:39:"...":true}
								</a>
							</li>
						{foreachelse}
							___COMMON_NONE___
						{/foreach}
					</ul>
				</div>
			{/if}
			
			{if $room.sidebar_configuration.active.buzzwords}
				<div class="fi_mdc_item">
					<h4>___COMMON_ATTACHED_BUZZWORDS___</h4>
					{foreach $room.buzzwords as $buzzword}
						{block name=sidebar_buzzwordbox_buzzword}
							<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&selbuzzword={$buzzword.to_item_id}">{$buzzword.name}</a>{if !$buzzword@last}, {/if}
						{/block}
					{foreachelse}
						___COMMON_NONE___
					{/foreach}
				</div>
			{/if}
			
			{if $room.sidebar_configuration.active.tags}
				<div class="fi_mdc_item">
					<h4>___COMMON_ATTACHED_TAGS___</h4>
						{* Tags Function *}
						{function name=tag_tree level=0}
							{foreach $nodes as $node}
								<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&name=selected&seltag_{$level}={$node.item_id}&seltag=yes">{$node.title}</a>{if !$node@last}, {/if}
								{if $node.children|count > 0}	{* recursive call *}
									{tag_tree nodes=$node.children level=$level+1}
								{/if}
							{/foreach}
						{/function}
					
					{* call function *}
					{tag_tree nodes=$room.tags}
				</div>
			{/if}
			
			<div class="clear"> </div>
		</div>
		
		<div class="clear"> </div>
	</div>
</div>
<!-- Ende fade_in_ground -->