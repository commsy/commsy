<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup_head">
			<a id="popup_close" href="" title="___COMMON_CLOSE___"><img
				src="{$basic.tpl_path}img/pop_close_btn.gif"
				alt="___COMMON_CLOSE___" /> </a>
			<h2>___COMMON_ENTER_NEW_DATE___</h2>

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
						<span class="input_label">___DATES_TIME_DAY_START___:</span>
						<span class="input_label">___COMMON_CALENDAR_DATE___</span> <input
							type="text" value="{if isset($item.date)}{$item.date}{/if}"
							name="form_data[dayStart]" class="size_80 mandatory" />
						<!-- TODO: Datum auswählen -->
						<span class="input_label">___COMMON_CLOCK___</span> <input
							type="text" value="{if isset($item.time)}{$item.time}{/if}"
							name="form_data[timeStart]" class="size_80" />
					</div>
					<div class="input_row">
						<span class="input_label">___DATES_TIME_DAY_END___:</span>
						<span class="input_label">___COMMON_CALENDAR_DATE___</span> <input
							type="text" value="{if isset($item.date)}{$item.date}{/if}"
							name="form_data[dateEnd]" class="size_80 mandatory" />
						<!-- TODO: Datum auswählen -->
						<span class="input_label">___COMMON_CLOCK___</span> <input
							type="text" value="{if isset($item.time)}{$item.time}{/if}"
							name="form_data[timeEnd]" class="size_80" />
					</div>
					<div class="input_row">
						<span class="input_label">___DATE_LOCATION___:</span> <input
							type="text" value="{if isset($item.date)}{$item.date}{/if}"
							name="form_data[place]" class="size_80 mandatory" />
					</div>
					<div class="input_row">
						<span class="input_label">___DATES_ADDON___</span>
						<span class="input_label">___DATES_ADDON_DESC___</span>
						<div class="hidden">
							<div class="input_row">
								<input type="radio" style="vertical-align:top;" tabindex="37" value="#999999" name="date_addon_color">
                                <img style="background-color:#999999; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="38" value="#CC0000" name="date_addon_color">
                                <img style="background-color:#cc0000; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="39" value="#FF6600" name="date_addon_color">
                                <img style="background-color:#ff6600; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="40" value="#FFCC00" name="date_addon_color">
                                <img style="background-color:#ffcc00; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="41" value="#FFFF66" name="date_addon_color">
                                <img style="background-color:#ffff66; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="42" value="#33CC00" name="date_addon_color">
                                <img style="background-color:#33cc00; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="43" value="#00CCCC" name="date_addon_color">
                                <img style="background-color:#00cccc; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="44" value="#3366FF" name="date_addon_color">
                                <img style="background-color:#3366ff; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="45" value="#6633FF" name="date_addon_color">
                                <img style="background-color:#6633ff; border:1px solid #cccccc;" src="images/spacer.gif">
                                <input type="radio" style="vertical-align:top;" tabindex="46" value="#CC33CC" name="date_addon_color">
                                <img style="background-color:#cc33cc; border:1px solid #cccccc;" src="images/spacer.gif">
							</div>
							<div>
								<input type="checkbox" tabindex="47" value="recurring" name="recurring">
                                <span style="font-size:10pt;">___DATES_RECURRING_DESC___:</span>
                                <select id="submit_form" style="font-size:10pt;" tabindex="48" size="0" name="recurring_select">
							</div>
							
						</div>
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
