<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


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
						<div class="input_row">
							<span class="input_label_80">___COMMON_TITLE___:<span class="required">*</span></span>
							<input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
						</div>
						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
						<div class="input_row">
							<div>___SECTION_OTHER_SECTIONS___:</div>
							<div>
        						{foreach $popup.sections as $section}
        							{$section->getTitle()}
        							<br/>
        						{/foreach}
        						&#060;___COMMON_NEW___&#062;
							</div>
						</div>
					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							<a href="" class="pop_tab_active">___MATERIAL_FILES___</a>
							{if $popup.is_owner == true}<a href="" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
							{if isset($popup.buzzwords)}<a href="" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}
							{if isset($popup.tags)}<a href="" class="pop_tab">___COMMON_TAGS___</a>{/if}
							<a href="" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
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