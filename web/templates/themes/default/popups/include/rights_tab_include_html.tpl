{if $popup.is_owner == true}
   {if !$popup.show_rights}
	   <div class="tab hidden" id="rights_tab">
   {else}
      <div class="tab" id="rights_tab">
   {/if}
		<div class="settings_area">
			{if $popup.overflow}
				<input type="checkbox" name="form_data[external_viewer]" value="1"{if $item.external_viewer == true} checked="checked"{/if}/>___EXTERNAL_VIEWER_DESCRIPTION___<br/>
				<input type="text" name="form_data[external_viewer_accounts]" value="{if isset($item.external_viewer_accounts)}{$item.external_viewer_accounts}{/if}"/>
			{else}
				{if $popup.config.with_activating}
					<input type="checkbox" name="form_data[private_editing]" value="1"{if $item.private_editing == true} checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}<br/>
					<input type="checkbox" name="form_data[hide]" value="1"{if $item.is_not_activated} checked="checked"{/if}/>___COMMON_HIDE___
					___DATES_HIDING_DAY___ <input class="datepicker" type="text" name="form_data[activating_date]" value="{if isset($item.activating_date)}{$item.activating_date}{/if}"/>
					___DATES_HIDING_TIME___ <input type="text" name="form_data[activating_time]" value="{if isset($item.activating_time)}{$item.activating_time}{/if}"/>

				{else}
					<input type="radio" name="form_data[public]" value="1" {if $item.public == '1'}checked="checked"{/if}/>___RUBRIC_PUBLIC_YES___<br/>
					<input type="radio" name="form_data[public]" value="0" {if $item.public == '0'}checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
				{/if}
			{/if}
		</div>
	</div>
{/if}