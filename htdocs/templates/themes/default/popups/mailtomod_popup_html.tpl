<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup">

			<div id="popup_head">
				<h2>___CONFIGURATION_SERVICE_EMAIL_MODERATOR___</h2>
				<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/pop_close_btn.gif" alt="___COMMON_CLOSE___" /></a>

				<div class="clear"> </div>
			</div>

			<div id="popup_content">
				<div id="content_row_one">
					<div class="input_row">
						<span class="input_label_150">___MAIL_SENDER___:</span>
						<p>{$popup.user.fullname} ({$popup.user.mail})</p>
					</div>
					<div class="input_row">
						<span class="input_label_150">___COMMON_MAIL_RECEIVER___:</span>
						<p>{$popup.mod.list}</p>
					</div>
					<div class="input_row">
						<span class="input_label_150">___COMMON_MAIL_SUBJECT___:</span><span class="required">*</span>
						<input id="mailtomod_subject" type="text" class="size_200 mandatory" name="form_data[subject]"/>
					</div>
				</div>
				<div id="content_row_two">
					<textarea rows="10" cols="100" name="form_data[content]">{$popup.body}</textarea> 
				</div>					
			</div>

		</div>
	</div>
</div>