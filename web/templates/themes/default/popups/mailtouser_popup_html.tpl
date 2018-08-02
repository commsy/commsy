<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img
						src="{$basic.tpl_path}img/popup_close.gif"
						alt="___COMMON_CLOSE___" />
					</a>
					<h2>___CONFIGURATION_SERVICE_EMAIL_MODERATOR___</h2>
					<div class="clear"></div>
				</div>
				<div id="popup_content_wrapper">
    				<div id="popup_content">
						<div class="input_row">
							<span class="input_label_150">___MAIL_SENDER___:</span>
							<div class="input_container_180">{$popup.user.fullname} ({$popup.user.mail})</div>
						</div>
						<div class="clear"></div>
						<div class="input_row">
							<span class="input_label_150">___COMMON_MAIL_RECEIVER___<span class="required">*</span>:</span>
							<div class="input_container_180" id="receiver">
							<input type="checkbox" name="form_data[receiverId]" value="{$popup.receiver.id}" checked>{$popup.receiver.fullname}<br/>
							
    							{*foreach $popup.mod.list as $ele}
    								{if $ele@total == 1}
    									<p>{$ele.text}</p>
    								{else}
    									<input type="checkbox" name="form_data[mod]" value="{$ele.value}" checked>{$ele.text}<br/>
    								{/if}
    							{/foreach*}
							</div>
						</div>
						<div class="input_row">
							<span class="input_label_150">___MAIL_SUBJECT___<span class="required">*</span>:</span>
							<div class="input_container_180" id="receiver">
								<input type="text" name="form_data[subject]" class="size_400" />
							</div>
						</div>
						<div class="input_row">
							<span class="input_label_150">___MAIL_BODY___:</span>
							<div class="input_container_180">
								<textarea cols="80" rows="10" name="form_data[mailcontent]">{if isset($popup.mailcontent)}{$popup.mailcontent}{/if}</textarea>
							</div>
							<div class="clear"></div>
						</div>
   				</div>
    				<div id="popup_tabs">
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="___EMAIL_CONTACT_MODERATOR___" />
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
