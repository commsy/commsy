{debug}
{* include template functions *}
{include file="include/functions.tpl" inline}
<div id="popup_wrapper">
	<div id="popup_my_area">
		<div id="popup_frame_my_area">
			<div id="popup_inner_my_area">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>
						___CONFIG_META_TITLE___
					</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="profile_content_row_three">
						<div class="tab_navigation">
							<a href="" class="pop_tab_active">___INTERNAL_META_TITLE___</a>
							<a href="" class="pop_tab">___INTERNAL_SPECIAL_TITLE___</a>
							<a href="" class="pop_tab">___COMMON_ACCOUNTS___</a>
							<a href="" class="pop_tab">___CONFIG_MODERATION_TITLE___</a>
							<a href="" class="pop_tab">___CONFIGURATION_PLUGIN_LINK___</a>
							<a href="" class="pop_tab">___HOME_EXTRA_TOOLS___</a>

							<div class="clear"> </div>
						</div>

						<div id="popup_tabcontent">
							<div class="tab" id="room_configuration">
								<div id="content_row_three">
									<fieldset>
										<p>
											<strong>___CONFIG_BASIC_DESC_TITLE___:</strong> ___CONFIG_BASIC_DESC___
										</p>
										<div class="input_row_100">
											<label for="room_name">___COMMON_ROOM_NAME___<span class="required">*</span>:</label>
											<input id="room_name" type="text" class="size_200" name="form_data[room_name]" value="{show var=$popup.room.room_name}"/>
											<input id="room_show_name" type="checkbox" name="form_data[room_show_name]" value="1"{if $popup.room.room_show_name == true} checked="checked"{/if} />
											<span for="room_show_name">___PREFERENCES_SHOW_TITLE_OPTION___</span>
										</div>

										<div class="input_row_100">
											<label for="room_language">___CONTEXT_LANGUAGE___<span class="required">*</span>:</label>
											<select class="size_200" style="width:200px;" id="room_language" name="form_data[language]">
												{foreach $popup.room.languages as $language}
													<option value="{$language.value}"{if $language.value == $popup.room.language} selected="selected"{/if}{if isset($language.disabled) && $language.disabled == true} disabled="disabled"{/if}>
														{$language.text}
													</option>
												{/foreach}
											</select>
										</div>

										<div class="input_row_100">
											<label for="room_logo">___LOGO_UPLOAD___:</label>
											<form id="picture_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=popup&action=save" method="post">
												<input type="hidden" name="module" value="configuration" />
												<input type="hidden" name="additional[tab]" value="room" />
												<input id="room_logo" size="29" type="file" style="width:200px" class="size_150 float-left" name="form_data[picture]" accept="image/*" />
											</form>
											<div class="clear"></div>
										</div>

										{if isset($popup.room.logo)}
											<div class="input_row">
												<div class="input_container_180" style="margin-left:100px;">
													<img style="width:200px" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$popup.room.logo}" alt="___USER_PICTURE_UPLOADFILE___" />
													<input id="delete_logo" type="checkbox" name="form_data[delete_logo]" value="1"/>___LOGO_DELETE_OPTION___
												</div>
											<div class="clear"></div>
											</div>
										{/if}

										<div class="input_row_100">
											<label for="room_logo">___DATE_PARTICIPANTS___:</label>
									        <input type="radio" name="form_data[member_check]" value="always" {if $popup.room.member_check == 'always'}checked{/if} onclick="disable_code()"/>___PREFERENCES_CHECK_NEW_MEMBERS_ALWAYS___
											<input type="radio" name="form_data[member_check]" value="never" {if $popup.room.member_check == 'never'}checked{/if} onclick="disable_code()"/>___PREFERENCES_CHECK_NEW_MEMBERS_NEVER___
									        <input type="radio" name="form_data[member_check]" value="withcode" {if $popup.room.member_check == 'withcode'}checked{/if} onclick="enable_code()"/>___PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE___:
											<input type="text" class="size_200" name="form_data[code]" value="{if isset($popup.room.code)}{$popup.room.code}{/if}" maxlength="255" size="30"/>
											<div class="clear"></div>
										</div>



										{* assignment *}
										{if $popup.room.in_project_room == true}
											{if !empty($popup.room.community_room_array)}
												<div class="input_row_100">
													<label for="room_communityrooms">
														___PREFERENCES_COMMUNITY_ROOMS___{if $popup.room.link_status != 'optional'}<span class="required">*</span>{/if}:
													</label>
													<select class="size_200"  style="width:200px;" id="room_communityrooms" name="form_data[communityrooms]">
														{foreach $popup.room.community_room_array as $room}
															<option value="{$room.value}"{if $room.disabled == true} disabled="disabled"{/if}>{$room.text}</option>
														{/foreach}
													</select>
													<input style="width:102px;" id="add_community_room" class="popup_button" type="button" value="___PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON___" />
													{if !empty($popup.room.assigned_community_room_array)}
														<div id="assigned_community_rooms" class="input_row_100" style="margin-left:100px;">
															{foreach $popup.room.assigned_community_room_array as $room}
																<input id="room_communityroomlist" type="checkbox" name="form_data[communityroomlist_{$room.value}]" value="{$room.value}" checked="checked" />{$room.text}
															{/foreach}
														</div>
													{/if}
													<div class="clear"></div>
												</div>
											{/if}

										{elseif $popup.room.in_community_room == true}
											<div class="input_row_100">
												___PREFERENCES_ROOM_ASSIGMENT___:

												<div class="input_container_180" style="margin-left:100px;">
													<input id="room_assignment_open" type="radio" name="form_data[room_assignment]" value="open"{if $popup.room.assignment == 'open'} checked="checked"{/if} />
													<label for="room_assignment_open">___COMMON_ASSIGMENT_ON___</label>
													<div class="clear"></div>
												</div>
												<div class="input_container_180" style="margin-left:100px;">
													<input id="room_assignment_closed" type="radio" name="form_data[room_assignment]" value="closed"{if $popup.room.assignment == 'closed'} checked="checked"{/if} />
													<label for="room_assignment_closed">___COMMON_ASSIGMENT_OFF___</label>
													<div class="clear"></div>
												</div>
											</div>
										{/if}

										{if isset($popup.room.time_array)}
											<div class="input_row_100">
												<label for="delete_logo" class="float-left">{i18n tag=COMMON_TIME_NAME context=portal}:</label>
												{foreach $popup.room.time_array as $time}
													<input id="room_time_{$time.value}" type="checkbox" name="form_data[room_time_{$time.value}]" value="{$time.value}"{if $time.checked == true} checked="checked"{/if}/>
													<span>{$time.text}</span>{if !$time@last}, {/if}
												{/foreach}
											</div>
										{/if}
									</fieldset>
									<fieldset>
										<p>
											<strong>___CONFIGURATION_USAGEINFO_FORM_CHOOSE_TEXT___:</strong> ___RUBRIC_ADMIN_DESC___ {i18n tag=INTERNAL_MODULE_CONF_DESC_SHORT param1=___MODULE_CONFIG_SHORT___} {i18n tag=INTERNAL_MODULE_CONF_DESC_TINY param1=___MODULE_CONFIG_TINY___} {i18n tag=INTERNAL_MODULE_CONF_DESC_NONE param1=___MODULE_CONFIG_NONE___}
										</p>
										<div class="input_row_100">
											<label for="rubric_choice">___COMMON_RUBRICS___<span class="required">*</span>:</label>
											{foreach $popup.room.rubric_conf_array as $conf_rubric}
												<div class="input_container_180" style="margin-left:100px;">
													<select class="size_200" style="width:200px;" name="form_data[rubric_{$conf_rubric@index}]">
														{foreach $popup.room.rubric_array as $rubric}
															<option value="{$rubric.value}"{if $rubric.value == $conf_rubric.value} selected="selected"{/if}>
																{$rubric.text}
															</option>
														{/foreach}
													</select>
													<select class="size_200" style="width:200px;" name="form_data[show_{$conf_rubric@index}]">
														<option value="short"{if 'short' == $conf_rubric.show} selected="selected"{/if}>
															___MODULE_CONFIG_SHORT___
														</option>
														<option value="tiny"{if 'tiny' == $conf_rubric.show} selected="selected"{/if}>
															___MODULE_CONFIG_TINY___
														</option>
														<option value="nodisplay"{if 'none' == $conf_rubric.show} selected="selected"{/if}>
															___MODULE_CONFIG_NONE___
														</option>
													</select>
												</div>
											{/foreach}
										</div>
									</fieldset>
									<fieldset>

										<p>
											<strong>___PREFERENCES_HEXACOLOR___:</strong> ___PREFERENCES_HEXACOLOR_DESC___
										</p>
										<div class="input_row_100">
											<label for="room_color_choice">___CONFIGURATION_COLOR_FORM_CHOOSE_TEXT___:</label>
											<select class="size_200"  style="width:200px;" id="room_color_choice" name="form_data[color_choice]">
												{foreach $popup.room.color_array as $color}
													<option value="{$color.value}"{if $color.disabled == true} disabled="disabled"{/if}{if $color.value == $popup.room.color_schema} selected="selected"{/if}>{$color.text}</option>
												{/foreach}
											</select>
										</div>

										<div id="room_color_preview" class="input_row">
											<img style="width:300px" src="" alt="preview" />
										</div>

										<div id="room_color_own">
											<div class="input_row_100">
												<label for="room_color_active_menu">___ROOM_COLOR_ACTIVE_MENU___</label>
												<input class="size_200 colorpicker" id="room_color_active_menu" type="text" name="form_data[color_active_menu]" value="{show var=$popup.room.color_active_menu}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_menu">___ROOM_COLOR_MENU___</label>
												<input class="size_200 colorpicker" id="room_color_menu" type="text" name="form_data[color_menu]" value="{show var=$popup.room.color_menu}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_right_column">___ROOM_COLOR_RIGHT_COLUMN___</label>
												<input class="size_200 colorpicker" id="room_color_right_column" type="text" name="form_data[color_right_column]" value="{show var=$popup.room.color_right_menu}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_content_bg">___ROOM_COLOR_CONTENT_BG___</label>
												<input class="size_200 colorpicker" id="room_color_content_bg" type="text" name="form_data[color_content_bg]" value="{show var=$popup.room.color_content_bg}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_link">___ROOM_COLOR_LINK___</label>
												<input class="size_200 colorpicker" id="room_color_link" type="text" name="form_data[color_link]" value="{show var=$popup.room.color_link}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_link_hover">___ROOM_COLOR_LINK_HOVER___</label>
												<input class="size_200 colorpicker" id="room_color_link_hover" type="text" name="form_data[color_link_hover]" value="{show var=$popup.room.color_link_hover}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_action_bg">___ROOM_COLOR_ACTION_BG___</label>
												<input class="size_200 colorpicker" id="room_color_action_bg" type="text" name="form_data[color_action_bg]" value="{show var=$popup.room.color_action_bg}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_action_icon">___ROOM_COLOR_ACTION_ICON___</label>
												<input class="size_200" colorpicker" id="room_color_action_icon" type="text" name="form_data[color_action_icon]" value="{show var=$popup.room.color_action_icon}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_action_icon_hover">___ROOM_COLOR_ACTION_ICON_HOVER___</label>
												<input class="size_200 colorpicker" id="room_color_action_icon_hover" type="text" name="form_data[color_action_icon_hover]" value="{show var=$popup.room.color_action_icon_hover}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_bg">___ROOM_COLOR_BG___</label>
												<input class="size_200 colorpicker" id="room_color_bg" type="text" name="form_data[color_bg]" value="{show var=$popup.room.color_bg}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_bg_image">___USER_PICTURE_UPLOADFILE___:</label>
												<form id="picture_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=popup&action=save" method="post">
													<input type="hidden" name="module" value="configuration" />
													<input type="hidden" name="additional[tab]" value="room_background" />
													<input id="room_color_bg_image" type="file" class="size_200 float-left" name="form_data[picture]" accept="image/*" />
												</form>
												<div class="clear"></div>
											</div>

											<div class="input_row_100">
												<label for="room_color_bg_image_repeat">___CONFIGURATION_BGIMAGE_REPEAT___</label>
												<input id="room_color_bg_image_repeat" type="checkbox" name="form_data[color_bg_image_repeat]" value="1"{if $popup.room.color_bg == true} checked="checked"{/if} />
											</div>

											{if !empty($popup.room.color_bg_image)}
												<div class="input_row">
													<div class="input_container_180">
														<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$popup.room.color_bg_image}" alt="___USER_PICTURE_UPLOADFILE___" />
													</div>
												</div>

												<div class="input_row">
													<div class="input_container_180">
														<input id="delete_bg_image" class="float-left" type="checkbox" name="form_data[delete_bg_image]" value="1"/>
														<label for="delete_bg_image" class="float-left">___USER_DEL_PIC_BUTTON___</label>
														<div class="clear"></div>
													</div>
												</div>
											{/if}
										</div>

									</fieldset>
									<fieldset>
										<div class="input_row">
											<strong>___PORTAL_ROOM_DESCRIPTION___:</strong> ___CONFIGURATION_ROOM_DESCRIPTION___
										</div>

										<div class="input_row">
											<div class="editor_content">
												<div id="description" class="ckeditor">{if isset($popup.room.description)}{$popup.room.description}{/if}</div>
											</div>
										</div>

{*										<div class="input_row">
											___CONFIGURATION_RSS___
										</div>

										<div class="input_row">
											<label for="room_rss_yes">___CONFIGURATION_RSS_YES___</label>
											<input id="room_rss_yes" type="radio" name="form_data[rss]" value="yes"{if $popup.room.rss == 'yes'} checked="checked"{/if} />
										</div>

										<div class="input_row">
											<label for="room_rss_no">___CONFIGURATION_RSS_NO___</label>
											<input id="room_rss_no" type="radio" name="form_data[rss]" value="no"{if $popup.room.rss == 'no'} checked="checked"{/if} />
										</div>
*}
										<div class="input_row">
											<input id="submit" type="button" class="popup_button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
										</div>

									</fieldset>

									<fieldset>

									</fieldset>

								</div>
							</div>

							<div class="tab hidden" id="user">
								<div id="content_row_three">
									<fieldset>
									</fieldset>
								</div>
							</div>

							<div class="tab hidden" id="newsletter">
								<div id="content_row_three">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>