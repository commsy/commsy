<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup_head">
			<a id="popup_close" href="" title="___COMMON_CLOSE___"><img
				src="{$basic.tpl_path}img/pop_close_btn.gif"
				alt="___COMMON_CLOSE___" /> </a>
			<h2>___COMMON_ENTER_NEW_ANNOUNCEMENT___</h2>

			<div class="clear"></div>
		</div>
		<div id="popup">

			<div id="popup_content">
				<div id="content_row_three">
					<div class="input_row">
						<span class="input_label">___COMMON_TITLE___:</span> <input
							type="text" value="{if isset($item.title)}{$item.title}{/if}"
							name="form_data[title]" class="size_200 mandatory" />
					</div>
					<div class="input_row">
						<span class="input_label">___ANNOUNCEMENT_SHOW_HOME_DATE___:</span>
						<span class="input_label">___COMMON_CALENDAR_DATE___</span> <input
							type="text" value="{if isset($item.date)}{$item.date}{/if}"
							name="form_data[dayEnd]" class="size_80 mandatory" />
						<!-- TODO: Datum auswählen -->
						<span class="input_label">___COMMON_CLOCK___</span> <input
							type="text" value="{if isset($item.time)}{$item.time}{/if}"
							name="form_data[timeEnd]" class="size_80" />
					</div>
					<div id="pop_editor">
						<span class="input_label">___COMMON_CONTENT___</span> <input
							type="hidden" value="" name="iid" /> <input type="hidden"
							value="{$detail.item_id}" name="announcement_id" /> <input
							type="hidden" value="1" name="ref_position" />
						<div class="editor_content">
							<div id="popup_ckeditor"></div>
							<input type="hidden" id="popup_ckeditor_content"
								name="form_data[description]" value="" />
						</div>
					</div>
					<div class="tab_navigation">
						{if $popup.edit == false}<a href="" class="pop_tab_active">Dateien
							anh&auml;ngen</a>{/if} <a href=""
							class="pop_tab{if $popup.edit == true}_active{/if}">___COMMON_RIGHTS___</a>
						{if isset($popup.buzzwords)}<a href="" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}

						<div class="clear"></div>
					</div>

					<div id="popup_tabcontent">
						{if $popup.edit == false}
						<div class="settings_area">

							<div class="sa_col_left">
								<div id="file_finished"></div>
								<input id="uploadify" name="uploadify" type="file" />

								<div>
									<a id="uploadify_doUpload"> <img
										src="{$basic.tpl_path}img/uploadify/button_upload_{$environment.lang}.png" />
									</a> <a id="uploadify_clearQuery"> <img
										src="{$basic.tpl_path}img/uploadify/button_abort_{$environment.lang}.png" />
									</a>
								</div>
							</div>

							<div class="sa_col_right">
								<p class="info_notice">
									<img src="{$basic.tpl_path}img/file_info_icon.gif" alt="Info" />
									{i18n tag=MATERIAL_MAX_FILE_SIZE
									param1=$popup.general.max_upload_size}
								</p>
							</div>

							<div class="clear"></div>
						</div>
						{/if}
					</div>
				</div>
				<div id="content_row_four">
					<div id="crt_actions_area">
						<input id="popup_button_create" class="popup_button" type="button" name="" value="___ANNOUNCEMENT_SAVE_BUTTON___" /> 
						<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
