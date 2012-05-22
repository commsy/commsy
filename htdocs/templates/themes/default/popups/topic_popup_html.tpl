<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


				<div id="popup_title">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>{if $popup.edit == false}___COMMON_ENTER_NEW_TOPIC___{else}___COMMON_TOPIC_EDIT___{/if}</h2>
					<div class="clear"> </div>
				</div>


				<div id="popup_content">
					<div class="input_row">
						<div class="input_label_80">___COMMON_TITLE___<span class="required">*</span>:</div>
						<input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
					</div>

					<div class="input_row">
						<div class="input_label_80" for="data_picture">___USER_PICTURE_UPLOADFILE___:</div>
						<form id="picture_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=rubric_popup&action=save" method="post">
							<input type="hidden" name="module" value="group" />
							<input type="hidden" name="additional[action]" value="upload_picture" />
							<input id="upload_hidden_iid" type="hidden" name="additional[iid]" value="" />
							<input id="data_picture" size="45" type="file" class="float-left" name="form_data[picture]" accept="image/*" />
						</form>
						<div class="clear"></div>
					</div>

					{if !empty($item.picture)}
						<div class="input_row">
							<div class="input_container">
								<img class="input_image" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$item.picture}" alt="___USER_PICTURE_UPLOADFILE___" />
							</div>
						</div>

						<div class="input_row">
							<div class="input_container">
								<input id="delete_picture" class="float-left" type="checkbox" name="form_data[delete_picture]" value="1"/>
								<label for="delete_picture" class="float-left">___USER_DEL_PIC_BUTTON___</label>
								<div class="clear"></div>
							</div>
						</div>
					{/if}

					<div class="editor_content">
						<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
					</div>

				</div>



				<div id="popup_tabs">
					<div class="tab_navigation">
						<a href="" class="pop_tab_active">___MATERIAL_FILES___</a>
						{if $popup.is_owner == true}<a href="" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
						<a href="" class="pop_tab">___TOPIC_PATH___</a>
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
								<input type="radio" name="form_data[public]" value="1" checked="checked"/>___RUBRIC_PUBLIC_YES___<br/>
								<input type="radio" name="form_data[public]" value="0"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
							</div>
						{/if}

						{include file="popups/include/edit_attach_items_include_html.tpl"}

						<div class="settings_area hidden">
							<div class="input_row">
								<div class="input_label_100">___TOPIC_PATH___:</div>
							</div>
							<div class="clear"></div>
						</div>
					</div>

					<div id="content_buttons">
						<div id="crt_actions_area">
							<input id="popup_button_create" class="popup_button" type="button" name="" value="{if $popup.edit == false}___COMMON_NEW_ITEM___{else}___COMMON_CHANGE_BUTTON___{/if}" />
							<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
						</div>
					</div>



				</div>
			</div>


			<div class="clear"></div>
		</div>
	</div>
</div>