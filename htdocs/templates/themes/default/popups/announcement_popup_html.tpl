<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_ANNOUNCEMENT___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						<div class="input_row">
							<div class="input_label_80">___COMMON_TITLE___<span class="required">*</span>:</div> <input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
						</div>
						<div class="input_row">
							<span  class="input_label_80">___ANNOUNCEMENT_SHOW_HOME_DATE___<span class="required">*</span>:</span>
							<span>___COMMON_CALENDAR_DATE___:</span><span class="required">*</span><input class="size_80 datepicker" type="text" value="{if isset($item.dayEnd)}{$item.dayEnd}{/if}" name="form_data[dayEnd]" />
							<span>___COMMON_CLOCK___:</span><input type="text" value="{if isset($item.timeEnd)}{$item.timeEnd}{/if}" name="form_data[timeEnd]" class="size_80" />
						</div>

						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
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
							{if $popup.is_owner == true}
								<div class="settings_area hidden">
									{if $popup.config.with_activating}
										<input type="checkbox" name="form_data[private_editing]" value="1"{if $item.private_editing == true} checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}<br/>
										<input type="checkbox" name="form_data[hide]" value="1"{if $item.is_not_activated} checked="checked"{/if}>___COMMON_HIDE___
										___DATES_HIDING_DAY___ <input class="datepicker" type="text" name="form_data[dayStart]" value="{if isset($item.activating_date)}{$item.activating_date}{/if}"/>
										___DATES_HIDING_TIME___ <input type="text" name="form_data[timeStart]" value="{if isset($item.activating_time)}{$item.activating_time}{/if}"/>

									{else}
										<input type="radio" name="form_data[public]" value="1" checked="checked"/>___RUBRIC_PUBLIC_YES___<br/>
										<input type="radio" name="form_data[public]" value="0"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
									{/if}
								</div>
							{/if}

							{if isset($popup.buzzwords)}
								<div class="settings_area hidden">
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
							{/if}

							{if isset($popup.tags)}
								<div class="settings_area hidden">
									<div id="tag_tree">
										{block name=sidebar_tagbox_treefunction}
											{* Tags Function *}
											{function name=tag_tree level=0}
												<ul>
												{foreach $nodes as $node}
													<li	id="node_{$node.item_id}"
														{if $node.children|count > 0}class="folder"{/if}>
														{if $node.match == true}<b>{$node.title}</b>
														{else}{$node.title}
														{/if}
													{if $node.children|count > 0}	{* recursive call *}
														{tag_tree nodes=$node.children level=$level+1}
													{/if}
												{/foreach}
												</ul>
											{/function}
										{/block}

										{* call function *}
										{tag_tree nodes=$popup.tags}
									</div>
								</div>
							{/if}

							{include file="popups/include/edit_attach_items_include_html.tpl"}

						</div>



						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button" type="button" name="" value="{if $popup.edit == false}___COMMON_NEW_ITEM___{else}___COMMON_CHANGE_BUTTON___{/if}" />
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