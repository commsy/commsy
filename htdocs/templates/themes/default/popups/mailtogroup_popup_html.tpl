<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img
						src="{$basic.tpl_path}img/popup_close.gif"
						alt="___COMMON_CLOSE___" />
					</a>
					<h2>{$popup.headline}</h2>
					<div class="clear"></div>
				</div>
				
				<div id="popup_content_wrapper">
    				<div id="popup_content">
    					<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>
						
						<div class="input_row">
							<span class="input_label_150">___COMMON_MAIL_SUBJECT___<span class="required">*</span>:</span>
							<div class="input_container_180">
								<input type="text" name="form_data[subject]" class="size_400" />
							</div>
							<div class="clear"></div>
						</div>
						
						<div class="input_row_100">
							<span class="input_label_150">___MAIL_BODY___<span class="required">*</span>:</span>
							<div class="input_container_180">
								<textarea cols="80" rows="10" name="form_data[mailcontent]"></textarea>
							</div>
							<div class="clear"></div>
						</div>
						
						{if isset($popup.groups)}
							<div class="input_row_100">
								<span class="input_label_150">___COMMON_RELEVANT_FOR___<span class="required">*</span>:</span>
								<div class="input_container_180">
									{foreach $popup.groups as $group}
										<input type="checkbox" name="form_data[groups]" value="{$group.value}"{if $group.checked} checked="checked"{/if} />{$group.text}<br/>
									{/foreach}
								</div>
								<div class="clear"></div>
							</div>
						{/if}
						
						<div class="input_row_100">
							<span class="input_label_150">___MAILCOPY_TO_SENDER___<span class="required">*</span>:</span>
							<div class="input_container_180">
								<input type="radio" name="form_data[copytosender]" value="true" />___COMMON_YES___<br/>
								<input type="radio" name="form_data[copytosender]" value="false" checked="checked" />___COMMON_NO___
							</div>
							<div class="clear"></div>
						</div>
    				</div>
    				<div id="popup_tabs">
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="___COMMON_MAIL_SEND_BUTTON___" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
