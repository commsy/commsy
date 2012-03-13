<div id="popup_wrapper">
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
						<span class="input_label">Titel</span> <input type="text" value="" name="form_data[title]" class="size_200" /> 
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
						<a href="" class="pop_tab">Art der Diskussion</a>
						<a href="" class="pop_tab">Zugriffsrechte</a>

						<div class="clear"> </div>
					</div>

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
							Es k&ouml;nnen nur Dateien mit maximal 48 MB Dateigr&ouml;&szlig;e hochgeladen werden.
							</p>
						</div>

						<div class="clear"> </div>
					</div>
					
					<div class="settings_area">
                    
                        <input type="checkbox" name="" value="" /> nur von Dennis Mustermann bearbeitbar<br/>
                        <input type="checkbox" name="" value="" /> verbergen (optional: bis zum <input type="text" value="00.00.0000" class="size_80" /> um <input type="text" value="00:00" class="size_50" /> Uhr)
                    </div>

				</div>

				<div id="content_row_four">
					<div id="crt_actions_area">
						<input class="popup_button" type="button" name="" value="Diskussion anlegen" />
						<input class="popup_button" type="button" name="" value="abbrechen" /> 
					</div>
				</div>

			</div>

		</div>
	</div>
</div>