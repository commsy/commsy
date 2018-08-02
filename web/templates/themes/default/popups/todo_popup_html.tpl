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
					<h2>___COMMON_TODO___</h2>
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
							<div class="input_label_100">___COMMON_TITLE___<span class="required">*</span>:</div>
							<input type="text" value="{if isset($item.title)}{$item.title|escape:"html"}{/if}" name="form_data[title]" class="size_400" />
						</div>
						<div class="input_row">
							<span  class="input_label_100">___TODO_DATE___:</span>
							<span>___DATES_END_DAY___:</span><input class="size_80 datepicker" type="text" value="{if isset($item.day_end)}{$item.day_end}{/if}" name="form_data[day_end]" />
							&nbsp;&nbsp;<span>___DATES_END_TIME___:</span><input type="text" value="{if isset($item.time_end)}{$item.time_end}{/if}" name="form_data[time_end]" class="size_80" />
						</div>
       					<div class="input_row">
							<div class="input_label_100">___TODO_TIME___:</div>
 							<input type="text" value="{if isset($item.minutes)}{$item.minutes}{/if}" name="form_data[minutes]" class="size_80" />
       						<select class="size_200" name="form_data[time_type]">
       							<option value="1"{if $item.time_type == '1'} selected="selected"{/if}>___TODO_TIME_MINUTES___</option>
       							<option value="2"{if $item.time_type == '2'} selected="selected"{/if}>___TODO_TIME_HOURS___</option>
       							<option value="3"{if $item.time_type == '3'} selected="selected"{/if}>___TODO_TIME_DAYS___</option>
       						</select>
       					</div>

       					<div class="input_row">
							<div class="input_label_100">___TODO_STATUS___<span class="required">*</span>:</div>
       						<select class="size_200" name="form_data[status]">
       							{foreach $item.status_array as $status}
       								<option value="{$status.value}"{if $item.status == $status.value} selected="selected"{/if}>{$status.text}</option>
       							{/foreach}
       						</select>
       					</div>

						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
					</div>

					<div id="popup_tabs">
						<div class="tab_navigation">
							<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
							{if isset($popup.buzzwords)}<a href="buzzwords_tab" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}
							{if isset($popup.tags)}<a href="tags_tab" class="pop_tab">___COMMON_TAGS___</a>{/if}
							{if !$popup.overflow}<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>{/if}
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{include file="popups/include/files_tab_include_html.tpl"}

							{include file="popups/include/rights_tab_include_html.tpl"}

							{include file="popups/include/buzzwords_tab_include_html.tpl"}

							{include file="popups/include/tags_tab_include_html.tpl"}

							{include file="popups/include/netnavigation_tab_include_html.tpl"}

						</div>



						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
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