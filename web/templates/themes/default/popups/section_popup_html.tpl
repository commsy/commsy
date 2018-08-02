<div id="popup_wrapper">
	<div id="popup_edit{if $popup.overflow}_stack{/if}">
		<div id="popup_frame">
			<div id="popup_inner"{if $popup.overflow} class="scrollPopup"{/if}>


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_PAGETITLE_SECTION___</h2>
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
						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
						<div class="input_row">
							<div>___SECTION_OTHER_SECTIONS___:</div>
                     <br/>
							<div>
                        {counter start=1 skip=1 assign="section_counter"}
        						{foreach $popup.sections as $section}
        							{$section_counter}. {$section->getTitle()}
        							<br/>
                           {counter}
        						{/foreach}
        						{if isset($item.title)}
        						{else}
        							{$section_counter}. &#060;___COMMON_NEW___&#062;
								{/if}
                        <br/>
                        ___SECTION_CHOOSE_POSITION___
                        <select name="form_data[number]">
                        {counter start=1 skip=1 assign="section_counter"}
                        {foreach $popup.sections as $section}
                           <option value="{$section_counter}" {if $item.number == $section_counter}selected{/if}>{$section_counter}</option>
                           {counter}
                        {/foreach}
                        {if isset($item.title)}
                        {else}
                           <option value="{$section_counter}" selected="selected">{$section_counter}</option>
                        {/if}
                        </select>
							</div>
						</div>
					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">

							{include file="popups/include/files_tab_include_html.tpl"}
						</div>



						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
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