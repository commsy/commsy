<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


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
					<h2>___COMMON_INSTITUTION___</h2>
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
							<input type="text" value="{if isset($item.name)}{$item.name|escape:"html"}{/if}" name="form_data[name]" class="size_400" />
						</div>

						<div class="input_row">
							<div class="input_label_80" for="data_picture">___USER_PICTURE_UPLOADFILE___:</div>

							<div class="uploader-single">
								<form method="post" action="UploadFile.php" id="myForm" enctype="multipart/form-data" >
								   <input id="data_picture" class="fileSelector"></input>
								   <div class="filePreview"></div>
								   <div class="fileList"></div>
								</form>
							</div>
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
							{if $popup.is_owner == true}
							   <a href="rights_tab" class="pop_tab_active">___COMMON_RIGHTS___</a>
							   <a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
							{else}
							   <a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab_active">___COMMON_ATTACHED_ENTRIES___</a>
							{/if}
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{if $popup.is_owner == true}
								<div class="tab" id="rights_tab">
									<div class="settings_area">
										<input type="radio" name="form_data[public]" value="1" {if $item.public == '1'}checked="checked"{/if}/>___RUBRIC_PUBLIC_YES___<br/>
										<input type="radio" name="form_data[public]" value="0" {if $item.public == '0'}checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
									</div>
								</div>
							{/if}

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