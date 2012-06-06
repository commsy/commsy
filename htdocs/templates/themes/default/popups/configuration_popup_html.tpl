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
							<a id="popup_account_tab" href="" class="pop_tab">___COMMON_ACCOUNTS___</a>
							<a href="" class="pop_tab">___CONFIG_MODERATION_TITLE___</a>
							<a href="" class="pop_tab">___INTERNAL_SPECIAL_TITLE___</a>
							<a href="" class="pop_tab">___HOME_EXTRA_TOOLS___</a>
							<a href="" class="pop_tab">___COMMON_EXTERNAL_SYSTEMS___</a>

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
											<form id="logo_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=popup&action=save" method="post">
												<input type="hidden" name="module" value="configuration" />
												<input type="hidden" name="additional[tab]" value="room_logo" />
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
									        <input type="radio" name="form_data[member_check]" value="always" {if $popup.room.member_check == 'always'}checked{/if}/>___PREFERENCES_CHECK_NEW_MEMBERS_ALWAYS___
											<input type="radio" name="form_data[member_check]" value="never" {if $popup.room.member_check == 'never'}checked{/if}/>___PREFERENCES_CHECK_NEW_MEMBERS_NEVER___
									        <input type="radio" name="form_data[member_check]" value="withcode" {if $popup.room.member_check == 'withcode'}checked{/if}/>___PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE___:
											<input type="text" class="size_200" id="code" name="form_data[code]" value="{if isset($popup.room.code)}{$popup.room.code}{else}___PREFERENCES_CHECK_NEW_MEMBERS_WITH_CODE_VALUE___{/if}" {if $popup.room.member_check != 'withcode'}disabled=disabled{/if} maxlength="255" size="30"/>
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
														<div id="assigned_community_rooms" class="input_row_100" style="margin-left:100px;">
															{foreach $popup.room.assigned_community_room_array as $room}
																<input id="room_communityroomlist" type="checkbox" name="form_data[communityroomlist_{$room.value}]" value="{$room.value}" checked="checked" />{$room.text}
															{/foreach}
														</div>
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
													<option value="{$color.value}"{if $color.disabled == true} disabled="disabled"{/if}{if $color.value == $popup.room.color_schema} selected="selected"{/if}>___{$color.text}___</option>
												{/foreach}
											</select>
										</div>

										<div id="room_color_preview" class="input_row">
											<img style="width:300px" src="" alt="preview" />
										</div>

										<div id="room_color_own">

											<div class="input_row_100">
												<label for="room_color_menu">___ROOM_COLOR_MENU___:</label>
												<input class="size_200 colorpicker" id="room_color_menu" type="text" name="form_data[color_menu]" value="{show var=$popup.room.color_menu}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_active_menu">___ROOM_COLOR_ACTIVE_MENU___:</label>
												<input class="size_200 colorpicker" id="room_color_active_menu" type="text" name="form_data[color_active_menu]" value="{show var=$popup.room.color_active_menu}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_right_column">___ROOM_COLOR_RIGHT_COLUMN___:</label>
												<input class="size_200 colorpicker" id="room_color_right_column" type="text" name="form_data[color_right_column]" value="{show var=$popup.room.color_right_menu}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_content_bg">___ROOM_COLOR_CONTENT_BG___:</label>
												<input class="size_200 colorpicker" id="room_color_content_bg" type="text" name="form_data[color_content_bg]" value="{show var=$popup.room.color_content_bg}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_link">___ROOM_COLOR_LINK___:</label>
												<input class="size_200 colorpicker" id="room_color_link" type="text" name="form_data[color_link]" value="{show var=$popup.room.color_link}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_link_hover">___ROOM_COLOR_LINK_HOVER___:</label>
												<input class="size_200 colorpicker" id="room_color_link_hover" type="text" name="form_data[color_link_hover]" value="{show var=$popup.room.color_link_hover}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_action_bg">___ROOM_COLOR_ACTION_BG___:</label>
												<input class="size_200 colorpicker" id="room_color_action_bg" type="text" name="form_data[color_action_bg]" value="{show var=$popup.room.color_action_bg}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_action_icon">___ROOM_COLOR_ACTION_ICON___:</label>
												<input class="size_200 colorpicker" id="room_color_action_icon" type="text" name="form_data[color_action_icon]" value="{show var=$popup.room.color_action_icon}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_action_icon_hover">___ROOM_COLOR_ACTION_ICON_HOVER___:</label>
												<input class="size_200 colorpicker" id="room_color_action_icon_hover" type="text" name="form_data[color_action_icon_hover]" value="{show var=$popup.room.color_action_icon_hover}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_bg">___ROOM_COLOR_BG___:</label>
												<input class="size_200 colorpicker" id="room_color_bg" type="text" name="form_data[color_bg]" value="{show var=$popup.room.color_bg}"/>
											</div>

											<div class="input_row_100">
												<label for="room_color_bg_image">___USER_PICTURE_UPLOADFILE___:</label>
												<form id="bg_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=popup&action=save" method="post">
													<input type="hidden" name="module" value="configuration" />
													<input type="hidden" name="additional[tab]" value="room_bg" />
													<input id="room_color_bg_image" type="file" class="size_200 float-left" name="form_data[picture]" accept="image/*" />
													<input style="margin-left:80px;" id="room_color_bg_image_repeat" type="checkbox" name="form_data[color_bg_image_repeat]" value="1"{if $popup.room.color_bg_image_repeat == true} checked="checked"{/if} /> ___CONFIGURATION_BGIMAGE_REPEAT___
													<input id="room_color_bg_image_fixed" type="checkbox" name="form_data[color_bg_image_fixed]" value="1"{if $popup.room.color_bg_image_fixed == true} checked="checked"{/if} /> ___CONFIGURATION_BGIMAGE_FIXED___
												</form>
												<div class="clear"></div>
											</div>

											{if !empty($popup.room.color_bg_image)}
												<div class="input_row">
													<div class="input_container_180" style="margin-left:100px;">
														<img style="width:200px" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$popup.room.color_bg_image}" alt="___USER_PICTURE_UPLOADFILE___" style="width: 200px" />
														<input id="delete_bg_image" type="checkbox" name="form_data[delete_bg_image]" value="1"/> ___USER_DEL_PIC_BUTTON___
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
									</fieldset>
									<div class="input_row">
										<input id="submit" type="button" class="popup_button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
									</div>

								</div>
							</div>

							<div class="tab hidden" id="accounts">
								<div id="content_row_three">
									
									<div id="popup_accounts">
										<div id="content_row_two_max">
						                    <div id="crt_content">
						                        <div id="crt_col_left">
						                            <div id="crt_row_area">
						                            </div>
						                        </div>
						
						                        <div id="crt_col_right">
						                            <div class="pop_item_navigation">
						                                <a id="first" href="#"><img src="{$basic.tpl_path}img/btn_ar_start2.gif" alt="Start" /></a>
						                                <a id="prev" href="#"><img src="{$basic.tpl_path}img/btn_ar_left2.gif" alt="zur&uuml;ck" /></a>
						                                <span>___COMMON_PAGE___ <span id="pop_item_current_page"></span>/<span id="pop_item_pages"></span></span>
						                                <a id="next" href="#"><img src="{$basic.tpl_path}img/btn_ar_right2.gif" alt="weiter" /></a>
						                                <a id="last" href="#"><img src="{$basic.tpl_path}img/btn_ar_end2.gif" alt="Ende" /></a>
						                            </div>
						
						                            <div class="pop_item_content">
						                                <input name="accounts_search_restriction" type="text" value="___HOME_SEARCH_SHORT_TO___" class="size_170" />
						                                <br/>
						                                <span class="sitenote">___COMMON_STATUS___</span><br/>
						                                <select name="accounts_status_restriction" size="1" class="size_170_select">
						                                	<option value="7">___ALL___</option>
						                                	<option value="8">___USER_USER___
						                                	<option value="-1" disabled="disabled">------------------</option>
						                                	<option value="6">___USER_STATUS_REJECTED___</option>
						                                	<option value="1">___USER_REQUEST___</option>
						                                	<option value="2">___USER_NORMAL_USER___</option>
						                                	<option value="3">___USER_STATUS_MODERATOR___</option>
						                                	<option value="10">___USER_STATUS_CONTACT___</option>
						                                </select>
						                                <br/>
						                                <input name="accounts_submit_restrictions" type="submit" value="___COMMON_SEARCH_OVERLAY_RESTRICTION_OPTIONS___" />
						                            </div>
						                        </div>
						
						                        <div class="clear"> </div>
						                    </div>
						                </div>
									</div>
								
								</div>
							</div>


							<div class="tab hidden" id="moderation_configuration">
								<div id="content_row_three">
									<fieldset>
										<p>
											<strong>___COMMON_INFORMATION_BOX___:</strong> ___COMMON_INFORMATION_BOX_ID_ENTRY___
										</p>
										<div class="input_row_150">
											<label for="room_name">___COMMON_INFORMATION_BOX_SHORT___:</label>
											<input id="room_name" type="text" class="size_200" name="form_data[item_id]" value="{show var=$popup.moderation.item_id}"/>
											<input type="radio" name="form_data[show_information_box]" value="1" {if $popup.moderation.show_information_box == '1'}checked{/if}/>___COMMON_SHOW_INFORMATION_BOX_YES___
         									<input type="radio" name="form_data[show_information_box]" value="0" {if $popup.moderation.show_information_box == '0'}checked{/if}/>___COMMON_SHOW_INFORMATION_BOX_NO___
										</div>
										<div class="clear"></div>
									</fieldset>
									<fieldset>
										<p>
											<strong>___PREFERENCES_USAGE_INFOS___:</strong> ___PREFERENCES_USAGE_INFOS_DESC___
										</p>
										<div class="input_row_100">
											<label for="room_name">___COMMON_CHOOSE_RUBRIC___:</label>
											<select class="size_200"  style="width:200px;" id="moderation_rubric" name="form_data[array_info_text_rubric]">
												{foreach $popup.moderation.array_info_text as $info_text}
													<option value="{$info_text.key}">{$info_text.rubric}</option>
												{/foreach}
											</select>
										</div>
										<div class="input_row_100">
											<label for="room_name">___COMMON_TITLE___:</label>
											{foreach $popup.moderation.array_info_text as $info_text}
												<input id="moderation_title_{$info_text.key}" type="text" class="size_200 {if $info_text@index >0}hidden{/if}" name="form_data[moderation_title_{$info_text.key}]" value="{show var=$info_text.title}"/>
											{/foreach}

										</div>
										<div class="input_row_100">
											<label for="room_name">___COMMON_TEXT___:</label>
											{foreach $popup.moderation.array_info_text as $info_text}
												<div class="editor_content {if $info_text@index >0}hidden{/if}" style="margin-left:100px;">
													<div id="moderation_description_{$info_text.key}" class="ckeditor">{if isset($info_text.text)}{$info_text.text}{/if}</div>
												</div>
											{/foreach}

										</div>

									</fieldset>
									<fieldset>
										<p>
											<strong>___CONFIGURATION_MAIL_FORM_HEADLINE___:</strong> ___PREFERENCES_MAIL_DESC___
										</p>
										<div class="input_row_100">
											<label for="room_name">___COMMON_CONFIGURATION_MAIL_FORM_TITLE___:</label>
											<select class="size_200"  style="width:200px;" id="mailtext_rubric" name="form_data[array_mail_text_rubric]">
												{foreach $popup.moderation.array_mail_text as $mailtext}
													<option id="mail_text_{$mailtext@index}" value="{$mailtext.value}">{$mailtext.text}</option>
												{/foreach}
											</select>
										</div>
										<div class="input_row_100">
											<label for="room_name">___COMMON_BODY___ (___DE___):</label>
											{foreach $popup.moderation.array_mail_text as $mail_text}
												{if $mail_text.value != -1 && $mail_text.value != 'disabled'}
													<div class="editor_content {if $mail_text@index >0}hidden{/if}" style="margin-left:100px;">
														<div id="moderation_mail_body_de_{$mail_text@index}" class="ckeditor">{if isset($mail_text.body_de)}{$mail_text.body_de}{/if}</div>
													</div>
												{/if}
											{/foreach}

										</div>
										<div class="input_row_100">
											<label for="room_name">___COMMON_BODY___ (___EN___):</label>
											{foreach $popup.moderation.array_mail_text as $mail_text}
												{if $mail_text.value != -1 && $mail_text.value != 'disabled'}
													<div class="editor_content {if $mail_text@index >0}hidden{/if}" style="margin-left:100px;">
														<div id="moderation_mail_body_en_{$mail_text@index}" class="ckeditor">{if isset($mail_text.body_en)}{$mail_text.body_en}{/if}</div>
													</div>
												{/if}
											{/foreach}

										</div>
									</fieldset>
									<div class="input_row">
										<input id="submit" type="button" class="popup_button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
									</div>
								</div>
							</div>


							<div class="tab hidden" id="additional_configuration">
								<div id="content_row_three">
									<fieldset>
										<p>
											<strong>___CONFIGURATION_STRUCTURE_OPTIONS_TITLE___</strong>
										</p>
										<div class="input_row_100">
											<label for="additional_structure">___COMMON_BUZZWORDS___:</label>
											<input id="radditional_buzzword" type="checkbox" name="form_data[buzzword]" value="yes"{if $popup.additional.buzzword == 'yes'} checked="checked"{/if} />___PREFERENCES_CONFIGURATION_BUZZWORD_VALUE___
											<input id="radditional_buzzword_mandatory" type="checkbox" name="form_data[buzzword_mandatory]" value="yes"{if $popup.additional.buzzword_mandatory == 'yes'} checked="checked"{/if} />___CONFIGURATION_BUZZWORDS_MANDATORY___
											<div class="clear"></div>
										</div>
										<div class="input_row_100">
											<label for="additional_structure">___COMMON_TAGS___:</label>
											<input id="radditional_tags" type="checkbox" name="form_data[tags]" value="yes"{if $popup.additional.tags == 'yes'} checked="checked"{/if} />___PREFERENCES_CONFIGURATION_TAGS_VALUE___
											<input id="radditional_tags_mandatory" type="checkbox" name="form_data[tags_mandatory]" value="yes"{if $popup.additional.tags_mandatory == 'yes'} checked="checked"{/if} />___CONFIGURATION_TAG_MANDATORY___
											<input id="radditional_tags_edit" type="checkbox" name="form_data[tags_edit]" value="yes"{if $popup.additional.tags_edit == 'yes'} checked="checked"{/if} />___CONFIGURATION_TAG_EDIT_BY_MODERATOR___
											<div class="clear"></div>
										</div>
									</fieldset>
									<fieldset>
										<p>
											<strong>___INTERNAL_TIME_SPREAD___:</strong> ___INTERNAL_TIME_SPREAD_DESC___
										</p>
										<div class="input_row_100">
											<label for="additional_time_spread">___COMMON_EDIT___:</label>
											<input class="size_50" id="time_spread" type="text" name="form_data[time_spread]" value="{$popup.additional.time_spread}"/> ___COMMON_DAYS___
											<div class="clear"></div>
										</div>
									</fieldset>
									<fieldset>
										<p>
											<strong>___COMMON_CONFIGURATION_DATES_FORM_TITLE___:</strong> ___CONFIGURATION_DATES_DESC___
										</p>
										<div class="input_row_100">
											<label for="additional_dates_status">___CONFIGURATION_DATES_LABEL___:</label>
											<input type="radio" name="form_data[dates_status]" value="normal" {if $popup.additional.dates_status == 'normal'} checked="checked"{/if}/> ___CONFIGURATION_DATES_PRESENTATION_NORMAL___
									        <input type="radio" name="form_data[dates_status]" value="calendar" {if $popup.additional.dates_status == 'calendar'} checked="checked"{/if}/> ___CONFIGURATION_DATES_PRESENTATION_CALENDAR_WEEK___
									        <input type="radio" name="form_data[dates_status]" value="calendar_month" {if $popup.additional.dates_status == 'calendar_month'} checked="checked"{/if}/> ___CONFIGURATION_DATES_PRESENTATION_CALENDAR___
											<div class="clear"></div>
										</div>
									</fieldset>
									<fieldset>
										<p>
											<strong>___COMMON_TODO_INDEX___:</strong> ___CONFIGURATION_TODO_STATUS_MANAGEMENT_DESC___
										</p>
										<div class="input_row_100">
											<label for="additional_status">___USER_STATUS_NEW___:</label>
											<input class="size_200" id="status" type="text" name="form_data[status]" value=""/>
											<input id="add_additional_status" type="button" class="popup_button" name="form_data[status_option]" value="___CONFIGURATION_TODO_NEW_STATUS_BUTTON___"/>

											<div id="additional_status_list" class="input_container_180" style="margin-left:100px;">
												{foreach $popup.additional.additional_extra_status_array as $extra_status}
													<input type="checkbox" name="form_data[additional_status_{$extra_status.value}]" value="{$extra_status.text}" checked="checked" />{$extra_status.text}
												{/foreach}
											</div>
											<div class="clear"></div>
										</div>
									</fieldset>
									<fieldset>
										<p>
											<strong>___CONFIGURATION_RSS___</strong>
										</p>
										<div class="input_row_100">
											<label for="additional_assessment">___COMMON_ASSESSMENT_LABEL___:</label>
											<input id="room_rss_yes" type="radio" name="form_data[rss]" value="yes"{if $popup.additional.rss == 'yes'} checked="checked"{/if} /> ___CONFIGURATION_RSS_YES___
											<input id="room_rss_no" type="radio" name="form_data[rss]" value="no"{if $popup.additional.rss == 'no'} checked="checked"{/if} /> ___CONFIGURATION_RSS_NO___
											<div class="clear"></div>
										</div>
									</fieldset>

									<fieldset>
										<p>
											<strong>___CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE___:</strong> ___CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE_LONG___ ___CONFIGURATION_TEMPLATE_FORM_SELECT_DESC___
										</p>
										<div class="input_row_100">
											<label for="additional_template">___COMMON_STATUS___:</label>
											<input type="checkbox" name="form_data[template]" value="1" {if $popup.additional.template == true} checked="checked"{/if}/> ___CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE___
											<div class="clear"></div>
										</div>
										<div class="input_row_100">
											<label for="additional_template_availability">___CONFIGURATION_TEMPLATE_GROUP___:</label>
											<select class="size_200"  style="width:200px;" id="additional_template_availability" name="form_data[template_availability]">
												<option value="0"{if $popup.additional.template_availability == '0'} selected="selected"{/if}>___CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_ALL_USERS___</option>
												<option value="1"{if $popup.additional.template_availability == '1'} selected="selected"{/if}>___CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_ROOM_USERS___</option>
												<option value="2"{if $popup.additional.template_availability == '2'} selected="selected"{/if}>___CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_ROOM_MODERATORS___</option>
											</select>
											<div class="clear"></div>
										</div>
										<div class="input_row">
											<div class="editor_content">
												<div id="template_description" class="ckeditor">{if isset($popup.additional.template_description)}{$popup.additional.template_description}{/if}</div>
											</div>
										</div>
									</fieldset>

									<fieldset>
										<p>
											<strong>___CONTEXT_ROOM_ARCHIVE___:</strong> ___ROOM_STATUS_LONG_DESCRIPTION___
										</p>
										<div class="input_row_100">
											<label for="additional_room_status">___COMMON_STATUS___:</label>
											<input type="checkbox" name="form_data[room_status]" value="2" {if $popup.additional.room_status == 2} checked="checked"{/if}/> ___ROOM_STATUS_DESCRIPTION___
											<div class="clear"></div>
										</div>
									</fieldset>

									<fieldset>
										<p>
											<strong>___AGB_CONFIRMATION___:</strong> ___CONFIGURATION_AGB_FORM_WANT_DESC___
										</p>
										<div class="input_row_100">
											<label for="additional_agb_status">___COMMON_STATUS___:</label>
											<input id="room_agb_status_yes" type="radio" name="form_data[agb_status]" value="1"{if $popup.additional.agb_status == '1'} checked="checked"{/if} /> ___COMMON_YES___
											<input id="room_agb_status_no" type="radio" name="form_data[agb_status]" value="2"{if $popup.additional.agb_status == '2'} checked="checked"{/if} /> ___COMMON_NO___
											<div class="clear"></div>
										</div>
										<div class="input_row_100">
											<label for="additional_agb_status">___COMMON_LANGUAGE___:</label>
											<select class="size_200"  style="width:200px;" id="additional_agb_description_text" name="form_data[agb_description_text]">
												<option value="de">___DE___</option>
												<option value="en">___EN___</option>
									         </select>
											<div class="clear"></div>
										</div>
										<div class="input_row_100">
											<label for="room_name">___COMMON_TEXT___ (___DE___):</label>
											<div class="editor_content" style="margin-left:100px;">
												<div id="agb_text_de" class="ckeditor">{if isset($popup.additional.agb_text_DE)}{$popup.additional.agb_text_DE}{/if}</div>
											</div>
										</div>
										<div class="input_row_100 hidden">
											<label for="room_name">___COMMON_TEXT___ (___EN___):</label>
											<div class="editor_content" style="margin-left:100px;">
												<div id="agb_text_en" class="ckeditor">{if isset($popup.additional.agb_text_EN)}{$popup.additional.agb_text_EN}{/if}</div>
											</div>
										</div>
									</fieldset>

									<div class="input_row">
										<input id="submit" type="button" class="popup_button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
									</div>
								</div>
							</div>

							<div class="tab hidden" id="addons">
								<div id="content_row_three">
									<fieldset>
										<p>
											<strong>___COMMON_ASSESSMENT___:</strong> ___COMMON_ASSESSMENT_EXPLANATION_VALUE___
										</p>
										<div class="input_row_100">
											<label for="addon_assessment">___COMMON_ASSESSMENT_LABEL___:</label>
											<input type="checkbox" name="form_data[assessment]" value="1" {if $popup.addon.assessment == true} checked="checked"{/if}/> ___COMMON_ASSESSMENT_CONFIGURATION_CHOICE_VALUE___
											<div class="clear"></div>
										</div>
									</fieldset>
									<fieldset>
										<p>
											<strong>___COMMON_WORKFLOW_DESCRIPTION___:</strong> ___COMMON_WORKFLOW_DESCRIPTION_DESC___
										</p>
										<div class="input_row_150">
											<label for="addon_workflow_resubmission">___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE___:</label>
											<input type="checkbox" name="form_data[workflow_resubmission]" value="yes" {if $popup.addon.workflow_resubmission == 'yes'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_ENABLE___
											<div class="clear"></div>
										</div>
										<div class="input_row_150">
											<label for="addon_workflow_validity">___COMMON_WORKFLOW_VALIDITY___:</label>
											<input type="checkbox" name="form_data[workflow_validity]" value="yes" {if $popup.addon.workflow_validity == 'yes'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_ENABLE___
											<div class="clear"></div>
										</div>
										<div class="input_row_150">
											<label for="addon_workflow_trafic_light">___PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_VALUE___:</label>
											<input type="checkbox" name="form_data[workflow_trafic_light]" value="yes" {if $popup.addon.workflow_trafic_light == 'yes'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_ENABLE___
											<div class="clear"></div>
										</div>
										<div class="input_row_150">
											<label for="addon_workflow_trafic_light_default">___PREFERENCES_CONFIGURATION_WORKFLOW_TRAFFIC_LIGHT_DEFAULT___:</label>
											<img src="images/commsyicons/workflow_traffic_light_green.png" style="height:12px;"> <input type="radio" name="form_data[workflow_trafic_light_default]" value="0_green" {if $popup.addon.workflow_trafic_light_default == '0_green'} checked="checked"{/if}/>
											<img src="images/commsyicons/workflow_traffic_light_yellow.png" style="margin-left:119px; height:12px;"><input type="radio" name="form_data[workflow_trafic_light_default]" value="1_yellow" {if $popup.addon.workflow_trafic_light_default == '1_yellow'} checked="checked"{/if}/>
											<img src="images/commsyicons/workflow_traffic_light_red.png" style="margin-left:123px; height:12px;"><input type="radio" name="form_data[workflow_trafic_light_default]" value="2_red" {if $popup.addon.workflow_trafic_light_default == '2_red'} checked="checked"{/if}/>
											<span style="margin-left:119px;">___COMMON_WORKFLOW_TRAFFIC_LIGHT_NONE___</span>
											<input type="radio" name="form_data[workflow_trafic_light_default]" value="3_none" {if $popup.addon.workflow_trafic_light_default == '3_none'} checked="checked"{/if}/>
											<div class="clear"></div>
										</div>
										<div class="input_row_150">
											<label for="addon_workflow_trafic_light_text">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT___:</label>
											<img src="images/commsyicons/workflow_traffic_light_green.png" style="height:12px;"> <input id="room_name" type="text" class="size_120" name="form_data[workflow_trafic_light_green_text]" value="{$popup.addon.workflow_trafic_light_green_text}"/>
											<img src="images/commsyicons/workflow_traffic_light_yellow.png" style="height:12px;"> <input id="room_name" type="text" class="size_120" name="form_data[workflow_trafic_light_yellox_text]" value="{$popup.addon.workflow_trafic_light_yellow_text}"/>
											<img src="images/commsyicons/workflow_traffic_light_red.png" style="height:12px;"> <input id="room_name" type="text" class="size_120" name="form_data[workflow_trafic_light_red_text]" value="{$popup.addon.workflow_trafic_light_red_text}"/>
											<div class="clear"></div>
										</div>
										<div class="input_row_150">
											<label for="addon_workflow_reader">___PREFERENCES_CONFIGURATION_WORKFLOW_READER_VALUE___:</label>
											<input type="checkbox" name="form_data[workflow_reader]" value="yes" {if $popup.addon.workflow_reader == 'yes'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_ENABLE___
											<div class="clear"></div>
										</div>
										<div class="input_container_180" style="margin-left:150px;">
											<input type="checkbox" name="form_data[workflow_reader_group]" value="yes" {if $popup.addon.workflow_reader_group == 'yes'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_READER_GROUP_VALUE___
											<input type="checkbox" name="form_data[workflow_reader_person]" value="yes" {if $popup.addon.workflow_reader_person == 'yes'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_READER_PERSON_VALUE___
										</div>
										<div class="input_container_180" style="margin-left:150px;">
											<input type="radio" name="form_data[workflow_resubmission_show_to]" value="moderator" {if $popup.addon.workflow_resubmission_show_to == 'moderator'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_SHOW_TO_MODERATOR_VALUE___
											<input type="radio" name="form_data[workflow_resubmission_show_to]" value="all" {if $popup.addon.workflow_resubmission_show_to == 'all'} checked="checked"{/if}/> ___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_SHOW_TO_ALL_VALUE___
										</div>
									</fieldset>
									<div class="input_row">
										<input id="submit" type="button" class="popup_button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
									</div>
								</div>
							</div>

							<div class="tab hidden" id="external_systems">
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