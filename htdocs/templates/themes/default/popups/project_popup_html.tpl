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
							<input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
						</div>

						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>

						{if !empty($item.community_room_array)}
							<div class="input_row_100">
								<label for="room_communityrooms">
									___PREFERENCES_COMMUNITY_ROOMS___{if $item.link_status != 'optional'}<span class="required">*</span>{/if}:
								</label>
								<select class="size_200"  style="width:200px;" id="room_communityrooms" name="form_data[communityrooms]">
									{foreach $item.community_room_array as $room}
										<option value="{$room.value}"{if $room.disabled == true} disabled="disabled"{/if}>{$room.text}</option>
									{/foreach}
								</select>
								<input style="width:102px;" id="add_community_room" class="popup_button" type="button" value="___PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON___" />
									<div id="assigned_community_rooms" class="input_row_100" style="margin-left:100px;">
										{foreach $item.assigned_community_room_array as $room}
											<input id="room_communityroomlist" type="checkbox" name="form_data[communityroomlist_{$room.value}]" value="{$room.value}" checked="checked" />{$room.text}
										{/foreach}
									</div>
								<div class="clear"></div>
							</div>
						{/if}

						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_NEW_ITEM___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>


					</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>