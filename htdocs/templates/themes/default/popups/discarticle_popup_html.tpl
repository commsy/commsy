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
						<span class="input_label">Titel</span> <input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_200 mandatory" />
					</div>
					
					<div id="pop_editor">
						<h2 id="pop_editor_head">Diskussionsbeitrag</h2>

						<input type="hidden" value="" name="iid"/>
						<input type="hidden" value="{$detail.item_id}" name="discussion_id"/>
						<input type="hidden" value="1" name="ref_position"/>
						<div class="editor_content">
							<div id="popup_ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
							<input type="hidden" id="popup_ckeditor_content" name="form_data[description]" value=""/>
						</div>
					</div>

					<div class="tab_navigation">
						<a href="" class="pop_tab_active">Dateien anh&auml;ngen</a>

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