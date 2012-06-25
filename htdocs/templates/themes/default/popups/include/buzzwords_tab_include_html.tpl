{if isset($popup.buzzwords)}
	<div class="tab hidden" id="buzzwords_tab">
		<div class="settings_area">
			<ul class="popup_buzzword_list">
				{foreach $popup.buzzwords as $buzzword}
					<li class="ui-state-default popup_buzzword_item">
						<input name="form_data[buzzwords]" value="{$buzzword.item_id}" type="checkbox"{if $buzzword.assigned == true} checked="checked"{/if}/>{$buzzword.name}
					</li>
				{/foreach}
				<div class="clear"></div>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
{/if}