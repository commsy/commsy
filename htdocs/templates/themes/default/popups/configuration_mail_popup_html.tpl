<div id="content_row_two_max">
	<fieldset>
		<div class="input_row_100">
			<div class="float-left">___COMMON_NAME___:</div>
			<div id="popup_accounts_mail_namelist" class="input_container">
				<ul>
					{foreach $popup.receiver as $receiver}
						<li>{$receiver}</li>
					{/foreach}
				</ul>
			</div>
		</div>
	</fieldset>
	<div class="clear"></div>

	<fieldset>
		{if $popup.send_mail_checkbox === true}
			<div class="input_row_100">
				<label for="send_mail">___INDEX_ACTION_FORM_MAIL___:</label>
				<input id="send_mail" type="checkbox" name="form_data[send_mail]" value="true" checked="checked" /> ___INDEX_ACTION_FORM_MAIL_TO___
			</div>
		{/if}
		
		<div class="input_row_100">
			<label for="send_mail">&nbsp;</label>
			<input id="send_mail" type="checkbox" name="form_data[copy_mod_cc]" value="true" /> ___INDEX_ACTION_FORM_CC___ <input id="send_mail" type="checkbox" name="form_data[copy_mod_bcc]" value="true" /> ___INDEX_ACTION_FORM_BCC_MODERATOR___
		</div>
		<div class="input_container" style="margin-left:100px;">
			<input id="send_mail" type="checkbox" name="form_data[copy_auth_cc]" value="true" /> ___INDEX_ACTION_FORM_CC___ <input id="send_mail" type="checkbox" name="form_data[copy_auth_bcc]" value="true" /> ___INDEX_ACTION_FORM_BCC___
		</div>
	</fieldset>
	<div class="clear"></div>

	<fieldset>
		<div class="input_row_100">
			<label for="subject">___COMMON_MAIL_SUBJECT___:</label>
			<input id="subject" type="text" name="form_data[subject]" value="{$popup.specific.subject}" style="width:350px;"/>
		</div>

		<div class="input_row_100">
			<textarea cols="80" rows="10" name="form_data[body]">{if isset($popup.specific.content)}{$popup.specific.content}{/if}</textarea>
		</div>
	</fieldset>

	<fieldset>
		<div class="input_row_100">
			<input id="submit" type="button" class="popup_button" name="send" value="___INDEX_ACTION_SEND_MAIL_BUTTON___"/>
			<input id="abort" type="button" class="popup_button" name="abort" value="___MAIL_NOT_SEND_BUTTON___"/>
		</div>
	</fieldset>
</div>