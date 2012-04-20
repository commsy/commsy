<div id="popup_wrapper">
	<div id="popup_background"></div>

	<div class="tm_dropmenu hidden">
		<div class="tm_di_ground_solid">

			<div id="popup">

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
											<input id="forname" type="text" class="size_200 mandatory" />
										</div>

										<div class="input_row">
											<label for="surname">___USER_LASTNAME___</label>
											<input id="surname" type="text" class="size_200 mandatory" />
										</div>

										<div class="input_row">
											<label for="user_id">___USER_USER_ID___</label>
											<input id="user_id" type="text" class="size_200 mandatory" />
										</div>

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

										<div class="input_row">
											<label for="language">___USER_LANGUAGE___</label>
											<select id="language">
												{foreach $popup.form.languages as $language}
													<option value="{$language.value}">{$language.text}</option>
												{/foreach}
											</select>
										</div>

										<div class="input_row">
											<label for="upload">___CONFIGURATION_NEW_UPLOAD___</label>

											<div class="input_container">
												<input id="upload" type="radio" name="form_data[upload]"/> ___CONFIGURATION_NEW_UPLOAD_YES___</br>
												<input type="radio" name="form_data[upload]"/> ___CONFIGURATION_NEW_UPLOAD_NO___
											</div>
										</div>

										<div class="input_row">
											<label for="auto_save">___CONFIGURATION_AUTO_SAVE___</label>

											<div class="input_container">
												<input id="auto_save" type="radio" name="form_data[auto_save]"/> ___CONFIGURATION_AUTO_SAVE_YES___</br>
												<input type="radio" name="form_data[auto_save]"/> ___CONFIGURATION_AUTO_SAVE_NO___
											</div>
										</div>

										<div class="input_row">
											<div class="input_container">
												<input id="submit" type="button" name="form_data[save]" value="___PREFERENCES_SAVE_BUTTON___"/>
												<input id="delete" type="button" name="form_data[delete]" value="___PREFERENCES_DELETE_BUTTON___"/>
											</div>

										</div>
									</fieldset>

									<fieldset>
										<legend>___ACCOUNT_MERGE___</legend>

										<div class="input_row">
											<div class="input_container">
												{i18n tag="ACCOUNT_MERGE_TEXT" param1=$popup.portal.portal_name}
											</div>
										</div>

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
								</div>
							</div>

							<div class="tab hidden">
								Personendaten
							</div>

							<div class="tab hidden">
								Raumliste
							</div>

							<div class="tab hidden">
								Newsletter
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


{*
<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup">

			<div id="popup_head">
				<h2>___BUZZWORDS_EDIT_HEADER___</h2>
				<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/pop_close_btn.gif" alt="___COMMON_CLOSE___" /></a>

				<div class="clear"> </div>
			</div>

			<div id="popup_content">

				<div class="tab_navigation">
                    <a href="" class="pop_tab_active">hinzuf&uuml;gen</a>
                    <a href="" class="pop_tab">zusammenlegen</a>
                    <a href="" class="pop_tab">bearbeiten</a>

                    <div class="clear"> </div>
                </div>

				<div id="popup_tabcontent">
					<div class="tab">
						<div id="content_row_one">
							<div class="input_row">
								<input id="buzzword_create_name" type="text" class="size_200 mandatory" />
								<input id="buzzword_create" class="popup_button" type="button" name="form_data[buzzword_create]" value="___BUZZWORDS_NEW_BUTTON___" />
							</div>
						</div>

						<div id="content_row_two">
							&nbsp;
						</div>
					</div>

					<div class="tab hidden">
						___BUZZWORDS_COMBINE_BUTTON___
					</div>

					<div class="tab hidden">
						<div id="content_row_one">
							{foreach $popup.buzzwords as $buzzword}
								<div class="input_row">
									<input type="text" value="{$buzzword.name}" class="buzzword_change_name size_200" />
									<input class="popup_button buzzword_change mandatory" type="button" name="form_data[{$buzzword.item_id}]" value="___BUZZWORDS_CHANGE_BUTTON___" />
									<input class="popup_button buzzword_attach" type="button" name="form_data[{$buzzword.item_id}]" value="___COMMON_ATTACH_BUTTON___" />
									<input class="popup_button buzzword_delete" type="button" name="form_data[{$buzzword.item_id}]" value="___COMMON_DELETE_BUTTON___" />
								</div>
							{/foreach}
						</div>

						<div id="content_row_two">
							&nbsp;
						</div>
					</div>
				</div>

			</div>

		</div>
	</div>
</div>

*}