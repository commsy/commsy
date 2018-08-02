<div id="popup_wrapper">
	<div id="popup_edit{if $popup.overflow}_stack{/if}">
		<div id="popup_frame">
			<div id="popup_inner"{if $popup.overflow} class="scrollPopup"{/if}>


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
	{if $item.edit_type == 'netnavigation'}
						<h2>___COMMON_NETNAVIGATION_ENTRIES___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_ITEM_ATTACH___{else}___COMMON_ITEM_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/netnavigation_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ITEM_ATTACH___{else}___COMMON_ITEM_ATTACH___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>
	{elseif $item.edit_type == 'buzzwords'}
						<h2>___COMMON_BUZZWORDS___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_BUZZWORD_ATTACH___{else}___COMMON_BUZZWORD_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/buzzwords_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ASSIGN___{else}___COMMON_ASSIGN___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>

	{elseif $item.edit_type == 'tags'}
						<h2>___COMMON_TAGS___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_TAG_ATTACH___{else}___COMMON_TAG_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/tags_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ASSIGN___{else}___COMMON_ASSIGN___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>

	{else}

					<h2>___COMMON_DATE___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>

						<div class="input_row">
							<span class="input_label_80">___COMMON_TITLE___:<span class="required">*</span></span>
							<input type="text" value="{if isset($item.title)}{$item.title|escape:"html"}{/if}" name="form_data[title]" class="size_400" />
						</div>
						<div class="input_row">
							<span class="input_label_80">___DATES_TIME_DAY_START___:<span class="required">*</span></span>
							<span class="input_label">___COMMON_CALENDAR_DATE___<span class="required">*</span></span>
							<input class="size_80 datepicker" type="text" value="{if isset($item.dayStart)}{$item.dayStart}{elseif $item.date_new_date}{$item.date_new_date}{/if}" name="form_data[dayStart]" />
							<span class="input_label">___COMMON_CLOCK___</span>
							<input type="text" value="{if isset($item.timeStart)}{$item.timeStart}{elseif $item.date_new_time}{$item.date_new_time}{/if}" name="form_data[timeStart]" class="size_80" />
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
							{if !$popup.overflow}<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>{/if}
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{include file="popups/include/files_tab_include_html.tpl"}

							<div class="tab hidden" id="addon_tab">
								<div class="settings_area">
									<div class="form_formatting_checkbox_box">
										<div style="font-size:10pt; text-align:left;">
											<input type="radio" style="vertical-align:top;" tabindex="34" value="#999999" name="form_data[date_addon_color]" {if $item.date_addon_color == '#999999'}checked="checked"{/if}><img style="background-color:#999999; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="35" value="#CC0000" name="form_data[date_addon_color]" {if $item.date_addon_color == '#CC0000'}checked="checked"{/if}><img style="background-color:#cc0000; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="36" value="#FF6600" name="form_data[date_addon_color]" {if $item.date_addon_color == '#FF6600'}checked="checked"{/if}><img style="background-color:#ff6600; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="37" value="#FFCC00" name="form_data[date_addon_color]" {if $item.date_addon_color == '#FFCC00'}checked="checked"{/if}><img style="background-color:#ffcc00; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="38" value="#FFFF66" name="form_data[date_addon_color]" {if $item.date_addon_color == '#FFFF66'}checked="checked"{/if}><img style="background-color:#ffff66; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="39" value="#33CC00" name="form_data[date_addon_color]" {if $item.date_addon_color == '#33CC00'}checked="checked"{/if}><img style="background-color:#33cc00; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="40" value="#00CCCC" name="form_data[date_addon_color]" {if $item.date_addon_color == '#00CCCC'}checked="checked"{/if}><img style="background-color:#00cccc; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="41" value="#3366FF" name="form_data[date_addon_color]" {if $item.date_addon_color == '#3366FF'}checked="checked"{/if}><img style="background-color:#3366ff; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="42" value="#6633FF" name="form_data[date_addon_color]" {if $item.date_addon_color == '#6633FF'}checked="checked"{/if}><img style="background-color:#6633ff; border:1px solid #cccccc;" src="images/spacer.gif">
										    <input type="radio" style="vertical-align:top;" tabindex="43" value="#CC33CC" name="form_data[date_addon_color]" {if $item.date_addon_color == '#CC33CC'}checked="checked"{/if}><img style="background-color:#cc33cc; border:1px solid #cccccc;" src="images/spacer.gif">
										</div>
										<div style="padding-top: 3px;">
											<br/>
											<br/>
										</div>
										<div style="padding-top: 3px;">
											<input type="checkbox" tabindex="44" value="recurring" name="form_data[recurring]" {if $item.is_recurring_date}checked="checked" disabled{/if}>&nbsp;<span style="font-size:10pt;">ist ein:</span>
										    <select id="submit_form" style="font-size:10pt;" tabindex="45" size="0" name="form_data[recurring_select]" {if $item.is_recurring_date}disabled{/if}>
										    	<option value="daily" {if $item.is_recurring_date == 'daily'}selected{/if}>___DATES_RECURRING_DAILY___</option>
										        <option value="weekly" {if $item.is_recurring_date == 'weekly'}selected{/if}>___DATES_RECURRING_WEEKLY___</option>
										        <option value="monthly" {if $item.is_recurring_date == 'monthly'}selected{/if}>___DATES_RECURRING_MONTHLY___</option>
										        <option value="yearly" {if $item.is_recurring_date == 'yearly'}selected{/if}>___DATES_RECURRING_YEARLY___</option>
										    </select>
										    ___DATES_RECURRING_DATE___
										    <br/>
										</div>
										<!-- daily -->
                              <div id="recurring_details_daily" {if $item.is_recurring_date and $item.is_recurring_date != 'daily'}class="hidden"{/if}>
                                 <div style="padding-top: 3px;"><!-- COMBINED FIELDS -->
                                    ___DATES_RECURRING_EVERY_DAY___&nbsp;<span class="required">*</span>
                                    <input type="text" class="text" tabindex="52" size="1" maxlength="4" value="{$item.recurring_day}" style="font-size:10pt;" name="form_data[recurring_day]" {if $item.is_recurring_date}disabled{/if}>
                                    &nbsp;. ___DATES_RECURRING_DAY___
                                 </div>
                              </div>

										<!-- weekly -->
                              <div id="recurring_details_weekly" {if $item.is_recurring_date != 'weekly' or !$item.is_recurring_date}class="hidden"{/if}>
   										<div style="padding-top: 3px;">
   											___DATES_RECURRING_EVERY_WEEK___&nbsp;<span class="required">*</span>
   											<input type="text" class="text" tabindex="47" size="1" maxlength="4" value="{$item.recurring_week}" style="font-size:10pt;" name="form_data[recurring_week]" {if $item.is_recurring_date}disabled{/if}>
   											&nbsp;. ___DATES_RECURRING_WEEK___
   										</div>
   										<div style="padding-top: 3px;">
   											<input type="checkbox" tabindex="48" value="monday" name="form_data[recurring_week_days_monday]" {if $item.recurring_week_days_monday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_MONDAY___</span>
   										   <input type="checkbox" tabindex="49" value="tuesday" name="form_data[recurring_week_days_tuesday]" {if $item.recurring_week_days_tuesday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_TUESDAY___</span>
   										   <input type="checkbox" tabindex="50" value="wednesday" name="form_data[recurring_week_days_wednesday]" {if $item.recurring_week_days_wednesday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_WEDNESDAY___</span>
   										   <input type="checkbox" tabindex="51" value="thursday" name="form_data[recurring_week_days_thusday]" {if $item.recurring_week_days_thursday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_THURSDAY___</span>
   										   <input type="checkbox" tabindex="52" value="friday" name="form_data[recurring_week_days_friday]" {if $item.recurring_week_days_friday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_FRIDAY___</span>
   										   <input type="checkbox" tabindex="53" value="saturday" name="form_data[recurring_week_days_saturday]" {if $item.recurring_week_days_saturday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_SATURDAY___</span>
   										   <input type="checkbox" tabindex="54" value="sunday" name="form_data[recurring_week_days_sunday]" {if $item.recurring_week_days_sunday}checked{/if} {if $item.is_recurring_date}disabled{/if}>&nbsp;<span style="font-size:10pt;">___COMMON_DATE_SUNDAY___</span>
   										</div>
                              </div>

                              <!-- monthly -->
                              <div id="recurring_details_monthly" {if $item.is_recurring_date != 'monthly' or !$item.is_recurring_date}class="hidden"{/if}>
                                 <div style="padding-top: 3px;">
                                    ___DATES_RECURRING_EVERY_MONTH___&nbsp;<span class="required">*</span>
                                    <input type="text" class="text" tabindex="54" size="1" maxlength="4" value="{$item.recurring_month}" style="font-size:10pt;" name="form_data[recurring_month]" {if $item.is_recurring_date}disabled{/if}>
                                    &nbsp;. ___DATES_RECURRING_MONTH___
                                    <select style="font-size:10pt;" tabindex="55" size="0" name="form_data[recurring_month_every]" {if $item.is_recurring_date}disabled{/if}>
                                       <option value="1" {if $item.recurring_month_every == "1"}selected{/if}>___DATES_RECURRING_FIRST___</option>
                                       <option value="2" {if $item.recurring_month_every == "2"}selected{/if}>___DATES_RECURRING_SECOND___</option>
                                       <option value="3" {if $item.recurring_month_every == "3"}selected{/if}>___DATES_RECURRING_THIRD___</option>
                                       <option value="4" {if $item.recurring_month_every == "4"}selected{/if}>___DATES_RECURRING_FOURTH___</option>
                                       <option value="5" {if $item.recurring_month_every == "5"}selected{/if}>___DATES_RECURRING_FIFTH___</option>
                                       <option value="last" {if $item.recurring_month_every == "last"}selected{/if}>___DATES_RECURRING_LAST___</option>
                                    </select>
                                    <select style="font-size:10pt;" tabindex="56" size="0" name="form_data[recurring_month_day_every]" {if $item.is_recurring_date}disabled{/if}>
                                       <option value="1" {if $item.recurring_month_day_every == "1"}selected{/if}>___COMMON_DATE_MONDAY___</option>
                                       <option value="2" {if $item.recurring_month_day_every == "2"}selected{/if}>___COMMON_DATE_TUESDAY___</option>
                                       <option value="3" {if $item.recurring_month_day_every == "3"}selected{/if}>___COMMON_DATE_WEDNESDAY___</option>
                                       <option value="4" {if $item.recurring_month_day_every == "4"}selected{/if}>___COMMON_DATE_THURSDAY___</option>
                                       <option value="5" {if $item.recurring_month_day_every == "5"}selected{/if}>___COMMON_DATE_FRIDAY___</option>
                                       <option value="6" {if $item.recurring_month_day_every == "6"}selected{/if}>___COMMON_DATE_SATURDAY___</option>
                                       <option value="0" {if $item.recurring_month_day_every == "7"}selected{/if}>___COMMON_DATE_SUNDAY___</option>
                                    </select>
                                 </div>
                              </div>

                              <!-- yearly -->
                              <div id="recurring_details_yearly" {if $item.is_recurring_date != 'yearly' or !$item.is_recurring_date}class="hidden"{/if}>
                                 <div style="padding-top: 3px;"><!-- COMBINED FIELDS -->
                                    ___DATES_RECURRING_EVERY_YEAR___&nbsp;<span class="required">*</span>
                                    <input type="text" class="text" tabindex="53" size="1" maxlength="4" value="{$item.recurring_year}" style="font-size:10pt;" name="form_data[recurring_year]" {if $item.is_recurring_date}disabled{/if}>
                                    &nbsp;.
                                    <select style="font-size:10pt;" tabindex="54" size="0" name="form_data[recurring_year_every]" {if $item.is_recurring_date}disabled{/if}>
                                       <option value="1" {if $item.recurring_year_every == "1"}selected{/if}>___COMMON_DATE_JANUARY_LONG___</option>
                                       <option value="2" {if $item.recurring_year_every == "2"}selected{/if}>___COMMON_DATE_FEBRUARY_LONG___</option>
                                       <option value="3" {if $item.recurring_year_every == "3"}selected{/if}>___COMMON_DATE_MARCH_LONG___</option>
                                       <option value="4" {if $item.recurring_year_every == "4"}selected{/if}>___COMMON_DATE_APRIL_LONG___</option>
                                       <option value="5" {if $item.recurring_year_every == "5"}selected{/if}>___COMMON_DATE_MAY_LONG___</option>
                                       <option value="6" {if $item.recurring_year_every == "6"}selected{/if}>___COMMON_DATE_JUNE_LONG___</option>
                                       <option value="7" {if $item.recurring_year_every == "7"}selected{/if}>___COMMON_DATE_JULY_LONG___</option>
                                       <option value="8" {if $item.recurring_year_every == "8"}selected{/if}>___COMMON_DATE_AUGUST_LONG___</option>
                                       <option value="9" {if $item.recurring_year_every == "9"}selected{/if}>___COMMON_DATE_SEPTEMBER_LONG___</option>
                                       <option value="10" {if $item.recurring_year_every == "10"}selected{/if}>___COMMON_DATE_OCTOBER_LONG___</option>
                                       <option value="11" {if $item.recurring_year_every == "11"}selected{/if}>___COMMON_DATE_NOVEMBER_LONG___</option>
                                       <option value="12" {if $item.recurring_year_every == "12"}selected{/if}>___COMMON_DATE_DECEMBER_LONG___</option>
                                    </select>
                                 </div>
                              </div>
                              <div style="padding-top: 3px;">
                                 ___DATES_RECURRING_END_DATE___:<span class="required">*</span>&nbsp;
                                 {if isset($item.recurring_end_date)}
                                    {$item.recurring_end_date}
                                    <input type="hidden" value="{$item.recurring_end_date}" name="form_data[recurring_end_date]" />
                                    <input type="hidden" value="true" name="form_data[recurring_ignore]" />
                                 {else}
                                    <input class="size_80 datepicker" type="text" value="" name="form_data[recurring_end_date]" />
                                 {/if}
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
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								{if $item.is_recurring_date}
                        <input id="popup_button_recurring" class="popup_button submit" data-custom="part: 'recurring'" type="button" name="" value="___DATES_CHANGE_RECURRING_BUTTON___" />
                        {/if}
                        <input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>



					</div>
				</div>
				{/if}
			</div>

			<div class="clear"></div>
		</div>
	</div>
</div>