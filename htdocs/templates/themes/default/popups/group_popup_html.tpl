<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_GROUP___</h2>
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
							<div class="input_label_80">___COMMON_NAME___<span class="required">*</span>:</div>
							<input type="text" value="{if isset($item.name)}{$item.name}{/if}" name="form_data[name]" class="size_400" />
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
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab_active">___COMMON_RIGHTS___</a>{/if}
							<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="{if $popup.is_owner == true}pop_tab{else}pop_tab_active{/if}">___COMMON_ATTACHED_ENTRIES___</a>
							<a href="grouproom_tab" class="pop_tab">___COMMON_GROUPROOM___</a>
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{if $popup.is_owner == true}
								<div class="tab" id="rights_tab">
									<div class="settings_area">
										<input type="radio" name="form_data[public]" value="1" checked="checked"/>___RUBRIC_PUBLIC_YES___<br/>
										<input type="radio" name="form_data[public]" value="0"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
									</div>
								</div>
							{/if}

							{include file="popups/include/netnavigation_tab_include_html.tpl"}

							<div class="tab hidden" id="grouproom_tab">
								<div class="settings_area">
									<div class="input_row">
										<div class="input_label_100">___COMMON_GROUPROOM___:</div>
										<div style="padding-top:5px;">
											<input type="checkbox" class="float-left" value="1" {if $item.group_room_activate}checked="checked"{/if} name="form_data[group_room_activate]"/> ___GROUPROOM_FORM_CHECKBOX_TEXT___
										</div>
									</div>
									<div class="clear"></div>
								</div>
							</div>
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