{*<!-- Start fade_in_ground -->
<div id="linked_expand" {if in_array("linked_expand",$detail.printcookie)}class="hidden"{/if}>
	<div class="fade_in_ground_linked">
		<div class="fi_morelinked">
			<div class="fi_md_linked_info">
				<img src="{$basic.tpl_path}img/fi_item_link.gif" alt="Zuordnungen" />
			</div>

			<div class="fi_md_content">

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
							{foreach $item.tags as $tag}
								<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&name=selected&seltag_{$tag.level}={$tag.item_id}&seltag=yes">{$tag.title}</a>{if !$tag@last}, {/if}
							{foreachelse}
								___COMMON_NONE___
							{/foreach}
					</div>
				{/if}

				<div class="clear"> </div>
			</div>

			<div class="clear"> </div>
		</div>
	</div>
</div>
<!-- Ende fade_in_ground -->*}