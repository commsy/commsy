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
					<h2>___COMMON_TOPIC___</h2>
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
							<div class="input_label_80">___COMMON_TITLE___<span class="required">*</span>:</div>
							<input type="text" value="{if isset($item.title)}{$item.title|escape:"html"}{/if}" name="form_data[title]" class="size_400" />
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
							<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
							<a href="path_tab" id="popup_path_tab" class="pop_tab">___TOPIC_PATH___</a>
							<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">

							{include file="popups/include/files_tab_include_html.tpl"}

							{include file="popups/include/rights_tab_include_html.tpl"}

							<div class="tab hidden" id="path_tab">
								<div class="settings_area">
									<div class="input_row">
										<ul id="popup_path_list">
										</ul>
									</div>
									<div class="clear"></div>
								</div>
							</div>

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