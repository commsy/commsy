<div id="content_row_two_max">
	<div class="input_row_100">
		<div class="float-left">___COMMON_NAME___:</div>
		<div id="popup_accounts_mail_namelist" class="input_container_180">
			<ul>
				{foreach $popup.receiver as $receiver}
					<li>{$receiver}</li>
				{/foreach}
			</ul>
		</div>
	</div>
	
	{if $popup.send_mail_checkbox === true}
		<div class="input_row_100">
			<label for="send_mail">___INDEX_ACTION_FORM_MAIL___:</label>
			<input id="send_mail" type="checkbox" name="form_data[send_mail]" value="true" checked="checked" />
		</div>
	{/if}
	
	{if $popup.copy_mod === true}
		<div class="input_row_100">
			<label for="send_mail">___MAILCOPY_TO_SENDER___:</label>
			<input id="send_mail" type="checkbox" name="form_data[copy_mod]" value="true" />
		</div>
	{else}
		<div class="input_row_100">
			<div class="float-left">___INDEX_ACTION_FORM_CC_BCC___:</div>
			
			{foreach $popup.cc_bcc as $entry}
				<label for="{$entry.value}">{$entry.text}</label>
				<input id="{$entry.value}" type="checkbox" name="form_data[{$entry.value}]" value="true" />
			{/foreach}
			
			
		</div>
	{/if}
	
	<div class="input_row_100">
		<label for="subject">___COMMON_MAIL_SUBJECT___</label>
		<input id="subject" type="text" name="form_data[subject]" value="{$popup.specific.subject}" />
	</div>
	
	<div class="input_row_100">
		<div class="editor_content">
			<div id="body" class="ckeditor">{if isset($popup.specific.content)}{$popup.specific.content}{/if}</div>
		</div>
	</div>
	
	{if $popup.send_mail_checkbox === true}
		<div class="input_row_100">
			<input id="submit" type="button" class="popup_button" name="send" value="___INDEX_ACTION_SEND_MAIL_BUTTON___"/>
		</div>
	{else}
		<div class="input_row_100">
			<input id="submit" type="button" class="popup_button" name="send" value="{$popup.submit_translation}"/>
		</div>
	{/if}								
</div>