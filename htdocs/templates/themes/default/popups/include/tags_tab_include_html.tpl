{if isset($popup.tags)}
	<div class="tab {if $item.edit_type != 'tags'}hidden{/if}" id="tags_tab">
		<div class="settings_area">
			<div class="tree"></div>
		</div>
	</div>
{/if}