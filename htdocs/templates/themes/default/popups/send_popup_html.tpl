<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img
						src="{$basic.tpl_path}img/popup_close.gif"
						alt="___COMMON_CLOSE___" />
					</a>
					<h2>___COMMON_EMAIL_TO_TITLE___</h2>
					<div class="clear"></div>
				</div>
				<div id="popup_content_wrapper">
    				<div id="popup_content">
    					<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>
						<div class="input_row">
							<span class="input_label_150">___MAIL_SUBJECT___<span class="required">*</span></span>
							<div class="input_container_180" id="reciever">
								<input type="text" name="form_data[subject]" class="size_400" />
							</div>
						</div>
						<div class="editor_content">
							<br/>
							<textarea name="form_data[body]" rows="20" cols="100">{if isset($popup.body)}{$popup.body}{/if}</textarea>
							<br/>
							<br/>
						</div>

						{if $popup.showAttendees == true}
							<div class="input_row">
								<span class="input_label_150">___COMMON_MAIL_SEND_TO_ASIGNED_PEOPLE___:</span>
								<div class="input_container_180">
									<input type="checkbox" name="form_data[copyToAttendees]" value="true" checked="checked" />{if $popup.attendeeType == "date"}___COMMON_MAIL_SEND_TO_ATTENDEES___{else}___COMMON_MAIL_SEND_TO_PROCESSORS___{/if}
								</div>
								<div class="clear"></div>
							</div>
						{/if}

						{if $popup.showGroupReceivers == true}
							<div class="input_row">
								<span class="input_label_150">{if $popup.withGroups}___COMMON_MAILTO_GROUPS___:{else}___COMMON_MAIL_RECEIVER___{/if}</span>
									{foreach $popup.groups as $group}
										<div class="input_container_180">
											<input type="checkbox" name="form_data[group_{$group.value}]" value="{$group.value}"{if $group.checked} checked="checked"{/if} />{if $popup.withGroups}{$group.text}{else}{i18n tag=COMMON_MAIL_ALL_IN_ROOM param1=$popup.numMembers}{/if}
										</div>
									{/foreach}
								<div class="clear"></div>
							</div>
						{else if $popup.showInstitutionReceivers == true}
							<div class="input_row">
								<span class="input_label_150">___COMMON_RELEVANT_FOR_INSTITUTION___:</span>
									{foreach $popup.institutions as $institution}
										<div class="input_container_180">
											<input type="checkbox" name="form_data[institution_{$institution.value}]" value="{$institution.value}"{if $institution.checked} checked="checked"{/if} />{$institution.text}
										</div>
									{/foreach}
								<div class="clear"></div>
							</div>
						{/if}

						{if $popup.allMembers}
							<div class="input_row">
								<span class="input_label_150">___COMMON_MAIL_RECEIVER___:</span>
								<div class="input_container_180">
									<input type="checkbox" name="form_data[allMembers]" value="true" checked="checked" />{i18n tag=COMMON_MAIL_ALL_IN_ROOM param1=$popup.numMembers}
								</div>
								<div class="clear"></div>
							</div>
						{/if}

						<div class="input_row">
							<span class="input_label_150">___MAILCOPY_TO_SENDER___:</span>
							<div class="input_container_180">
								<input type="radio" name="form_data[copyToSender]" value="true" />___COMMON_YES___
								<input type="radio" name="form_data[copyToSender]" value="false" checked="checked" />___COMMON_NO___
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
