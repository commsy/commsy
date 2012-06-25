<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_DATE___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						<div class="input_row">
							<span class="input_label_80">___COMMON_TITLE___:<span class="required">*</span></span>
							<input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
						</div>
						<div class="input_row">
							<span class="input_label_80">___DATES_TIME_DAY_START___:<span class="required">*</span></span>
							<span class="input_label">___COMMON_CALENDAR_DATE___<span class="required">*</span></span>
							<input class="size_80 datepicker" type="text" value="{if isset($item.dayStart)}{$item.dayStart}{/if}" name="form_data[dayStart]" />
							<span class="input_label">___COMMON_CLOCK___</span>
							<input type="text" value="{if isset($item.timeStart)}{$item.timeStart}{/if}" name="form_data[timeStart]" class="size_80" />
						</div>
						<div class="input_row">
							<span class="input_label_80">___DATES_TIME_DAY_END___:</span>
							<span class="input_label">___COMMON_CALENDAR_DATE___&nbsp;&nbsp;</span>
							<input class="size_80 datepicker" type="text" value="{if isset($item.dayEnd)}{$item.dayEnd}{/if}" name="form_data[dayEnd]" />
							<span class="input_label">___COMMON_CLOCK___</span>
							<input type="text" value="{if isset($item.timeEnd)}{$item.timeEnd}{/if}" name="form_data[timeEnd]" class="size_80" />
						</div>
						<div class="input_row">
							<span class="input_label_80">___DATE_LOCATION___:</span>
							<input type="text" value="{if isset($item.place)}{$item.place}{/if}" name="form_data[place]" class="size_400" />
						</div>
						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>
							<a href="addon_tab" class="pop_tab">___DATES_ADDON_DESC___</a>
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
							{if isset($popup.buzzwords)}<a href="buzzwords_tab" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}
							{if isset($popup.tags)}<a href="tags_tab" class="pop_tab">___COMMON_TAGS___</a>{/if}
							<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{include file="popups/include/files_tab_include_html.tpl"}
							
							<div class="tab hidden" id="addon_tab">
								<div class="settings_area">
									<div class="form_formatting_checkbox_box">
										<div style="font-size:10pt; text-align:left;">
											<input type="radio" style="vertical-align:top;" tabindex="34" value="#999999" name="date_addon_color"><img style="background-color:#999999; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="35" value="#CC0000" name="date_addon_color"><img style="background-color:#cc0000; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="36" value="#FF6600" name="date_addon_color"><img style="background-color:#ff6600; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="37" value="#FFCC00" name="date_addon_color"><img style="background-color:#ffcc00; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="38" value="#FFFF66" name="date_addon_color"><img style="background-color:#ffff66; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="39" value="#33CC00" name="date_addon_color"><img style="background-color:#33cc00; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="40" value="#00CCCC" name="date_addon_color"><img style="background-color:#00cccc; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="41" value="#3366FF" name="date_addon_color"><img style="background-color:#3366ff; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="42" value="#6633FF" name="date_addon_color"><img style="background-color:#6633ff; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="43" value="#CC33CC" name="date_addon_color"><img style="background-color:#cc33cc; border:1px solid #cccccc;" src="images/spacer.gif">
										</div>
										<div style="padding-top: 3px;">
											<br/>
											<br/>
										</div>
										<div style="padding-top: 3px;">
											<input type="checkbox" tabindex="44" value="recurring" name="recurring">&nbsp;<span style="font-size:10pt;">ist ein:</span>
										    <select id="submit_form" style="font-size:10pt;" tabindex="45" size="0" name="recurring_select">
										    	<option value="daily">___DATES_RECURRING_DAILY___</option>
										        <option value="weekly">___DATES_RECURRING_WEEKLY___</option>
										        <option value="monthly">___DATES_RECURRING_MONTHLY___</option>
										        <option value="yearly">___DATES_RECURRING_YEARLY___</option>
										    </select>
										    ___DATES_RECURRING_DATE___
										    <br/>
										</div>
										<div style="padding-top: 3px;">
											___DATES_RECURRING_EVERY_WEEK___&nbsp;
											<input type="text" class="text" tabindex="47" size="1" maxlength="4" value="" style="font-size:10pt;" name="recurring_week">
											&nbsp;. ___DATES_RECURRING_WEEK___
										</div>
										<div style="padding-top: 3px;">
											<input type="checkbox" tabindex="48" value="monday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_MONDAY___</span>
										    <input type="checkbox" tabindex="49" value="tuesday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_TUESDAY___</span>
										   	<input type="checkbox" tabindex="50" value="wednesday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_WEDNESDAY___</span>
										    <input type="checkbox" tabindex="51" value="thursday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_THURSDAY___</span>
										    <input type="checkbox" tabindex="52" value="friday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_FRIDAY___</span>
										    <input type="checkbox" tabindex="53" value="saturday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_SATURDAY___</span>
										    <input type="checkbox" tabindex="54" value="sunday" name="recurring_week_days[]">&nbsp;<span style="font-size:10pt;">___COMMON_DATE_SUNDAY___</span>
										</div>
										<div style="padding-top: 3px;">
											___DATES_RECURRING_END_DATE___:<span class="required">*</span>&nbsp;
											<input class="datepicker" type="text" tabindex="55" size="13" maxlength="13" value="" style="font-size:10pt;" name="recurring_end_date">
										</div>
										<div class="clear"></div>
									</div>
								</div>
							</div>

							{include file="popups/include/rights_tab_include_html.tpl"}
							
							{include file="popups/include/buzzwords_tab_include_html.tpl"}
							
							{include file="popups/include/tags_tab_include_html.tpl"}
							
							{include file="popups/include/netnavigation_tab_include_html.tpl"}
						</div>



						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_NEW_ITEM___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>



					</div>
				</div>
			</div>

			<div class="clear"></div>
		</div>
	</div>
</div>