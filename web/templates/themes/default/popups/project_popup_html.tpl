<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_PROJECT___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>
						{debug}

						<div class="input_row">
							<div class="input_label_80">___COMMON_TITLE___<span class="required">*</span>:</div>
							<input type="text" value="{if isset($item.title)}{$item.title|escape:"html"}{/if}" name="form_data[title]" class="size_400" />
						</div>
						
						{if $popup.withTemplate}					
						<div class="input_row">	
							<div class="input_label_80">Vorlage:</div>
							<select name="form_data[template]">
							    <option value="empty">___CONFIGURATION_TEMPLATE_NO_CHOICE___</option>
								<option disabled="disabled">-----------------------</option>
								{foreach $popup.template as $temp}
								<option value="{$temp.value}">{$temp.text}</option>
								{/foreach}
							</select>
						</div>
						{/if}

						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>

						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>


					</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>