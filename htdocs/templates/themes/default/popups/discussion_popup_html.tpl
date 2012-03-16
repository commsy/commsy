<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup">

			<div id="popup_head">
				<h2>Neue Diskussion erstellen</h2>
				<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/pop_close_btn.gif" alt="___COMMON_CLOSE___" /></a>

				<div class="clear"> </div>
			</div>

			<div id="popup_content">

				<div id="content_row_three">
					<div class="input_row">
						<span class="input_label">Titel</span> <input type="text" value="" name="form_data[title]" class="size_200 mandatory" />
						
						<span class="input_label">Art der Diskussion</span>
						<input type="radio" name="form_data[discussion_type]" value="1" checked="checked">___DISCUSSION_SIMPLE___
						<input type="radio" name="form_data[discussion_type]" value="2">___DISCUSSION_THREADED___
					</div>

					<div id="pop_editor">
						<h2 id="pop_editor_head">Initialbeitrag der Diskussion</h2>
						
						<input type="hidden" value="" name="iid"/>
						<input type="hidden" value="{$detail.item_id}" name="discussion_id"/>
						<input type="hidden" value="1" name="ref_position"/>
						<div class="editor_content">
							<div id="ckeditor"></div>
							<input type="hidden" id="ckeditor_content" name="form_data[description]" value=""/>
						</div>
					</div>

					<div class="tab_navigation">
						<a href="" class="pop_tab_active">Dateien anh&auml;ngen</a>
						<a href="" class="pop_tab">Zugriffsrechte</a>
						<a href="" class="pop_tab">Schlagwörter</a>
						<a href="" class="pop_tab">Kategorien</a>

						<div class="clear"> </div>
					</div>

					<div id="popup_tabcontent">
						<div class="settings_area">

							<div class="sa_col_left">
								<div id="file_finished"></div>
								<input id="uploadify" name="uploadify" type="file" />

								<div>
									<a id="uploadify_doUpload">
										<img src="{$basic.tpl_path}img/uploadify/button_upload_{$environment.lang}.png" />
									</a>
									<a id="uploadify_clearQuery">
										<img src="{$basic.tpl_path}img/uploadify/button_abort_{$environment.lang}.png" />
									</a>
								</div>
							</div>

							<div class="sa_col_right">
								<p class="info_notice">
								<img src="{$basic.tpl_path}img/file_info_icon.gif" alt="Info"/>
								{i18n tag=MATERIAL_MAX_FILE_SIZE param1=$popup.general.max_upload_size}
								</p>
							</div>

							<div class="clear"> </div>
						</div>
						
						<div class="settings_area hidden">
							{if $popup.config.with_activating}
								<input type="checkbox" name="form_data[private_editing]" value="1"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}<br/>
								<input type="checkbox" name="form_data[hide]" value="1">___COMMON_HIDE___
								___DATES_HIDING_DAY___ <input type="text" name="form_data[dayStart]" value=""/>
								___DATES_HIDING_TIME___ <input type="text" name="form_data[timeStart]" value=""/>

							{else}
								{if $popup.general.is_new}
									<input type="radio" name="form_data[public]" value="1" checked="checked"/>___RUBRIC_PUBLIC_YES___<br/>
									<input type="radio" name="form_data[public]" value="0"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
								{else}
									{*
									$current_user = $this->_environment->getCurrentUser();
									$creator = $this->_item->getCreatorItem();

									if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
									$this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
									} else {
									$this->_form->addHidden('public','');
									}
									*}
								{/if}
							{/if}		
						</div>
						
						<div class="settings_area hidden">
							Schlagwörter
						</div>
						
						<div class="settings_area hidden">
							Kategorien
						</div>
					</div>

				</div>

				<div id="content_row_four">
					<div id="crt_actions_area">
						<input id="popup_button_create" class="popup_button" type="button" name="" value="Diskussion anlegen" />
						<input id="popup_button_abort" class="popup_button" type="button" name="" value="abbrechen" /> 
					</div>
				</div>

			</div>

		</div>
	</div>
</div>