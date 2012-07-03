<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_DISCUSSION___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>

					<div id="popup_content">
						<div class="input_row">
							<span class="input_label_80">___COMMON_TITLE___:</span><span class="required">*</span>
							<input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
						</div>

						{if $popup.edit == false}
							<div class="input_row">
								<span class="input_label_150">___CONFIGURATION_DISCUSSION___:</span><span class="required">*</span>
								<input type="radio" name="form_data[discussion_type]" value="1" checked="checked"><span class="input_radio">___DISCUSSION_SIMPLE___</span>
								<input type="radio" name="form_data[discussion_type]" value="2"><span class="input_radio">___DISCUSSION_THREADED___</span>
							</div>
						{/if}
						{if $popup.edit == false}
							</div>
							<div id="popup_content">
								<div class="input_row">
									<div id="pop_editor">
										<h2 id="pop_editor_head">___DISCUSSION_INIT_ARTICLE___</h2>
										<span class="input_label">___COMMON_SUBJECT___:</span><span class="required">*</span>
										<input type="text" class="size_400" name="form_data[subject]" value=""/>

										<input type="hidden" value="" name="iid"/>
										<input type="hidden" value="{$detail.item_id}" name="discussion_id"/>
										<input type="hidden" value="1" name="ref_position"/>
										<div class="editor_content">
											<div id="description" class="ckeditor"></div>
										</div>
									</div>
								</div>
						{/if}
					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							{if $popup.edit == false}<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>{/if}
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab{if $popup.edit == true}_active{/if}">___COMMON_RIGHTS___</a>{/if}
							{if isset($popup.buzzwords)}<a href="buzzwords_tab" class="pop_tab{if $popup.edit == true && $popup.is_owner == false}_active{/if}">___COMMON_BUZZWORDS___</a>{/if}
							{if isset($popup.tags)}<a href="tags_tab" class="pop_tab{if $popup.edit == true && $popup.is_owner == false && !isset($popup.buzzwords)}_active{/if}">___COMMON_TAGS___</a>{/if}
							<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{if $popup.edit == false}
								{include file="popups/include/files_tab_include_html.tpl"}
							{/if}
							
							{if $popup.is_owner == true}
								<div class="tab{if $popup.edit == false} hidden{/if}" id="rights_tab">
									<div class="settings_area">
										{if $popup.config.with_activating}
											<input type="checkbox" name="form_data[private_editing]" value="1"{if $item.private_editing == true} checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}<br/>
											<input type="checkbox" name="form_data[hide]" value="1"{if $item.is_not_activated} checked="checked"{/if}>___COMMON_HIDE___
											___DATES_HIDING_DAY___ <input class="datepicker" type="text" name="form_data[dayStart]" value="{if isset($item.activating_date)}{$item.activating_date}{/if}"/>
											___DATES_HIDING_TIME___ <input type="text" name="form_data[timeStart]" value="{if isset($item.activating_time)}{$item.activating_time}{/if}"/>
	
										{else}
											<input type="radio" name="form_data[public]" value="1" {if $item.public == '1'}checked="checked"{/if}/>___RUBRIC_PUBLIC_YES___<br/>
											<input type="radio" name="form_data[public]" value="0" {if $item.public == '0'}checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
										{/if}
									</div>
								</div>
							{/if}

							{if isset($popup.buzzwords)}
								<div class="tab{if $popup.edit == false || $popup.is_owner == true} hidden{/if}" id="buzzwords_tab">
									<div class="settings_area">
										<ul class="popup_buzzword_list">
											{foreach $popup.buzzwords as $buzzword}
												<li id="buzzword_{$buzzword.item_id}" class="ui-state-default popup_buzzword_item">
													<input type="checkbox"{if $buzzword.assigned == true} checked="checked"{/if}/>{$buzzword.name}
												</li>
											{/foreach}
											<div class="clear"></div>
										</ul>
										<div class="clear"></div>
									</div>
								</div>
							{/if}
							
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