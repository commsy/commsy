<!-- Start fade_in_ground -->
<div id="linked_expand" {if !$detail.is_reference_bar_visible}class="hidden"{/if}>
	<div class="fade_in_ground_linked">
		<div class="fi_morelinked">
			<div class="fi_md_linked_info">
				<img src="{$basic.tpl_path}img/fi_item_link.gif" alt="Zuordnungen" />
			</div>

			<div class="fi_md_content">
				{if isset($detail.content.item_id)}
					{assign var="iid" value=$detail.content.item_id}
				{else}
					{assign var="iid" value=$detail.item_id}
				{/if}

				{if $room.sidebar_configuration.active.buzzwords}
					<div class="fi_mdc_item" style="margin-right:10px; width:265px;">
						<h4 style="display:inline;">___COMMON_ATTACHED_BUZZWORDS___</h4>
							(<a style="display:inline;" class="open_popup open_popup_context_nav" data-custom="iid: {$iid}, module: '{$environment.module}', editType: 'buzzwords'" href="#">___COMMON_ATTACH_LINK___</a>)<br/>
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
					<div class="fi_mdc_item" style="margin-right:10px; width:255px;">
						<h4 style="display:inline;">___COMMON_ATTACHED_TAGS___</h4>
							(<a style="display:inline;" class="open_popup open_popup_context_nav" data-custom="iid: {$iid}, module: '{$environment.module}', editType: 'tags'" href="#">___COMMON_ATTACH_LINK___</a>)<br/>
							{*{foreach $item.tags as $tag}
								<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&seltag={$tag.item_id}">{$tag.title}</a>{if !$tag@last}, {/if}
							{foreachelse}
								___COMMON_NONE___
							{/foreach}*}
							<div class="subtree">
								<img src="{$basic.tpl_path}img/ajax_loader.gif" />
							</div>
					</div>
				{/if}

				<div class="clear"> </div>
			</div>

			<div class="clear"> </div>
		</div>
	</div>
</div>
<!-- Ende fade_in_ground -->