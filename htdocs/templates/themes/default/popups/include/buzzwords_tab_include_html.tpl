{if isset($popup.buzzwords)}
	<div class="tab {if $item.edit_type != 'buzzwords'}hidden{/if}" id="buzzwords_tab">
		<div class="settings_area">
			<ul class="popup_buzzword_list">
				{foreach $popup.buzzwords as $buzzword}
					<li class="ui-state-default popup_buzzword_item">
						<input name="form_data[buzzwords]" value="{$buzzword.item_id}" type="checkbox"{if $buzzword.assigned == true} checked="checked"{/if}/>{$buzzword.name}
					</li>
				{/foreach}
				<div class="clear"></div>
			</ul>
			<div style="float:left; margin-left:10px">{*___BUZZWORDS_NEW_BUTTON___:*} <input id="new_buzzword_input" name="form_data[new_buzzword]" value="" type="text" /></div>
			<div style="float:left; margin-left: 10px;"><input class="popup_button" id="popup_button_add_buzzword" type="button" value="___BUZZWORDS_NEW_BUTTON___"></div>
			<div class="clear"></div>
		</div>
	</div>
{/if}