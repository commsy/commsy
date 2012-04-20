{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_background"></div>

	<div class="tm_dropmenu hidden">
		<div class="tm_di_ground_solid">

			<div class="popup">

				<div id="popup_content">
					<div id="profile_content_row_three">
						<div class="tab_navigation">
							<a href="" class="pop_tab_active">___PROFILE_ACCOUNT_DATA___</a>
							<a href="" class="pop_tab">___PROFILE_USER_DATA___</a>
							<a href="" class="pop_tab">___PROFILE_ROOM_LIST_DATA___</a>
							<a href="" class="pop_tab">___PROFILE_NEWSLETTER_DATA___</a>

							<div class="clear"> </div>
						</div>

						<div id="popup_tabcontent">
							<div class="tab">
								<div id="content_row_three">
									<fieldset>
										<legend>___MYAREA_MY_PROFILE___</legend>

										<div class="input_row">
											<label for="forname">___USER_FIRSTNAME___</label>
											<input id="forname" type="text" class="size_200 mandatory" name="form_data[forname]" value="{show var=$popup.form.account.firstname}"/>
										</div>

										<div class="input_row">
											<label for="surname">___USER_LASTNAME___</label>
											<input id="surname" type="text" class="size_200 mandatory" name="form_data[surname]" value="{show var=$popup.form.account.lastname}"/>
										</div>
										
										<div class="input_row">
											{if $popup.form.config.show_account_change_form === true}
												<label for="user_id">___USER_USER_ID___</label>
												<input id="user_id" type="text" class="size_200 mandatory" name="form_data[user_id]" value="{show var=$popup.form.account.user_id}"/>
											{else}
												{show var=$popup.form.account.user_id}
											{/if}
										</div>
										
										{if $popup.form.config.show_password_change_form === true}
											<div class="input_row">
												<label for="old_password">___USER_PASSWORD_OLD___</label>
												<input id="old_password" type="text" class="size_200" />
											</div>
	
											<div class="input_row">
												<label for="new_password">___USER_PASSWORD_NEW___</label>
												<input id="new_password" type="text" class="size_200" />
											</div>
	
											<div class="input_row">
												<label for="new_password_confirm">___USER_PASSWORD_NEW2___</label>
												<input id="new_password_confirm" type="text" class="size_200" />
											</div>
										{/if}

										<div class="input_row">
											<label for="language">___USER_LANGUAGE___</label>
											<select id="language">
												{foreach $popup.form.languages as $language}
													<option value="{$language.value}"{if $language.value == $popup.form.account.language} selected="selected"{/if}>{$language.text}</option>
												{/foreach}
											</select>
										</div>
										
										{if $popup.form.config.show_mail_change_form === true}
											<div class="input_row">
												<label for="mail_account">___USER_EMAIL___</label>
												
												<div class="input_container">
													<input id="mail_account" type="checkbox"{if $popup.form.account.email_account == true} checked="checked"{/if}/> ___USER_MAIL_GET_ACCOUNT___<br/>
													<input id="mail_room" type="checkbox"{if $popup.form.account.email_room == true} checked="checked"{/if}/> ___USER_MAIL_OPEN_ROOM_PO___
												</div>
											</div>
										{/if}

										<div class="input_row">
											<label for="upload">___CONFIGURATION_NEW_UPLOAD___</label>

											<div class="input_container">
												<input id="upload" type="radio" name="form_data[upload]"{if $popup.form.account.new_upload == true} checked="checked"{/if}/> ___CONFIGURATION_NEW_UPLOAD_YES___<br/>
												<input type="radio" name="form_data[upload]"{if $popup.form.account.new_upload != true} checked="checked"{/if}/> ___CONFIGURATION_NEW_UPLOAD_NO___
											</div>
										</div>

										<div class="input_row">
											<label for="auto_save">___CONFIGURATION_AUTO_SAVE___</label>

											<div class="input_container">
												<input id="auto_save" type="radio" name="form_data[auto_save]"{if $popup.form.account.auto_save == true} checked="checked"{/if}/> ___CONFIGURATION_AUTO_SAVE_YES___<br/>
												<input type="radio" name="form_data[auto_save]"{if $popup.form.account.auto_save != true} checked="checked"{/if}/> ___CONFIGURATION_AUTO_SAVE_NO___
											</div>
										</div>

										<div class="input_row">
											<div class="input_container">
												<input id="submit" type="button" name="form_data[save]" value="___PREFERENCES_SAVE_BUTTON___"/>
												<input id="delete" type="button" name="form_data[delete]" value="___PREFERENCES_DELETE_BUTTON___"/>
											</div>

										</div>
									</fieldset>
									
									{if $popup.form.config.show_merge_form === true}
										<fieldset>
											<legend>___ACCOUNT_MERGE___</legend>
	
											<div class="input_row">
												<div class="input_container">
													{i18n tag="ACCOUNT_MERGE_TEXT" param1=$popup.portal.portal_name}
												</div>
											</div>
											
											{if sizeof($popup.form.data.auth_source_array) > 1 && $popup.form.config.show_auth_source === true}
												<div class="input_row">
													<label for="auth_source">___USER_AUTH_SOURCE___</label>
													<select id="auth_source">
														{foreach $popup.form.data.auth_source_array as $auth_source}
															<option value="{$auth_source.value}"{if $auth_source.value == $popup.form.data.default_auth_source} selected="selected"{/if}>{$auth_source.text}</option>
														{/foreach}
													</select>
												</div>
											{/if}
            								
											<div class="input_row">
												<label for="merge_user_id">___USER_USER_ID___</label>
												<input id="merge_user_id" type="text" class="size_200 mandatory" />
											</div>
	
											<div class="input_row">
												<label for="merge_user_password">___USER_PASSWORD___</label>
												<input id="merge_user_password" type="text" class="size_200 mandatory" />
											</div>
	
											<div class="input_row">
												<div class="input_container">
													___COMMON_DONT_STOP___
												</div>
											</div>
	
											<div class="input_row">
												<div class="input_container">
													<input id="merge" type="button" name="form_data[merge]" value="___ACCOUNT_MERGE_BUTTON___"/>
												</div>
											</div>
										</fieldset>
									{/if}
								</div>
							</div>

							<div class="tab hidden">
								<div id="content_row_three">
									<fieldset>
										<legend>Allgemein</legend>
										
										<div class="input_row">
											<label for="data_title">___USER_TITLE___</label>
											<input id="data_title" type="text" class="size_200 float-left" />
											<input id="data_title_all" type="checkbox" class="float-left" />
											<label for="data_title_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_birthday">___USER_BIRTHDAY___</label>
											<input id="data_birthday" type="text" class="size_200 float-left" />
											<input id="data_birthday_all" type="checkbox" class="float-left" />
											<label for="data_birthday_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_picture">___USER_PICTURE_UPLOADFILE___</label>
											<input id="data_picture" type="file" class="size_200 float-left" />
											<input id="data_picture_all" type="checkbox" class="float-left" />
											<label for="data_picture_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>
									
									<fieldset>
										<legend>Kontakt</legend>
										
										<div class="input_row">
											<label for="data_mail">___USER_EMAIL___</label>
											<input id="data_mail" type="text" class="size_200 float-left" />
											<input id="data_mail_all" type="checkbox" class="float-left" />
											<label for="data_mail_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_telephone">___USER_TELEPHONE___</label>
											<input id="data_telephone" type="text" class="size_200 float-left" />
											<input id="data_telephone_all" type="checkbox" class="float-left" />
											<label for="data_telephone_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_cellularphone">___USER_CELLULARPHONE___</label>
											<input id="data_cellularphone" type="text" class="size_200 float-left" />
											<input id="data_cellularphone_all" type="checkbox" class="float-left" />
											<label for="data_cellularphone_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>
									
									<fieldset>
										<legend>Adresse</legend>
										
										<div class="input_row">
											<label for="data_street">___USER_STREET___</label>
											<input id="data_street" type="text" class="size_200 float-left" />
											<input id="data_street_all" type="checkbox" class="float-left" />
											<label for="data_street_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_zipcode">___USER_ZIPCODE___</label>
											<input id="data_zipcode" type="text" class="size_200 float-left" />
											<input id="data_zipcode_all" type="checkbox" class="float-left" />
											<label for="data_zipcode_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_city">___USER_CITY___</label>
											<input id="data_city" type="text" class="size_200 float-left" />
											<input id="data_city_all" type="checkbox" class="float-left" />
											<label for="data_city_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_room">___USER_ROOM___</label>
											<input id="data_room" type="text" class="size_200 float-left" />
											<input id="data_room_all" type="checkbox" class="float-left" />
											<label for="data_room_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>
									
									<fieldset>
										<legend>Organisation</legend>
										
										<div class="input_row">
											<label for="data_organisation">___USER_ORGANISATION___</label>
											<input id="data_organisation" type="text" class="size_200 float-left" />
											<input id="data_organisation_all" type="checkbox" class="float-left" />
											<label for="data_organisation_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_position">___USER_POSITION___</label>
											<input id="data_position" type="text" class="size_200 float-left" />
											<input id="data_position_all" type="checkbox" class="float-left" />
											<label for="data_position_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>
									
									<fieldset>
										<legend>Messenger</legend>
										
										<div class="input_row">
											<div class="input_container">
												___USER_MESSENGER_NUMBERS_TEXT___
											</div>
										</div>
										
										<div class="input_row">
											<label for="data_icq">___USER_ICQ___</label>
											<input id="data_icq" type="text" class="size_200 float-left" />
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_msn">___USER_MSN___</label>
											<input id="data_msn" type="text" class="size_200 float-left" />
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_skype">___USER_SKYPE___</label>
											<input id="data_skype" type="text" class="size_200 float-left" />
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_yahoo">___USER_YAHOO___</label>
											<input id="data_yahoo" type="text" class="size_200 float-left" />
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<div class="input_container">
												<input id="data_messenger_all" type="checkbox" class="float-left" />
												<label for="data_messenger_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
												<div class="clear"></div>
											</div>
										</div>
									</fieldset>
									
									<fieldset>
										<legend>Sonstiges</legend>
										
										<div class="input_row">
											<label for="data_homepage">___USER_HOMEPAGE___</label>
											<input id="data_homepage" type="text" class="size_200 float-left" />
											<input id="data_homepage_all" type="checkbox" class="float-left" />
											<label for="data_homepage_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
										
										<div class="input_row">
											<label for="data_description">___USER_DESCRIPTION___</label>
											<textarea id="data_description"></textarea>
										</div>
										
										<div class="input_row">
											<div class="input_container">
												<input id="data_position_all" type="checkbox" class="float-left" />
												<label for="data_position_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
												<div class="clear"></div>
											</div>
										</div>
									</fieldset>
									
									<div class="input_row">
										<input id="submit" type="button" name="form_data[save]" value="___PREFERENCES_SAVE_BUTTON___"/>
									</div>
								</div>
							</div>

							<div class="tab hidden">
								<div id="content_row_three">
									<div class="input_row">
										<div class="input_container">
											___PROFILE_ROOMLIST_CUSTOMIZING_DESCRIPTION___
										</div>
									</div>
									
									<div class="input_row">
										edit area
									</div>
									
									<div class="input_row">
										<div class="input_container">
											<input id="roomlist_delete" type="checkbox" class="float-left" />
											<label for="roomlist_delete" class="float-left autowidth">___PROFILE_ROOMLIST_DELETE_OPTION___</label>
										</div>
									</div>
								</div>
							</div>

							<div class="tab hidden">
								<div id="content_row_three">
									<div class="input_row">
										<label for="newsletter">___USER_STATUS___</label>

										<div class="input_container">
											<input id="newsletter" type="radio" name="form_data[newsletter]"/> ___CONFIGURATION_NEWSLETTER_NONE___<br/>
											<input type="radio" name="form_data[newsletter]"/> ___CONFIGURATION_NEWSLETTER_WEEKLY___<br/>
											<input type="radio" name="form_data[newsletter]"/> ___CONFIGURATION_NEWSLETTER_DAILY___
										</div>
									</div>
									
									<div class="input_row">
										<div class="input_container">
											___CONFIGURATION_NEWSLETTER_NOTE___
										</div>
									</div>
									
									<div class="input_row">
										<div class="input_container">
											<input id="submit" type="button" name="form_data[save]" value="___PREFERENCES_SAVE_BUTTON___"/>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>